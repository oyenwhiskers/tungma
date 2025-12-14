<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $bill->bill_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        .bill-container {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            border: 3px solid #000;
        }

        .bill-header {
            border-bottom: 3px solid #000;
            padding: 12px 15px;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: flex-start;
        }

        .company-info {
            font-size: 10px;
            line-height: 1.5;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header-center {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .bill-type-box {
            background: #000;
            color: white;
            padding: 6px 25px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            white-space: nowrap;
        }

        .header-subtitle {
            font-size: 9px;
            font-weight: normal;
        }

        .header-right {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .company-chinese {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }

        .bill-number-box {
            border: 2px solid #000;
            padding: 5px 12px;
            text-align: center;
        }

        .bill-number-label {
            font-size: 10px;
            font-weight: bold;
        }

        .bill-number {
            font-size: 22px;
            font-weight: bold;
            color: red;
            letter-spacing: 1px;
        }

        .route-section {
            display: grid;
            grid-template-columns: 1fr 1fr 280px;
            border-bottom: 3px solid #000;
        }

        .route-col {
            padding: 12px;
            border-right: 3px solid #000;
        }

        .route-col:last-child {
            border-right: none;
        }

        .route-label {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .company-box {
            border: 1px solid #000;
            padding: 8px;
            min-height: 70px;
            margin-bottom: 12px;
            font-size: 11px;
        }

        .field-label {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .field-value {
            font-size: 11px;
            min-height: 16px;
        }

        .checkbox-row {
            display: flex;
            gap: 30px;
            margin-bottom: 15px;
            justify-content: center;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid #000;
        }

        .checkbox.checked {
            background: #000;
            position: relative;
        }

        .checkbox.checked::after {
            content: '✓';
            color: white;
            font-size: 14px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .checkbox-label {
            font-size: 11px;
            font-weight: bold;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .info-value {
            border-bottom: 1px solid #000;
            min-height: 18px;
            font-size: 10px;
            padding: 2px 0;
        }

        .estimated-text {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }

        .business-hours {
            font-size: 8px;
            text-align: center;
            margin-top: 3px;
        }

        .main-section {
            display: grid;
            grid-template-columns: 1fr 200px 280px;
            border-bottom: 3px solid #000;
        }

        .description-col {
            padding: 12px;
            border-right: 3px solid #000;
        }

        .description-col:last-child {
            border-right: none;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .description-table {
            width: 100%;
            border-collapse: collapse;
        }

        .description-table td {
            padding: 4px 8px;
            font-size: 10px;
            border: 1px solid #ddd;
        }

        .description-table td:first-child {
            width: 50%;
        }

        .total-amount {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
        }

        .sst-note {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
        }

        .consignee-col {
            padding: 12px;
        }

        .consignee-field {
            margin-bottom: 12px;
        }

        .consignee-field .info-value {
            min-height: 20px;
        }

        .sign-field {
            min-height: 50px !important;
        }

        .footer-note {
            background: #000;
            color: white;
            padding: 10px 15px;
            font-size: 8.5px;
            line-height: 1.6;
        }

        .copy-labels {
            text-align: center;
            padding: 8px;
            font-size: 9px;
            font-weight: bold;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .bill-container {
                max-width: 100%;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Print Bill</button>

    <div class="bill-container">
        <!-- Header -->
        <div class="bill-header">
            <div class="company-info">
                <div class="company-name">{{ $bill->company->name ?? 'COMPANY NAME' }}</div>
                @if($bill->company)
                    <div>{{ $bill->company->address ?? '' }}</div>
                    @if($bill->company->contact_number)<div>TEL: {{ $bill->company->contact_number }}</div>@endif
                    @if($bill->company->email)<div>EMAIL: {{ $bill->company->email }}</div>@endif
                @endif
            </div>

            <div class="header-center">
                @php
                    $paymentMethod = $paymentDetails['method'] ?? 'cash';
                    $methodLabels = [
                        'cash' => 'CASH SALES',
                        'bank_transfer' => 'BANK TRANSFER',
                        'e_wallet_qr' => 'E-WALLET/QR',
                        'e_wallet' => 'E-WALLET',
                        'credit_card' => 'CREDIT CARD',
                        'cod' => 'C.O.D.'
                    ];
                    $displayMethod = $methodLabels[$paymentMethod] ?? strtoupper(str_replace('_', ' ', $paymentMethod));
                @endphp
                <div class="bill-type-box">{{ $displayMethod }}</div>
                <div class="header-subtitle">全貨時請帶 I/C 或証件<br>Sila membawa I/C / Passport semasa mengambil Barangan.</div>
            </div>

            <div class="header-right">
                <div class="company-chinese">東 馬 快 車<br>TUNG MA EXPRESS</div>
                <div class="bill-number-box">
                    <div class="bill-number-label">CS No. D</div>
                    <div class="bill-number">{{ $bill->bill_code }}</div>
                </div>
            </div>
        </div>

        <!-- Route Section -->
        <div class="route-section">
            <!-- FROM Column -->
            <div class="route-col">
                <div class="route-label">FROM {{ strtoupper($bill->fromCompany->name ?? $bill->company->name ?? 'ORIGIN') }}</div>
                <div class="field-label">COMPANY NAME :</div>
                <div class="company-box">
                    {{ $bill->sender_name ?? '' }}
                </div>
                <div class="field-label">CONTACT NO. :</div>
                <div class="field-value">{{ $bill->sender_phone ?? '' }}</div>
            </div>

            <!-- TO Column -->
            <div class="route-col">
                <div class="route-label">TO {{ strtoupper($bill->toCompany->name ?? 'DESTINATION') }}</div>
                <div class="field-label">COMPANY NAME :</div>
                <div class="company-box">
                    {{ $bill->receiver_name ?? '' }}
                </div>
                <div class="field-label">CONTACT NO. :</div>
                <div class="field-value">{{ $bill->receiver_phone ?? '' }}</div>
            </div>

            <!-- Right Info Column -->
            <div class="route-col">
                <div class="checkbox-row">
                    <div class="checkbox-item">
                        <div class="checkbox {{ ($paymentDetails['method'] ?? '') == 'cash' ? 'checked' : '' }}"></div>
                        <span class="checkbox-label">CASH</span>
                    </div>
                    <div class="checkbox-item">
                        <div class="checkbox {{ ($paymentDetails['method'] ?? '') == 'cod' ? 'checked' : '' }}"></div>
                        <span class="checkbox-label">C.O.D.</span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">DATE :</div>
                    <div class="info-value">{{ $bill->date ? $bill->date->format('d/m/Y') : '' }}</div>
                </div>

                <div class="info-row">
                    <div class="info-label">TIME FROM {{ strtoupper($bill->company->name ?? 'SDK') }} :</div>
                    <div class="info-value">{{ $bill->bus_datetime ? $bill->bus_datetime->format('h:i A') : '' }}</div>
                </div>

                <div class="estimated-text">
                    ESTIMATED TO ARRIVE IN {{ $bill->eta ?? '7' }} DAYS
                </div>
                <div class="business-hours">(BUSINESS HOUR : 7am - 8pm)</div>
            </div>
        </div>

        <!-- Main Section: Description + Total + Consignee -->
        <div class="main-section">
            <!-- Description Column -->
            <div class="description-col">
                <div class="section-title">DESCRIPTION</div>
                <table class="description-table">
                    <tr>
                        <td>Plastik</td>
                        <td>Kotak</td>
                    </tr>
                    <tr>
                        <td>Karung</td>
                        <td>Spring</td>
                    </tr>
                    <tr>
                        <td>Sampul</td>
                        <td>Bag</td>
                    </tr>
                    <tr>
                        <td>Bungkusan</td>
                        <td>Gabus</td>
                    </tr>
                    <tr>
                        <td>Roll</td>
                        <td>Besi</td>
                    </tr>
                    <tr>
                        <td>Battery</td>
                        <td>Tayar</td>
                    </tr>
                    <tr>
                        <td>Kiriman Duit</td>
                        <td>Tong</td>
                    </tr>
                </table>
                @if($bill->description)
                <div style="margin-top: 10px; font-size: 10px; padding: 5px; border-top: 1px solid #ccc;">
                    <strong>Notes:</strong> {{ $bill->description }}
                </div>
                @endif
            </div>

            <!-- Total Column -->
            <div class="description-col">
                <div class="section-title" style="text-align: center;">TOTAL RM</div>
                <div class="total-amount">{{ number_format($bill->amount, 2) }}</div>
                @if($sstDetails && isset($sstDetails['rate']))
                <div class="sst-note">{{ $sstDetails['rate'] }}% SST INCLUDED IN TOTAL</div>
                @endif
            </div>

            <!-- Consignee Column -->
            <div class="description-col consignee-col">
                <div class="section-title">CONSIGNEE</div>

                <div class="consignee-field">
                    <div class="info-label">NAME :</div>
                    <div class="info-value">{{ $bill->receiver_name ?? '' }}</div>
                </div>

                <div class="consignee-field">
                    <div class="info-label">I/C :</div>
                    <div class="info-value">{{ $customerInfo['ic'] ?? '' }}</div>
                </div>

                <div class="consignee-field">
                    <div class="info-label">DATE :</div>
                    <div class="info-value">{{ $bill->date ? $bill->date->format('d/m/Y') : '' }}</div>
                </div>

                <div class="consignee-field">
                    <div class="info-label">SIGN :</div>
                    <div class="info-value sign-field"></div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        @php
            $policySnapshot = $bill->policy_snapshot;
            $policyDescription = null;

            if (is_string($policySnapshot)) {
                $policySnapshot = json_decode($policySnapshot, true);
            }

            if ($policySnapshot && isset($policySnapshot['description'])) {
                $policyDescription = $policySnapshot['description'];
            } elseif ($bill->courierPolicy && $bill->courierPolicy->description) {
                $policyDescription = $bill->courierPolicy->description;
            }
        @endphp
        <div class="footer-note">
            {!! $policyDescription ? nl2br(e($policyDescription)) : 'OUR COMPANY WILL ONLY RESPONSIBLE FOR MAXIMUM RM200.00 FOR GOODS/DOCUMENTS LOST. CLAIMS MUST BE ACCOMPANY WITH ALL PROVE REQUIRED.<br>Kiriman barang / surat mesti dituntut dalam masa 1 bulan, lepas itu tidak ditanggung. Pihak Syarikat tidak bertanggungjawab terhadap hilang barang / duit dalam kiriman sampul surat.' !!}
        </div>

        <!-- Copy Labels -->
        <div class="copy-labels">
            1. CUSTOMER COPY&nbsp;&nbsp;&nbsp;&nbsp;2. OFFICE COPY&nbsp;&nbsp;&nbsp;&nbsp;3. RECEIVER COPY&nbsp;&nbsp;&nbsp;&nbsp;4. BOOK COPY
        </div>
    </div>

    <script>
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
