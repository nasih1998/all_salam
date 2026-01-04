<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation - {{$quote['Ref']}}</title>
    <style>
        @page { 
            size: A4;
            margin: 10mm 15mm; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Arial', sans-serif; 
            font-size: 9pt; 
            color: #1f2937; 
            line-height: 1.4; 
            padding: 15px 20px;
            max-width: 100%;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <table style="width: 100%; margin-bottom: 12px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                @if(!empty($setting['logo']) && file_exists(public_path('images/'.$setting['logo'])))
                    <img src="{{public_path('images/'.$setting['logo'])}}" alt="Logo" style="max-height: 60px; max-width: 180px;">
                @endif
            </td>
            <td style="width: 70%; vertical-align: top; text-align: right;">
                <div style="font-size: 18pt; font-weight: bold; color: #1a56db; margin-bottom: 6px; letter-spacing: 0.5px;">QUOTATION</div>
                <div style="display: inline-block; background: #f3f4f6; padding: 5px 12px; border-radius: 4px; font-size: 10pt; font-weight: bold; color: #4b5563; margin-bottom: 8px;">{{$quote['Ref']}}</div>
                <table style="width: 100%; font-size: 8pt; margin-top: 6px;" cellpadding="3" cellspacing="0">
                    <tr>
                        <td style="text-align: right; color: #6b7280; font-weight: 600;">Date:</td>
                        <td style="text-align: right; color: #1f2937; font-weight: 500;">{{$quote['date']}}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; color: #6b7280; font-weight: 600;">Quote #:</td>
                        <td style="text-align: right; color: #1f2937; font-weight: 500;">{{$quote['Ref']}}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; color: #6b7280; font-weight: 600;">Status:</td>
                        <td style="text-align: right;">
                            @php
                                $statusColors = [
                                    'sent' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                    'pending' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                    'draft' => ['bg' => '#e5e7eb', 'color' => '#374151'],
                                ];
                                $statusKey = strtolower($quote['statut']);
                                $statusStyle = $statusColors[$statusKey] ?? ['bg' => '#dbeafe', 'color' => '#1e40af'];
                            @endphp
                            <span style="background: {{$statusStyle['bg']}}; color: {{$statusStyle['color']}}; padding: 3px 8px; border-radius: 3px; font-size: 7pt; font-weight: bold; text-transform: uppercase;">{{$quote['statut']}}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Divider -->
    <div style="height: 2px; background: #1a56db; margin: 8px 0 10px 0;"></div>

    <!-- Customer & Company Info Section -->
    <table style="width: 100%; margin-bottom: 12px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="background: #1a56db; padding: 5px 10px; border-bottom: 1px solid #3b82f6;">
                        <div style="color: #ffffff; font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;">QUOTE FOR</div>
                    </div>
                    <div style="padding: 8px 10px; background: #f9fafb;">
                        <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 4px;">{{$quote['client_name']}}</div>
                        <div style="font-size: 7.5pt; color: #6b7280; line-height: 1.5;">
                            <div><strong style="color: #1f2937;">Phone:</strong> {{$quote['client_phone']}}</div>
                            <div><strong style="color: #1f2937;">Email:</strong> {{$quote['client_email']}}</div>
                            <div><strong style="color: #1f2937;">Address:</strong> {{$quote['client_adr']}}</div>
                            @if($quote['client_tax'])
                                <div><strong style="color: #1f2937;">Tax #:</strong> {{$quote['client_tax']}}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="background: #1a56db; padding: 5px 10px; border-bottom: 1px solid #3b82f6;">
                        <div style="color: #ffffff; font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;">FROM</div>
                    </div>
                    <div style="padding: 8px 10px; background: #f9fafb;">
                        <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 4px;">{{$setting['CompanyName']}}</div>
                        <div style="font-size: 7.5pt; color: #6b7280; line-height: 1.5;">
                            <div><strong style="color: #1f2937;">Phone:</strong> {{$setting['CompanyPhone']}}</div>
                            <div><strong style="color: #1f2937;">Email:</strong> {{$setting['email']}}</div>
                            <div><strong style="color: #1f2937;">Address:</strong> {{$setting['CompanyAdress']}}</div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #e5e7eb;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background: #1a56db;">
                <th style="padding: 6px 5px; text-align: left; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">PRODUCT</th>
                <th style="padding: 6px 5px; text-align: right; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">PRICE</th>
                <th style="padding: 6px 5px; text-align: right; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">QTY</th>
                <th style="padding: 6px 5px; text-align: right; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">DISC</th>
                <th style="padding: 6px 5px; text-align: right; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">TAX</th>
                <th style="padding: 6px 5px; text-align: right; font-size: 8pt; font-weight: bold; color: #ffffff; text-transform: uppercase;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @foreach ($details as $detail)
            <tr style="border-bottom: 1px solid #e5e7eb; background: {{$rowIndex % 2 == 0 ? '#ffffff' : '#f9fafb'}};">
                <td style="padding: 5px; vertical-align: top;">
                    <div style="font-weight: 600; font-size: 8.5pt; color: #1f2937; margin-bottom: 1px;">{{$detail['name']}}</div>
                    <div style="font-size: 7pt; color: #6b7280;">Code: {{$detail['code']}}</div>
                    @if($detail['is_imei'] && $detail['imei_number'] !==null)
                        <div style="font-size: 7pt; color: #3b82f6; margin-top: 1px;">SN: {{$detail['imei_number']}}</div>
                    @endif
                </td>
                <td style="padding: 5px; text-align: right; font-size: 8.5pt; color: #1f2937;">{{$detail['price']}}</td>
                <td style="padding: 5px; text-align: right; font-size: 8.5pt; color: #1f2937;">{{$detail['quantity']}} {{$detail['unitSale']}}</td>
                <td style="padding: 5px; text-align: right; font-size: 8.5pt; color: #ef4444;">{{$detail['DiscountNet']}}</td>
                <td style="padding: 5px; text-align: right; font-size: 8.5pt; color: #1f2937;">{{$detail['taxe']}}</td>
                <td style="padding: 5px; text-align: right; font-size: 9pt; font-weight: bold; color: #1a56db;">{{$detail['total']}}</td>
            </tr>
            @php $rowIndex++; @endphp
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section -->
    <table style="width: 100%; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 58%;"></td>
            <td style="width: 42%; vertical-align: top;">
                <table style="width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 5px 10px; font-size: 8pt; font-weight: 600; color: #6b7280;">Subtotal:</td>
                        <td style="padding: 5px 10px; text-align: right; font-size: 8.5pt; font-weight: 600; color: #1f2937;">{{$symbol}} {{number_format((float)($quote['GrandTotal'] - $quote['TaxNet'] + $quote['discount'] - $quote['shipping']), 2)}}</td>
                    </tr>
                    <tr style="background: #ffffff; border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 5px 10px; font-size: 8pt; font-weight: 600; color: #6b7280;">Order Tax:</td>
                        <td style="padding: 5px 10px; text-align: right; font-size: 8.5pt; font-weight: 600; color: #1f2937;">{{$symbol}} {{$quote['TaxNet']}}</td>
                    </tr>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 5px 10px; font-size: 8pt; font-weight: 600; color: #6b7280;">Discount:</td>
                        <td style="padding: 5px 10px; text-align: right; font-size: 8.5pt; font-weight: 600; color: #ef4444;">- {{$symbol}} {{$quote['discount']}}</td>
                    </tr>
                    <tr style="background: #ffffff; border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 5px 10px; font-size: 8pt; font-weight: 600; color: #6b7280;">Shipping:</td>
                        <td style="padding: 5px 10px; text-align: right; font-size: 8.5pt; font-weight: 600; color: #1f2937;">{{$symbol}} {{$quote['shipping']}}</td>
                    </tr>
                    <tr style="background: #1a56db;">
                        <td style="padding: 8px 10px; font-size: 10pt; font-weight: bold; color: #ffffff;">TOTAL:</td>
                        <td style="padding: 8px 10px; text-align: right; font-size: 11pt; font-weight: bold; color: #ffffff;">{{$symbol}} {{$quote['GrandTotal']}}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div style="margin-top: 15px; padding-top: 10px; border-top: 2px solid #e5e7eb;">
        @if($setting['is_invoice_footer'] && $setting['invoice_footer'] !==null)
            <div style="padding: 8px 10px; background: #f9fafb; border-left: 3px solid #1a56db; border-radius: 3px; margin-bottom: 10px;">
                <p style="font-size: 7.5pt; color: #6b7280; line-height: 1.5; margin: 0;">{{$setting['invoice_footer']}}</p>
            </div>
        @endif
        <div style="text-align: center; padding: 8px 0;">
            <p style="font-size: 10pt; font-weight: bold; color: #1a56db; margin: 0; letter-spacing: 0.3px;">Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
