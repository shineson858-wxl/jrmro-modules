@php
    $labels = [
        'en' => ['quote'=>'QUOTATION','no'=>'Quote No.','date'=>'Date','bill'=>'Bill To','sku'=>'SKU','desc'=>'Description','qty'=>'Qty','price'=>'Unit Price','amount'=>'Amount','subtotal'=>'Subtotal','tax'=>'Tax (VAT)','total'=>'Total','totalvnd'=>'Total (VND)','rate'=>'Exchange Rate','cur'=>'CNY'],
        'zh_CN' => ['quote'=>'报价单','no'=>'报价单号','date'=>'日期','bill'=>'客户','sku'=>'SKU','desc'=>'商品名称','qty'=>'数量','price'=>'单价','amount'=>'金额','subtotal'=>'小计','tax'=>'税','total'=>'合计','totalvnd'=>'合计(越南盾)','rate'=>'汇率','cur'=>'人民币'],
        'zh_TW' => ['quote'=>'報價單','no'=>'報價單號','date'=>'日期','bill'=>'客戶','sku'=>'SKU','desc'=>'商品名稱','qty'=>'數量','price'=>'單價','amount'=>'金額','subtotal'=>'小計','tax'=>'稅','total'=>'合計','totalvnd'=>'合計(越南盾)','rate'=>'匯率','cur'=>'人民幣'],
        'vi' => ['quote'=>'BÁO GIÁ','no'=>'Số báo giá','date'=>'Ngày','bill'=>'Khách hàng','sku'=>'SKU','desc'=>'Mô tả','qty'=>'SL','price'=>'Đơn giá','amount'=>'Thành tiền','subtotal'=>'Tạm tính','tax'=>'Thuế','total'=>'Tổng','totalvnd'=>'Tổng (VND)','rate'=>'Tỷ giá','cur'=>'CNY'],
    ];
    $L = $labels[$locale] ?? $labels['en'];
    $no = $quote->quote_no ?: 'Q'.str_pad($quote->id, 4, '0', STR_PAD_LEFT);
@endphp
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica','Arial',sans-serif; color:#222; font-size:12px; }
        .brand { color:#F96302; font-size:22px; font-weight:bold; }
        .brand small { display:block; font-size:9px; color:#888; }
        .doc { float:right; font-size:18px; font-weight:bold; }
        .hdr { border-bottom:2px solid #F96302; padding-bottom:10px; margin-bottom:14px; overflow:hidden; }
        .blk { width:100%; margin-bottom:12px; }
        .blk td { vertical-align:top; font-size:12px; }
        table.items { width:100%; border-collapse:collapse; margin-top:10px; }
        table.items th { background:#F96302; color:#fff; padding:6px 8px; text-align:left; font-size:11px; }
        table.items td { border:1px solid #ddd; padding:6px 8px; }
        .num { text-align:right; }
        .totals { margin-top:12px; width:100%; border-collapse:collapse; }
        .totals td { padding:4px 8px; }
        .grand td { font-weight:bold; background:#f2ece6; border-top:1px solid #999; }
        .vnd td { color:#2e7d32; font-weight:bold; }
        .note { margin-top:14px; font-size:11px; color:#666; }
        .footer { margin-top:30px; font-size:10px; color:#999; }
        .sign { margin-top:40px; text-align:right; font-size:12px; }
    </style>
</head>
<body>
    <div class="hdr">
        <div class="brand">JR MRO 超市<small>INDUSTRIAL · TOOLS · HARDWARE · SAFETY</small></div>
        <div class="doc">{{ $L['quote'] }}</div>
    </div>

    <table class="blk">
        <tr>
            <td style="width:55%">
                <b>{{ $L['bill'] }}:</b><br>
                {{ $quote->customer_name }}<br>
                @if ($quote->company){{ $quote->company }}<br>@endif
                @if ($quote->phone){{ $quote->phone }}<br>@endif
                {{ $quote->email }}
            </td>
            <td>
                {{ $L['no'] }}: {{ $no }}<br>
                {{ $L['date'] }}: {{ $quote->created_at }}<br>
                {{ $L['rate'] }}: 1 {{ $L['cur'] }} = {{ number_format($quote->exchange_rate, 0) }} VND
            </td>
        </tr>
    </table>

    <table class="items">
        <tr>
            <th style="width:15%">{{ $L['sku'] }}</th>
            <th>{{ $L['desc'] }}</th>
            <th class="num" style="width:8%">{{ $L['qty'] }}</th>
            <th class="num" style="width:15%">{{ $L['price'] }} ({{ $L['cur'] }})</th>
            <th class="num" style="width:16%">{{ $L['amount'] }} ({{ $L['cur'] }})</th>
        </tr>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->name }}</td>
                <td class="num">{{ $item->qty }}</td>
                <td class="num">{{ number_format($item->price_cny, 2) }}</td>
                <td class="num">{{ number_format($item->total_cny, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr><td style="width:76%"></td><td class="num">{{ $L['subtotal'] }}</td><td class="num">¥ {{ number_format($quote->subtotal_cny, 2) }}</td></tr>
        <tr><td></td><td class="num">{{ $L['tax'] }} ({{ $quote->tax_rate }}%)</td><td class="num">¥ {{ number_format($quote->tax_cny, 2) }}</td></tr>
        <tr class="grand"><td></td><td class="num">{{ $L['total'] }}</td><td class="num">¥ {{ number_format($quote->total_cny, 2) }}</td></tr>
        <tr class="vnd"><td></td><td class="num">{{ $L['totalvnd'] }}</td><td class="num">₫ {{ number_format($quote->total_vnd) }}</td></tr>
    </table>

    @if ($quote->note)
        <div class="note"><b>{{ $L['note'] ?? 'Note' }}:</b> {{ $quote->note }}</div>
    @endif
    <div class="footer">JR MRO 超市 · MRO 工业耗材 / 工具 / 五金 / 劳保</div>
    <div class="sign">JR MRO 超市</div>
</body>
</html>
