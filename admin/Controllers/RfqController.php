<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Helpers\DocSourceAdapter;
use Webkul\Admin\Http\Controllers\Controller;

class RfqController extends Controller
{
    /**
     * CNY → VND 汇率（与前台定价保持一致）。
     */
    const VND_RATE = 3500;

    /**
     * 询价单列表。
     */
    public function index()
    {
        $status = request()->query('status', 'all');

        $query = DocSourceAdapter::rfqQuery();

        if (in_array($status, ['pending', 'quoted', 'accepted', 'closed'])) {
            $query->where('status', $status);
        }

        $rfqs = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // C2: 按 SKU 汇总历史报价价（来自已生成报价单明细），用于预填与批量报价
        $historyMap = [];

        $skus = $rfqs->pluck('product_sku')->filter()->unique()->values();

        if ($skus->isNotEmpty()) {
            $historyRows = DB::table('mro_quote_items as qi')
                ->join('mro_quotes as q', 'q.id', '=', 'qi.quote_id')
                ->whereIn('qi.sku', $skus)
                ->where('qi.price_cny', '>', 0)
                ->orderByDesc('qi.id')
                ->get(['qi.sku', 'qi.price_cny']);

            foreach ($historyRows as $hr) {
                if (! isset($historyMap[$hr->sku])) {
                    $historyMap[$hr->sku] = $hr->price_cny;
                }
            }
        }

        return view('admin::catalog.rfq.index', compact('rfqs', 'status', 'historyMap'));
    }

    /**
     * 询价单详情。
     */
    public function edit($id)
    {
        $rfq = DocSourceAdapter::rfq($id);

        if (! $rfq) {
            return redirect()->route('admin.catalog.rfq.index')->with('error', '询价单不存在');
        }

        return view('admin::catalog.rfq.edit', compact('rfq'));
    }

    /**
     * 提交报价并通知客户。
     */
    public function quote(\Illuminate\Http\Request $request, $id)
    {
        $rfq = DocSourceAdapter::rfq($id);

        if (! $rfq) {
            return redirect()->route('admin.catalog.rfq.index')->with('error', '询价单不存在');
        }

        $request->validate([
            'price_cny'  => 'required|numeric|min:0',
            'status'     => 'required|in:pending,quoted,accepted,closed',
            'quote_note' => 'nullable|string|max:1000',
        ]);

        $priceCny   = (float) $request->input('price_cny');
        $priceVnd   = (int) round($priceCny * $this->exchangeRate());
        $status     = $request->input('status');
        $quoteNote  = $request->input('quote_note', '');

        DB::table('mro_rfqs')->where('id', $id)->update([
            'price_cny'  => $priceCny,
            'price_vnd'  => $priceVnd,
            'quote_note' => $quoteNote,
            'status'     => $status,
        ]);

        // 仅「已报价」时通知客户
        if ($status === 'quoted' && $rfq->email) {
            try {
                $this->notifyCustomer($rfq, $priceCny, $priceVnd, $quoteNote);
            } catch (\Throwable $e) {
                logger()->error('RFQ 报价邮件发送失败: '.$e->getMessage());
            }
        }

        return redirect()->route('admin.catalog.rfq.index')->with('success', '报价已保存'.($status === 'quoted' ? '并已邮件通知客户' : ''));
    }

    /**
     * 发送报价邮件给客户。
     */
    protected function notifyCustomer($rfq, $priceCny, $priceVnd, $quoteNote)
    {
        $html = '<h3>您的询价已收到报价</h3>'
            .'<p>您好，'.$rfq->name.'：</p>'
            .'<p>您对以下商品的询价已获得我方报价：</p>'
            .'<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse">'
            .'<tr><th align="left">商品</th><td>'.$rfq->product_name.'</td></tr>'
            .'<tr><th align="left">SKU</th><td>'.$rfq->product_sku.'</td></tr>'
            .'<tr><th align="left">数量</th><td>'.$rfq->qty.'</td></tr>'
            .'<tr><th align="left">报价（越南盾）</th><td>'.number_format($priceVnd).' ₫</td></tr>'
            .'<tr><th align="left">报价（人民币基准价）</th><td>¥ '.number_format($priceCny, 2).'</td></tr>'
            .'</table>'
            .'<p>报价说明：'.($quoteNote ? nl2br(e($quoteNote)) : '（无）').'</p>'
            .'<p>如需下单或有任何疑问，欢迎随时联系我们。</p>'
            .'<p>— JR MRO 超市</p>';

        Mail::html($html, function ($message) use ($rfq) {
            $message->to($rfq->email, $rfq->name)
                ->subject('您的询价报价通知 - JR MRO 超市');
        });
    }

    /**
     * 读取全局默认汇率（后台「单据中心」可配置）。
     */
    protected function exchangeRate()
    {
        return (float) (DB::table('core_config')->where('code', 'catalog.quote.default_exchange_rate')->value('value') ?: 3500);
    }

    /**
     * C2 批量报价：一次为多个待报价询价单报价，
     * 优先取该 SKU 的历史成交价，无则用询价单已有报价。
     */
    public function bulkQuote(\Illuminate\Http\Request $request)
    {
        $ids  = (array) $request->input('rfq_ids', []);
        $rate = $this->exchangeRate();
        $count = 0;

        foreach ($ids as $id) {
            $rfq = DocSourceAdapter::rfq((int) $id);

            if (! $rfq || $rfq->status !== 'pending') {
                continue;
            }

            $history = DB::table('mro_quote_items as qi')
                ->join('mro_quotes as q', 'q.id', '=', 'qi.quote_id')
                ->where('qi.sku', $rfq->product_sku)
                ->where('qi.price_cny', '>', 0)
                ->orderByDesc('qi.id')
                ->value('qi.price_cny');

            $priceCny = (float) ($history ?: $rfq->price_cny);

            if ($priceCny <= 0) {
                continue;
            }

            $priceVnd = (int) round($priceCny * $rate);

            DB::table('mro_rfqs')->where('id', $id)->update([
                'price_cny'  => $priceCny,
                'price_vnd'  => $priceVnd,
                'quote_note' => ($rfq->quote_note ?: '').($history ? ' [按历史价报价]' : ''),
                'status'     => 'quoted',
            ]);

            if ($rfq->email) {
                try {
                    $this->notifyCustomer($rfq, $priceCny, $priceVnd, '按历史成交价批量报价');
                } catch (\Throwable $e) {
                    logger()->error('批量报价邮件失败: '.$e->getMessage());
                }
            }

            $count++;
        }

        return redirect()->route('admin.catalog.rfq.index')
            ->with('success', "已批量报价 {$count} 条询价（按 SKU 历史成交价）");
    }
}
