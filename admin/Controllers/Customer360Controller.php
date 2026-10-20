<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class Customer360Controller extends Controller
{
    public function index(Request $request)
    {
        $customers = DB::table('customers')->orderBy('id')->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $customer = null;
        $summary  = null;
        $quotes   = collect();
        $orders   = collect();

        if ($request->filled('customer_id')) {
            $customer = DB::table('customers')->where('id', $request->input('customer_id'))->first();

            if ($customer) {
                $cid = $customer->id;

                $rfqCount   = DB::table('mro_rfqs')->where('customer_id', $cid)->count();
                $quoteStats = DB::table('mro_quotes')->where('customer_id', $cid)
                    ->select(DB::raw('count(*) as cnt'), DB::raw('COALESCE(sum(total_cny), 0) as total'))->first();
                $orderStats = DB::table('mro_orders')->where('customer_id', $cid)
                    ->select(DB::raw('count(*) as cnt'), DB::raw('COALESCE(sum(total_cny), 0) as total'),
                        DB::raw('COALESCE(sum(case when payment_status != "paid" then total_cny else 0 end), 0) as unpaid'),
                        DB::raw('sum(case when invoice_no is not null and invoice_no != "" then 1 else 0 end) as invoiced'))->first();

                $summary = [
                    'rfq'        => $rfqCount,
                    'quotes'     => $quoteStats->cnt ?? 0,
                    'quote_total' => (float) ($quoteStats->total ?? 0),
                    'orders'     => $orderStats->cnt ?? 0,
                    'order_total' => (float) ($orderStats->total ?? 0),
                    'unpaid'     => (float) ($orderStats->unpaid ?? 0),
                    'invoiced'   => (int) ($orderStats->invoiced ?? 0),
                ];

                $quotes = DB::table('mro_quotes')->where('customer_id', $cid)
                    ->orderByDesc('id')->limit(10)
                    ->get(['id', 'quote_no', 'status', 'total_cny', 'total_vnd', 'created_at']);

                $orders = DB::table('mro_orders')->where('customer_id', $cid)
                    ->orderByDesc('id')->limit(10)
                    ->get(['id', 'order_no', 'status', 'total_cny', 'payment_status', 'invoice_no', 'delivery_status', 'created_at']);
            }
        }

        return view('admin::catalog.customer360.index', compact('customers', 'customer', 'summary', 'quotes', 'orders'));
    }
}