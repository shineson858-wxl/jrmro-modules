<x-admin::layouts>
    <x-slot:title>
        商品电子表格导入
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            商品电子表格导入
        </p>

        <a href="{{ route('admin.catalog.products.index') }}" class="primary-button">
            返回商品列表
        </a>
    </div>

    @if (session('import_report'))
        @php $r = session('import_report'); @endphp
        <div class="mt-5 rounded-lg border p-5 {{ $r['failed'] ? 'border-red-300 bg-red-50 dark:bg-red-900/20' : 'border-green-300 bg-green-50 dark:bg-green-900/20' }}">
            <p class="text-lg font-bold {{ $r['failed'] ? 'text-red-700' : 'text-green-700' }}">
                导入报告：总计 {{ $r['total'] }}，成功 {{ $r['created'] }}，跳过(已存在) {{ $r['skipped'] }}，失败 {{ $r['failed'] }}
            </p>
            <p class="mt-1 text-sm text-gray-600">
                成功率 {{ $r['successRate'] }}%
            </p>

            @if ($r['created'] > 0)
                <div class="mt-3">
                    <p class="font-semibold">已导入商品：</p>
                    <div class="mt-1 max-h-40 overflow-auto rounded border p-2 text-sm">
                        @foreach ($r['imported'] as $sku => $id)
                            <div>{{ $sku }}（id={{ $id }}）</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($r['errors']))
                <div class="mt-3">
                    <p class="font-semibold text-red-700">失败明细（CSV 行号: 原因）：</p>
                    <div class="mt-1 max-h-40 overflow-auto rounded border border-red-200 p-2 text-sm">
                        @foreach ($r['errors'] as $line => $msg)
                            <div class="text-red-700">第{{ $line }}行: {{ $msg }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.catalog.products.import') }}"
        enctype="multipart/form-data"
        class="mt-5 max-w-3xl rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
    >
        @csrf

        <div class="mb-5">
            <label class="mb-1 block font-semibold text-gray-700">
                ① 选择商品表格（CSV，UTF-8）
            </label>
            <p class="mb-2 text-xs text-gray-500">
                表头需含 20 列：sku, name, url_key, price, weight, category_l1_id, category_l2_id, model_number, material_id, unit_id, supplier, certification, application, warranty, qty, images, status, featured, new, description（可留空但列名不能缺）
            </p>
            <input type="file" name="csv_file" accept=".csv,.txt" required class="rounded border p-2" />
        </div>

        <div class="mb-5">
            <label class="mb-1 block font-semibold text-gray-700">
                ② 选择商品图片（可多选；文件名须与表格 images 列一致）
            </label>
            <p class="mb-2 text-xs text-gray-500">
                一个商品多张图：在 images 列用英文分号 ; 分隔多个文件名，如 <code>glove1.jpg;helmet.jpg</code>；随后在这里一并选中这些图片文件上传。导入时自动转为 webp。
            </p>
            <input type="file" name="image_files[]" accept="image/*" multiple class="rounded border p-2" />
        </div>

        <div class="flex items-center gap-2.5">
            <button type="submit" class="primary-button">
                开始导入
            </button>
            <span class="text-xs text-gray-500">导入完成后在本页显示成功/失败报告，失败行给出原因，改后重传即可。</span>
        </div>
    </form>
</x-admin::layouts>
