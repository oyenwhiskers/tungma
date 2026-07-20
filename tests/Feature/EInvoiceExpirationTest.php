<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoiceExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_eform_fails_if_bill_is_older_than_5_days()
    {
        $company = Company::create([
            'name' => 'TungMa Test',
            'bill_id_prefix' => 'SDK',
        ]);

        // Create a bill created 6 days ago
        $bill = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00001',
            'date' => now()->subDays(6),
            'amount' => 100,
        ]);
        
        // Force the created_at to be 6 days ago since it's a test
        $bill->created_at = now()->subDays(6);
        $bill->save();

        $response = $this->postJson('/api/submit-eform', [
            'date_time' => now()->toDateTimeString(),
            'bill_id' => $bill->id,
            'amount' => 100,
            'tin_number' => 'C1234567890',
            'customer_name' => 'John Doe',
            'customer_type' => 'Individual',
            'address' => '123 Test St',
            'postcode' => '50000',
            'city' => 'Kuala Lumpur',
            'state' => 'Kuala Lumpur',
            'country' => 'Malaysia'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'E-Invoice request has expired. E-Invoice can only be requested within 5 days of bill creation.'
        ]);
    }

    public function test_submit_eform_succeeds_if_bill_is_within_5_days()
    {
        $company = Company::create([
            'name' => 'TungMa Test',
            'bill_id_prefix' => 'SDK',
        ]);

        // Create a bill created today
        $bill = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00002',
            'date' => now(),
            'amount' => 100,
        ]);

        $response = $this->postJson('/api/submit-eform', [
            'date_time' => now()->toDateTimeString(),
            'bill_id' => $bill->id,
            'amount' => 100,
            'tin_number' => 'C1234567890',
            'customer_name' => 'John Doe',
            'customer_type' => 'Individual',
            'address' => '123 Test St',
            'postcode' => '50000',
            'city' => 'Kuala Lumpur',
            'state' => 'Kuala Lumpur',
            'country' => 'Malaysia'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Saved successfully'
        ]);
    }
}
