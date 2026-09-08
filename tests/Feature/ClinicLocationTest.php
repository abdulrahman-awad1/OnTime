<?php

use App\Models\ClinicLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns list of clinic locations', function () {

    ClinicLocation::factory()->create([
        'name' => 'فرع المعادي',
    ]);

    $response = $this->getJson('/api/clinic-locations');

    $response->assertStatus(200);

    $response->assertJsonFragment([
        'name' => 'فرع المعادي',
    ]);
});
