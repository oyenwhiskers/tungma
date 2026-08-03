<?php

namespace Tests\Feature;

use App\Models\BusDepartures;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusDepartureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_recreate_deleted_bus_departure()
    {
        $company = Company::create([
            'name' => 'TungMa Test Company',
            'bill_id_prefix' => 'TMC',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin);

        // 1. Create departure 20:00 (8:00 PM)
        $response = $this->post(route('bus-departures.store'), [
            'departure_time' => '20:00',
        ]);

        $response->assertRedirect(route('bus-departures.index'));
        $this->assertDatabaseHas('bus_departures', [
            'company_id' => $company->id,
            'departure_time' => '20:00',
            'deleted_at' => null,
        ]);

        $departure = BusDepartures::where('company_id', $company->id)
            ->where('departure_time', '20:00')
            ->first();

        // 2. Delete the departure
        $deleteResponse = $this->delete(route('bus-departures.destroy', $departure->id));
        $deleteResponse->assertRedirect(route('bus-departures.index'));

        $this->assertSoftDeleted('bus_departures', [
            'id' => $departure->id,
        ]);

        // 3. Try to recreate departure 20:00 again
        $recreateResponse = $this->post(route('bus-departures.store'), [
            'departure_time' => '20:00',
        ]);

        $recreateResponse->assertRedirect(route('bus-departures.index'));
        $recreateResponse->assertSessionHasNoErrors();

        // Ensure 20:00 is active again in the database
        $this->assertDatabaseHas('bus_departures', [
            'company_id' => $company->id,
            'departure_time' => '20:00',
            'deleted_at' => null,
        ]);
    }
}
