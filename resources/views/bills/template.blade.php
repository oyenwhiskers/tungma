<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bill {{ $bill->bill_code }}</title>
    <style>
        @page {
            margin: 25px; /* Reduced from 40px to pull content up */
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

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', 'Noto Sans SC', sans-serif;
            font-size: 8px; /* Shrunk further to 8px */
            color: #000;
            padding: 0;
            margin: 0;
        }

        .bill-container {
            width: 98%;
            margin: 0 auto;
            border: 1.5px solid #000;
            position: relative;
            padding: 2px;
            z-index: 10;
        }

        .cutting-line {
            position: absolute;
            top: 148.5mm; /* Restore to center of A4 */
            left: -25px;  /* Offset new 25px margin */
            right: -25px;
            border-top: 1px dashed #888;
            height: 1px;
            z-index: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            border: 1px solid #000;
        }

        /* Header Specifics */
        .company-info {
            padding: 10px;
            border: none;
            width: 40%;
            vertical-align: top;
        }

        .logo-section {
            padding: 5px;
            text-align: center;
            border: none;
            width: 30%;
            vertical-align: middle;
        }

        .payment-section {
            padding: 10px;
            text-align: center;
            border: none;
            width: 30%;
            vertical-align: middle;
        }

        .company-name {
            font-size: 18px;
            font-weight: 900;
        }

        .sst-no {
            font-size: 10px;
            margin-top: 2px;
        }

        .cash-sales-badge {
            background: #1a1a1a;
            color: #fff;
            padding: 8px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
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
        }

        .box {
            width: 18px;
            height: 18px;
            border: 2px solid #000;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
            text-align: center;
            line-height: 14px;
            font-weight: bold;
            font-size: 16px;
        }

        .box.checked {
            background: #000;
            color: #fff;
        }

        /* Route & Details Section */
        .label-cell {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            padding: 6px;
            text-transform: uppercase;
        }

        .cs-no {
            font-size: 14px;
            color: #000;
        }

        .cs-val {
            color: #d00;
            font-size: 18px;
        }

        .data-content {
            padding: 3px;
        }

        .field-label {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .field-value {
            font-size: 13px; /* Slightly smaller */
            font-weight: bold;
            margin-bottom: 2px; /* Reduced from 12px */
        }

        /* Main Content */
        .desc-cell {
            padding: 10px;
            width: 37%;
        }

        .total-cell {
            padding: 10px;
            width: 33%;
            text-align: center;
        }

        .qr-cell {
            padding: 10px;
            width: 30%;
            text-align: center;
        }

        .total-amount {
            font-size: 20px; /* Shrunk total font */
            font-weight: bold;
            margin: 5px 0;
        }

        .description-item {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Footer */
        .black-footer {
            background: #000;
            color: #fff;
            padding: 8px 12px;
            font-size: 8px;
            display: flex;
            justify-content: space-between;
        }

        .footer-disclaimer {
            width: 45%;
            line-height: 1.2;
        }

        .policy-disclaimer-section {
            padding: 10px 15px;
            font-size: 9px;
            line-height: 1.4;
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
            line-height: 1.4;
        }

        .copy-labels {
            text-align: center;
            padding: 5px;
            font-size: 10px;
            font-weight: bold;
            word-spacing: 15px;
        }

        .copy-label {
            text-align: center;
            padding: 8px;
            font-size: 14px;
            font-weight: bold;
            background: #000;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
</head>

<body>

    <div class="cutting-line"></div>

    <div class="bill-container">
        @php
            $logoPath = public_path('images/logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }
        @endphp
        <!-- <div class="copy-label">CUSTOMER COPY</div> -->
        <table style="border: none; border-bottom: 2px solid #000;">
            <tr>
                <td class="company-info">
                    <div class="company-name">{{ $bill->company->name }} <span
                            style="font-size: 10px;">({{ $bill->company->registration_number }})</span></div>
                    <div style="font-size: 10px; line-height: 1.4;">
                        @php
                            $addressWords = preg_split('/\s+/', $bill->company->address ?? '');
                            $firstLine = implode(' ', array_slice($addressWords, 0, 5));
                            $secondLine = implode(' ', array_slice($addressWords, 5, 5));
                            $thirdLine = implode(' ', array_slice($addressWords, 10, 5));
                            $remainingLine = implode(' ', array_slice($addressWords, 15, 5));
                        @endphp
                        {{ $firstLine }}<br>
                        {{ $secondLine }}<br>
                        {{ $thirdLine }}<br>
                        {{ $remainingLine }}
                        SDK COURIER TEL: 089-228904<br>
                        K.K. TEL: 012-8832277, 016-5839239<br>
                        SST NO: {{ $bill->company->sst_number }}<br>
                        Trace your parcel at: <br>
                        <strong>tracking.tungmaexpress.com.my</strong>
                    </div>
                </td>
                <td class="logo-section">
                    <img src="data:image/png;base64,{{ $logoBase64 }}"
                        style="width: 80px; margin-bottom: 5px; margin-left: -100px;">
                    <div style="font-size: 18px; font-weight: bold; letter-spacing: 4px; margin-left: -100px;">東 馬 快 車
                    </div>
                    <div style="font-size: 14px; font-weight: bold; margin-left: -100px;">TUNG MA EXPRESS</div>
                </td>
                <td class="payment-section">
                    <div class="cash-sales-badge">CASH SALES</div>
                    <table class="cb-table">
                        <tr>
                            <td>
                                <div class="box {{ ($paymentDetails['method'] ?? '') == 'cash' ? 'checked' : '' }}">
                                    @if(($paymentDetails['method'] ?? '') == 'cash')/@endif</div> CASH
                            </td>
                            <td>
                                <div
                                    class="box {{ in_array($paymentDetails['method'] ?? '', ['qr', 'e_wallet']) ? 'checked' : '' }}">
                                    @if(in_array($paymentDetails['method'] ?? '', ['qr', 'e_wallet']))/@endif</div> QR
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="box {{ ($paymentDetails['method'] ?? '') == 'cod' ? 'checked' : '' }}">
                                    @if(($paymentDetails['method'] ?? '') == 'cod')/@endif</div> C.O.D
                            </td>
                            <td>
                                <div class="box {{ ($paymentDetails['method'] ?? '') == 'bank_transfer' ? 'checked' : '' }}">
                                    @if(($paymentDetails['method'] ?? '') == 'bank_transfer')/@endif</div> A/C
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="label-cell" style="width: 37%;">FROM {{ strtoupper($bill->fromCompany->name ?? 'N/A') }}</td>
                <td class="label-cell" style="width: 33%; font-size: 10px;">TO {{ strtoupper($bill->toCompany->address ?? 'N/A') }}</td>
                <td class="label-cell" style="width: 30%; text-align: left;">
                    <span class="cs-no">CS No. D:</span> <span class="cs-val">{{ $bill->bill_code }}</span><br>
                    <span style="font-size: 10px; font-weight: normal; text-transform: none;">Created by: {{ $bill->creator->name ?? 'System' }}</span>
                </td>
            </tr>
            <tr>
                <td class="data-content">
                    <div class="field-label">Sender Name:</div>
                    <div class="field-value">{{ strtoupper($bill->sender_name ?? '') }}</div>
                    <div class="field-label">Contact No:</div>
                    <div class="field-value">{{ $bill->sender_phone ?? '' }}</div>
                </td>
                <td class="data-content">
                    <div class="field-label">Receiver Name:</div>
                    <div class="field-value">{{ strtoupper($bill->receiver_name ?? '') }}</div>
                    <div class="field-label">Contact No:</div>
                    <div class="field-value">{{ $bill->receiver_phone ?? '' }}</div>
                </td>
                <td class="data-content">
                    <div class="field-label">DATE: {{ $bill->date ? $bill->date->format('d/m/Y') : '16/12/2025' }}</div>
                    <div class="field-label">TIME FROM SDK: {{ $bill->busDeparture->departure_time ?? 'N/A' }}</div>
                    <div style="font-size: 8px; font-weight: bold;">
                        ESTIMATED TO ARRIVE IN 7 HOURS<br>
                        <span style="font-weight: normal;">(BUSINESS HOUR: 7am - 8pm)</span>
                    </div>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="desc-cell">
                    <div class="field-label" style="margin-bottom: 8px;">Description:</div>
                    <div class="description-item">
                        @php
                            $descArr = json_decode($bill->description, true);
                        @endphp
                        @if(is_array($descArr))
                            @foreach($descArr as $item)
                                {{ $item['product'] ?? '' }} x{{ $item['quantity'] ?? '' }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            {{ $bill->description }}
                        @endif
                    </div>
                </td>
                <td class="total-cell">
                    <div style="font-weight: bold; font-size: 14px;">TOTAL RM</div>
                    <div class="total-amount">{{ number_format($bill->amount, 2) }}</div>
                    <div style="font-size: 9px; font-weight: bold;">6% SST EXCLUDED IN TOTAL</div>
                </td>
                <td class="qr-cell">
                    @php
                        // Generate QR code with URL for E-Invoice using Endroid QR Code
                        // Data sent to frontend via query parameters
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
                            // Fallback to SVG if PNG fails
                            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                                ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                                ->data($qrData)
                                ->size(120)
                                ->margin(2)
                                ->build();
                            $qrCodeBase64 = $qrCode->getDataUri();
                        }
                    @endphp
                    <img src="{{ $qrCodeBase64 }}" style="width: 80px; display: block; margin: 0 auto;">
                    <div style="font-size: 7px; font-weight: bold; margin-top: 2px;">
                        Scan for E-Invoice
                    </div>
                </td>
            </tr>
        </table>

        <table class="policy-disclaimer-section" style="width: 100%; border: none;">
            <tr>
                <td class="policy-left" style="border: none;">
                    <div class="policy-box">
                        @php
                            $policySnapshot = $bill->policy_snapshot;
                            if (is_string($policySnapshot)) {
                                $policySnapshot = json_decode($policySnapshot, true);
                            }
                        @endphp
                        {{ $policySnapshot['description'] ?? '' }}
                    </div>
                </td>
                <td class="policy-right" style="border: none;">
                    Kiriman barang / surat mesti dituntut dalam masa 1 bulan lepas itu tidak ditanggung. Pihak Syarikat
                    tidak bertanggungjawab terhadap hilang wang / duit dalam kiriman sampul surat.
                </td>
            </tr>
        </table>
    </div>
</body>

</html>