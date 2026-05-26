<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\CashSale;
use App\Models\Debtor;
use Illuminate\Support\Facades\Log;

class BillObserver
{
    /**
     * Handle the Bill "created" event.
     */
    public function created(Bill $bill)
    {
        $this->createCashSaleFromBill($bill);
    }

    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill)
    {
        // Decide if we update the existing Cash Sale or ignore.
        // For now, let's update it if it exists.
        $cashSale = $bill->cashSale;
        if ($cashSale) {
            // Logic to update cash sale could go here
            // For now, simpler to maybe just log or skip, 
            // but let's implement basic update logic or delete/recreate
            // delete/recreate is risky if IDs change. 
            // Let's just update the header and detail.

            $cashSale->update([
                'doc_date' => $bill->date,
                'description' => $bill->description,
            ]);

            // Assume single detail for now
            $cashSale->details()->update([
                'amount' => $bill->amount,
                'description' => $bill->description,
            ]);

            // Regenerate XML
            $xml = $cashSale->generateXml();
            $cashSale->update(['generated_xml' => $xml]);
        } else {
            $this->createCashSaleFromBill($bill);
        }
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleted(Bill $bill)
    {
        if ($bill->cashSale) {
            $bill->cashSale->delete();
        }
    }

    /**
     * Handle the Bill "restored" event.
     */
    public function restored(Bill $bill)
    {
        if ($bill->cashSale()->onlyTrashed()->exists()) {
            $bill->cashSale()->restore();
        } elseif (!$bill->cashSale()->exists()) {
            $this->createCashSaleFromBill($bill);
        }
    }

    /**
     * Handle the Bill "force deleted" event.
     */
    public function forceDeleted(Bill $bill)
    {
        if ($bill->cashSale) {
            $bill->cashSale->forceDelete();
        }
    }

    protected function createCashSaleFromBill(Bill $bill)
    {
        try {
            // SCENARIO 1: Use Default Data (ID 1)
            $defaultDebtor = Debtor::find(1);

            if (!$defaultDebtor) {
                Log::warning("Default Debtor (ID 1) not found. Skipping CashSale creation for Bill {$bill->id}");
                return;
            }

            // 1. Create Header
            $cashSale = CashSale::create([
                'bill_id' => $bill->id,
                'doc_no' => $bill->bill_code,
                'doc_date' => $bill->date,
                'debtor_code' => $defaultDebtor->acc_no, // e.g. 300-1000
                'debtor_name' => $defaultDebtor->company_name, // Default seeded name
                'description' => $bill->description ?? 'CASH SALE',
                'display_term' => $defaultDebtor->display_term ?? 'C.O.D.',
                'ref' => null,
                'sales_agent' => null,
            ]);

            // 2. Create Detail (Assuming single line item based on Bill model structure)
            $cashSale->details()->create([
                'item_code' => 'A001', // Default service item code
                'uom' => 'UNIT',
                'qty' => 1,
                'unit_price' => $bill->amount,
                'location' => 'HQ', // Default
                'description' => $bill->description ?? 'Service Charge',
                'amount' => $bill->amount,
            ]);

            // 3. Generate XML
            $xml = $cashSale->generateXml();
            $cashSale->update(['generated_xml' => $xml]);

            // 4. TODO: Send to AutoCount API
            // AutoCountService::send($xml);

        } catch (\Exception $e) {
            Log::error('Failed to create Cash Sale for Bill ' . $bill->id . ': ' . $e->getMessage());
        }
    }
}
