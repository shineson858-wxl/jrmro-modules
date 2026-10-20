<x-admin::layouts>
    <x-slot:title>对账报表（J3）</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">单据中心报表 / 对账</p>
        <a href="{{ route('admin.catalog.reports.export') }}" class="rounded-md bg-[#F96302] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">导出客户对账 CSV</a>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">月度成交汇总（近 6 个月）</p>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="py-2">月份</th><th class="py-2">报价单数</th><th class="py-2">报价额(CNY)</th><th class="py-2">订单数</th><th class="py-2">成交额(CNY)</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($months as $m)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="py-2">{{ $m->ym }}</td>
                            <td class="py-2">{{ $m->quotes }}</td>
                            <td class="py-2">{{ number_format($m->quote_total, 2) }}</td>
                            <td class="py-2">{{ $orderMonths->firstWhere('ym', $m->ym)->orders ?? 0 }}</td>
                            <td class="py-2">{{ number_format($orderMonths->firstWhere('ym', $m->ym)->order_total ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">客户对账汇总</p>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="py-2">客户</th><th class="py-2">订单数</th><th class="py-2">成交额(CNY)</th><th class="py-2">未收款(CNY)</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($customers as $c)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="py-2">{{ $c->customer_name }}</td>
                            <td class="py-2">{{ $c->orders }}</td>
                            <td class="py-2">{{ number_format($c->total, 2) }}</td>
                            <td class="py-2 {{ $c->unpaid > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($c->unpaid, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-500">暂无订单数据</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- F6 财务四表：应收账龄 / 存货结存 / 税费汇总 / 资金对账 --}}
    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">财务月结四表（F6）</p>
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                <p class="mb-2 text-xs font-semibold text-gray-500">① 应收账龄（未收款按到期分层）</p>
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="py-1">账龄</th><th class="py-1 text-right">金额(CNY)</th><th class="py-1 text-right">单数</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($aging as $a)
                            <tr class="text-gray-700 dark:text-gray-300"><td class="py-1">{{ $a->bucket }}</td><td class="py-1 text-right {{ $a->bucket === '60天以上' ? 'text-red-600' : '' }}">{{ number_format($a->amt, 2) }}</td><td class="py-1 text-right">{{ $a->cnt }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-center text-gray-500">无应收</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                <p class="mb-2 text-xs font-semibold text-gray-500">② 存货结存（成本×实时库存）</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">¥ {{ number_format($stockRow->stock_cny ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500">{{ $stockRow->skus ?? 0 }} 个 SKU 结存成本</p>
            </div>
            <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                <p class="mb-2 text-xs font-semibold text-gray-500">③ 税费汇总（销项 VAT）</p>
                <p class="text-2xl font-bold text-[#F96302]">¥ {{ number_format($taxRow->output_tax ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500">已开票 {{ $taxRow->invoices ?? 0 }} 张</p>
            </div>
            <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                <p class="mb-2 text-xs font-semibold text-gray-500">④ 资金对账（收 / 应收）</p>
                <div class="flex justify-between"><span class="text-sm text-gray-600">已收款(CNY)</span><span class="font-semibold text-green-600">¥ {{ number_format($cashRow->received ?? 0, 2) }}</span></div>
                <div class="mt-1 flex justify-between"><span class="text-sm text-gray-600">应收(CNY)</span><span class="font-semibold {{ ($cashRow->receivable ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">¥ {{ number_format($cashRow->receivable ?? 0, 2) }}</span></div>
            </div>
        </div>
    </div>
</x-admin::layouts>