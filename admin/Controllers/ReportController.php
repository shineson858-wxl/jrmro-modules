<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()
    {
        $months = DB::table('mro_quotes')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('count(*) as quotes'), DB::raw('COALESCE(sum(total_cny), 0) as quote_total'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->get();

        $orderMonths = DB::table('mro_orders')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('count(*) as orders'), DB::raw('COALESCE(sum(total_cny), 0) as order_total'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->get();

        $customers = DB::table('mro_orders')
            ->select('customer_name',
                DB::raw('count(*) as orders'),
                DB::raw('COALESCE(sum(total_cny), 0) as total'),
                DB::raw('COALESCE(sum(case when payment_status != "paid" then total_cny else 0 end), 0) as unpaid'))
            ->groupBy('customer_name')
            ->orderByDesc('total')
            ->get();

        // F6-1 应收账龄：按到期日分层（未到期 / 0-30 / 31-60 / 60+）
        $aging = DB::table('mro_orders')
            ->where('payment_status', '!=', 'paid')->where('payment_status', '!=', 'cancelled')
            ->select(DB::raw("CASE WHEN due_at IS NULL OR due_at >= NOW() THEN '未到期' WHEN DATEDIFF(NOW(), due_at) <= 30 THEN '0-30天' WHEN DATEDIFF(NOW(), due_at) <= 60 THEN '31-60天' ELSE '60天以上' END bucket"),
                DB::raw('COALESCE(sum(total_cny),0) amt'), DB::raw('count(*) cnt'))
            ->groupBy('bucket')->get();

        // F6-2 存货结存：成本 × 实时库存
        $stockRow = DB::table('mro_product_cost as c')
            ->join('product_inventories as i', 'i.product_id', 'c.product_id')
            ->select(DB::raw('COALESCE(sum(c.cost_cny * i.qty),0) stock_cny'),
                DB::raw('count(distinct c.product_id) skus'))->first();

        // F6-3 税费汇总：销项税（VAT 输出）
        $taxRow = DB::table('mro_orders')->select(DB::raw('COALESCE(sum(tax_cny),0) output_tax'),
            DB::raw('count(invoice_no) invoices'))->first();

        // F6-4 资金对账：已收款 / 应收
        $cashRow = DB::table('mro_orders')
            ->select(DB::raw('COALESCE(sum(case when payment_status="paid" then total_cny else 0 end),0) received'),
                DB::raw('COALESCE(sum(case when payment_status!="paid" and payment_status!="cancelled" then total_cny else 0 end),0) receivable'))
            ->first();

        return view('admin::catalog.reports.index', compact('months', 'orderMonths', 'customers', 'aging', 'stockRow', 'taxRow', 'cashRow'));
    }

    public function export()
    {
        $customers = DB::table('mro_orders')
            ->select('customer_name', DB::raw('count(*) as orders'), DB::raw('COALESCE(sum(total_cny), 0) as total'), DB::raw('COALESCE(sum(case when payment_status != "paid" then total_cny else 0 end), 0) as unpaid'))
            ->groupBy('customer_name')
            ->orderByDesc('total')
            ->get();

        $rows = [['客户', '订单数', '成交额(CNY)', '未收款(CNY)']];

        foreach ($customers as $c) {
            $rows[] = [$c->customer_name, $c->orders, (float) $c->total, (float) $c->unpaid];
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, 'JR-MRO-客户对账'.date('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}