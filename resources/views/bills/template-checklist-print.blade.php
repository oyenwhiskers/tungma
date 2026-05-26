<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
            font-size: 7.5px;
            color: #000;
        }

        .bill-container {
            width: 97%;
            margin: 0 auto;
            height: 134mm;
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
            font-size: 13px;
            font-weight: 900;
        }

        .sst-no {
            font-size: 8px;
            margin-top: 1px;
        }

        .cash-sales-badge {
            background: #1a1a1a;
            color: #fff;
            padding: 4px 14px;
            border-radius: 5px;
            font-size: 12px;
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
            font-size: 10px;
            padding: 2px 3px;
            text-transform: uppercase;
        }

        .cs-no {
            font-size: 12px;
            color: #000;
        }

        .cs-val {
            color: #000;
            font-size: 15px;
        }

        .data-content {
            padding: 2px 5px;
        }

        .field-label {
            font-size: 10px;
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
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
        }

        .description-item {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            max-height: 45mm;
            overflow: hidden;
        }

        /* CONSIGNEE Section */
        .consignee-section {
            border: 1px solid #000;
            padding: 0;
            width: 30%;
        }

        .consignee-header {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 2px;
            border-bottom: 1px solid #000;
        }

        .consignee-top {
            padding: 2px;
        }

        .consignee-field {
            display: flex;
            margin-bottom: 1px;
        }

        .consignee-label {
            width: 45px;
            font-size: 9px;
            padding-left: 5px;
        }

        .consignee-bottom {
            padding: 2px;
        }

        .consignee-sign {
            display: flex;
            align-items: flex-start;
        }

        .consignee-sign-label {
            width: 45px;
            font-size: 10px;
            padding-left: 5px;
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
    @foreach($bills as $bill)
        @php
            $sstDetails = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
            $paymentDetails = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
        @endphp

        @if($copyType === 'combined')
            {{-- Page 1: Customer --}}
            <div class="page-break">
                @include('bills.template', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'customer', 'isPdf' => true, 'omitLayout' => true])
            </div>
            {{-- Page 2: Office --}}
            <div class="page-break">
                @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => true, 'omitLayout' => true])
            </div>
            {{-- Page 3: Receiver --}}
            <div class="page-break">
                @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'receiver', 'isPdf' => true, 'omitLayout' => true])
            </div>

        @elseif($copyType === 'office' || $copyType === 'receiver')
            {{-- Office Copy --}}
            <div class="page-break">
                @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => true, 'omitLayout' => true])
            </div>
            {{-- Receiver Copy --}}
            <div class="page-break">
                @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'receiver', 'isPdf' => true, 'omitLayout' => true])
            </div>

        @else
            {{-- Single Copy --}}
            <div class="page-break">
                @php 
                    $view = in_array($copyType, ['office', 'receiver', 'book']) ? 'bills.template-office' : 'bills.template';
                @endphp
                                                                                        @include($view, ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => $copyType, 'isPdf' => true, 'omitLayout' => true])
                                                                                       </div>


         @endif
    @endforeach
</body>
</html>
