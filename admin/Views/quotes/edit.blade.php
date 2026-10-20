<x-admin::layouts>
    <x-slot:title>
        报价单详情 / 调价
    </x-slot:title>

    @php
        $order = $quote->status === 'confirmed'
            ? \Illuminate\Support\Facades\DB::table('mro_orders')->where('quote_id', $quote->id)->first()
            : null;
        $expired = $quote->valid_until && strtotime($quote->valid_until) < time();

        $statusMap = [
            'draft'     => ['草稿', 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200'],
            'issued'    => ['已发送', 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'],
            'confirmed' => ['已确认', 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'],
            'expired'   => ['已过期', 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'],
            'cancelled' => ['已取消', 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
        ];
        [$statusLabel, $statusCls] = $statusMap[$quote->status] ?? [$quote->status, 'bg-gray-100 text-gray-500'];

        $steps = ['draft' => '草稿', 'issued' => '已发送', 'confirmed' => '已确认', 'order' => '订单生成'];
        $stepOrder = ['draft' => 0, 'issued' => 1, 'confirmed' => 2, 'order' => 3];
        $currentStep = $quote->status === 'confirmed' ? 'order' : $quote->status;
        $currentIdx = $stepOrder[$currentStep] ?? 0;
        if ($quote->status === 'confirmed') { $currentIdx = 4; }
    @endphp

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div class="flex items-center gap-3 flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                报价单 #{{ $quote->id }}（{{ $quote->quote_no ?: 'Q'.str_pad($quote->id, 4, '0', STR_PAD_LEFT) }}）
            </p>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusCls }}">{{ $statusLabel }}</span>
            @if (in_array($quote->status, ['issued', 'confirmed']))
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $expired ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    有效期至：{{ $quote->valid_until }}
                    @if ($expired)（已过期）@endif
                </span>
            @endif
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.quotes.export-excel', $quote->id) }}" class="secondary-button">导出 Excel</a>
            <a href="{{ route('admin.quotes.export-pdf', $quote->id) }}" class="secondary-button">导出 PDF</a>
            @if (in_array($quote->status, ['draft', 'issued']))
                <form method="POST" action="{{ route('admin.quotes.send-email', $quote->id) }}" onsubmit="return confirm('确认发送报价单邮件给客户？');">
                    @csrf
                    <button type="submit" class="secondary-button">发送邮件</button>
                </form>
            @endif
            <a href="{{ route('admin.quotes.print', $quote->id) }}" target="_blank" class="primary-button">打印标准报价单</a>
            <a href="{{ route('admin.quotes.index') }}" class="text-sm text-blue-600 hover:underline">返回列表</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">{{ session('error') }}</div>
    @endif

    {{-- 生命周期时间线 --}}
    <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">报价单生命周期</p>
        <div class="flex items-center gap-1 flex-wrap">
            @foreach ($steps as $key => $label)
                @php $idx = $stepOrder[$key]; $done = $idx < $currentIdx; $now = $idx === $currentIdx; @endphp
                <div class="flex items-center">
                    <div class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium
                        {{ $done ? 'bg-orange-500 text-white' : ($now ? 'bg-orange-100 text-orange-700 ring-2 ring-orange-400 dark:bg-orange-900 dark:text-orange-200' : 'bg-gray-100 text-gray-400 dark:bg-gray-800') }}">
                        <span>{{ $done ? '✓' : ($now ? '●' : '○') }}</span>{{ $label }}
                    </div>
                    @if (! $loop->last)
                        <span class="mx-1 text-gray-400">{{ $idx < $currentIdx ? '→' : '···' }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($order)
            <p class="mt-3 text-sm text-green-700 dark:text-green-300">
                已生成正式订单：
                <a href="{{ route('admin.orders.edit', $order->id) }}" class="font-semibold underline">{{ $order->order_no }}</a>
            </p>
        @endif
    </div>

    {{-- 状态机动作区 --}}
    <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50/60 p-4 dark:border-orange-800 dark:bg-orange-900/20">
        <p class="mb-3 text-sm font-semibold text-orange-800 dark:text-orange-200">流程操作（状态机驱动，操作后自动锁定内容哈希并记录审计）</p>
        <div class="flex flex-wrap items-center gap-3">
            @if ($quote->status === 'draft')
                <form method="POST" action="{{ route('admin.quotes.send', $quote->id) }}" onsubmit="return confirm('确认发送报价？发送后报价进入【已发送】并设置有效期、锁定内容、向客户发送确认邮件。');">
                    @csrf
                    <button type="submit" class="primary-button">发送报价</button>
                </form>
            @endif
            @if ($quote->status === 'issued' && ! $expired)
                <form method="POST" action="{{ route('admin.quotes.confirm', $quote->id) }}" onsubmit="return confirm('确认客户已接受该报价？确认后将生成正式订单并进入签章流程。');">
                    @csrf
                    <button type="submit" class="primary-button">确认报价 → 生成订单</button>
                </form>
                <form method="POST" action="{{ route('admin.quotes.expire', $quote->id) }}" onsubmit="return confirm('确认将该报价标记为已过期？');">
                    @csrf
                    <button type="submit" class="secondary-button">标记过期</button>
                </form>
            @endif
            @if ($quote->status === 'issued' && $expired)
                <p class="text-sm text-red-600">该报价已超过有效期，客户确认链接将失效。可重新编辑后重新发送。</p>
            @endif
            @if (in_array($quote->status, ['draft', 'issued']))
                <form method="POST" action="{{ route('admin.quotes.cancel', $quote->id) }}" onsubmit="return confirm('确认取消该报价？');">
                    @csrf
                    <button type="submit" class="secondary-button">取消报价</button>
                </form>
            @endif
            @if ($quote->status === 'confirmed')
                <p class="text-sm text-green-700 dark:text-green-300">该报价已确认，正式订单已生成，不可再修改报价内容。</p>
            @endif
            @if (in_array($quote->status, ['expired', 'cancelled']))
                <p class="text-sm text-gray-500">该报价处于【{{ $statusLabel }}】终态，不可再执行流程操作。</p>
            @endif
        </div>
    </div>

    @if (in_array($quote->status, ['issued', 'confirmed']))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-xs text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            内容哈希已锁定：<code class="font-mono">{{ substr($quote->content_hash, 0, 32) }}…</code>
            <span class="ml-2">（报价内容一旦变更将自动重算并重新锁定，用于追溯与防篡改）</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.quotes.update', $quote->id) }}" class="mt-5">
        @csrf
        <div class="grid gap-5 lg:grid-cols-3">
            {{-- 客户信息 --}}
            <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
                <p class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">客户信息</p>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">客户姓名</label>
                        <input name="customer_name" value="{{ $quote->customer_name }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">邮箱</label>
                        <input name="email" value="{{ $quote->email }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">电话</label>
                        <input name="phone" value="{{ $quote->phone }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">公司</label>
                        <input name="company" value="{{ $quote->company }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">报价单语言</label>
                        <select name="locale" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                            <option value="en" @selected($quote->locale === 'en')>English</option>
                            <option value="zh_CN" @selected($quote->locale === 'zh_CN')>简体中文</option>
                            <option value="zh_TW" @selected($quote->locale === 'zh_TW')>繁體中文</option>
                            <option value="vi" @selected($quote->locale === 'vi')>Tiếng Việt</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">来源</label>
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $quote->source_type === 'cart' ? '购物车' : ($quote->source_type === 'rfq' ? '询价单' : ($quote->source_type ?: '—')) }}
                        </div>
                    </div>
                    @if (in_array($quote->status, ['issued', 'confirmed']))
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">有效期至</label>
                            <div class="text-sm {{ $expired ? 'text-red-600' : 'text-gray-700 dark:text-gray-300' }}">{{ $quote->valid_until }} {{ $expired ? '（已过期）' : '' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 商品明细 + 调价 --}}
            <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800 lg:col-span-2">
                <p class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">商品明细（可逐行调整价格 / 数量）</p>
                <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">SKU</th>
                                <th class="px-3 py-2">名称</th>
                                <th class="px-3 py-2 text-right">数量</th>
                                <th class="px-3 py-2 text-right">单价(CNY)</th>
                                <th class="px-3 py-2 text-right">小计(CNY)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                            @foreach ($items as $item)
                                <tr class="text-sm text-gray-700 dark:text-gray-300">
                                    <td class="px-3 py-2 text-xs">{{ $item->sku }}</td>
                                    <td class="max-w-[220px] px-3 py-2"><div class="truncate" title="{{ $item->name }}">{{ $item->name }}</div></td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" min="1" name="qty[{{ $item->id }}]" value="{{ $item->qty }}" class="w-20 rounded-md border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-900" />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" step="0.01" min="0" name="price[{{ $item->id }}]" value="{{ $item->price_cny }}" class="w-28 rounded-md border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-900" />
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium">¥ {{ number_format($item->total_cny, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 税率 / 汇率 / 合计 --}}
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">税率 %</label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $quote->tax_rate }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">汇率 (CNY→VND)</label>
                        <input type="number" step="0.01" min="0" name="exchange_rate" value="{{ $quote->exchange_rate }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
                    </div>
                    <div class="flex items-end justify-end gap-5">
                        <div class="text-right">
                            <div class="text-xs text-gray-500">小计</div>
                            <div class="font-medium">¥ {{ number_format($quote->subtotal_cny, 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">税</div>
                            <div class="font-medium">¥ {{ number_format($quote->tax_cny, 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">合计</div>
                            <div class="text-lg font-bold text-green-600">¥ {{ number_format($quote->total_cny, 2) }}</div>
                            <div class="text-sm text-green-700">₫ {{ number_format($quote->total_vnd) }}</div>
                        </div>
                    </div>
                </div>

                @if (in_array($quote->status, ['draft', 'issued']))
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="primary-button">保存修改（重算金额并重新锁定哈希）</button>
                    </div>
                @else
                    <p class="mt-4 text-xs text-gray-500">报价已进入【{{ $statusLabel }}】终态，内容不可修改。</p>
                @endif
            </div>
        </div>
    </form>

    {{-- 审计日志 --}}
    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">报价审计日志</p>
        @php
            $logs = \Illuminate\Support\Facades\DB::table('mro_audit_logs')->where('entity_type', 'quote')->where('entity_id', $quote->id)->orderByDesc('id')->limit(10)->get();
        @endphp
        @if ($logs->isEmpty())
            <p class="text-sm text-gray-500">暂无审计记录</p>
        @else
            <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                @foreach ($logs as $log)
                    <li>[{{ $log->created_at }}] {{ $log->operator }} 执行「{{ $log->action }}」@if ($log->after_value) → {{ $log->after_value }} @endif</li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- C4 议价/跟进记录 --}}
    <div class="mt-6 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">议价 / 跟进记录（C4）</p>
        @php
            $negotiations = \Illuminate\Support\Facades\DB::table('mro_negotiations')->where('quote_id', $quote->id)->orderBy('round')->get();
        @endphp
        @if ($negotiations->isEmpty())
            <p class="mb-3 text-sm text-gray-500">暂无议价记录。客户还价后在此留痕。</p>
        @else
            <table class="mb-3 w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="py-2">轮次</th><th class="py-2">方向</th><th class="py-2">报价(CNY)</th><th class="py-2">报价(VND)</th><th class="py-2">备注</th><th class="py-2">操作人</th><th class="py-2">时间</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($negotiations as $n)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="py-2">{{ $n->round }}</td>
                            <td class="py-2">{{ $n->party === 'customer' ? '客户还价' : '我方报价' }}</td>
                            <td class="py-2">{{ $n->offered_cny !== null ? number_format($n->offered_cny, 2) : '—' }}</td>
                            <td class="py-2">{{ $n->offered_vnd !== null ? number_format($n->offered_vnd) : '—' }}</td>
                            <td class="py-2">{{ $n->note ?: '—' }}</td>
                            <td class="py-2">{{ $n->operator ?: '—' }}</td>
                            <td class="py-2">{{ $n->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <form method="POST" action="{{ route('admin.quotes.negotiate', $quote->id) }}" class="grid gap-3 sm:grid-cols-4">
            @csrf
            <select name="party" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="sales">我方报价</option>
                <option value="customer">客户还价</option>
            </select>
            <input type="number" name="offered_cny" step="0.01" min="0" placeholder="金额 CNY" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
            <input type="text" name="note" placeholder="跟进备注" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
            <button type="submit" class="rounded-md bg-[#F96302] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">保存议价记录</button>
        </form>
    </div>
</x-admin::layouts>
