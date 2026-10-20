<x-admin::layouts>
    <x-slot:title>
        报价单
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            报价单（{{ $quotes->total() }}）
        </p>
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- 全局默认汇率 / 税率 --}}
    <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50/70 p-4 dark:border-orange-800 dark:bg-orange-900/20">
        <p class="text-sm font-semibold text-orange-800 dark:text-orange-200">全局默认汇率 / 税率（新报价单自动采用，逐单可在编辑页覆盖）</p>
        <form method="POST" action="{{ route('admin.quotes.store-defaults') }}" class="mt-3 flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">默认汇率 (1 CNY → ? VND)</label>
                <input type="number" step="1" min="0" name="default_exchange_rate" value="{{ $defaultRate }}"
                       class="w-44 rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">默认税率 (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="default_tax_rate" value="{{ $defaultTax }}"
                       class="w-32 rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
            </div>
            <button type="submit" class="rounded-md bg-[#F96302] px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                保存默认值
            </button>
        </form>
    </div>

    @php
        $tabs = [
            'all'       => '全部',
            'draft'     => '待发送',
            'issued'    => '已发送',
            'confirmed' => '已确认',
            'expired'   => '已过期',
            'cancelled' => '已取消',
        ];
    @endphp
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($tabs as $val => $label)
            <a href="{{ route('admin.quotes.index', ['status' => $val]) }}"
               class="rounded-full px-4 py-1.5 text-sm {{ $status === $val ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-5 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-[#F96302]">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-white">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">单号</th>
                    <th class="px-4 py-3">客户</th>
                    <th class="px-4 py-3">来源</th>
                    <th class="px-4 py-3 text-right">件数</th>
                    <th class="px-4 py-3 text-right">合计(CNY)</th>
                    <th class="px-4 py-3 text-right">合计(VND)</th>
                    <th class="px-4 py-3">状态</th>
                    <th class="px-4 py-3">有效期至</th>
                    <th class="px-4 py-3">时间</th>
                    <th class="px-4 py-3 text-center">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                @forelse ($quotes as $quote)
                    @php
                        $srcMap = ['cart' => ['购物车', 'bg-sky-100 text-sky-700'], 'rfq' => ['询价单', 'bg-purple-100 text-purple-700']];
                        $src = $srcMap[$quote->source_type] ?? [$quote->source_type ?: '—', 'bg-gray-100 text-gray-500'];
                        $hasOrder = $quote->status === 'confirmed'
                            ? \Illuminate\Support\Facades\DB::table('mro_orders')->where('quote_id', $quote->id)->value('order_no')
                            : null;
                        $statusMap = [
                            'draft'     => ['待发送', 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200'],
                            'issued'    => ['已发送', 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'],
                            'confirmed' => ['已确认', 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'],
                            'expired'   => ['已过期', 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'],
                            'cancelled' => ['已取消', 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
                        ];
                        [$label, $cls] = $statusMap[$quote->status] ?? [$quote->status, 'bg-gray-100 text-gray-500'];
                    @endphp
                    <tr class="text-sm text-gray-700 dark:text-gray-300">
                        <td class="px-4 py-3">{{ $quote->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $quote->quote_no ?: 'Q'.str_pad($quote->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $quote->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $quote->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $src[1] }}">{{ $src[0] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \Illuminate\Support\Facades\DB::table('mro_quote_items')->where('quote_id', $quote->id)->count() }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-600">¥ {{ number_format($quote->total_cny, 2) }}</td>
                        <td class="px-4 py-3 text-right">₫ {{ number_format($quote->total_vnd) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs {{ $quote->valid_until && strtotime($quote->valid_until) < time() ? 'text-red-500' : 'text-gray-500' }}">
                            {{ $quote->valid_until ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $quote->created_at }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.quotes.edit', $quote->id) }}" class="primary-button !px-3 !py-1 text-xs">
                                编辑/调价
                            </a>
                            @if ($hasOrder)
                                <div class="mt-1 text-xs text-green-600">订单 {{ $hasOrder }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-sm text-gray-500">
                            暂无报价单（可从前台购物车「生成报价单」创建）
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $quotes->links() }}
    </div>
</x-admin::layouts>
