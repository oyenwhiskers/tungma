<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use App\Models\Bill;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateBillPdf implements ShouldQueue
{
    use Queueable;

    public $bill;

    /**
     * Create a new job instance.
     */
    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bill = $this->bill;

        // Load relationships needed for the view
        $bill->load('company', 'courierPolicy', 'fromCompany', 'toCompany', 'busDeparture');

        // Parse JSON fields
        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
        }

        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
        }

        // Use 'combined' copy type by default, or you can loop and generate multiple if needed.
        // Assuming mobile app wants the main combined/customer copy.
        $copyType = 'combined';
        $templateView = 'bills.template-combined'; // Or just 'bills.template' if you want single copy

        // Render PDF (using same logic as controller) with isPdf flag
        $pdf = \PDF::loadView($templateView, compact('bill', 'sstDetails', 'paymentDetails', 'copyType') + ['isPdf' => true])
            ->setOptions(['isFontSubsetting' => true])
            ->setPaper('a4', 'portrait');

        // Generate filename
        $filename = 'bill-' . $bill->bill_code . '.pdf';
        $path = 'bills/' . $filename;

        // Store file publicly
        Storage::disk('public')->put($path, $pdf->output());

        // Update bill with full URL
        $url = Storage::url($path);
        
        // Ensure full URL if Storage::url returns relative
        if (!str_starts_with($url, 'http')) {
            $url = asset($url);
        }

        \Log::info('PDF Generated for Bill ' . $bill->id . ': ' . $url);
        
        $updated = $bill->update(['pdf_url' => $url]);

        \Log::info('Bill ' . $bill->id . ' Updated: ' . ($updated ? 'YES' : 'NO'));
    }
}
