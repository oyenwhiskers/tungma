<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill {{ $bill->bill_code }} - All Copies</title>
</head>
<body>
    {{-- Customer Copy --}}
    @include('bills.template', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'customer', 'isPdf' => $isPdf])
    
    <div style="page-break-after: always;"></div>
    
    {{-- Office Copy 1 --}}
    @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => $isPdf])
    
    <div style="page-break-after: always;"></div>
    
    {{-- Office Copy 2 --}}
    @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office', 'isPdf' => $isPdf])
</body>
</html>
