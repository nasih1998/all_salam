<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Invoice - {{$sale['Ref']}}</title>
    <style>
        @page { 
            size: A4;
            margin: 10mm; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Arial', sans-serif; 
            font-size: 10pt; 
            color: #000; 
            line-height: 1.3; 
            padding: 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        .header-box {
            border: 2px solid #000;
            padding: 15px;
            text-align: center;
            margin-bottom: 10px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            line-height: 1.5;
        }
        .invoice-details {
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        .invoice-details td {
            padding: 6px 10px;
            border: 1px solid #000;
            font-size: 9pt;
        }
        .invoice-details .label {
            font-weight: bold;
            width: 25%;
            background: #f5f5f5;
        }
        .items-table {
            border: 2px solid #000;
            margin-bottom: 10px;
        }
        .items-table th {
            background: #f5f5f5;
            border: 1px solid #000;
            padding: 8px 5px;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            font-size: 9pt;
        }
        .items-table .row-num {
            text-align: center;
            width: 5%;
        }
        .items-table .code {
            text-align: center;
            width: 15%;
        }
        .items-table .qty {
            text-align: center;
            width: 10%;
        }
        .items-table .price {
            text-align: right;
            width: 15%;
        }
        .items-table .total {
            text-align: right;
            width: 15%;
        }
        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .totals-table {
            border: 1px solid #000;
            margin-bottom: 10px;
            width: 50%;
            margin-left: auto;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 9pt;
        }
        .totals-table .label {
            font-weight: bold;
            background: #f5f5f5;
            width: 50%;
        }
        .totals-table .amount {
            text-align: right;
            width: 50%;
        }
        .totals-table .grand-total {
            font-weight: bold;
            font-size: 10pt;
        }
        .signature-section {
            margin-top: 20px;
        }
        .signature-box {
            border: 1px solid #000;
            padding: 40px 10px 10px 10px;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
        }
        .total-items {
            text-align: center;
            padding: 8px;
            font-weight: bold;
            background: #f5f5f5;
            border: 1px solid #000;
            border-top: 2px solid #000;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-box">
        <div class="logo-container">
            @if(!empty($setting['logo']) && file_exists(public_path('images/'.$setting['logo'])))
                <img src="{{public_path('images/'.$setting['logo'])}}" alt="Logo" style="max-height: 80px; max-width: 200px;">
            @endif
        </div>
        <div class="company-name">{{$setting['CompanyName']}}</div>
        <div class="company-info">
            <div>📍 {{$setting['CompanyAdress']}}</div>
            <div>📞 {{$setting['CompanyPhone']}} | ✉ {{$setting['email']}}</div>
        </div>
    </div>

    <!-- Invoice Details -->
    <table class="invoice-details" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Invoice #:</td>
            <td>{{$sale['Ref']}}</td>
            <td class="label">Date:</td>
            <td>{{$sale['date']}}</td>
        </tr>
        <tr>
            <td class="label">Customer:</td>
            <td>{{$sale['client_name']}}</td>
            <td class="label">Seller:</td>
            <td>{{$setting['CompanyName']}}</td>
        </tr>
        <tr>
            <td class="label">Phone:</td>
            <td>{{$sale['client_phone']}}</td>
            <td class="label">Warehouse:</td>
            <td>Default Warehouse</td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="row-num">#</th>
                <th>Product Name</th>
                <th class="code">Code</th>
                <th class="qty">Qty</th>
                <th class="price">Price</th>
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 1; @endphp
            @foreach ($details as $detail)
            <tr>
                <td class="row-num">{{$rowIndex}}</td>
                <td>
                    {{$detail['name']}}
                    @if($detail['is_imei'] && $detail['imei_number'] !==null)
                        <br><small style="color: #666;">SN: {{$detail['imei_number']}}</small>
                    @endif
                </td>
                <td class="code">{{$detail['code']}}</td>
                <td class="qty">{{$detail['quantity']}} {{$detail['unitSale']}}</td>
                <td class="price">{{$symbol}} {{$detail['price']}}</td>
                <td class="total">{{$symbol}} {{$detail['total']}}</td>
            </tr>
            @php $rowIndex++; @endphp
            @endforeach
        </tbody>
    </table>
    
    <div class="total-items">Total Items: {{count($details)}}</div>

    <!-- Totals Section -->
    <table class="totals-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="amount">{{$symbol}} {{number_format((float)($sale['GrandTotal'] - $sale['TaxNet'] + $sale['discount'] - $sale['shipping']), 2)}}</td>
        </tr>
        @if($sale['TaxNet'] > 0)
        <tr>
            <td class="label">Tax:</td>
            <td class="amount">{{$symbol}} {{$sale['TaxNet']}}</td>
        </tr>
        @endif
        @if($sale['discount'] > 0)
        <tr>
            <td class="label">Discount:</td>
            <td class="amount">- {{$symbol}} {{$sale['discount']}}</td>
        </tr>
        @endif
        @if($sale['shipping'] > 0)
        <tr>
            <td class="label">Shipping:</td>
            <td class="amount">{{$symbol}} {{$sale['shipping']}}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td class="label">TOTAL:</td>
            <td class="amount">{{$symbol}} {{$sale['GrandTotal']}}</td>
        </tr>
        <tr>
            <td class="label">Paid Amount:</td>
            <td class="amount">{{$symbol}} {{$sale['paid_amount']}}</td>
        </tr>
        <tr>
            <td class="label">Amount Due:</td>
            <td class="amount">{{$symbol}} {{$sale['due']}}</td>
        </tr>
    </table>

    <!-- Signature Section -->
    <table class="signature-section" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%;">
                <div class="signature-box">Customer Signature</div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <div class="signature-box">Seller Signature</div>
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    @if($setting['is_invoice_footer'] && $setting['invoice_footer'] !==null)
    <div style="margin-top: 15px; padding: 10px; border: 1px solid #000; background: #f9f9f9; text-align: center; font-size: 8pt;">
        {{$setting['invoice_footer']}}
    </div>
    @endif
</body>
</html>
