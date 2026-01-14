<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checklist Bills - {{ $bills->first()->date ?? 'N/A' }}</title>
</head>
<body>
@foreach($bills as $index => $bill)
    @php
        // Parse JSON fields for each bill
        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
        }

        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
        }
    @endphp

    @if($copyType === 'combined')
        {{-- Customer Copy --}}
        @include('bills.template', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'customer'])
        
        @if($index < count($bills) - 1 || true)
            <div style="page-break-after: always;"></div>
        @endif
        
        {{-- Office Copy 1 --}}
        @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office'])
        
        <div style="page-break-after: always;"></div>
        
        {{-- Office Copy 2 --}}
        @include('bills.template-office', ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => 'office'])
    @else
        {{-- Single copy type --}}
        @include($templateView, ['bill' => $bill, 'sstDetails' => $sstDetails, 'paymentDetails' => $paymentDetails, 'copyType' => $copyType])
    @endif

    {{-- Page break between bills (except last) --}}
    @if($index < count($bills) - 1)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>
