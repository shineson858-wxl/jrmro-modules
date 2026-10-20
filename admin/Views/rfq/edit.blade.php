<x-admin::layouts>
    <x-slot:title>
        询价单详情 / 报价
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            询价单 #{{ $rfq->id }}
        </p>

        <a href="{{ route('admin.catalog.rfq.index') }}" class="text-sm text-blue-600 hover:underline">
            返回列表
        </a>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        {{-- 询价信息 --}}
        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">询价信息</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">客户姓名</dt><dd>{{ $rfq->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">邮箱</dt><dd>{{ $rfq->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">电话</dt><dd>{{ $rfq->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">公司</dt><dd>{{ $rfq->company ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">商品</dt><dd>{{ $rfq->product_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">SKU</dt><dd>{{ $rfq->product_sku }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">数量</dt><dd>{{ $rfq->qty }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">提交时间</dt><dd>{{ $rfq->created_at }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">语言</dt><dd>{{ $rfq->locale }}</dd></div>
            </dl>
            @if ($rfq->note)
                <div class="mt-4 rounded bg-gray-50 p-3 text-sm dark:bg-gray-900">
                    <p class="mb-1 text-xs text-gray-500">客户留言</p>
                    {{ $rfq->note }}
                </div>
            @endif
        </div>

        {{-- 报价表单 --}}
        <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
            <p class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">填写报价</p>
            <form method="POST" action="{{ route('admin.catalog.rfq.quote', $rfq->id) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">报价（人民币基准价 CNY）</label>
                        <input name="price_cny" type="number" step="0.01" min="0" required
                               value="{{ $rfq->price_cny }}"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                        <p class="mt-1 text-xs text-gray-400">前台将按汇率 3500 换算为越南盾显示</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">报价说明（客户可见）</label>
                        <textarea name="quote_note" rows="4"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">{{ $rfq->quote_note }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">状态</label>
                        <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                            <option value="pending" @selected($rfq->status === 'pending')>待报价</option>
                            <option value="quoted" @selected($rfq->status === 'quoted')>已报价</option>
                            <option value="accepted" @selected($rfq->status === 'accepted')>成交</option>
                            <option value="closed" @selected($rfq->status === 'closed')>已关闭</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="primary-button">保存报价</button>
                        <p class="text-xs text-gray-400">状态选择「已报价」时会向客户发送邮件</p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
