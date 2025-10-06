<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة الدفع #{{ $payment->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#111; }
        .container { max-width: 800px; margin: 0 auto; padding: 16px; }
        .header { display:flex; justify-content: space-between; align-items:center; margin-bottom: 12px; }
        .title { font-size: 20px; font-weight:700; }
        .section { margin-top: 16px; border-top:1px solid #e5e7eb; padding-top: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; }
    </style>
    @php
        $shopName = config('app.name', 'MoonDiet');
    @endphp
    </head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">فاتورة الدفع رقم #{{ $payment->id }}</div>
            <div>{{ $shopName }}</div>
        </div>

        <div class="section">
            <strong>العميل:</strong> {{ $user->name ?? ('User #' . $user->id) }}<br>
            <strong>البريد:</strong> {{ $user->email ?? '-' }}<br>
            <strong>التاريخ:</strong> {{ $payment->created_at }}
        </div>

        <div class="section">
            <strong>تفاصيل الدفع</strong>
            <table>
                <tr><th>المزود</th><td>{{ strtoupper($payment->provider) }}</td></tr>
                <tr><th>المرجع</th><td>{{ $payment->provider_intent_id }}</td></tr>
                <tr><th>الحالة</th><td>{{ $payment->status }}</td></tr>
                <tr><th>القيمة</th><td>{{ number_format((float)$payment->amount, 2) }} {{ $payment->currency }}</td></tr>
            </table>
        </div>

        @if($order)
        <div class="section">
            <strong>تفاصيل الطلب #{{ $order->id }}</strong>
            <table>
                <tr><th>الحالة</th><td>{{ $order->status }}</td></tr>
                <tr><th>الإجمالي قبل الضريبة</th><td>{{ number_format((float)$order->subtotal, 2) }}</td></tr>
                <tr><th>الضريبة</th><td>{{ number_format((float)($order->tax ?? 0), 2) }}</td></tr>
                <tr><th>رسوم التوصيل</th><td>{{ number_format((float)$order->delivery_fee, 2) }}</td></tr>
                <tr><th>الخصم</th><td>{{ number_format((float)$order->discount, 2) }}</td></tr>
                <tr><th>الإجمالي</th><td>{{ number_format((float)$order->total, 2) }}</td></tr>
            </table>

            @if($order->items && $order->items->count())
            <div class="section">
                <strong>بنود الطلب</strong>
                <table>
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $it)
                            <tr>
                                <td>{{ optional($it->menuItem)->name ?? ('#'.$it->menu_item_id) }}</td>
                                <td>{{ $it->quantity }}</td>
                                <td>{{ number_format((float)$it->unit_price, 2) }}</td>
                                <td>{{ number_format((float)$it->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        <div class="section">
            <small>هذه الفاتورة صالحة للاستخدام الداخلي. لإصدار فاتورة ضريبية رسمية، يرجى تفعيل قالب ضريبي وإضافة رقم ضريبي.</small>
        </div>
    </div>
</body>
</html>


