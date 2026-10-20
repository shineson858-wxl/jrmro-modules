<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Helpers\DocSourceAdapter;

class DealingsController extends Controller
{
    /**
     * 单据中心概览：统一展示询价单 / 报价单 / 购物车订单。
     */
    public function index()
    {
        // 询价单
        $rfqTotal   = DocSourceAdapter::rfqCount();
        $rfqPending = DocSourceAdapter::rfqCountBy('pending');
        $rfqQuoted  = DocSourceAdapter::rfqCountIn(['quoted', 'accepted', 'completed']);
        $rfqRecent  = DocSourceAdapter::recentRfqs(5);

        // 报价单
        $quoteTotal  = DB::table('mro_quotes')->count();
        $quoteDraft  = DB::table('mro_quotes')->where('status', 'draft')->count();
        $quoteIssued = DB::table('mro_quotes')->whereIn('status', ['issued', 'accepted'])->count();
        $quoteVnd    = (int) DB::table('mro_quotes')->sum('total_vnd');
        $quoteRecent = DB::table('mro_quotes')->orderByDesc('id')->limit(5)->get();

        // 购物车订单（原生）
        $orderTotal    = DB::table('orders')->count();
        $orderPending  = DB::table('orders')->whereIn('status', ['pending', 'processing'])->count();
        $orderComplete = DB::table('orders')->where('status', 'completed')->count();
        $orderRecent   = DB::table('orders')->orderByDesc('id')->limit(5)->get();

        return view('admin::dealings.index', compact(
            'rfqTotal', 'rfqPending', 'rfqQuoted', 'rfqRecent',
            'quoteTotal', 'quoteDraft', 'quoteIssued', 'quoteVnd', 'quoteRecent',
            'orderTotal', 'orderPending', 'orderComplete', 'orderRecent'
        ));
    }
}
