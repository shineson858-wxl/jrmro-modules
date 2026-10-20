<x-admin::layouts>
    <x-slot:title>
        导入历史
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            商品导入历史（{{ $logs->total() }}）
        </p>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.catalog.products.index') }}" class="text-sm text-blue-600 hover:underline">
                返回商品列表
            </a>
        </div>
    </div>

    <div class="mt-5 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">CSV 路径</th>
                    <th class="px-4 py-3 text-right">总计</th>
                    <th class="px-4 py-3 text-right">成功</th>
                    <th class="px-4 py-3 text-right">跳过</th>
                    <th class="px-4 py-3 text-right">失败</th>
                    <th class="px-4 py-3 text-right">成功率</th>
                    <th class="px-4 py-3 text-right">耗时(ms)</th>
                    <th class="px-4 py-3">时间</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                @forelse ($logs as $log)
                    <tr class="text-sm text-gray-700 dark:text-gray-300">
                        <td class="px-4 py-3">{{ $log->id }}</td>
                        <td class="max-w-[280px] truncate px-4 py-3 text-xs text-gray-500" title="{{ $log->csv_path }}">
                            {{ $log->csv_path }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ $log->total }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-600">{{ $log->created }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $log->skipped }}</td>
                        <td class="px-4 py-3 text-right {{ $log->failed ? 'font-medium text-red-600' : 'text-gray-500' }}">
                            {{ $log->failed }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ $log->success_rate }}%</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $log->duration_ms }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $log->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                            暂无导入记录
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</x-admin::layouts>
