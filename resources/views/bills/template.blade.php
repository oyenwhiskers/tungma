<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bill {{ $bill->bill_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }

        .bill-container {
            width: 100%;
            border: 3px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .border-bottom {
            border-bottom: 3px solid #000;
        }

        .border-right {
            border-right: 3px solid #000;
        }

        .border-thin {
            border: 1px solid #000;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .bill-type-box {
            background: #000;
            color: white;
            padding: 6px 25px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            display: inline-block;
        }

        .header-subtitle {
            font-size: 9px;
            margin-top: 5px;
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
            display: inline-block;
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

        .section-label {
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
        }

        .field-label {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid #000;
            display: inline-block;
            vertical-align: middle;
            margin-right: 5px;
        }

        .checkbox-checked {
            background: #000;
            color: white;
            text-align: center;
            line-height: 14px;
        }

        .info-underline {
            border-bottom: 1px solid #000;
            min-height: 18px;
            padding: 2px 0;
        }

        .description-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
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

        .padding-cell {
            padding: 12px;
        }

        .small-text {
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <!-- Header -->
        <table class="border-bottom">
            <tr>
                <td style="width: 35%; padding: 12px 15px;">
                    <div class="company-name">{{ $bill->company->name ?? 'COMPANY NAME' }}</div>
                    <div style="font-size: 10px;">
                        @if($bill->company)
                            {{ $bill->company->address ?? '' }}<br>
                            @if($bill->company->contact_number)TEL: {{ $bill->company->contact_number }}<br>@endif
                            @if($bill->company->email)EMAIL: {{ $bill->company->email }}@endif
                        @endif
                    </div>
                </td>
                <td style="width: 30%; padding: 12px 15px; text-align: center;">
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
                    <div class="header-subtitle">
                        Sila membawa I/C / Passport semasa mengambil Barangan.
                    </div>
                </td>
                <td style="width: 35%; padding: 12px 15px; text-align: center;">
                    <div class="company-chinese">TUNG MA EXPRESS</div>
                    <div class="bill-number-box">
                        <div class="bill-number-label">CS No. D</div>
                        <div class="bill-number">{{ $bill->bill_code }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Route Section -->
        <table class="border-bottom">
            <tr>
                <td style="width: 35%; padding: 12px;" class="border-right">
                    <div class="section-label">FROM {{ strtoupper($bill->fromCompany->name ?? $bill->company->name ?? 'ORIGIN') }}</div>
                    <div class="field-label">COMPANY NAME :</div>
                    <div class="company-box">{{ $bill->sender_name ?? '' }}</div>
                    <div class="field-label">CONTACT NO. :</div>
                    <div>{{ $bill->sender_phone ?? '' }}</div>
                </td>
                <td style="width: 35%; padding: 12px;" class="border-right">
                    <div class="section-label">TO {{ strtoupper($bill->toCompany->name ?? 'DESTINATION') }}</div>
                    <div class="field-label">COMPANY NAME :</div>
                    <div class="company-box">{{ $bill->receiver_name ?? '' }}</div>
                    <div class="field-label">CONTACT NO. :</div>
                    <div>{{ $bill->receiver_phone ?? '' }}</div>
                </td>
                <td style="width: 30%; padding: 12px;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span class="checkbox {{ ($paymentDetails['method'] ?? '') == 'cash' ? 'checkbox-checked' : '' }}">
                            @if(($paymentDetails['method'] ?? '') == 'cash')✓@endif
                        </span>
                        <span class="bold">CASH</span>
                        &nbsp;&nbsp;
                        <span class="checkbox {{ ($paymentDetails['method'] ?? '') == 'cod' ? 'checkbox-checked' : '' }}">
                            @if(($paymentDetails['method'] ?? '') == 'cod')✓@endif
                        </span>
                        <span class="bold">C.O.D.</span>
                    </div>

                    <div style="margin-bottom: 8px;">
                        <div class="field-label">DATE :</div>
                        <div class="info-underline">{{ $bill->date ? $bill->date->format('d/m/Y') : '' }}</div>
                    </div>

                    <div style="margin-bottom: 8px;">
                        <div class="field-label">TIME FROM {{ strtoupper($bill->company->name ?? 'SDK') }} :</div>
                        <div class="info-underline">{{ $bill->busDeparture ? \Carbon\Carbon::parse($bill->busDeparture->departure_time)->format('h:i A') : '' }}</div>
                    </div>

                    <div class="bold text-center small-text" style="margin-top: 10px;">
                        ESTIMATED TO ARRIVE IN {{ $bill->eta ?? '7' }} DAYS
                    </div>
                    <div class="small-text text-center" style="margin-top: 3px;">
                        (BUSINESS HOUR : 7am - 8pm)
                    </div>
                </td>
            </tr>
        </table>

        <!-- Main Section -->
        <table class="border-bottom">
            <tr>
                <td style="width: 40%; padding: 12px;" class="border-right">
                    <div class="bold" style="margin-bottom: 10px;">DESCRIPTION</div>
                    <table class="description-table" style="width: 100%;">
                        <tr>
                            <td style="width: 50%;">Plastik</td>
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
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 10px;">
                        <strong>Notes:</strong> {{ $bill->description }}
                    </div>
                    @endif
                </td>
                <td style="width: 25%; padding: 12px; text-align: center;" class="border-right">
                    <div class="bold" style="margin-bottom: 8px;">TOTAL RM</div>
                    <div style="font-size: 18px; font-weight: bold; margin-top: 30px;">
                        {{ number_format($bill->amount, 2) }}
                    </div>
                    @if($sstDetails && isset($sstDetails['rate']))
                    <div class="bold small-text" style="margin-top: 15px;">
                        {{ $sstDetails['rate'] }}% SST INCLUDED IN TOTAL
                    </div>
                    @endif
                </td>
                <td style="width: 35%; padding: 12px;">
                    <div class="bold" style="margin-bottom: 10px;">CONSIGNEE</div>

                    <div style="margin-bottom: 12px;">
                        <div class="field-label">NAME :</div>
                        <div class="info-underline">{{ $bill->receiver_name ?? '' }}</div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="field-label">I/C :</div>
                        <div class="info-underline">{{ $bill->customer_ic_number ?? data_get($customerInfo, 'ic') }}</div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="field-label">DATE :</div>
                        <div class="info-underline">{{ $bill->date ? $bill->date->format('d/m/Y') : '' }}</div>
                    </div>

                    <div>
                        <div class="field-label">SIGN :</div>
                        <div class="info-underline" style="min-height: 50px;"></div>
                    </div>
                </td>
            </tr>
        </table>

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
            @if($policyDescription)
                {!! nl2br(e($policyDescription)) !!}
            @else
                OUR COMPANY WILL ONLY RESPONSIBLE FOR MAXIMUM RM200.00 FOR GOODS/DOCUMENTS LOST. CLAIMS MUST BE ACCOMPANY WITH ALL PROVE REQUIRED.<br>
                Kiriman barang / surat mesti dituntut dalam masa 1 bulan, lepas itu tidak ditanggung. Pihak Syarikat tidak bertanggungjawab terhadap hilang barang / duit dalam kiriman sampul surat.
            @endif
        </div>

        <!-- Copy Labels -->
        <div class="copy-labels">
            1. CUSTOMER COPY&nbsp;&nbsp;&nbsp;&nbsp;2. OFFICE COPY&nbsp;&nbsp;&nbsp;&nbsp;3. RECEIVER COPY&nbsp;&nbsp;&nbsp;&nbsp;4. BOOK COPY
        </div>
    </div>
</body>
</html>
