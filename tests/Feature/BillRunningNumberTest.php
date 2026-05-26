<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillRunningNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_running_number_generation_and_rollover()
    {
        // Create a Company with prefix SDK
        $company = Company::create([
            'name' => 'TungMa Test',
            'bill_id_prefix' => 'SDK',
        ]);

        // Use reflection to test the private generateNextBillCode method
        $controller = new \App\Http\Controllers\BillController();
        $method = new \ReflectionMethod($controller, 'generateNextBillCode');
        $method->setAccessible(true);

        // Scenario 1: No previous bills
        $code1 = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-A00001', $code1);

        // Scenario 2: Previous bill exists, increment numeric part
        $bill1 = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00001',
            'date' => now(),
            'amount' => 100,
        ]);
        $code2 = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-A00002', $code2);

        // Scenario 3: Rollover numeric part to next alphabet (A99999 -> B00001)
        $bill1->update(['bill_code' => 'SDK-A99999']);
        $code3 = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-B00001', $code3);

        // Scenario 4: Rollover from Z to AA (Z99999 -> AA00001)
        $bill1->update(['bill_code' => 'SDK-Z99999']);
        $code4 = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-AA00001', $code4);

        // Scenario 5: Rollover from ZZ to AAA (ZZ99999 -> AAA00001)
        $bill1->update(['bill_code' => 'SDK-ZZ99999']);
        $code5 = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-AAA00001', $code5);
    }

    public function test_ignores_old_format_when_generating_next_code()
    {
        $company = Company::create([
            'name' => 'TungMa Test 2',
            'bill_id_prefix' => 'SDK',
        ]);

        // Create an old format bill (which has a higher ID, so it is "latest" by ID)
        $billOld = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK00005',
            'date' => now(),
            'amount' => 100,
        ]);

        $controller = new \App\Http\Controllers\BillController();
        $method = new \ReflectionMethod($controller, 'generateNextBillCode');
        $method->setAccessible(true);

        // It should ignore the old format bill and fallback to generating SDK-A00001
        $code = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-A00001', $code);

        // Create a new format bill (e.g. SDK-A00001)
        Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00001',
            'date' => now(),
            'amount' => 100,
        ]);

        // Even though SDK00005 has a higher ID,
        // it should pick SDK-A00001 as the latest new format and increment to SDK-A00002
        $codeNext = $method->invoke($controller, $company->id);
        $this->assertEquals('SDK-A00002', $codeNext);
    }
}
