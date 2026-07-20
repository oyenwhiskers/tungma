@if(!isset($omitLayout) || !$omitLayout)
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Bill {{ $bill->bill_code }}</title>
@endif
    <style>
        @page {
            margin: 10px;
        }

        @font-face {
            font-family: 'Noto Sans SC';
            src: url('{{ $isPdf ? public_path('fonts/static/NotoSansSC-Regular.ttf') : asset('fonts/static/NotoSansSC-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Noto Sans SC';
            src: url('{{ $isPdf ? public_path('fonts/static/NotoSansSC-Bold.ttf') : asset('fonts/static/NotoSansSC-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Noto Sans SC', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #000;
        }

        .bill-container {
            width: 95%;
            margin: 3% auto;
            min-height: 125mm;
            height: auto;
            border-top: 1.5px solid #000;
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            border-left: 1.5px solid #000;
            position: relative;
            padding: 4px 0;
            box-sizing: border-box;
        }

        .cutting-line {
            position: absolute;
            top: 138mm;
            left: -25px;
            right: -25px;
            border-top: 1px dashed #888;
            height: 1px;
            z-index: 1;
        }

        table {
            width: 98%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            vertical-align: top;
            border: 1px solid #000;
        }

        /* Header Specifics */
        .company-info {
            padding: 0 5px;
            border: none;
            width: 35%;
            vertical-align: top;
        }

        .logo-section {
            padding: 0 5px;
            text-align: center;
            border: none;
            width: 30%;
            vertical-align: top;
        }

        .payment-section {
            padding: 0 5px;
            text-align: center;
            border: none;
            width: 35%;
            vertical-align: top;
        }

        .company-name {
            font-size: 15px;
            font-weight: 900;
        }

        .sst-no {
            font-size: 9.5px;
            margin-top: 1px;
        }

        .cash-sales-badge {
            background: #1a1a1a;
            color: #fff;
            padding: 4px 14px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 2px;
        }

        /* Checkbox Styling */
        .cb-table {
            border: none;
            margin: 0 auto;
            width: auto;
        }

        .cb-table td {
            border: none;
            padding: 4px 8px;
            text-align: left;
            vertical-align: middle;
            font-size: 10px;
        }

        .box {
            width: 14px;
            height: 14px;
            border: 1.5px solid #000;
            display: inline-block;
            margin-right: 4px;
            vertical-align: middle;
            text-align: center;
            line-height: 11px;
            font-weight: bold;
            font-size: 12px;
        }

        .box.checked {
            background: #000;
            color: #fff;
        }

        /* Route & Details Section */
        .label-cell {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 11.5px;
            padding: 2px 3px;
            text-transform: uppercase;
        }

        .cs-no {
            font-size: 13px;
            color: #000;
        }

        .cs-val {
            color: #000;
            font-size: 17px;
        }

        .data-content {
            padding: 2px 5px;
        }

        .field-label {
            font-size: 9px;
            margin-bottom: 0;
        }

        .field-value {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 0;
        }

        /* Main Content */
        .desc-cell {
            padding: 4px;
            width: 37%;
        }

        .total-cell {
            padding: 4px;
            width: 33%;
            text-align: center;
        }

        .qr-cell {
            padding: 5px;
            width: 30%;
            text-align: center;
        }

        .total-amount {
            font-size: 22px;
            font-weight: bold;
            margin: 5px 0;
        }

        .description-item {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
            max-height: 45mm;
            overflow: hidden;
        }

        /* Footer */
        .black-footer {
            background: #000;
            color: #fff;
            padding: 4px 12px;
            font-size: 8.5px;
            display: flex;
            justify-content: space-between;
        }

        .footer-disclaimer {
            width: 45%;
            line-height: 1.2;
        }

        .policy-disclaimer-section {
            padding: 4px 15px;
            font-size: 9px;
            line-height: 1.2;
        }

        .policy-left {
            width: 45%;
            vertical-align: top;
        }

        .policy-box {
            background: #000;
            color: #fff;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 8px;
            line-height: 1.2;
        }

        .policy-right {
            width: 50%;
            text-align: left;
            vertical-align: top;
            padding-left: 15px;
            font-size: 9px;
            line-height: 1.2;
        }

        .copy-labels {
            text-align: center;
            padding: 5px;
            font-size: 11px;
            font-weight: bold;
            word-spacing: 15px;
        }

        .copy-label {
            text-align: center;
            padding: 8px;
            font-size: 15px;
            font-weight: bold;
            background: #000;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
    @if(!isset($omitLayout) || !$omitLayout)
        </head>

        <body>
    @endif

    <div class="cutting-line"></div>

    <div class="bill-container">
        <table style="border: none; border-bottom: 2px solid #000;">
            <tr>
                <td class="company-info">
                    <div class="company-name">{{ $bill->company->name }} <span
                            style="font-size: 10px;">({{ $bill->company->registration_number }})</span></div>
                    <div style="font-size: 8px; line-height: 1.0;">
                        @php
                            $addressWords = preg_split('/\s+/', $bill->company->address ?? '');
                            $firstLine = implode(' ', array_slice($addressWords, 0, 5));
                            $secondLine = implode(' ', array_slice($addressWords, 5, 5));
                            $thirdLine = implode(' ', array_slice($addressWords, 10, 5));
                            $remainingLine = implode(' ', array_slice($addressWords, 15, 5));
                        @endphp
                        {{ $firstLine }} {{ $secondLine }} {{ $thirdLine }} {{ $remainingLine }}<br>
                        SDK COURIER TEL: 089-228904 | K.K. TEL: 012-8832277, 016-5839239<br>
                        SST NO: {{ $bill->company->sst_number }}<br>
                        Trace your parcel at: <strong>tracking.tungmaexpress.com.my</strong>
                    </div>
                </td>
                <td class="logo-section">
                    @php
                        $logoPath = public_path('images/logo-monotone.png');
                        if (!file_exists($logoPath)) {
                            $logoPath = public_path('images/logo.png');
                        }
                        $logoBase64 = '';
                        if (file_exists($logoPath)) {
                            $logoBase64 = base64_encode(file_get_contents($logoPath));
                        }
                    @endphp
                    <img src="data:image/png;base64,{{ $logoBase64 }}" style="width: 85px; margin-top: -10px; margin-bottom: 2px;">
                    <div style="font-size: 18px; font-weight: bold; letter-spacing: 2px;">東 馬 快 車
                    </div>
                    <div style="font-size: 10px; font-weight: bold;">TUNG MA EXPRESS</div>
                </td>
                <td class="payment-section">
                    <div class="cash-sales-badge">CASH SALES</div>
                    <table class="cb-table">
                        <tr>
                            <td>
                                <div class="box {{ ($paymentDetails['method'] ?? '') == 'cash' ? 'checked' : '' }}">
                                    @if(($paymentDetails['method'] ?? '') == 'cash')/@endif
                                </div> CASH
                            </td>
                            <td>
                                <div
                                    class="box {{ in_array($paymentDetails['method'] ?? '', ['qr', 'e_wallet', 'qr_pay']) ? 'checked' : '' }}">
                                    @if(in_array($paymentDetails['method'] ?? '', ['qr', 'e_wallet', 'qr_pay']))/@endif
                                </div> QR
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="box {{ ($paymentDetails['method'] ?? '') == 'cod' ? 'checked' : '' }}">
                                    @if(($paymentDetails['method'] ?? '') == 'cod')/@endif
                                </div> C.O.D
                            </td>
                            <td>
                                <div
                                    class="box {{ in_array($paymentDetails['method'] ?? '', ['bank_transfer', 'bank']) ? 'checked' : '' }}">
                                    @if(in_array($paymentDetails['method'] ?? '', ['bank_transfer', 'bank']))/@endif
                                </div> A/C
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="label-cell" style="width: 37%;">FROM
                    {{ strtoupper($bill->fromCompany->based_in ?? 'N/A') }}
                </td>
                <td class="label-cell" style="width: 33%;">TO
                    {{ strtoupper($bill->toCompany->based_in ?? 'N/A') }}
                </td>
                <td class="label-cell" style="width: 30%;">
                    <span class="cs-no">CS No. D:</span> <span class="cs-val">{{ $bill->bill_code }}</span>
                </td>
            </tr>
            <tr>
                <td class="data-content">
                    <div class="field-label">Sender Name:</div>
                    <div class="field-value" style="margin-bottom: 2px;">{{ strtoupper($bill->sender_name ?? '') }}</div>
                    <div class="field-label">Contact No:</div>
                    <div class="field-value">{{ $bill->sender_phone ?? '' }}</div>
                </td>
                <td class="data-content">
                    <div class="field-label">Receiver Name:</div>
                    <div class="field-value" style="margin-bottom: 2px;">{{ strtoupper($bill->receiver_name ?? '') }}</div>
                    <div class="field-label">Contact No:</div>
                    <div class="field-value">{{ $bill->receiver_phone ?? '' }}</div>
                </td>
                <td class="data-content" style="text-align: center; vertical-align: middle; padding: 4px 2px;">
                    <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">DATE: {{ $bill->date ? $bill->date->format('d/m/Y') : '16/12/2025' }}</div>
                    <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">TIME FROM SDK: {{ $bill->busDeparture->departure_time ?? '7:00 AM' }}</div>
                    <div style="font-size: 9px; font-weight: bold; line-height: 1.25;">
                        ESTIMATED TO ARRIVE IN 7 HOURS
                        <span style="font-weight: normal; font-size: 7.5px; display: block; margin-top: 1px;">(BUSINESS HOUR: 7.00am - 7.30pm)</span>
                    </div>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td colspan="2" style="padding: 0 15px 0 10px; width: 70%; vertical-align: top;">
                    <div style="min-height: 38mm; height: auto; overflow: visible; display: flex; flex-direction: column;">
                        <div style="flex-grow: 1;">
                            <table style="width: 100%; border: none; font-size: 11.5px; margin-bottom: 2px; line-height: 1.1;">
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; width: 10%;">Item</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; width: 30%;">Description</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; width: 10%;">Qty</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; text-align: right; width: 15%;">U/price</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; text-align: right; width: 15%;">Total Tax</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 0 0 2px 0; font-weight: bold; text-align: right; width: 20%;">Total (RM)</td>
                        </tr>
                        @php
                            $descArr = json_decode($bill->description, true);
                            $total = $bill->amount;
                        @endphp
                        @if(is_array($descArr))
                            @foreach($descArr as $index => $item)
                                @php 
                                    $itemPriceIncl = $item['price'] ?? 0;
                                    $itemTotalIncl = ($item['quantity'] ?? 0) * $itemPriceIncl;
                                    $itemTotalExcl = round($itemTotalIncl / 1.06, 2);
                                    $itemTax = $itemTotalIncl - $itemTotalExcl;
                                    $itemPriceExcl = round($itemPriceIncl / 1.06, 2);
                                @endphp
                                <tr>
                                    <td style="border: none; padding: 1px 0;">{{ $index + 1 }}</td>
                                    <td style="border: none; padding: 1px 0; font-weight: bold;">{{ $item['product'] ?? '' }}</td>
                                    <td style="border: none; padding: 1px 0;">{{ $item['quantity'] ?? '' }}</td>
                                    <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($itemPriceIncl, 2) }}</td>
                                    <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($itemTax, 2) }}</td>
                                    <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($itemTotalIncl, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            @php
                                $excludingTax = round($total / 1.06, 2);
                                $taxAmount = $total - $excludingTax;
                            @endphp
                            <tr>
                                <td style="border: none; padding: 1px 0;">1</td>
                                <td style="border: none; padding: 1px 0; font-weight: bold;">{{ $bill->description }}</td>
                                <td style="border: none; padding: 1px 0;">1</td>
                                <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($total, 2) }}</td>
                                <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($taxAmount, 2) }}</td>
                                <td style="border: none; padding: 1px 0; text-align: right;">{{ number_format($total, 2) }}</td>
                            </tr>
                        @endif
                    </table>
                        </div>
                        <div style="text-align: right;">
                    <table align="right" style="margin-left: auto; margin-right: 0; width: auto; border-collapse: separate; border-spacing: 0 3px; font-size: 10.5px; font-weight: bold; margin-top: 1px; line-height: 1.1;">
                        @php
                            $excludingTax = round($total / 1.06, 2);
                            $taxAmount = $total - $excludingTax;
                        @endphp
                        <tr>
                            <td style="border: none; text-align: right; padding: 2px 8px 2px 0; vertical-align: middle; white-space: nowrap;">Sub Total (RM)</td>
                            <td style="border: 1px solid #000; text-align: right; padding: 2px 8px; width: 85px; vertical-align: middle;">{{ number_format($excludingTax, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; text-align: right; padding: 2px 8px 2px 0; vertical-align: middle; white-space: nowrap;">Inclusive Service Tax @ 6% on (RM {{ number_format($excludingTax, 2) }})</td>
                            <td style="border: 1px solid #000; text-align: right; padding: 2px 8px; width: 85px; vertical-align: middle;">{{ number_format($taxAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; text-align: right; padding: 2px 8px 2px 0; vertical-align: middle; white-space: nowrap;">Final Total (RM)</td>
                            <td style="border: 1px solid #000; text-align: right; padding: 2px 8px; width: 85px; vertical-align: middle;">{{ number_format($total, 2) }}</td>
                        </tr>
                    </table>
                        </div>
                    </div>
                </td>
                <td class="qr-cell" style="vertical-align: middle;">
                    @php
                        // Generate QR code with URL for E-Invoice using Endroid QR Code
                        $qrData = url('https://einvoice.tungmaexpress.com.my') . '?' . http_build_query([
                            'bill_id' => $bill->id,
                            'bill_code' => $bill->bill_code,
                            'date' => optional($bill->date)->format('Y-m-d'),
                            'amount' => $bill->amount,
                        ]);

                        try {
                            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                                ->writer(new \Endroid\QrCode\Writer\PngWriter())
                                ->data($qrData)
                                ->size(120)
                                ->margin(2)
                                ->build();
                            $qrCodeBase64 = $qrCode->getDataUri();
                        } catch (\Exception $e) {
                            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                                ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                                ->data($qrData)
                                ->size(120)
                                ->margin(2)
                                ->build();
                            $qrCodeBase64 = $qrCode->getDataUri();
                        }
                    @endphp
                    <img src="{{ $qrCodeBase64 }}" style="width: 100px; display: block; margin: 0 auto; margin-top: 5px;">
                    <div style="font-size: 6px; font-weight: bold; margin-top: 1px;">
                        Scan for E-Invoice
                    </div>
                    <div style="font-size: 7px; font-weight: bold; margin-top: 3px; text-transform: uppercase;">Submit within 3 days</div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="border: none; padding: 0;">
                    <table class="policy-disclaimer-section" style="width: 100%; border: none; margin-top: 1px;">
                        <tr>
                            <td class="policy-left" style="border: none; padding: 0 5px; width: 50%; vertical-align: top;">
                                <div class="policy-box" style="font-size: 7px; line-height: 1.1;">
                                    @php
                                        $policySnapshot = $bill->policy_snapshot;
                                        if (is_string($policySnapshot)) {
                                            $policySnapshot = json_decode($policySnapshot, true);
                                        }
                                    @endphp
                                    {{ $policySnapshot['description'] ?? '' }}
                                </div>
                            </td>
                            <td class="policy-right" style="border: none; padding: 0 5px; font-size: 7px; line-height: 1.1; width: 50%; vertical-align: top;">
                                <div>
                                    Kiriman barang / surat mesti dituntut dalam masa 1 bulan lepas itu tidak ditanggung.
                                    Pihak Syarikat
                                    tidak bertanggungjawab terhadap hilang wang / duit dalam kiriman sampul surat.
                                </div>
                                <div style="text-align: right; margin-top: 4px; font-size: 8px; font-weight: normal; color: #333;">
                                    Created by: {{ $bill->creator->name ?? 'System' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
@if(!isset($omitLayout) || !$omitLayout)
        </body>

        </html>
    @endif