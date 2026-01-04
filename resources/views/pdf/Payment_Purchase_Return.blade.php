<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Return Payment Receipt - {{$payment['Ref']}}</title>
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
                <div style="font-size: 18pt; font-weight: bold; color: #f59e0b; margin-bottom: 6px; letter-spacing: 0.5px;">RETURN PAYMENT</div>
                <div style="display: inline-block; background: #fef3c7; padding: 5px 12px; border-radius: 4px; font-size: 10pt; font-weight: bold; color: #92400e; margin-bottom: 8px;">{{$payment['Ref']}}</div>
                <table style="width: 100%; font-size: 8pt; margin-top: 6px;" cellpadding="3" cellspacing="0">
                    <tr>
                        <td style="text-align: right; color: #6b7280; font-weight: 600;">Date:</td>
                        <td style="text-align: right; color: #1f2937; font-weight: 500;">{{$payment['date']}}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; color: #6b7280; font-weight: 600;">Receipt #:</td>
                        <td style="text-align: right; color: #1f2937; font-weight: 500;">{{$payment['Ref']}}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Divider -->
    <div style="height: 2px; background: #f59e0b; margin: 8px 0 10px 0;"></div>

    <!-- Supplier & Company Info Section -->
    <table style="width: 100%; margin-bottom: 15px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="background: #f59e0b; padding: 5px 10px; border-bottom: 1px solid #d97706;">
                        <div style="color: #ffffff; font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;">RECEIVED FROM</div>
                    </div>
                    <div style="padding: 10px; background: #fffbeb;">
                        <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 5px;">{{$payment['supplier_name']}}</div>
                        <div style="font-size: 7.5pt; color: #6b7280; line-height: 1.6;">
                            <div><strong style="color: #1f2937;">Phone:</strong> {{$payment['supplier_phone']}}</div>
                            <div><strong style="color: #1f2937;">Email:</strong> {{$payment['supplier_email']}}</div>
                            <div><strong style="color: #1f2937;">Address:</strong> {{$payment['supplier_adr']}}</div>
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="background: #f59e0b; padding: 5px 10px; border-bottom: 1px solid #d97706;">
                        <div style="color: #ffffff; font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;">COMPANY INFO</div>
                    </div>
                    <div style="padding: 10px; background: #fffbeb;">
                        <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 5px;">{{$setting['CompanyName']}}</div>
                        <div style="font-size: 7.5pt; color: #6b7280; line-height: 1.6;">
                            <div><strong style="color: #1f2937;">Phone:</strong> {{$setting['CompanyPhone']}}</div>
                            <div><strong style="color: #1f2937;">Email:</strong> {{$setting['email']}}</div>
                            <div><strong style="color: #1f2937;">Address:</strong> {{$setting['CompanyAdress']}}</div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Payment Details -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 1px solid #e5e7eb;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background: #f59e0b;">
                <th style="padding: 10px; text-align: left; font-size: 9pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">RETURN REFERENCE</th>
                <th style="padding: 10px; text-align: left; font-size: 9pt; font-weight: bold; color: #ffffff; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">PAYMENT METHOD</th>
                <th style="padding: 10px; text-align: right; font-size: 9pt; font-weight: bold; color: #ffffff; text-transform: uppercase;">AMOUNT RECEIVED</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background: #fffbeb;">
                <td style="padding: 12px 10px; font-size: 10pt; font-weight: 600; color: #1f2937;">{{$payment['return_Ref']}}</td>
                <td style="padding: 12px 10px; font-size: 10pt; color: #1f2937;">{{$payment['payment_method']}}</td>
                <td style="padding: 12px 10px; text-align: right; font-size: 13pt; font-weight: bold; color: #f59e0b;">{{$symbol}} {{$payment['montant']}}</td>
            </tr>
        </tbody>
    </table>

    <!-- Amount Box -->
    <div style="text-align: center; margin: 25px 0;">
        <div style="display: inline-block; background: #f59e0b; padding: 15px 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="color: rgba(255,255,255,0.9); font-size: 9pt; margin-bottom: 3px;">TOTAL AMOUNT RECEIVED</div>
            <div style="color: #ffffff; font-size: 22pt; font-weight: bold;">{{$symbol}} {{$payment['montant']}}</div>
        </div>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; padding-top: 10px; border-top: 2px solid #e5e7eb;">
        @if($setting['is_invoice_footer'] && $setting['invoice_footer'] !==null)
            <div style="padding: 8px 10px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 3px; margin-bottom: 10px;">
                <p style="font-size: 7.5pt; color: #6b7280; line-height: 1.5; margin: 0;">{{$setting['invoice_footer']}}</p>
            </div>
        @endif
        <div style="text-align: center; padding: 8px 0;">
            <p style="font-size: 10pt; font-weight: bold; color: #f59e0b; margin: 0; letter-spacing: 0.3px;">Payment Received Successfully!</p>
        </div>
    </div>
</body>
</html>
