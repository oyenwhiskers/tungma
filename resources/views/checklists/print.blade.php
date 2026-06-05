<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Proof of Collection</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Noto Sans SC', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.15;
            margin: 20px 22px 15px 22px;
        }
        #page-header {
            position: fixed;
            top: 15px;
            right: 22px;
            font-size: 9px;
            font-weight: bold;
            color: #000;
        }
        #page-header:after {
            content: "Page " counter(page);
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .main-table th {
            border-top: 1px solid #000;
            border-bottom: 1.5px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3.5px 4px;
            font-weight: bold;
            text-align: left;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 3.5px 4px;
            font-size: 9.5px;
            vertical-align: middle;
        }

        /*
         * PAGE-BREAK FIX:
         * - Each bill is its own <tbody> (same as before).
         * - page-break-inside:avoid + break-inside:avoid on <tbody> tells the
         *   engine to keep all rows together.
         * - ADDITIONALLY, every item <tr> gets page-break-after:avoid so the
         *   engine never breaks AFTER an item row (which would orphan the
         *   outstanding row on the next page).
         * - The outstanding <tr> gets page-break-before:avoid for the same reason.
         * These two properties together guarantee the outstanding row is never
         * separated from its bill, even when tbody avoid is ignored.
         */
        .bill-tbody {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .item-row {
            page-break-after: avoid;
            break-after: avoid;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .outstanding-row {
            page-break-before: avoid;
            break-before: avoid;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Receiver column — merged rowspan layout */
        .rcv {
            vertical-align: middle;
            text-align: center;
            padding: 0;
            background-color: #fff;
            border: 1px solid #000;
        }

        .bold-text      { font-weight: bold; }
        .secondary-info { font-size: 9.5px; color: #000; }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    <div id="page-header"></div>

    <table class="main-table">
        <thead>
            <tr style="border: none;">
                <td colspan="9" style="border: none; padding: 5px 0 6px 10px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 0;">
                                <div class="title">Proof of Collection</div>
                                <div class="subtitle">
                                    @if(isset($fromTerminal) || isset($toTerminal))
                                        FROM: {{ strtoupper($fromTerminal ?? 'ALL') }} &nbsp;&nbsp;&nbsp;&nbsp; TO: {{ strtoupper($toTerminal ?? 'ALL') }}
                                    @else
                                        TERMINAL : {{ strtoupper($terminal ?? 'ALL') }}
                                    @endif
                                </div>
                            </td>
                            <td style="border: none; padding: 0; text-align: right;"></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th style="width: 9%;">Document Date</th>
                <th style="width: 8%;">Document No.</th>
                <th style="width: 3%;">COD/CASH</th>
                <th style="width: 13%;">SENDER</th>
                <th style="width: 13%;">RECEIVER</th>
                <th style="width: 7%; text-align: center;">Unit Price</th>
                <th style="width: 12%;">Description</th>
                <th style="width: 6%; text-align: center;">Quantity</th>
                <th style="width: 29%;">Receiver<br><span style="font-size: 8px; font-weight: normal;">Name / IC No. / Contact No. / Signature / Date</span></th>
            </tr>
        </thead>

        @foreach($bills as $bill)
            @php
                $items = [];
                if ($bill->description) {
                    $decoded = json_decode($bill->description, true);
                    if (is_array($decoded)) {
                        $items = $decoded;
                    }
                }
                if (empty($items)) {
                    $items = [[
                        'product'  => $bill->description ?: 'No Description',
                        'price'    => $bill->amount,
                        'quantity' => 1,
                    ]];
                }
                $itemCount = count($items);

                $payMethod = 'CASH';
                if ($bill->payment_details) {
                    $payDetails = is_string($bill->payment_details)
                        ? json_decode($bill->payment_details, true)
                        : $bill->payment_details;
                    if (strtolower($payDetails['method'] ?? '') === 'cod') {
                        $payMethod = 'COD';
                    }
                }
                $isCOD       = ($payMethod === 'COD');
                $outstanding = $isCOD ? number_format($bill->amount, 2) : '0.00';
            @endphp

            <tbody class="bill-tbody">
                @foreach($items as $index => $item)
                <tr class="item-row">
                    <td>
                        <span class="bold-text">{{ $bill->created_at ? $bill->created_at->format('j/n/Y') : ($bill->date ? $bill->date->format('j/n/Y') : '—') }}</span><br>
                        <span class="secondary-info">{{ $bill->created_at ? $bill->created_at->format('g:i:s A') : '' }}</span>
                    </td>
                    <td class="bold-text">{{ $bill->bill_code }}</td>
                    <td>{{ $payMethod }}</td>
                    <td>
                        <span class="bold-text">{{ \Illuminate\Support\Str::limit(strtoupper($bill->sender_name), 22, '..') }}</span><br>
                        <span class="secondary-info">{{ $bill->sender_phone }}</span>
                    </td>
                    <td>
                        <span class="bold-text">{{ \Illuminate\Support\Str::limit(strtoupper($bill->receiver_name), 22, '..') }}</span><br>
                        <span class="secondary-info">{{ $bill->receiver_phone }}</span>
                    </td>
                    <td style="text-align: center;">{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td class="bold-text">{{ strtoupper($item['product'] ?? '') }}</td>
                    <td style="text-align: center;">{{ $item['quantity'] ?? 0 }}</td>
                    @if($index === 0)
                    <td class="rcv" rowspan="{{ $itemCount + 1 }}"></td>
                    @endif
                </tr>
                @endforeach

                <tr class="outstanding-row">
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: 1px solid #000; border-right: none;"></td>
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none;"></td>
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none;"></td>
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none;"></td>
                    <td style="text-align: right; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; font-weight: normal;">Out Standing:</td>
                    <td style="text-align: center; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; font-weight: bold;">{{ $outstanding }}</td>
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none;"></td>
                    <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: 1px solid #000;"></td>
                </tr>
            </tbody>
        @endforeach
    </table>

</body>
</html>