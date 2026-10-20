<x-admin::layouts>
    <x-slot:title>经营看板（L1）</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white">MRO 经营看板</p>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-500">今日成交 (CNY)</p><p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($data['today_cny'], 0) }}</p><p class="text-xs text-gray-400">{{ $data['today_c'] }} 单</p></div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-500">本月成交 (CNY)</p><p class="mt-1 text-2xl font-bold text-[#F96302]">{{ number_format($data['month_cny'], 0) }}</p><p class="text-xs text-gray-400">{{ $data['month_c'] }} 单</p></div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-500">待处理询价/报价</p><p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ $data['pending_rfq'] }} / {{ $data['pending_quote'] }}</p><p class="text-xs text-gray-400">新询价 / 待发报价</p></div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-500">待处理订单 / 欠款</p><p class="mt-1 text-2xl font-bold {{ $data['pending_orders'] > 0 || $data['unpaid_total'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $data['pending_orders'] }} / {{ number_format($data['unpaid_total'], 0) }}</p><p class="text-xs text-gray-400">未收单 / 欠款CNY</p></div>
    </div>

    {{-- 收入确认口径（F2，会计）：delivered=已确认收入；已交付未收款=应收 --}}
    <div class="mt-5 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <p class="text-sm font-semibold text-gray-800 dark:text-white">收入确认（会计口径：交付签收确认）</p>
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div class="rounded-md bg-orange-50 p-4 dark:bg-orange-900/20"><p class="text-xs text-gray-500">已确认收入（已交付）</p><p class="mt-1 text-2xl font-bold text-[#F96302]">¥ {{ number_format($data['rev_confirmed'], 0) }}</p></div>
            <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/20"><p class="text-xs text-gray-500">已收款（现金/核销）</p><p class="mt-1 text-2xl font-bold text-green-600">¥ {{ number_format($data['rev_received'], 0) }}</p></div>
            <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/20"><p class="text-xs text-gray-500">应收（已交付未收款）</p><p class="mt-1 text-2xl font-bold {{ $data['rev_receivable'] > 0 ? 'text-red-600' : 'text-green-600' }}">¥ {{ number_format($data['rev_receivable'], 0) }}</p></div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800 lg:col-span-2">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">近 30 天成交趋势 (CNY)</p>
            @if ($trend->isEmpty())
                <p class="text-sm text-gray-500">暂无已收款成交。</p>
            @else
                <canvas id="trendChart" height="180"></canvas>
                <script>
                    (function () {
                        var labels = []; var values = [];
                        @foreach ($trend as $t) labels.push("{{ $t->d }}"); values.push({{ $t->total }}); @endforeach
                        if (typeof window.__trendChartInited !== 'undefined') return; window.__trendChartInited = true;
                        var canvas = document.getElementById('trendChart');
                        if (!canvas || typeof Chart === 'undefined') return;
                        new Chart(canvas, { type: 'line', data: { labels: labels, datasets: [{ label: '成交CNY', data: values, borderColor: '#F96302', backgroundColor: 'rgba(249,99,2,.1)', fill: true, tension: .3 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
                    })();
                </script>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">缺货商品</p>
            <p class="text-3xl font-bold {{ $data['out_of_stock'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $data['out_of_stock'] }}</p>
            <p class="mt-1 text-xs text-gray-500">库存为 0 的商品数</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">Top 商品（按销量）</p>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="py-2">SKU</th><th class="py-2">名称</th><th class="py-2">销量</th><th class="py-2">CNY</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($topProducts as $p)
                        <tr class="text-gray-700 dark:text-gray-300"><td class="py-2">{{ $p->sku }}</td><td class="py-2">{{ $p->name }}</td><td class="py-2">{{ $p->qty }}</td><td class="py-2">{{ number_format($p->total, 0) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-500">暂无</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">Top 客户</p>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="py-2">客户</th><th class="py-2">订单</th><th class="py-2">成交CNY</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($topCustomers as $c)
                        <tr class="text-gray-700 dark:text-gray-300"><td class="py-2">{{ $c->customer_name }}</td><td class="py-2">{{ $c->c }}</td><td class="py-2">{{ number_format($c->total, 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">暂无</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>