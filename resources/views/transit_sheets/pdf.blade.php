<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transit Sheet / Manifest</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 50px 45px 30px 45px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding: 5px 20px;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.1;
        }

        .header {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 8px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            text-decoration: underline;
        }

        .header .subtitle {
            font-size: 9px;
            color: #444;
        }

        .page-number {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 8px;
            font-weight: bold;
        }

        /* Wrapper table for 2-column layout */
        .wrapper-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            table-layout: fixed;
        }

        .col-side {
            width: 48%;
            vertical-align: top;
        }

        .col-spacer {
            width: 4%;
        }

        /* Inner data tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 8.5px;
            text-align: center;
            vertical-align: middle;
            height: 15px;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9px;
            height: 22px;
        }

        .data-table td.amount-col {
            text-align: right;
            padding-right: 5px;
        }

        .data-table tr.total-row td {
            font-weight: bold;
            background-color: #fafafa;
        }

        /* Bottom Summary Box */
        .summary-container {
            width: 100%;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .summary-table {
            width: 300px;
            margin-left: auto;
            /* align right */
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 8px;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-table td.label {
            text-align: left;
        }

        .summary-table td.value {
            text-align: right;
            border-bottom: 1px solid #000;
        }

        .summary-table tr.grand-total td {
            font-size: 13px;
            border-bottom: 2px double #000;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    @foreach($pages as $pageIndex => $page)
        @if($pageIndex > 0)
            <div style="page-break-before: always;"></div>
        @endif

        <div class="header">
            <h1>
                @if(isset($toCompanies) && $toCompanies->count() === 1)
                    {{ $fromCompany->bill_id_prefix ?? $fromCompany->name }}-{{ $toCompanies->first()->bill_id_prefix ?? $toCompanies->first()->name }}
                    {{ $fromCompany->name }} Goods List
                @elseif(isset($toCompanies) && $toCompanies->count() > 1)
                    {{ $fromCompany->bill_id_prefix ?? $fromCompany->name }} - SELECTED BRANCHES {{ $fromCompany->name }} Goods
                    List
                @else
                    {{ $fromCompany->bill_id_prefix ?? $fromCompany->name }} - ALL BRANCHES {{ $fromCompany->name }} Goods List
                @endif
            </h1>
            <div class="subtitle">
                Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
            <div class="page-number">
                Page {{ $pageIndex + 1 }} of {{ $pages->count() }}
            </div>
        </div>

        @php
            // Calculate page-specific totals
            $pageLeftCash = 0;
            $pageLeftCod = 0;
            foreach ($page['left'] as $b) {
                $m = strtolower($b->payment_details['method'] ?? '');
                if (in_array($m, ['cash', 'qr', 'a/c', 'ewallet', 'e-wallet', 'e_wallet', 'e-wallet/qr', 'ewallet/qr', 'e_wallet_qr', 'bank_transfer', 'bank', 'bank transfer']))
                    $pageLeftCash += $b->amount;
                elseif ($m === 'cod')
                    $pageLeftCod += $b->amount;
            }

            $pageRightCash = 0;
            $pageRightCod = 0;
            foreach ($page['right'] as $b) {
                $m = strtolower($b->payment_details['method'] ?? '');
                if (in_array($m, ['cash', 'qr', 'a/c', 'ewallet', 'e-wallet', 'e_wallet', 'e-wallet/qr', 'ewallet/qr', 'e_wallet_qr', 'bank_transfer', 'bank', 'bank transfer']))
                    $pageRightCash += $b->amount;
                elseif ($m === 'cod')
                    $pageRightCod += $b->amount;
            }
        @endphp

        <table class="wrapper-table">
            <tr>
                <!-- Left Side Table -->
                <td class="col-side">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">DATE</th>
                                <th style="width: 35%;">BIL NO.</th>
                                <th style="width: 20%;">CASH (RM)</th>
                                <th style="width: 20%;">C.O.D (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($page['left'] as $bill)
                                @php
                                    $method = strtolower($bill->payment_details['method'] ?? '');
                                    $dispAmount = $bill->amount == (int) $bill->amount ? number_format($bill->amount, 0) : number_format($bill->amount, 2);
                                @endphp
                                <tr>
                                    <td>{{ $bill->date ? $bill->date->format('j/n/y') : '' }}</td>
                                    <td>{{ $bill->bill_code }}</td>
                                    <td class="amount-col">
                                        {{ in_array($method, ['cash', 'qr', 'a/c', 'ewallet', 'e-wallet', 'e_wallet', 'e-wallet/qr', 'ewallet/qr', 'e_wallet_qr', 'bank_transfer', 'bank', 'bank transfer']) ? $dispAmount : '' }}
                                    </td>
                                    <td class="amount-col">{{ $method === 'cod' ? $dispAmount : '' }}</td>
                                </tr>
                            @endforeach

                            {{-- Pad empty rows to maintain alignment with right column or layout --}}
                            @if($page['left']->count() < $page['right']->count())
                                @for($i = 0; $i < ($page['right']->count() - $page['left']->count()); $i++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                            @endif

                            <!-- Left Column Totals -->
                            <tr class="total-row">
                                <td colspan="2">TOTAL</td>
                                <td class="amount-col">
                                    {{ $pageLeftCash > 0 ? 'RM ' . ($pageLeftCash == (int) $pageLeftCash ? number_format($pageLeftCash, 0) : number_format($pageLeftCash, 2)) : '—' }}
                                </td>
                                <td class="amount-col">
                                    {{ $pageLeftCod > 0 ? 'RM ' . ($pageLeftCod == (int) $pageLeftCod ? number_format($pageLeftCod, 0) : number_format($pageLeftCod, 2)) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                <!-- Spacer Column -->
                <td class="col-spacer"></td>

                <!-- Right Side Table -->
                <td class="col-side">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">DATE</th>
                                <th style="width: 35%;">BIL NO.</th>
                                <th style="width: 20%;">CASH (RM)</th>
                                <th style="width: 20%;">C.O.D (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($page['right'] as $bill)
                                @php
                                    $method = strtolower($bill->payment_details['method'] ?? '');
                                    $dispAmount = $bill->amount == (int) $bill->amount ? number_format($bill->amount, 0) : number_format($bill->amount, 2);
                                @endphp
                                <tr>
                                    <td>{{ $bill->date ? $bill->date->format('j/n/y') : '' }}</td>
                                    <td>{{ $bill->bill_code }}</td>
                                    <td class="amount-col">
                                        {{ in_array($method, ['cash', 'qr', 'a/c', 'ewallet', 'e-wallet', 'e_wallet', 'e-wallet/qr', 'ewallet/qr', 'e_wallet_qr', 'bank_transfer', 'bank', 'bank transfer']) ? $dispAmount : '' }}
                                    </td>
                                    <td class="amount-col">{{ $method === 'cod' ? $dispAmount : '' }}</td>
                                </tr>
                            @endforeach

                            {{-- Pad empty rows --}}
                            @if($page['right']->count() < $page['left']->count())
                                @for($i = 0; $i < ($page['left']->count() - $page['right']->count()); $i++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                            @endif

                            <!-- Right Column Totals -->
                            <tr class="total-row">
                                <td colspan="2">TOTAL</td>
                                <td class="amount-col">
                                    {{ $pageRightCash > 0 ? 'RM ' . ($pageRightCash == (int) $pageRightCash ? number_format($pageRightCash, 0) : number_format($pageRightCash, 2)) : '—' }}
                                </td>
                                <td class="amount-col">
                                    {{ $pageRightCod > 0 ? 'RM ' . ($pageRightCod == (int) $pageRightCod ? number_format($pageRightCod, 0) : number_format($pageRightCod, 2)) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Show Grand Summary at the bottom of the final page --}}
        @if($pageIndex === $pages->count() - 1)
            <div class="summary-container">
                <table class="summary-table">
                    <tr>
                        <td class="label">CASH Total:</td>
                        <td class="value">RM {{ number_format($totalCash, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">COD Total:</td>
                        <td class="value">RM {{ number_format($totalCod, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="label">GRAND TOTAL (T):</td>
                        <td class="value">RM {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </div>
        @endif

    @endforeach

</body>

</html>