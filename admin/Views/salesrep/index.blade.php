<x-admin::layouts>
    <x-slot:title>业务员管理（K1）</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white">业务员 / 业绩管理</p>

    @if (session('success'))
        <p class="mt-3 rounded-md bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</p>
    @endif

    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">指派客户给业务员</p>
        <form method="POST" action="{{ route('admin.salesrep.assign') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[220px] flex-1">
                <label class="mb-1 block text-xs text-gray-500">业务员</label>
                <select name="admin_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    @foreach ($admins as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}（{{ $a->email }}）</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[260px] flex-1">
                <label class="mb-1 block text-xs text-gray-500">负责客户（可多选）</label>
                <select name="customer_ids[]" multiple size="6" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}（{{ $c->email }}）</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#F96302] px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">保存指派</button>
        </form>
    </div>

    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">业务员业绩（按其客户单据汇总）</p>
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wide text-gray-500">
                <tr><th class="py-2">业务员</th><th class="py-2">客户数</th><th class="py-2">询价</th><th class="py-2">报价</th><th class="py-2">报价额(CNY)</th><th class="py-2">订单</th><th class="py-2">成交额(CNY)</th><th class="py-2">未收款(CNY)</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($performance as $p)
                    <tr class="text-gray-700 dark:text-gray-300">
                        <td class="py-2 font-semibold">{{ $p['admin']->name }}</td>
                        <td class="py-2">{{ $p['customers'] }}</td>
                        <td class="py-2">{{ $p['rfq'] }}</td>
                        <td class="py-2">{{ $p['quotes'] }}</td>
                        <td class="py-2">{{ number_format($p['quote_total'], 0) }}</td>
                        <td class="py-2">{{ $p['orders'] }}</td>
                        <td class="py-2">{{ number_format($p['order_total'], 0) }}</td>
                        <td class="py-2 {{ $p['unpaid'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($p['unpaid'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 text-center text-gray-500">尚未指派任何客户，先在上方指派。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">当前指派明细</p>
        <ul class="grid gap-1 text-xs text-gray-600 dark:text-gray-400 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($assignments as $a)
                <li>[业务员 {{ $a->admin_id }}] → 客户 #{{ $a->customer_id }}</li>
            @empty
                <li>暂无指派</li>
            @endforelse
        </ul>
    </div>
</x-admin::layouts>