<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Facades\Cart;
use Webkul\Admin\Http\Controllers\Catalog\QuoteController as AdminQuoteController;

class QuoteController extends Controller
{
    /**
     * 从当前购物车生成 MRO 标准报价单。
     */
    public function create(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:120',
            'email'         => 'required|email|max:120',
        ]);

        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index')
                ->with('error', '购物车为空，请先添加商品');
        }

        $subtotal = 0.0;
        $rows = [];

        foreach ($cart->items as $item) {
            $price = (float) ($item->base_price ?: $item->price);
            $qty   = (int) ($item->quantity ?: 1);
            $total = round($price * $qty, 2);

            $subtotal += $total;

            $rows[] = [
                'product_id' => $item->product_id,
                'sku'        => $item->sku ?: ($item->product?->sku ?: ''),
                'name'       => $item->name ?: ($item->product?->name ?: $item->sku),
                'qty'        => $qty,
                'price_cny'  => $price,
                'total_cny'  => $total,
            ];
        }

        $taxRate      = (float) (DB::table('core_config')->where('code', 'catalog.quote.default_tax_rate')->value('value') ?: 10);
        $exchangeRate = (float) (DB::table('core_config')->where('code', 'catalog.quote.default_exchange_rate')->value('value') ?: 3500);
        $taxCny       = round($subtotal * $taxRate / 100, 2);
        $totalCny     = round($subtotal + $taxCny, 2);
        $totalVnd     = (int) round($totalCny * $exchangeRate);

        $id = DB::table('mro_quotes')->insertGetId([
            'quote_no'       => null,
            'source_type'    => 'cart',
            'source_id'      => $cart->id,
            'customer_id'    => $cart->customer_id,
            'customer_name'  => $request->input('customer_name'),
            'email'          => $request->input('email'),
            'phone'          => $request->input('phone'),
            'company'        => $request->input('company'),
            'tax_rate'       => $taxRate,
            'exchange_rate'  => $exchangeRate,
            'status'         => 'draft',
            'subtotal_cny'   => $subtotal,
            'tax_cny'        => $taxCny,
            'total_cny'      => $totalCny,
            'total_vnd'      => $totalVnd,
            'locale'         => app()->getLocale(),
            'note'           => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        foreach ($rows as $row) {
            DB::table('mro_quote_items')->insert(array_merge($row, ['quote_id' => $id]));
        }

        $no = 'Q'.str_pad($id, 4, '0', STR_PAD_LEFT);
        DB::table('mro_quotes')->where('id', $id)->update(['quote_no' => $no]);

        return redirect()->route('shop.checkout.cart.index')
            ->with('success', '报价单已生成：'.$no.'（后台「报价单」可查看、调价、导出）');
    }

    /**
     * 客户在线确认报价（邮件确认链接入口）：
     * 1) 校验一次性令牌
     * 2) 仅已发送(issued)且未过期的报价可确认
     * 3) 报价单 -> confirmed，生成正式订单(pending_sign)
     * 4) 记录客户签章（记录式，内容哈希锁定）
     */
    public function accept(Request $request, $id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return view('shop::customers.quote-confirm', ['success' => false, 'message' => '报价单不存在']);
        }

        // 一次性令牌校验
        $token = substr(hash('sha256', $quote->id.'|'.$quote->content_hash), 0, 32);
        if ($request->query('token') !== $token) {
            return view('shop::customers.quote-confirm', ['success' => false, 'message' => '无效的确认令牌，请从邮件中的确认链接进入']);
        }

        // 状态校验
        if ($quote->status === 'confirmed') {
            return view('shop::customers.quote-confirm', ['success' => false, 'message' => '该报价单已被确认']);
        }
        if ($quote->status !== 'issued') {
            return view('shop::customers.quote-confirm', ['success' => false, 'message' => '该报价单当前状态无法确认（状态：'.$quote->status.'）']);
        }
        if ($quote->valid_until && strtotime($quote->valid_until) < time()) {
            return view('shop::customers.quote-confirm', ['success' => false, 'message' => '该报价单已过期，无法确认。请联系我们重新报价。']);
        }

        // 标记报价已确认
        DB::table('mro_quotes')->where('id', $id)->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // 生成正式订单（待签章）
        $orderId = (new AdminQuoteController)->createOrderFromQuote($id);

        $order = DB::table('mro_orders')->where('id', $orderId)->first();

        // 客户签章（记录式，锁定订单内容）
        $items = DB::table('mro_order_items')->where('order_id', $orderId)->get();
        $hash = hash('sha256', json_encode([
            'customer_name' => $order->customer_name,
            'email'         => $order->email,
            'company'       => $order->company,
            'tax_rate'      => (string) $order->tax_rate,
            'exchange_rate' => (string) $order->exchange_rate,
            'total_cny'     => (string) $order->total_cny,
            'total_vnd'     => (string) $order->total_vnd,
            'items'         => array_map(fn ($it) => [$it->sku, (int) $it->qty, (string) $it->price_cny], $items->all()),
        ], JSON_UNESCAPED_UNICODE));

        DB::table('mro_signatures')->insert([
            'order_id'        => $orderId,
            'signer_type'     => 'customer',
            'signer_name'     => $quote->customer_name,
            'signer_identity' => $quote->email,
            'signature_data'  => json_encode([
                'nonce'       => bin2hex(random_bytes(8)),
                'content_hash'=> $hash,
                'signed_at'   => now()->toDateTimeString(),
                'via'         => 'online_confirm',
            ], JSON_UNESCAPED_UNICODE),
            'content_hash'    => $hash,
            'signed_at'       => now(),
        ]);

        // 记录客户签章后订单哈希锁定
        DB::table('mro_orders')->where('id', $orderId)->update(['content_hash' => $hash]);

        return view('shop::customers.quote-confirm', [
            'success' => true,
            'message' => '报价单已确认，订单已生成',
            'order'   => $order,
        ]);
    }

    /**
     * C1 批量询价：将购物车全部商品生成一条询价批次（多行 RFQ，共享 batch_id）。
     */
    public function bulkRfqFromCart(Request $request)
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index')->with('error', '购物车为空，无法批量询价');
        }

        $customer = $cart->customer ?? null;

        $name    = trim((string) $request->input('name')) ?: ($customer->first_name ?? '');
        $email   = trim((string) $request->input('email')) ?: ($customer->email ?? '');
        $phone   = $request->input('phone') ?: null;
        $company = $request->input('company') ?: null;

        if (! $email) {
            return redirect()->route('shop.checkout.cart.index')->with('error', '请填写联系邮箱后再批量询价');
        }

        $batch = 'RFQ'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        $count = 0;

        foreach ($cart->items as $item) {
            $pid   = $item->product_id;
            $sku   = $item->sku ?: ($item->product?->sku ?: '');
            $pname = $item->name ?: ($item->product?->name ?: $sku);

            DB::table('mro_rfqs')->insert([
                'customer_id'  => $cart->customer_id,
                'batch_id'     => $batch,
                'name'         => $name,
                'email'        => $email,
                'phone'        => $phone,
                'company'      => $company,
                'product_id'   => $pid,
                'product_sku'  => $sku,
                'product_name' => $pname,
                'qty'          => (int) ($item->quantity ?: 1),
                'note'         => $request->input('note') ?: null,
                'locale'       => app()->getLocale(),
                'status'       => 'pending',
            ]);

            $count++;
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                '批量询价批次 '.$batch.'：共 '.$count.' 件商品，'.$name.'，邮箱 '.$email,
                function ($message) {
                    $message->to(config('mail.from.address', 'admin@example.com'))->subject('新批量询价 - JR MRO 超市');
                }
            );
        } catch (\Throwable $e) {
            logger()->error('批量询价通知邮件失败: '.$e->getMessage());
        }

        return redirect()->route('shop.checkout.cart.index')
            ->with('success', '已提交 '.$count.' 件商品的批量询价，批次号：'.$batch);
    }
}
