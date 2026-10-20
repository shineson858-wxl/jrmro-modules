<x-admin::layouts>
    <x-slot:title>进价 / 毛利（K4）</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white">进价 / 毛利管理</p>

    @if (session('success'))
        <p class="mt-3 rounded-md bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</p>
    @endif

    <div class="mt-5 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <form method="GET" class="flex items-center gap-3">
            <input name="q" value="{{ $q }}" placeholder="搜索 SKU / 名称" class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <button class="rounded-md bg-[#F96302] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">搜索</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.catalog.productcost.update') }}">
        @csrf
        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800">
                    <tr><th class="px-3 py-2 text-left">SKU</th><th class="px-3 py-2 text-left">名称</th><th class="px-3 py-2 text-right">售价(CNY)</th><th class="px-3 py-2 text-right">成本(CNY)</th><th class="px-3 py-2 text-right">毛利额</th><th class="px-3 py-2 text-right">毛利率</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $r)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="px-3 py-2">{{ $r->sku }}</td>
                            <td class="px-3 py-2">{{ $r->name }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($r->price, 2) }}</td>
                            <td class="px-3 py-2 text-right"><input name="cost[{{ $r->product_id }}]" value="{{ $r->cost_cny }}" class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-900"></td>
                            <td class="px-3 py-2 text-right {{ $r->margin < 0 ? 'text-red-500' : 'text-green-600' }}">{{ number_format($r->margin, 2) }}</td>
                            <td class="px-3 py-2 text-right {{ $r->margin_pct < 0 ? 'text-red-500' : 'text-green-600' }}">{{ $r->margin_pct }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex items-center gap-4">
            <button class="rounded-md bg-[#F96302] px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">保存成本价</button>
            <span class="text-xs text-gray-500">{{ $rows->total() }} 条记录</span>
        </div>
        {{ $rows->withQueryString()->links() }}
    </form>
</x-admin::layouts>