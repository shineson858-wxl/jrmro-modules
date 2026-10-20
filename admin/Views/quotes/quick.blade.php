<x-admin::layouts>
    <x-slot:title>快速报价（K3）</x-slot:title>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <p class="text-xl font-bold text-gray-800 dark:text-white">后台快速报价工具</p>
    <p class="mt-1 text-sm text-gray-500">选客户 → 输 SKU 与数量 → 自动带历史价 + 实时库存 → 一键生成报价单（可再进报价单调价/发送）。当前汇率 1 CNY = {{ number_format($rate) }} VND</p>

    <div class="mt-5 rounded-lg border border-gray-200 p-5 dark:border-gray-800">
        <form method="POST" action="{{ route('admin.quotes.quick.generate') }}" id="quickQuoteForm">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <select name="customer_id" required class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">选择客户</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}（{{ $c->email }}）</option>
                    @endforeach
                </select>
                <input type="text" name="note" placeholder="报价备注（可选）" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
            </div>

            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">商品明细（SKU × 数量）</p>
                    <button type="button" id="addLine" class="rounded-md bg-[#F96302] px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700">+ 加一行</button>
                </div>
                <table class="w-full text-left">
                    <thead class="text-xs uppercase tracking-wide text-gray-500">
                        <tr><th class="py-2">SKU</th><th class="py-2 w-28">数量</th><th class="py-2 w-16"></th></tr>
                    </thead>
                    <tbody id="linesBody">
                        <tr>
                            <td class="py-1"><input name="sku[]" required placeholder="如 MRO-BG-008" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" /></td>
                            <td class="py-1"><input name="qty[]" type="number" min="1" value="1" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" /></td>
                            <td class="py-1 text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="mt-5 rounded-md bg-[#F96302] px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">生成报价单</button>
        </form>
    </div>

    <script>
        document.getElementById('addLine').addEventListener('click', function () {
            var tbody = document.getElementById('linesBody');
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="py-1"><input name="sku[]" placeholder="如 MRO-BG-008" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" /></td>'
                + '<td class="py-1"><input name="qty[]" type="number" min="1" value="1" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" /></td>'
                + '<td class="py-1 text-center"><button type="button" class="text-red-500 text-sm" onclick="this.closest(\'tr\').remove()">✕</button></td>';
            tbody.appendChild(tr);
        });
    </script>
</x-admin::layouts>