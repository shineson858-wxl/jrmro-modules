<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class QuickQuoteController extends Controller
{
    public function index()
    {
        $customers = DB::table('customers')->orderBy('id')->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $rate = DB::table('core_config_data')->where('config_code', 'catalog.quote.default_exchange_rate')->value('value') ?? 3500;

        return view('admin::catalog.quotes.quick', compact('customers', 'rate'));
    }

    public function generate(Request $request)
    {
        $request->validate(['customer_id' => 'required|integer']);

        $customer = DB::table('customers')->where('id', $request->input('customer_id'))->first();

        if (! $customer) {
            return back()->with('error', '客户不存在');
        }

        $rate   = (float) (DB::table('core_config_data')->where('config_code', 'catalog.quote.default_exchange_rate')->value('value') ?? 3500);
        $taxRate = (float) (DB::table('core_config_data')->where('config_code', 'tax.default.tax_rate')->value('value') ?? 10);
        $skus   = $request->input('sku', []);
        $qtys   = $request->input('qty', []);

        $items  = [];
        $errors = [];

        foreach ($skus as $i => $sku) {
            $sku = trim((string) $sku);

            if ($sku === '') {
                continue;
            }

            $qty = max(1, (int) ($qtys[$i] ?? 1));

            $p = DB::table('product_flat')->where('sku', $sku)->whereNull('parent_id')->orderBy('id')->first();

            if (! $p) {
                $errors[] = $sku;

                continue;
            }

            $hist  = DB::table('mro_quote_items')->where('sku', $sku)->orderByDesc('id')->value('price_cny');
            $stock = (int) DB::table('product_inventories')->where('product_id', $p->product_id)->sum('qty');

            // H4：企业协议价优先（mro_price_tiers 按公司+商品），其次历史价，最后目录价
            $companyId = DB::table('mro_company_users')->where('customer_id', $customer->id)->value('company_id');
            $tier = $companyId
                ? DB::table('mro_price_tiers')->where('company_id', $companyId)->where('product_id', $p->product_id)->value('price_cny')
                : null;

            $price = $tier !== null
                ? (float) $tier
                : ($hist !== null ? (float) $hist : (float) ($p->price ?? 0));

            $items[] = [
                'product_id' => $p->product_id,
                'sku'        => $sku,
                'name'       => $p->name,
                'qty'        => $qty,
                'price_cny'  => $price,
                'total_cny'  => round($price * $qty, 2),
                'stock'      => $stock,
                'hist'       => $hist,
                'tier'       => $tier,
            ];
        }

        if (! $items) {
            return back()->with('error', '未找到有效 SKU'.($errors ? '（无效：'.implode(', ', $errors).'）' : ''));
        }

        $subtotal = array_sum(array_column($items, 'total_cny'));
        $tax      = round($subtotal * $taxRate / 100, 2);
        $total    = $subtotal + $tax;
        $vnd      = (int) round($total * $rate);

        $id = DB::table('mro_quotes')->insertGetId([
            'quote_no'      => null,
            'source_type'   => 'manual',
            'customer_id'   => $customer->id,
            'customer_name' => trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')),
            'email'         => $customer->email ?? null,
            'phone'         => $customer->phone ?? null,
            'tax_rate'      => $taxRate,
            'exchange_rate' => $rate,
            'status'        => 'draft',
            'currency'      => 'VND',
            'subtotal_cny'  => $subtotal,
            'tax_cny'       => $tax,
            'total_cny'     => $total,
            'total_vnd'     => $vnd,
            'locale'        => app()->getLocale(),
            'note'          => $request->input('note') ?: null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $quoteNo = 'Q'.date('Ymd').'-'.str_pad($id, 4, '0', STR_PAD_LEFT);

        DB::table('mro_quotes')->where('id', $id)->update(['quote_no' => $quoteNo]);

        foreach ($items as $it) {
            DB::table('mro_quote_items')->insert([
                'quote_id'   => $id,
                'product_id' => $it['product_id'],
                'sku'        => $it['sku'],
                'name'       => $it['name'],
                'qty'        => $it['qty'],
                'price_cny'  => $it['price_cny'],
                'total_cny'  => $it['total_cny'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.quotes.edit', $id)->with('success', '快速报价单已生成：'.$quoteNo.($errors ? '（无效 SKU：'.implode(', ', $errors).'）' : ''));
    }
}