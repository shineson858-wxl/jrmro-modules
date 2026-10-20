@php $currency = '₫'; @endphp

<div class="flex flex-col gap-6 p-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">单据中心</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">统一管理 MRO 交易单据：购物车订单 · 询价单 · 报价单</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.quotes.index') }}" class="rounded-md bg-[#F96302] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">进入报价单</a>
            <a href="{{ route('admin.catalog.rfq.index') }}" class="rounded-md border border-[#F96302] px-4 py-2 text-sm font-semibold text-[#F96302] hover:bg-orange-50">处理询价</a>
        </div>
    </div>

    {{-- 状态卡片 --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- 询价单 --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">询价单 (RFQ)</p>
                <span class="rounded-full bg-[#F96302]/10 px-2 py-0.5 text-xs font-bold text-[#F96302]">待处理 {{ $rfqPending }}</span>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-800 dark:text-white">{{ $rfqTotal }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">已报价 {{ $rfqQuoted }} · 共 {{ $rfqTotal }} 条</p>
            <a href="{{ route('admin.catalog.rfq.index') }}" class="mt-3 inline-block text-xs font-semibold text-[#F96302] hover:underline">查看全部 →</a>
        </div>

        {{-- 报价单 --}}
        <div class="rounded-xl border border-[#F96302]/40 bg-[#F96302]/5 p-4 shadow-sm dark:border-[#F96302]/40 dark:bg-[#F96302]/10">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-[#B85C12] dark:text-orange-300">报价单 (Quote)</p>
                <span class="rounded-full bg-[#F96302] px-2 py-0.5 text-xs font-bold text-white">草稿 {{ $quoteDraft }}</span>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-800 dark:text-white">{{ $quoteTotal }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">已发出 {{ $quoteIssued }} · 金额合计 {{ number_format($quoteVnd) }} {{ $currency }}</p>
            <a href="{{ route('admin.quotes.index') }}" class="mt-3 inline-block text-xs font-semibold text-[#F96302] hover:underline">查看全部 →</a>
        </div>

        {{-- 订单 --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">购物车订单</p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">处理中 {{ $orderPending }}</span>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-800 dark:text-white">{{ $orderTotal }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">已完成 {{ $orderComplete }} · 共 {{ $orderTotal }} 单</p>
            <a href="{{ route('admin.sales.orders.index') }}" class="mt-3 inline-block text-xs font-semibold text-[#F96302] hover:underline">查看全部 →</a>
        </div>
    </div>

    {{-- 最近单据 --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- 最近询价 --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">最近询价单</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($rfqRecent as $r)
                    <a href="{{ route('admin.catalog.rfq.index') }}" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $r->product_name ?: '（多商品询价）' }}</p>
                            <p class="text-xs text-gray-400">{{ $r->name }} · {{ $r->email }}</p>
                        </div>
                        <span class="rounded-full bg-{{ $r->status === 'pending' ? 'orange' : 'green' }}-100 px-2 py-0.5 text-xs font-semibold text-{{ $r->status === 'pending' ? 'orange' : 'green' }}-700 dark:bg-{{ $r->status === 'pending' ? 'orange' : 'green' }}-900/30 dark:text-{{ $r->status === 'pending' ? 'orange' : 'green' }}-300">{{ $r->status }}</span>
                    </a>
                @empty
                    <p class="px-4 py-4 text-sm text-gray-400">暂无询价</p>
                @endforelse
            </div>
        </div>

        {{-- 最近报价单 --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">最近报价单</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($quoteRecent as $q)
                    <a href="{{ route('admin.quotes.edit', $q->id) }}" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $q->quote_no ?: ('Q'.str_pad($q->id, 4, '0', STR_PAD_LEFT)) }} · {{ $q->customer_name }}</p>
                            <p class="text-xs text-gray-400">{{ number_format($q->total_vnd) }} {{ $currency }}</p>
                        </div>
                        <span class="rounded-full bg-{{ $q->status === 'draft' ? 'orange' : 'green' }}-100 px-2 py-0.5 text-xs font-semibold text-{{ $q->status === 'draft' ? 'orange' : 'green' }}-700 dark:bg-{{ $q->status === 'draft' ? 'orange' : 'green' }}-900/30 dark:text-{{ $q->status === 'draft' ? 'orange' : 'green' }}-300">{{ $q->status }}</span>
                    </a>
                @empty
                    <p class="px-4 py-4 text-sm text-gray-400">暂无报价单</p>
                @endforelse
            </div>
        </div>

        {{-- 最近订单 --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">最近订单</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($orderRecent as $o)
                    <a href="{{ route('admin.sales.orders.view', $o->id) }}" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div>
                            <p class="text-sm text-gray-800 dark:text-gray-100">订单 #{{ $o->increment_id }}</p>
                            <p class="text-xs text-gray-400">{{ $o->customer_email }}</p>
                        </div>
                        <span class="text-xs font-semibold text-{{ $o->status === 'completed' ? 'green' : 'orange' }}-700 dark:text-{{ $o->status === 'completed' ? 'green' : 'orange' }}-300">{{ $o->status }}</span>
                    </a>
                @empty
                    <p class="px-4 py-4 text-sm text-gray-400">暂无订单</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 单据流转说明 --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">单据流转闭环</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">购物车 → <span class="font-semibold text-[#F96302]">询价单</span>（客户询价）→ <span class="font-semibold text-[#F96302]">报价单</span>（你报价，可调价/税率/汇率）→ <span class="font-semibold text-[#F96302]">订单</span>（客户下单成交）</p>
    </div>
</div>
