<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * DocSourceAdapter —— 单据管理中心「外部数据源」防腐层（ACL）。
 *
 * 职责边界：购物车 / 询价单 属外部数据源，本模块只读不写，
 * 一律通过本 Adapter 读取，禁止业务代码直接查外部表。
 *
 * 当前实现为 Bagisto 同库直读（最低耦合）；若未来外部系统独立，
 * 只需替换本 Adapter 内部实现为 HTTP/RPC 调用，业务代码无需改动。
 */
class DocSourceAdapter
{
    /* ================= 询价单（外部数据源，只读） ================= */

    /** 询价单列表查询构造器（供分页/筛选）。 */
    public static function rfqQuery()
    {
        return DB::table('mro_rfqs');
    }

    /** 单条询价单详情。 */
    public static function rfq($id)
    {
        return DB::table('mro_rfqs')->where('id', $id)->first();
    }

    /** 询价单总数。 */
    public static function rfqCount()
    {
        return DB::table('mro_rfqs')->count();
    }

    /** 指定状态询价单数。 */
    public static function rfqCountBy($status)
    {
        return DB::table('mro_rfqs')->where('status', $status)->count();
    }

    /** 多个状态询价单数。 */
    public static function rfqCountIn($statuses)
    {
        return DB::table('mro_rfqs')->whereIn('status', $statuses)->count();
    }

    /** 最近 N 条询价单。 */
    public static function recentRfqs($n = 5)
    {
        return DB::table('mro_rfqs')->orderByDesc('id')->limit($n)->get();
    }
}
