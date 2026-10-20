<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class ProductCostController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));

        $rows = DB::table('product_flat as p')
            ->leftJoin('mro_product_cost as c', 'c.product_id', 'p.product_id')
            ->whereNull('p.parent_id')
            ->when($q !== '', function ($b) use ($q) {
                return $b->where(function ($w) use ($q) {
                    $w->where('p.sku', 'like', "%$q%")->orWhere('p.name', 'like', "%$q%");
                });
            })
            ->select('p.product_id', 'p.sku', 'p.name', 'p.price', 'c.cost_cny')
            ->orderBy('p.product_id')
            ->paginate(50);

        $rows->getCollection()->transform(function ($r) {
            $r->cost_cny  = (float) ($r->cost_cny ?? 0);
            $r->price     = (float) ($r->price ?? 0);
            $r->margin    = $r->price - $r->cost_cny;
            $r->margin_pct = $r->price > 0 ? round($r->margin / $r->price * 100, 1) : 0;
            return $r;
        });

        return view('admin::catalog.productcost.index', compact('rows', 'q'));
    }

    public function update(Request $request)
    {
        $costs = $request->input('cost', []);
        $ip = $request->ip();
        $user = auth('admin')->user()->name ?? 'admin';
        foreach ($costs as $pid => $cost) {
            if (! is_numeric($cost)) {
                continue;
            }
            $pid = (int) $pid;
            $new = (float) $cost;
            $old = (float) DB::table('mro_product_cost')->where('product_id', $pid)->value('cost_cny');
            // F5 财务审批留痕：记录变更前后值 + 操作人 IP
            DB::table('mro_product_cost')->updateOrInsert(
                ['product_id' => $pid],
                ['cost_cny' => $new, 'updated_at' => now()]
            );
            DB::table('mro_audit_logs')->insert([
                'entity_type' => 'product_cost', 'entity_id' => $pid, 'action' => 'set_cost',
                'operator' => $user, 'operator_type' => 'admin',
                'before_value' => (string) $old, 'after_value' => (string) $new,
                'ip' => $ip, 'created_at' => now(),
            ]);
        }

        return back()->with('success', '成本价已保存（含变更留痕）。');
    }
}