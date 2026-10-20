<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Exports\QuoteExport;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Barryvdh\DomPDF\Facade\Pdf;
use Webkul\Admin\Http\Controllers\Controller;

class QuoteController extends Controller
{
    use DocHelpers;

    /**
     * 报价单列表（支持全部状态筛选）。
     */
    public function index()
    {
        $status = request()->query('status', 'all');

        $query = DB::table('mro_quotes');

        if (in_array($status, ['draft', 'issued', 'confirmed', 'expired', 'cancelled'])) {
            $query->where('status', $status);
        }

        $quotes = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $defaultRate = DB::table('core_config')->where('code', 'catalog.quote.default_exchange_rate')->value('value') ?: 3500;
        $defaultTax  = DB::table('core_config')->where('code', 'catalog.quote.default_tax_rate')->value('value') ?: 10;

        return view('admin::catalog.quotes.index', compact('quotes', 'status', 'defaultRate', 'defaultTax'));
    }

    /**
     * 保存全局默认汇率/税率。
     */
    public function storeDefaults(\Illuminate\Http\Request $request)
    {
        $rate = (float) $request->input('default_exchange_rate', 3500);
        $tax  = (float) $request->input('default_tax_rate', 10);

        DB::table('core_config')->updateOrInsert(
            ['code' => 'catalog.quote.default_exchange_rate', 'channel_code' => null, 'locale_code' => null],
            ['value' => (string) $rate, 'updated_at' => now()]
        );
        DB::table('core_config')->updateOrInsert(
            ['code' => 'catalog.quote.default_tax_rate', 'channel_code' => null, 'locale_code' => null],
            ['value' => (string) $tax, 'updated_at' => now()]
        );

        return redirect()->route('admin.quotes.index')->with('success', '全局默认汇率 / 税率已保存（新报价单将自动采用）');
    }

    /**
     * 报价单详情/编辑。
     */
    public function edit($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->orderBy('id')->get();

        return view('admin::catalog.quotes.edit', compact('quote', 'items'));
    }

    /**
     * 保存报价单（逐商品调价/数量、税率、汇率、状态，自动重算）。
     * 状态仅允许编辑期状态（draft/issued），confirmed/expired/cancelled 由专门动作驱动。
     */
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        $request->validate([
            'tax_rate'      => 'required|numeric|min:0|max:100',
            'exchange_rate' => 'required|numeric|min:0',
            'status'        => 'required|in:draft,issued,confirmed',
            'locale'        => 'required|in:en,zh_CN,zh_TW,vi',
        ]);

        // 状态机校验：只有允许的流转才能改状态
        if (isset($this->stateMap['quote'][$quote->status]) && $request->input('status') !== $quote->status) {
            [$ok, $err] = $this->canTransition('quote', $quote->status, $request->input('status'));
            if (! $ok) {
                return redirect()->route('admin.quotes.edit', $id)->with('error', '状态流转不合法：'.$err);
            }
        }

        $taxRate      = (float) $request->input('tax_rate', 10);
        $exchangeRate = (float) $request->input('exchange_rate', 3500);
        $status       = $request->input('status');
        $locale       = $request->input('locale', 'en');

        $prices = $request->input('price', []);
        $qtys   = $request->input('qty', []);

        $subtotal = 0.0;

        foreach (DB::table('mro_quote_items')->where('quote_id', $id)->get() as $item) {
            $p = (float) ($prices[$item->id] ?? $item->price_cny);
            $q = (int) ($qtys[$item->id] ?? $item->qty);
            if ($p < 0) {
                $p = 0;
            }
            if ($q < 1) {
                $q = 1;
            }
            $t = round($p * $q, 2);

            DB::table('mro_quote_items')->where('id', $item->id)->update([
                'price_cny' => $p,
                'qty'       => $q,
                'total_cny' => $t,
            ]);

            $subtotal += $t;
        }

        $taxCny  = round($subtotal * $taxRate / 100, 2);
        $total   = round($subtotal + $taxCny, 2);
        $totalVnd = (int) round($total * $exchangeRate);

        $before = $quote;

