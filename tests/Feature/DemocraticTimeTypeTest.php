<?php

use App\Models\DemocraticTimeType;

test('democratic time type can be created with all required fields', function () {
    $type = DemocraticTimeType::factory()->create([
        'name' => 'Assemblée Générale',
        'acronym' => 'AG',
        'convocation_interval_days' => 14,
    ]);

    expect($type->name)->toBe('Assemblée Générale')
        ->and($type->acronym)->toBe('AG')
        ->and($type->convocation_interval_days)->toBe(14);
});

test('democratic time type has timestamps', function () {
    $type = DemocraticTimeType::factory()->create();

    expect($type->created_at)->not->toBeNull()
        ->and($type->updated_at)->not->toBeNull();
});

test('democratic time type convocation interval is cast to integer', function () {
    $type = DemocraticTimeType::factory()->create([
        'convocation_interval_days' => 7,
    ]);

    expect($type->convocation_interval_days)->toBeInt()
        ->and($type->convocation_interval_days)->toBe(7);
});

test('democratic time type can be updated', function () {
    $type = DemocraticTimeType::factory()->create([
        'name' => 'Conseil d\'Administration',
        'acronym' => 'CA',
        'convocation_interval_days' => 7,
    ]);

    $type->update([
        'name' => 'Bureau',
        'acronym' => 'BU',
        'convocation_interval_days' => 3,
    ]);

    expect($type->fresh()->name)->toBe('Bureau')
        ->and($type->fresh()->acronym)->toBe('BU')
        ->and($type->fresh()->convocation_interval_days)->toBe(3);
});

test('democratic time type can be deleted', function () {
    $type = DemocraticTimeType::factory()->create();
    $id = $type->id;

    $type->delete();

    expect(DemocraticTimeType::find($id))->toBeNull();
});

test('multiple democratic time types can exist with different names', function () {
    DemocraticTimeType::factory()->create(['name' => 'Assemblée Générale', 'acronym' => 'AG']);
    DemocraticTimeType::factory()->create(['name' => 'Conseil d\'Administration', 'acronym' => 'CA']);
    DemocraticTimeType::factory()->create(['name' => 'Bureau', 'acronym' => 'BU']);

    expect(DemocraticTimeType::count())->toBe(3);
});
