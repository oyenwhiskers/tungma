<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_by_date_displays_bills_correctly()
    {
        $company = Company::create([
            'name' => 'TungMa Test',
            'bill_id_prefix' => 'SDK',
        ]);

        $user = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
            'password' => bcrypt('password'),
        ]);

        // Create two bills on same date
        $bill1 = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00002',
            'date' => '2026-07-17',
            'amount' => 100,
        ]);

        $bill2 = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00001',
            'date' => '2026-07-17',
            'amount' => 50,
        ]);

        $response = $this->actingAs($user)->get(route('checklists.showByDate', ['date' => '2026-07-17']));

        $response->assertStatus(200);
        // SDK-A00001 should appear before SDK-A00002 since they are sorted by bill_code
        $response->assertSeeInOrder(['SDK-A00001', 'SDK-A00002']);
    }

    public function test_save_by_date_updates_checkmarks()
    {
        $company = Company::create([
            'name' => 'TungMa Test',
            'bill_id_prefix' => 'SDK',
        ]);

        $user = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
            'password' => bcrypt('password'),
        ]);

        $bill1 = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00001',
            'date' => '2026-07-17',
            'amount' => 100,
        ]);

        $bill2 = Bill::create([
            'company_id' => $company->id,
            'bill_code' => 'SDK-A00002',
            'date' => '2026-07-17',
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->post(route('checklists.saveByDate'), [
            'date' => '2026-07-17',
            'bill_ids' => [$bill1->id]
        ]);

        $response->assertRedirect();
        
        $this->assertEquals($user->id, $bill1->fresh()->checked_by);
        $this->assertNull($bill2->fresh()->checked_by);
    }
}
