<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class OpsDashboardController extends Controller
{
    public function index()
    {
        $today = DB::table('mro_orders')->whereDate('created_at', now()->toDateString())
            ->where('payment_status', 'paid')
            ->select(DB::raw('COALESCE(sum(total_cny),0) s'), DB::raw('count(*) c'))->first();

        $month = DB::table('mro_orders')->whereRaw("DATE_FORMAT(created_at,'%Y-%m') = ?", [now()->format('Y-m')])
            ->where('payment_status', 'paid')
            ->select(DB::raw('COALESCE(sum(total_cny),0) s'), DB::raw('count(*) c'))->first();

        $pendingRfq    = DB::table('mro_rfqs')->whereIn('status', ['new', 'pending'])->count();
        $pendingQuote  = DB::table('mro_quotes')->whereIn('status', ['draft', 'sent'])->count();
        $pendingOrders = DB::table('mro_orders')->where('status', 'confirmed')->where('payment_status', 'unpaid')->count();
        $unpaidTotal   = DB::table('mro_orders')->where('payment_status', '!=', 'paid')->where('payment_status', '!=', 'cancelled')->sum('total_cny');

        // 收入确认口径（F2）：控制权转移=客户签收(delivered)；已交付=已确认收入；已交付未收款=应收
        $revConfirmed = (float) DB::table('mro_orders')->where('delivery_status', 'delivered')->sum('total_cny');
        $revReceived  = (float) DB::table('mro_orders')->where('delivery_status', 'delivered')->where('payment_status', 'paid')->sum('total_cny');
        $revReceivable = (float) DB::table('mro_orders')->where('delivery_status', 'delivered')->where('payment_status', '!=', 'paid')->sum('total_cny');

        $topProducts = DB::table('mro_order_items')
            ->select('sku', 'name', DB::raw('sum(qty) qty'), DB::raw('sum(total_cny) total'))
            ->groupBy('sku', 'name')->orderByDesc('qty')->limit(5)->get();

        $topCustomers = DB::table('mro_orders')->where('payment_status', 'paid')
            ->select('customer_name', DB::raw('count(*) c'), DB::raw('COALESCE(sum(total_cny),0) total'))
            ->groupBy('customer_name')->orderByDesc('total')->limit(5)->get();

        $outOfStock = DB::table('product_flat as pf')
            ->whereNull('pf.parent_id')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('product_inventories as pi')
                    ->whereColumn('pi.product_id', 'pf.product_id')
                    ->havingRaw('sum(pi.qty) > 0');
            })->count();

        $trend = DB::table('mro_orders')->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(29))
            ->select(DB::raw("DATE_FORMAT(created_at,'%m-%d') d"), DB::raw('COALESCE(sum(total_cny),0) total'))
            ->groupBy('d')->orderBy('d')->get();

        $data = [
            'today_cny'    => (float) ($today->s ?? 0), 'today_c' => (int) ($today->c ?? 0),
            'month_cny'    => (float) ($month->s ?? 0), 'month_c' => (int) ($month->c ?? 0),
            'pending_rfq'  => $pendingRfq, 'pending_quote' => $pendingQuote,
            'pending_orders' => $pendingOrders, 'unpaid_total' => (float) $unpaidTotal,
            'rev_confirmed' => $revConfirmed, 'rev_received' => $revReceived, 'rev_receivable' => $revReceivable,
            'out_of_stock' => (int) $outOfStock,
        ];

        return view('admin::catalog.opsdashboard.index', compact('data', 'topProducts', 'topCustomers', 'trend'));
    }
}