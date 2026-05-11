<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 10mm 5mm 5mm 5mm;
        }

        @font-face {
            font-family: 'Noto Sans SC';
            src: url('{{ public_path('fonts/static/NotoSansSC-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Noto Sans SC';
            src: url('{{ public_path('fonts/static/NotoSansSC-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans SC', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #000;
        }

        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: auto;
        }

        /* Shared Bill Styles */
        .bill-container {
            width: 97%;
            margin: 0 auto;
            height: 142mm;
            /* Default height */
            border-top: 1.5px solid #000;
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            border-left: 1.5px solid #000;
            position: relative;
            padding: 2px 0;
            box-sizing: border-box;
        }

        /* Specifically for 2-copy mode to prevent page spill */
        .cutting-line {
            position: absolute;
            top: 148.5mm;
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

        .company-info {
            padding: 3px;
            border: none;
            width: 35%;
            vertical-align: top;
        }

        .logo-section {
            padding: 3px;
            text-align: center;
            border: none;
            width: 30%;
            vertical-align: middle;
        }

        .payment-section {
            padding: 3px;
            text-align: center;
            border: none;
            width: 35%;
            vertical-align: middle;
        }

        .company-name {
            font-size: 12px;
            font-weight: 900;
        }

        .cash-sales-badge {
            background: #1a1a1a;
            color: #fff;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 3px;
        }

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

        .label-cell {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 4px;
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
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .desc-cell {
            padding: 5px;
            width: 37%;
        }

        .total-cell {
            padding: 5px;
            width: 33%;
            text-align: center;
        }

        .qr-cell {
            padding: 5px;
            width: 30%;
            text-align: center;
        }

        .total-amount {
            font-size: 18px;
            font-weight: bold;
            margin: 2px 0;
        }

        .description-item {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            max-height: 45mm;
            overflow: hidden;
        }

        .policy-disclaimer-section {
            padding: 5px 10px;
            font-size: 8px;
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
            padding-left: 10px;
            font-size: 8px;
            line-height: 1.2;
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

        /* Office Template Specifics */
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
            border-bottom: 1px solid #000;
        }

        .consignee-field {
            display: flex;
            margin-bottom: 2px;
        }

        .consignee-label {
            width: 40px;
            font-size: 10px;
        }

        .consignee-bottom {
            padding: 4px;
        }

        .consignee-sign {
            display: flex;
            align-items: flex-start;
        }

        .consignee-sign-label {
            width: 40px;
            font-size: 10px;
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
