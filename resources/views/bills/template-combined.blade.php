<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill {{ $bill->bill_code }} - All Copies</title>
    <style>
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

        body {
            font-family: 'Noto Sans SC', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
        }
    </style>
</head>
<body>
    {{-- Customer Copy --}}
    @include('bills.template', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'customer', 'isPdf' => $isPdf, 'omitLayout' => true])
    
    <div style="page-break-after: always;"></div>
    
    {{-- Office Copy 1 --}}
    @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => $isPdf, 'omitLayout' => true])
    
    <div style="page-break-after: always;"></div>
    
    {{-- Office Copy 2 --}}
    @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => $isPdf, 'omitLayout' => true])
</body>
</html>
