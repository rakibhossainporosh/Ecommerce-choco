<?php

use App\Models\Courier;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');
});

test('courier can be created', function () {
    $courier = Courier::factory()->create([
        'name' => 'Test Courier',
        'code' => 'test-courier',
    ]);

    expect($courier->id)->not->toBeNull()
        ->and($courier->name)->toBe('Test Courier')
        ->and($courier->code)->toBe('test-courier');
});

test('courier code must be unique', function () {
    Courier::factory()->create(['code' => 'unique-code']);

    expect(fn () => Courier::factory()->create(['code' => 'unique-code']))
        ->toThrow(QueryException::class);
});

test('courier credentials are encrypted in database', function () {
    $credentials = ['api_key' => 'secret_value'];

    $courier = Courier::factory()->create([
        'credentials' => $credentials,
    ]);

    // Check raw DB value
    $rawRecord = DB::table('couriers')->where('id', $courier->id)->first();

    // The raw value should not contain the secret string
    expect($rawRecord->credentials)->not->toContain('secret_value')
        // But the model should cast it back correctly
        ->and($courier->credentials)->toBe($credentials);
});
