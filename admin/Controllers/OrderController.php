<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * 订单生命周期管理（待签章 -> 已签章 -> 已完成 / 已取消）。
 * 记录式电子签章：双方签章写入 mro_signatures，内容哈希锁定不可篡改。
 */
class OrderController extends Controller
{
    use DocHelpers;

    public function index()
    {
        $status = request()->query('status', 'all');

        $query = DB::table('mro_orders');

        if (in_array($status, ['pending_sign', 'signed', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $orders = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin::catalog.orders.index', compact('orders', 'status'));
    }

    public function edit($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        $items = DB::table('mro_order_items')->where('order_id', $id)->orderBy('id')->get();
        $signatures = DB::table('mro_signatures')->where('order_id', $id)->orderBy('id')->get();

        // 内容完整性校验（是否被篡改）
        $valid = true;
        $expected = $this->orderHash($order, $items);
        if ($order->content_hash && hash_equals($order->content_hash, $expected)) {
            $valid = true;
        } else {
            $valid = false;
        }

        // 报价单关联
        $quote = $order->quote_id ? DB::table('mro_quotes')->where('id', $order->quote_id)->first() : null;

        return view('admin::catalog.orders.edit', compact('order', 'items', 'signatures', 'valid', 'expected', 'quote'));
    }

    /**
     * 卖方签章（后台）：pending_sign -> signed。
     */
    public function sign($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        [$ok, $err] = $this->canTransition('order', $order->status, 'signed');
        if (! $ok) {
            return redirect()->route('admin.orders.edit', $id)->with('error', '无法签章：'.$err);
        }

        // 签章前锁定内容哈希（若尚未锁定）
        $items = DB::table('mro_order_items')->where('order_id', $id)->get();
        $hash = $this->orderHash($order, $items);

        // 卖方签章（记录式）
        DB::table('mro_signatures')->insert([
            'order_id'        => $id,
            'signer_type'     => 'seller',
            'signer_name'     => auth('admin')->user()->name ?? auth('admin')->user()->email ?? 'JR MRO',
            'signer_identity' => auth('admin')->user()->email ?? null,
            'signature_data'  => json_encode([
                'nonce'       => bin2hex(random_bytes(8)),
                'content_hash'=> $hash,
                'signed_at'   => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE),
            'content_hash'    => $hash,
            'signed_at'       => now(),
        ]);

        DB::table('mro_orders')->where('id', $id)->update([
            'status'       => 'signed',
            'signed_at'    => now(),
            'content_hash' => $hash,
        ]);

        $this->audit('order', $id, 'seller_sign', null, ['status' => 'signed', 'hash' => $hash]);

        return redirect()->route('admin.orders.edit', $id)->with('success', '卖方已签章，订单已锁定（内容不可篡改）');
    }

    /**
     * 客户签章（后台代操作或客户在线签章）：pending_sign/signed -> signed。
     */
    public function signCustomer($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        if (! in_array($order->status, ['pending_sign', 'signed'], true)) {
            return redirect()->route('admin.orders.edit', $id)->with('error', '当前状态不能进行客户签章');
        }

        $items = DB::table('mro_order_items')->where('order_id', $id)->get();
        $hash = $this->orderHash($order, $items);

        // 校验内容是否已被篡改
        if ($order->content_hash && ! hash_equals($order->content_hash, $hash)) {
            return redirect()->route('admin.orders.edit', $id)->with('error', '订单内容已被篡改，签章被拒绝！');
        }

        DB::table('mro_signatures')->insert([
            'order_id'        => $id,
            'signer_type'     => 'customer',
            'signer_name'     => $order->customer_name,
            'signer_identity' => $order->email,
            'signature_data'  => json_encode([
                'nonce'       => bin2hex(random_bytes(8)),
                'content_hash'=> $hash,
                'signed_at'   => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE),
            'content_hash'    => $hash,
            'signed_at'       => now(),
        ]);

        DB::table('mro_orders')->where('id', $id)->update([
            'status'    => 'signed',
            'signed_at' => now(),
        ]);

        $this->audit('order', $id, 'customer_sign', null, ['status' => 'signed']);

        return redirect()->route('admin.orders.edit', $id)->with('success', '客户已签章，双方签章完成');
    }

    /**
     * 订单完成：signed -> completed。
     */
    public function complete($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        [$ok, $err] = $this->canTransition('order', $order->status, 'completed');
        if (! $ok) {
            return redirect()->route('admin.orders.edit', $id)->with('error', '无法完成：'.$err);
        }

        DB::table('mro_orders')->where('id', $id)->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $this->audit('order', $id, 'complete', null, ['status' => 'completed']);

        return redirect()->route('admin.orders.edit', $id)->with('success', '订单已完成');
    }

    /**
     * 取消订单：pending_sign/signed -> cancelled。
     */
    public function cancel($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        [$ok, $err] = $this->canTransition('order', $order->status, 'cancelled');
        if (! $ok) {
            return redirect()->route('admin.orders.edit', $id)->with('error', '无法取消：'.$err);
        }

        DB::table('mro_orders')->where('id', $id)->update(['status' => 'cancelled']);

        $this->audit('order', $id, 'cancel', null, ['status' => 'cancelled']);

        return redirect()->route('admin.orders.edit', $id)->with('success', '订单已取消');
    }

    /**
     * 校验订单内容完整性（是否被篡改）。
     */
    public function verify($id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->route('admin.orders.index')->with('error', '订单不存在');
        }

        $items = DB::table('mro_order_items')->where('order_id', $id)->get();
        $expected = $this->orderHash($order, $items);

        if ($order->content_hash && hash_equals($order->content_hash, $expected)) {
            return redirect()->route('admin.orders.edit', $id)
                ->with('success', '订单内容完整，哈希校验通过（未被篡改）');
        }

        $this->audit('order', $id, 'verify_failed', null, ['hash' => $order->content_hash, 'expected' => $expected]);

        return redirect()->route('admin.orders.edit', $id)
            ->with('error', '订单内容已被篡改！存储哈希与当前内容不一致。');
    }

    /**
     * 批次④ 结算：VAT 发票开具 + 对公核销。
     */
    public function billing(\Illuminate\Http\Request $request, $id)
    {
        $order = DB::table('mro_orders')->where('id', $id)->first();

        if (! $order) {
            return redirect()->back()->with('error', '订单不存在');
        }

        $action = $request->input('action');

        if ($action === 'issue_invoice') {
            if (! $order->invoice_no) {
                $invoiceNo = 'INV'.date('Ymd').'-'.str_pad($order->id, 4, '0', STR_PAD_LEFT);

                DB::table('mro_orders')->where('id', $id)->update([
                    'invoice_no' => $invoiceNo,
                    'updated_at' => now(),
                ]);

                $this->audit('order', $id, 'issue_invoice', null, ['invoice_no' => $invoiceNo]);

                return redirect()->route('admin.orders.edit', $id)->with('success', 'VAT 发票已开具：'.$invoiceNo);
            }

            return redirect()->route('admin.orders.edit', $id)->with('success', '该订单已开具发票');
        }

        if ($action === 'verify_payment') {
            $status  = $request->input('payment_status') === 'paid' ? 'paid' : 'unpaid';
            $ref     = $request->input('payment_ref') ?: null;

            DB::table('mro_orders')->where('id', $id)->update([
                'payment_ref'    => $ref,
                'payment_status' => $status,
                'updated_at'     => now(),
            ]);

            $this->audit('order', $id, 'verify_payment', $order->payment_status, $status.($ref ? ' / '.$ref : ''));

            return redirect()->route('admin.orders.edit', $id)->with('success', $status === 'paid' ? '对公核销已确认' : '对公核销已更新');
        }

        if ($action === 'delivery') {
            $allowedDs = ['pending', 'dispatched', 'in_transit', 'delivered'];
            $ds        = $request->input('delivery_status');

            if (! in_array($ds, $allowedDs, true)) {
                $ds = 'pending';
            }

            DB::table('mro_orders')->where('id', $id)->update([
                'delivery_type'    => $request->input('delivery_type') === 'pickup' ? 'pickup' : 'shipping',
                'urgent'           => $request->boolean('urgent') ? 1 : 0,
                'pickup_point_id'  => $request->input('pickup_point_id') ?: null,
                'delivery_status'  => $ds,
                'updated_at'       => now(),
            ]);

            $this->audit('order', $id, 'update_delivery', null, [
                'delivery_type'   => $request->input('delivery_type'),
                'urgent'          => $request->boolean('urgent') ? '1' : '0',
                'delivery_status' => $ds,
            ]);

            return redirect()->route('admin.orders.edit', $id)->with('success', '配送方式 / 加急 / 配送状态已更新');
        }

        return redirect()->route('admin.orders.edit', $id)->with('error', '未知操作');
    }
}
