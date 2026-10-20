<x-admin::layouts>
    <x-slot:title>
        询价单管理
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            询价单（{{ $rfqs->total() }}）
        </p>
    </div>

    {{-- 状态筛选 --}}
    @php
        $tabs = [
            'all'     => '全部',
            'pending' => '待报价',
            'quoted'  => '已报价',
            'accepted'=> '成交',
            'closed'  => '已关闭',
        ];
    @endphp
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($tabs as $val => $label)
            <a href="{{ route('admin.catalog.rfq.index', ['status' => $val]) }}"
               class="rounded-full px-4 py-1.5 text-sm {{ $status === $val ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.catalog.rfq.bulk-quote') }}" id="bulk-quote-form">
        @csrf
        <div class="mt-5 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-[#F96302]">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-white">
                    <th class="px-4 py-3"><input type="checkbox" id="rfq-check-all" onclick="document.querySelectorAll('.rfq-check').forEach(c=>c.checked=this.checked)" class="accent-orange-600"></th>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">客户</th>
                    <th class="px-4 py-3">商品 / SKU</th>
                    <th class="px-4 py-3 text-right">数量</th>
                    <th class="px-4 py-3 text-right">历史价(¥)</th>
                    <th class="px-4 py-3 text-right">报价(VND)</th>
                    <th class="px-4 py-3">状态</th>
                    <th class="px-4 py-3">时间</th>
                    <th class="px-4 py-3 text-center">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                @forelse ($rfqs as $rfq)
                    <tr class="text-sm text-gray-700 dark:text-gray-300">
                        <td class="px-4 py-3"><input type="checkbox" name="rfq_ids[]" value="{{ $rfq->id }}" class="rfq-check accent-orange-600"></td>
                        <td class="px-4 py-3">{{ $rfq->id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $rfq->name }}</div>
                            <div class="text-xs text-gray-500">{{ $rfq->email }}</div>
                        </td>
                        <td class="max-w-[220px] px-4 py-3">
                            <div class="truncate" title="{{ $rfq->product_name }}">{{ $rfq->product_name }}</div>
                            <div class="text-xs text-gray-500">{{ $rfq->product_sku }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">{{ $rfq->qty }}</td>
                        <td class="px-4 py-3 text-right text-xs {{ isset($historyMap[$rfq->product_sku]) ? 'font-medium text-gray-600' : 'text-gray-400' }}">
                            {{ isset($historyMap[$rfq->product_sku]) ? '¥ '.number_format($historyMap[$rfq->product_sku], 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right {{ $rfq->price_vnd ? 'font-medium text-green-600' : 'text-gray-400' }}">
                            {{ $rfq->price_vnd ? number_format($rfq->price_vnd) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $map = [
                                    'pending'  => ['待报价', 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200'],
                                    'quoted'   => ['已报价', 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'],
                                    'accepted' => ['成交', 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'],
                                    'closed'   => ['已关闭', 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'],
                                ];
                                [$label, $cls] = $map[$rfq->status] ?? [$rfq->status, 'bg-gray-100 text-gray-500'];
                            @endphp
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $rfq->created_at }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.catalog.rfq.edit', $rfq->id) }}" class="primary-button !px-3 !py-1 text-xs">
                                查看/报价
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                            暂无询价单
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-[#F96302] px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                批量按历史价报价（勾选项）
            </button>
            <span class="text-xs text-gray-500">仅对「待报价」询价生效，优先取该 SKU 历史成交价；无历史价则沿用询价单原价。</span>
        </div>
    </form>

    <div class="mt-6">
        {{ $rfqs->links() }}
    </div>
</x-admin::layouts>
