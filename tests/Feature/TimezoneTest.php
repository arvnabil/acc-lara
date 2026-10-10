<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

uses(Tests\TestCase::class);

beforeEach(function () {
    // Override the phpunit DB_DATABASE so we connect to actual MySQL database for timezone testing
    Config::set('database.connections.mysql.database', 'accommerce');
});

test('app timezone is Asia/Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
    expect(date_default_timezone_get())->toBe('Asia/Jakarta');
});

test('mysql connection timezone is +07:00', function () {
    $timezone = DB::connection('mysql')->selectOne('SELECT @@session.time_zone AS tz')->tz;
    expect($timezone)->toBe('+07:00');
});

test('database timestamp aligns with php time', function () {
    DB::connection('mysql')->statement('CREATE TEMPORARY TABLE tz_test (id INT AUTO_INCREMENT PRIMARY KEY, created_at TIMESTAMP)');
    DB::connection('mysql')->statement('INSERT INTO tz_test (created_at) VALUES (NOW())');
    
    $row = DB::connection('mysql')->selectOne('SELECT created_at FROM tz_test ORDER BY id DESC LIMIT 1');
    $dbTime = Carbon::parse($row->created_at);
    $phpTime = Carbon::now();

    // Check if the difference is within 5 seconds
    expect($phpTime->diffInSeconds($dbTime))->toBeLessThan(5);
});
