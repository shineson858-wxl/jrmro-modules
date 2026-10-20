<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class SalesRepController extends Controller
{
    public function index()
    {
        $admins    = DB::table('admins')->orderBy('id')->get(['id', 'name', 'email']);
        $customers = DB::table('customers')->orderBy('id')->get(['id', 'first_name', 'last_name', 'email']);

        $performance = [];

        foreach (DB::table('mro_sales_reps')->select('admin_id')->distinct()->orderBy('admin_id')->get() as $rep) {
            $admin = DB::table('admins')->where('id', $rep->admin_id)->first();
            if (! $admin) {
                continue;
            }

            $cids = DB::table('mro_sales_reps')->where('admin_id', $rep->admin_id)->pluck('customer_id');

            $rfq   = DB::table('mro_rfqs')->whereIn('customer_id', $cids)->count();
            $quote = DB::table('mro_quotes')->whereIn('customer_id', $cids)->select(DB::raw('count(*) c'), DB::raw('COALESCE(sum(total_cny), 0) t'))->first();
            $order = DB::table('mro_orders')->whereIn('customer_id', $cids)->select(DB::raw('count(*) c'), DB::raw('COALESCE(sum(total_cny), 0) t'), DB::raw('COALESCE(sum(case when payment_status != "paid" then total_cny else 0 end), 0) u'))->first();

            $performance[] = [
                'admin'   => $admin,
                'customers' => count($cids),
                'rfq'     => $rfq,
                'quotes'  => (int) ($quote->c ?? 0),
                'quote_total' => (float) ($quote->t ?? 0),
                'orders'  => (int) ($order->c ?? 0),
                'order_total' => (float) ($order->t ?? 0),
                'unpaid'  => (float) ($order->u ?? 0),
            ];
        }

        $assignments = DB::table('mro_sales_reps')->orderBy('admin_id')->get();

        return view('admin::catalog.salesrep.index', compact('admins', 'customers', 'performance', 'assignments'));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'admin_id'    => 'required|integer',
            'customer_ids' => 'nullable|array',
        ]);

        $adminId = $request->input('admin_id');
        $cids    = array_map('intval', (array) $request->input('customer_ids', []));

        DB::table('mro_sales_reps')->where('admin_id', $adminId)->delete();

        foreach ($cids as $cid) {
            DB::table('mro_sales_reps')->insert([
                'admin_id'    => $adminId,
                'customer_id' => $cid,
                'created_at'  => now(),
            ]);
        }

        DB::table('mro_audit_logs')->insert([
            'entity_type'   => 'sales_rep',
            'entity_id'     => $adminId,
            'action'        => 'assign_customers',
            'operator'      => auth('admin')->user()->name ?? 'admin',
            'operator_type' => 'admin',
            'after_value'   => '客户 ' . implode(',', $cids),
            'created_at'    => now(),
        ]);

        return redirect()->route('admin.salesrep.index')->with('success', '业务员客户分配已更新。');
    }
}