        DB::table('mro_quotes')->where('id', $id)->update([
            'customer_name' => $request->input('customer_name', $quote->customer_name),
            'email'         => $request->input('email', $quote->email),
            'phone'         => $request->input('phone', $quote->phone),
            'company'       => $request->input('company', $quote->company),
            'note'          => $request->input('note', $quote->note),
            'tax_rate'      => $taxRate,
            'exchange_rate' => $exchangeRate,
            'status'        => $status,
            'locale'        => $locale,
            'subtotal_cny'  => $subtotal,
            'tax_cny'       => $taxCny,
            'total_cny'     => $total,
            'total_vnd'     => $totalVnd,
        ]);

        // 内容锁定：报价已发送/确认后，任何编辑都会使哈希失效，需重新锁定
        $fresh = DB::table('mro_quotes')->where('id', $id)->first();
        $items = DB::table('mro_quote_items')->where('quote_id', $id)->get();
        $hash  = $this->quoteHash($fresh, $items);
        DB::table('mro_quotes')->where('id', $id)->update(['content_hash' => $hash]);

        $this->audit('quote', $id, 'update', (array) $before, (array) $fresh);

        return redirect()->route('admin.quotes.edit', $id)->with('success', '报价单已更新并重算完成');
    }

    /**
     * 发送报价单（draft -> issued）：锁定有效期 + 内容哈希 + 发邮件（含客户确认链接）。
     */
    public function sendQuote($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        [$ok, $err] = $this->canTransition('quote', $quote->status, 'issued');
        if (! $ok) {
            return redirect()->route('admin.quotes.edit', $id)->with('error', '无法发送：'.$err);
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->get();

        // 默认有效期 7 天（可改）
        $validDays = (int) config('mro.quote_valid_days', 7);
        $validUntil = now()->addDays($validDays);
        $hash = $this->quoteHash($quote, $items);

        $before = (array) $quote;

        DB::table('mro_quotes')->where('id', $id)->update([
            'status'       => 'issued',
            'issued_at'    => now(),
            'valid_until'  => $validUntil,
            'content_hash' => $hash,
        ]);

        $quote->status = 'issued';
        $quote->issued_at = now();
        $quote->valid_until = $validUntil;
        $quote->content_hash = $hash;

        $this->audit('quote', $id, 'send', $before, ['status' => 'issued', 'valid_until' => (string) $validUntil]);

        $this->emailQuote($quote, $items, $validUntil);

        return redirect()->route('admin.quotes.edit', $id)->with('success', "报价单已发送（有效期至 {$validUntil}），并已邮件通知客户确认");
    }

    /**
     * 报价确认（后台代确认）：issued -> confirmed。
     */
    public function confirmQuote($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        [$ok, $err] = $this->canTransition('quote', $quote->status, 'confirmed');
        if (! $ok) {
            return redirect()->route('admin.quotes.edit', $id)->with('error', '无法确认：'.$err);
        }

        $before = (array) $quote;

        DB::table('mro_quotes')->where('id', $id)->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->audit('quote', $id, 'confirm', $before, ['status' => 'confirmed']);

        // 确认报价 -> 生成订单
        $orderId = $this->createOrderFromQuote($id);

        return redirect()->route('admin.quotes.edit', $id)
            ->with('success', '报价单已确认，并已生成订单 #'.$orderId.'（待签章）');
    }

    /**
     * 取消报价单：draft/issued/confirmed -> cancelled。
     */
    public function cancelQuote($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        [$ok, $err] = $this->canTransition('quote', $quote->status, 'cancelled');
        if (! $ok) {
            return redirect()->route('admin.quotes.edit', $id)->with('error', '无法取消：'.$err);
        }

        $before = (array) $quote;

        DB::table('mro_quotes')->where('id', $id)->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->audit('quote', $id, 'cancel', $before, ['status' => 'cancelled']);

        return redirect()->route('admin.quotes.edit', $id)->with('success', '报价单已取消');
    }

    /**
     * 过期报价单：issued -> expired（cron 或手动）。
     */
    public function expireQuote($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return redirect()->route('admin.quotes.index')->with('error', '报价单不存在');
        }

        [$ok, $err] = $this->canTransition('quote', $quote->status, 'expired');
        if (! $ok) {
            return redirect()->route('admin.quotes.edit', $id)->with('error', '无法过期：'.$err);
        }

        $before = (array) $quote;

        DB::table('mro_quotes')->where('id', $id)->update(['status' => 'expired']);

        $this->audit('quote', $id, 'expire', $before, ['status' => 'expired']);

        return redirect()->route('admin.quotes.edit', $id)->with('success', '报价单已标记为过期');
    }

    /**
     * 由已确认报价单生成正式订单（订单 = 待签章）。
     */
    public function createOrderFromQuote($quoteId): int
    {
        $quote = DB::table('mro_quotes')->where('id', $quoteId)->first();

        if (! $quote) {
            throw new \RuntimeException('报价单不存在');
        }

        // 已确认且未过期才允许生成订单
        if ($quote->status !== 'confirmed') {
            throw new \RuntimeException('只有已确认的报价单才能生成订单');
        }
        if ($quote->valid_until && strtotime($quote->valid_until) < time()) {
            throw new \RuntimeException('报价单已过期，不能生成订单');
        }

        // 防重复生成
        $existing = DB::table('mro_orders')->where('quote_id', $quoteId)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $orderId = DB::table('mro_orders')->insertGetId([
            'order_no'      => null,
            'quote_id'      => $quoteId,
            'customer_id'   => $quote->customer_id,
            'customer_name' => $quote->customer_name,
            'email'         => $quote->email,
            'phone'         => $quote->phone,
            'company'       => $quote->company,
            'status'        => 'pending_sign',
            'source_type'   => $quote->source_type ?: 'cart',
            'source_id'     => $quote->source_id,
            'tax_rate'      => $quote->tax_rate,
            'exchange_rate' => $quote->exchange_rate,
            'subtotal_cny'  => $quote->subtotal_cny,
            'tax_cny'       => $quote->tax_cny,
            'total_cny'     => $quote->total_cny,
            'total_vnd'     => $quote->total_vnd,
            'currency'      => $quote->currency ?: 'VND',
            'locale'        => $quote->locale,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        foreach (DB::table('mro_quote_items')->where('quote_id', $quoteId)->get() as $item) {
            DB::table('mro_order_items')->insert([
                'order_id'   => $orderId,
                'product_id' => $item->product_id,
                'sku'        => $item->sku,
                'name'       => $item->name,
                'qty'        => $item->qty,
                'price_cny'  => $item->price_cny,
                'total_cny'  => $item->total_cny,
            ]);
        }

        $order = DB::table('mro_orders')->where('id', $orderId)->first();
        $orderItems = DB::table('mro_order_items')->where('order_id', $orderId)->get();

        $no = $this->genNo('mro_orders', 'SO');
        DB::table('mro_orders')->where('id', $orderId)->update([
            'order_no'     => $no,
            'content_hash' => $this->orderHash($order, $orderItems),
        ]);

        $this->audit('order', $orderId, 'create', null, ['quote_id' => $quoteId, 'order_no' => $no]);

        return $orderId;
    }

    /**
     * 发送报价单邮件（含客户在线确认链接）。
     */
    protected function emailQuote($quote, $items, $validUntil): void
    {
        $no = $quote->quote_no ?: 'Q'.str_pad($quote->id, 4, '0', STR_PAD_LEFT);
        $locale = $quote->locale ?: 'en';

        $labels = [
            'en' => ['confirm' => 'Confirm & Create Order', 'valid' => 'Valid until', 'subtotal' => 'Subtotal', 'tax' => 'Tax', 'total' => 'Total', 'totalvnd' => 'Total (VND)'],
            'zh_CN' => ['confirm' => '确认并生成订单', 'valid' => '有效期至', 'subtotal' => '小计', 'tax' => '税', 'total' => '合计', 'totalvnd' => '合计(越南盾)'],
            'zh_TW' => ['confirm' => '確認並生成訂單', 'valid' => '有效期至', 'subtotal' => '小計', 'tax' => '稅', 'total' => '合計', 'totalvnd' => '合計(越南盾)'],
            'vi' => ['confirm' => 'Xác nhận & tạo đơn hàng', 'valid' => 'Hiệu lực đến', 'subtotal' => 'Tạm tính', 'tax' => 'Thuế', 'total' => 'Tổng', 'totalvnd' => 'Tổng (VND)'],
        ];
        $L = $labels[$locale] ?? $labels['en'];

        // 确认链接：带一次性令牌（hash(报价id + content_hash)），仅 issued 且未过期可确认
        $token = substr(hash('sha256', $quote->id.'|'.$quote->content_hash), 0, 32);
        $confirmUrl = url('/quote/confirm/'.$quote->id.'?token='.$token);

        $lines = [];
        foreach ($items as $it) {
            $lines[] = "  - {$it->name} x{$it->qty}  ¥{$it->price_cny}  = ¥{$it->total_cny}";
        }

        $body = ($locale === 'en'
            ? "Dear {$quote->customer_name},\n\nYour quotation {$no} has been issued.\n\nItems:\n"
            : ($locale === 'vi'
                ? "Kính gửi {$quote->customer_name},\n\nBáo giá {$no} của quý khách đã được phát hành.\n\nChi tiết:\n"
                : "尊敬的 {$quote->customer_name}，\n\n您的报价单 {$no} 已生成。\n\n商品明细：\n"));

        $body .= implode("\n", $lines);
        $body .= "\n\n{$L['subtotal']}: ¥{$quote->subtotal_cny}";
        $body .= "\n{$L['tax']}({$quote->tax_rate}%): ¥{$quote->tax_cny}";
        $body .= "\n{$L['total']}: ¥{$quote->total_cny}";
        $body .= "\n{$L['totalvnd']}: ₫".number_format($quote->total_vnd);
        $body .= "\n\n{$L['valid']}: {$validUntil}";
        $body .= "\n\n请点击确认生成订单：{$confirmUrl}";
        $body .= "\n{$L['confirm']} 后系统将自动创建正式订单（待双方签章）。";
        $body .= "\n\nJR MRO 超市";

        try {
            Mail::raw($body, function ($message) use ($quote, $no) {
                $message->to($quote->email)
                    ->subject('报价单 '.$no.' - JR MRO 超市（待确认）');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('报价单确认邮件发送失败: '.$e->getMessage());
        }
    }

    /**
     * 生成标准 MRO 报价单文档（可打印 / 多语种）。
     */
    public function printQuote($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            abort(404);
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->orderBy('id')->get();

        $locale = request()->query('locale', $quote->locale ?: 'en');

        return view('admin::catalog.quotes.print', compact('quote', 'items', 'locale'));
    }

    /**
     * 导出报价单为 Excel。
     */
    public function exportExcel($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            abort(404);
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->orderBy('id')->get();

        $no = $quote->quote_no ?: 'Q'.str_pad($id, 4, '0', STR_PAD_LEFT);

        $headings = ['SKU', '商品名称', '数量', '单价(CNY)', '小计(CNY)'];

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [$item->sku, $item->name, $item->qty, number_format($item->price_cny, 2), number_format($item->total_cny, 2)];
        }

        $rows[] = ['', '', '', '小计', number_format($quote->subtotal_cny, 2)];
        $rows[] = ['', '', '', '税('.$quote->tax_rate.'%)', number_format($quote->tax_cny, 2)];
        $rows[] = ['', '', '', '合计(CNY)', number_format($quote->total_cny, 2)];
        $rows[] = ['', '', '', '合计(VND)', number_format($quote->total_vnd, 0)];
        $rows[] = ['', '', '', '汇率', '1 CNY = '.number_format($quote->exchange_rate, 0).' VND'];

        return ExcelFacade::download(new QuoteExport($rows, $headings), '报价单-'.$no.'.xlsx');
    }

    /**
     * 导出报价单为 PDF（dompdf）。
     */
    public function exportPdf($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            abort(404);
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->orderBy('id')->get();

        $locale = request()->query('locale', $quote->locale ?: 'en');
        $no = $quote->quote_no ?: 'Q'.str_pad($id, 4, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('admin::catalog.quotes.pdf', compact('quote', 'items', 'locale'));

        $content = $pdf->output();
        $content = (strncmp($content, "\xEF\xBB\xBF", 3) === 0) ? substr($content, 3) : $content;

        // 清理 Laravel 引导期可能输出的 BOM / 空白，避免污染二进制 PDF
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="quote-'.$no.'.pdf"',
        ]);
    }

    /**
     * 发送报价单邮件给客户（附 PDF）。
     */
    public function sendEmail($id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            abort(404);
        }

        $items = DB::table('mro_quote_items')->where('quote_id', $id)->orderBy('id')->get();

        $no = $quote->quote_no ?: 'Q'.str_pad($id, 4, '0', STR_PAD_LEFT);
        $locale = $quote->locale ?: 'en';

        $labels = [
            'en' => ['subtotal' => 'Subtotal', 'tax' => 'Tax', 'total' => 'Total', 'totalvnd' => 'Total (VND)'],
            'zh_CN' => ['subtotal' => '小计', 'tax' => '税', 'total' => '合计', 'totalvnd' => '合计(越南盾)'],
            'zh_TW' => ['subtotal' => '小計', 'tax' => '稅', 'total' => '合計', 'totalvnd' => '合計(越南盾)'],
            'vi' => ['subtotal' => 'Tạm tính', 'tax' => 'Thuế', 'total' => 'Tổng', 'totalvnd' => 'Tổng (VND)'],
        ];
        $L = $labels[$locale] ?? $labels['en'];

        $pdf = Pdf::loadView('admin::catalog.quotes.pdf', compact('quote', 'items', 'locale'));

        $pdfContent = $pdf->output();
        $pdfContent = (strncmp($pdfContent, "\xEF\xBB\xBF", 3) === 0) ? substr($pdfContent, 3) : $pdfContent;

        $body = ($locale === 'en'
            ? "Dear {$quote->customer_name},\n\nYour quotation {$no} has been issued.\n\nItems:\n"
            : ($locale === 'vi'
                ? "Kính gửi {$quote->customer_name},\n\nBáo giá {$no} của quý khách đã được phát hành.\n\nChi tiết:\n"
                : "尊敬的 {$quote->customer_name}，\n\n您的报价单 {$no} 已生成。\n\n商品明细：\n"));

        foreach ($items as $item) {
            $body .= "  - {$item->name} x{$item->qty}  ¥{$item->price_cny}  = ¥{$item->total_cny}\n";
        }

        $body .= "\n{$L['subtotal']}: ¥{$quote->subtotal_cny}";
        $body .= "\n{$L['tax']}({$quote->tax_rate}%): ¥{$quote->tax_cny}";
        $body .= "\n{$L['total']}: ¥{$quote->total_cny}";
        $body .= "\n{$L['totalvnd']}: ₫".number_format($quote->total_vnd);
        $body .= "\n\nJR MRO 超市";

        try {
            Mail::raw($body, function ($message) use ($quote, $no, $pdfContent) {
                $message->to($quote->email)
                    ->subject('报价单 '.$no.' - JR MRO 超市')
                    ->attachData($pdfContent, '报价单-'.$no.'.pdf', ['mime' => 'application/pdf']);
            });

            return redirect()->route('admin.quotes.edit', $id)->with('success', '报价单邮件已发送至 '.$quote->email);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('报价单邮件发送失败: '.$e->getMessage());

            return redirect()->route('admin.quotes.edit', $id)
                ->with('error', '报价单邮件发送失败（SMTP 被拒收或未配置）：'.$e->getMessage());
        }
    }

    /**
     * C4 议价/跟进记录：客户还价 → 再报价，留痕。
     */
    public function negotiate(\Illuminate\Http\Request $request, $id)
    {
        $quote = DB::table('mro_quotes')->where('id', $id)->first();

        if (! $quote) {
            return back()->with('error', '报价单不存在');
        }

        $round = (int) (DB::table('mro_negotiations')->where('quote_id', $id)->max('round') ?? 0) + 1;

        $party  = $request->input('party') === 'customer' ? 'customer' : 'sales';
        $amount = $request->input('offered_cny');

        DB::table('mro_negotiations')->insert([
            'quote_id'     => $id,
            'round'        => $round,
            'offered_cny'  => $amount ?: null,
            'offered_vnd'  => $amount ? (int) round((float) $amount * (float) $quote->exchange_rate) : null,
            'party'        => $party,
            'note'         => $request->input('note') ?: null,
            'operator'     => auth()->user()->name ?? 'admin',
            'created_at'   => now(),
        ]);

        return back()->with('success', '议价记录已保存（第 '.$round.' 轮，'.($party === 'customer' ? '客户还价' : '我方报价').'）');
    }
}
