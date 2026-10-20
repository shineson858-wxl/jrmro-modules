<x-admin::layouts>
    <x-slot:title>客户 360°（K2）</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white">客户 360° 视图</p>

    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[260px] flex-1">
                <label class="mb-1 block text-xs text-gray-500">选择客户</label>
                <select name="customer_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->first_name }} {{ $c->last_name }}（{{ $c->email }}）</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#F96302] px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">查看全景</button>
        </form>
    </div>

    @if ($customer && $summary)
        <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $customer->first_name }} {{ $customer->last_name }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $customer->email }} · {{ $customer->phone }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-900"><p class="text-xs text-gray-500">询价单</p><p class="text-lg font-bold text-gray-800 dark:text-white">{{ $summary['rfq'] }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-900"><p class="text-xs text-gray-500">报价单</p><p class="text-lg font-bold text-gray-800 dark:text-white">{{ $summary['quotes'] }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-900"><p class="text-xs text-gray-500">报价额(CNY)</p><p class="text-sm font-bold text-gray-800 dark:text-white">{{ number_format($summary['quote_total'], 0) }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-900"><p class="text-xs text-gray-500">订单</p><p class="text-lg font-bold text-gray-800 dark:text-white">{{ $summary['orders'] }}</p></div>
                <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-900"><p class="text-xs text-gray-500">发票</p><p class="text-lg font-bold text-gray-800 dark:text-white">{{ $summary['invoiced'] }}</p></div>
                <div class="rounded-lg bg-red-50 p-3 text-center dark:bg-red-900/20"><p class="text-xs text-red-500">欠款(CNY)</p><p class="text-sm font-bold text-red-600">{{ number_format($summary['unpaid'], 0) }}</p></div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
                <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">近期报价单</p>
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="py-2">单号</th><th class="py-2">状态</th><th class="py-2">CNY</th><th class="py-2">时间</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($quotes as $q)
                            <tr class="text-gray-700 dark:text-gray-300"><td class="py-2"><a href="{{ route('admin.quotes.edit', $q->id) }}" class="text-blue-600 hover:underline">{{ $q->quote_no }}</a></td><td class="py-2">{{ $q->status }}</td><td class="py-2">{{ number_format($q->total_cny, 0) }}</td><td class="py-2">{{ $q->created_at }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">暂无</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
                <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">近期订单</p>
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="py-2">单号</th><th class="py-2">状态</th><th class="py-2">配送</th><th class="py-2">CNY</th><th class="py-2">收款</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($orders as $o)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="py-2"><a href="{{ route('admin.orders.edit', $o->id) }}" class="text-blue-600 hover:underline">{{ $o->order_no }}</a></td>
                                <td class="py-2">{{ $o->status }}</td>
                                <td class="py-2">{{ $o->delivery_status }}</td>
                                <td class="py-2">{{ number_format($o->total_cny, 0) }}</td>
                                <td class="py-2 {{ $o->payment_status === 'paid' ? 'text-green-600' : 'text-red-600' }}">{{ $o->payment_status === 'paid' ? '已收' : '未收' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">暂无</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($request->filled('customer_id'))
        <p class="mt-4 text-sm text-red-500">客户不存在。</p>
    @endif
</x-admin::layouts>