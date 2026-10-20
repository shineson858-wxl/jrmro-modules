@php
    // 多语种标签
    $labels = [
        'en' => [
            'quote' => 'QUOTATION', 'no' => 'Quote No.', 'date' => 'Date', 'validity' => 'Validity',
            'bill' => 'Bill To', 'name' => 'Name', 'company' => 'Company', 'phone' => 'Phone', 'email' => 'Email',
            'sku' => 'SKU', 'desc' => 'Description', 'qty' => 'Qty', 'price' => 'Unit Price', 'amount' => 'Amount',
            'subtotal' => 'Subtotal', 'tax' => 'Tax (VAT)', 'total' => 'Total', 'totalvnd' => 'Total (VND)',
            'rate' => 'Exchange Rate', 'note' => 'Notes', 'thank' => 'Thank you for your inquiry. Valid for 15 days.',
            'currency' => 'CNY', 'vnd' => 'VND',
        ],
        'zh_CN' => [
            'quote' => '报价单', 'no' => '报价单号', 'date' => '日期', 'validity' => '有效期',
            'bill' => '客户', 'name' => '姓名', 'company' => '公司', 'phone' => '电话', 'email' => '邮箱',
            'sku' => 'SKU', 'desc' => '商品名称', 'qty' => '数量', 'price' => '单价', 'amount' => '金额',
            'subtotal' => '小计', 'tax' => '税 (增值税)', 'total' => '合计', 'totalvnd' => '合计 (越南盾)',
            'rate' => '汇率', 'note' => '备注', 'thank' => '感谢您的询价，本报价有效期为 15 天。',
            'currency' => '人民币', 'vnd' => '越南盾',
        ],
        'zh_TW' => [
            'quote' => '報價單', 'no' => '報價單號', 'date' => '日期', 'validity' => '有效期',
            'bill' => '客戶', 'name' => '姓名', 'company' => '公司', 'phone' => '電話', 'email' => '信箱',
            'sku' => 'SKU', 'desc' => '商品名稱', 'qty' => '數量', 'price' => '單價', 'amount' => '金額',
            'subtotal' => '小計', 'tax' => '稅 (增值稅)', 'total' => '合計', 'totalvnd' => '合計 (越南盾)',
            'rate' => '匯率', 'note' => '備註', 'thank' => '感謝您的詢價，本報價有效期為 15 天。',
            'currency' => '人民幣', 'vnd' => '越南盾',
        ],
        'vi' => [
            'quote' => 'BÁO GIÁ', 'no' => 'Số báo giá', 'date' => 'Ngày', 'validity' => 'Hiệu lực',
            'bill' => 'Khách hàng', 'name' => 'Tên', 'company' => 'Công ty', 'phone' => 'Điện thoại', 'email' => 'Email',
            'sku' => 'SKU', 'desc' => 'Mô tả', 'qty' => 'SL', 'price' => 'Đơn giá', 'amount' => 'Thành tiền',
            'subtotal' => 'Tạm tính', 'tax' => 'Thuế (VAT)', 'total' => 'Tổng', 'totalvnd' => 'Tổng (VND)',
            'rate' => 'Tỷ giá', 'note' => 'Ghi chú', 'thank' => 'Cảm ơn quý khách, báo giá có hiệu lực 15 ngày.',
            'currency' => 'CNY', 'vnd' => 'VND',
        ],
    ];
    $L = $labels[$locale] ?? $labels['en'];
    $no = $quote->quote_no ?: 'Q'.str_pad($quote->id, 4, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $L['quote'] }} {{ $no }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, "Microsoft YaHei", sans-serif; color: #333; background: #fff; }
    .sheet { max-width: 800px; margin: 0 auto; padding: 40px 36px; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #F96302; padding-bottom: 18px; }
    .brand { font-size: 26px; font-weight: 800; color: #F96302; letter-spacing: 1px; }
    .brand small { display: block; font-size: 11px; color: #777; font-weight: 400; letter-spacing: 2px; }
    .doc-title { font-size: 22px; font-weight: 700; color: #222; text-align: right; }
    .meta { display: flex; justify-content: space-between; margin-top: 22px; gap: 24px; }
    .meta .blk { width: 50%; }
    .meta .blk h4 { font-size: 12px; color: #F96302; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .meta p { font-size: 13px; line-height: 1.6; }
    .meta .row { display: flex; font-size: 13px; line-height: 1.7; }
    .meta .row b { width: 90px; color: #666; font-weight: 600; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 26px; font-size: 13px; }
    table.items th { background: #F96302; color: #fff; text-align: left; padding: 9px 10px; font-size: 12px; }
    table.items td { border: 1px solid #e2e2e2; padding: 9px 10px; }
    table.items tr:nth-child(even) td { background: #faf7f4; }
    .num { text-align: right; }
    .totals { margin-top: 18px; margin-left: auto; width: 300px; font-size: 13px; }
    .totals .row { display: flex; justify-content: space-between; padding: 5px 8px; }
    .totals .grand { font-size: 15px; font-weight: 700; background: #f2ece6; border-left: 3px solid #F96302; }
    .totals .vnd { color: #2e7d32; font-weight: 700; }
    .note { margin-top: 26px; font-size: 12px; color: #666; border-top: 1px dashed #ccc; padding-top: 12px; }
    .thank { margin-top: 14px; font-size: 12px; color: #888; }
    .footer { margin-top: 34px; display: flex; justify-content: space-between; font-size: 12px; color: #999; }
    .sign { margin-top: 60px; text-align: right; }
    .sign .line { width: 200px; border-top: 1px solid #333; margin-left: auto; margin-bottom: 6px; }
    .sign p { font-size: 12px; }
    @media print {
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .no-print { display: none; }
        .sheet { max-width: none; padding: 0; }
    }
</style>
</head>
<body>
<div class="sheet">
    <div class="header">
        <div class="brand">
            JR MRO 超市
            <small>INDUSTRIAL · TOOLS · HARDWARE · SAFETY</small>
        </div>
        <div class="doc-title">{{ $L['quote'] }}</div>
    </div>

    <div style="margin-top:14px;font-size:12px;color:#666;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span><b>JR MRO 超市</b> · {{ $locale === 'en' ? 'North Bac Ninh / Bac Giang Industrial Zones, Vietnam' : ($locale === 'vi' ? 'KCN Bắc Ninh / Bắc Giang, Việt Nam' : '越南 · 北宁 / 北江工业区') }}</span>
        <span>{{ $locale === 'en' ? 'Tax No.' : ($locale === 'vi' ? 'MST' : '税号') }}: 2400XXXXXX · {{ $locale === 'en' ? 'Tel' : ($locale === 'vi' ? 'ĐT' : '电话') }}: +84 90 000 0000</span>
    </div>

    <div class="meta">
        <div class="blk">
            <h4>{{ $L['bill'] }}</h4>
            <p>
                <b>{{ $L['name'] }}:</b> {{ $quote->customer_name }}<br/>
                @if ($quote->company)<b>{{ $L['company'] }}:</b> {{ $quote->company }}<br/>@endif
                @if ($quote->phone)<b>{{ $L['phone'] }}:</b> {{ $quote->phone }}<br/>@endif
                <b>{{ $L['email'] }}:</b> {{ $quote->email }}
            </p>
        </div>
        <div class="blk">
            <div class="row"><b>{{ $L['no'] }}:</b> {{ $no }}</div>
            <div class="row"><b>{{ $L['date'] }}:</b> {{ $quote->created_at }}</div>
            <div class="row"><b>{{ $L['validity'] }}:</b> {{ $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('Y-m-d') : '15 '.($locale === 'en' ? 'days' : ($locale === 'vi' ? 'ngày' : '天')) }}</div>
            <div class="row"><b>{{ $L['rate'] }}:</b> 1 {{ $L['currency'] }} = {{ number_format($quote->exchange_rate, 0) }} {{ $L['vnd'] }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:16%">{{ $L['sku'] }}</th>
                <th>{{ $L['desc'] }}</th>
                <th class="num" style="width:9%">{{ $L['qty'] }}</th>
                <th class="num" style="width:14%">{{ $L['price'] }} ({{ $L['currency'] }})</th>
                <th class="num" style="width:16%">{{ $L['amount'] }} ({{ $L['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>
                        <svg class="barcode" data-sku="{{ $item->sku }}" style="width:120px;height:26px;"></svg>
                        <div>{{ $item->sku }}</div>
                    </td>
                    <td>{{ $item->name }}</td>
                    <td class="num">{{ $item->qty }}</td>
                    <td class="num">{{ number_format($item->price_cny, 2) }}</td>
                    <td class="num">{{ number_format($item->total_cny, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>{{ $L['subtotal'] }}</span><span>¥ {{ number_format($quote->subtotal_cny, 2) }}</span></div>
        <div class="row"><span>{{ $L['tax'] }} ({{ $quote->tax_rate }}%)</span><span>¥ {{ number_format($quote->tax_cny, 2) }}</span></div>
        <div class="row grand"><span>{{ $L['total'] }}</span><span>¥ {{ number_format($quote->total_cny, 2) }}</span></div>
        <div class="row vnd"><span>{{ $L['totalvnd'] }}</span><span>₫ {{ number_format($quote->total_vnd) }}</span></div>
        <div class="vatnote" style="margin-top:8px;padding:8px 10px;border:1.5px solid #F96302;background:#fff3ea;color:#b45309;font-weight:700;font-size:13px;text-align:center;border-radius:4px;">
            本报价金额已含 10% 增值税（VAT）· Giá này đã bao gồm thuế GTGT (VAT) 10% · Price includes 10% VAT
        </div>
    </div>

    @if ($quote->note)
        <div class="note"><b>{{ $L['note'] }}:</b> {{ $quote->note }}</div>
    @endif
    <div class="thank">{{ $L['thank'] }}</div>

    <div class="note">
        <b>{{ $locale === 'en' ? 'Terms & Conditions' : ($locale === 'vi' ? 'Điều khoản thương mại' : '商务条款') }}:</b>
        <p style="margin-top:6px;line-height:1.7;">
            {{ $locale === 'en' ? '1. Payment: T/T 30 days or COD.  2. Delivery: within 5-7 working days, FOB / DDP per agreement.  3. Warranty: 12 months for power tools.  4. Prices exclude import duties unless stated.' : ($locale === 'vi' ? '1. Thanh toán: T/T 30 ngày hoặc COD.  2. Giao hàng: trong 5-7 ngày làm việc, FOB/DDP theo thỏa thuận.  3. Bảo hành: 12 tháng đối với dụng cụ điện.  4. Giá chưa gồm thuế nhập khẩu trừ khi ghi rõ.' : '1. 付款：T/T 30 天或货到付款。 2. 交货：5-7 个工作日，按约定 FOB / DDP。 3. 质保：电动工具 12 个月。 4. 价格不含进口关税（另有注明除外）。') }}
        </p>
    </div>

    <!-- I3 双语并列商务条款（越语主 + 中文副） -->
    <div class="note" style="margin-top:10px;border-top:1px dashed #ccc;padding-top:8px;">
        <b>Điều khoản / 商务条款（双语并列）</b>
        <table style="width:100%;margin-top:6px;line-height:1.7;font-size:12px;border-collapse:collapse;">
            <tr><td style="border-bottom:1px solid #eee;padding:3px 0;">1. Thanh toán: T/T 30 ngày hoặc COD.　—　1. 付款：T/T 30 天或货到付款。</td></tr>
            <tr><td style="border-bottom:1px solid #eee;padding:3px 0;">2. Giao hàng: trong 5-7 ngày làm việc, FOB / DDP theo thỏa thuận.　—　2. 交货：5-7 个工作日，按约定 FOB / DDP。</td></tr>
            <tr><td style="border-bottom:1px solid #eee;padding:3px 0;">3. Bảo hành: 12 tháng đối với dụng cụ điện.　—　3. 质保：电动工具 12 个月。</td></tr>
            <tr><td style="padding:3px 0;">4. Giá chưa gồm thuế nhập khẩu trừ khi ghi rõ.　—　4. 价格不含进口关税（另有注明除外）。</td></tr>
        </table>
    </div>

    <div class="sign" style="display:flex;justify-content:space-between;text-align:center;gap:40px;">
        <div style="text-align:center;">
            <div style="width:220px;border-top:1px solid #333;margin:0 auto 6px;"></div>
            <p>{{ $locale === 'en' ? 'JR MRO (Supplier)' : ($locale === 'vi' ? 'JR MRO (Nhà cung cấp)' : 'JR MRO（供应商）') }}</p>
        </div>
        <div style="text-align:center;">
            <div style="width:220px;border-top:1px solid #333;margin:0 auto 6px;"></div>
            <p>{{ $locale === 'en' ? 'Customer Confirmation' : ($locale === 'vi' ? 'Xác nhận khách hàng' : '客户确认') }}</p>
        </div>
    </div>

    <div class="footer">
        <span>JR MRO 超市 · MRO 工业耗材 / 工具 / 五金 / 劳保</span>
        <button class="no-print" onclick="window.print()" style="padding:8px 16px;background:#F96302;color:#fff;border:0;border-radius:4px;cursor:pointer;">
            {{ $locale === 'en' ? 'Print / Save PDF' : ($locale === 'vi' ? 'In / Lưu PDF' : '打印 / 存为 PDF') }}
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    try {
        document.querySelectorAll('.barcode').forEach(function (el) {
            JsBarcode(el, el.getAttribute('data-sku'), { format: 'CODE128', width: 1.5, height: 26, displayValue: false });
        });
    } catch (e) {}
</script>
</body>
</html>
