<?php

use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class);

test('migrate fresh and rollback down succeeds without errors', function () {
    $freshExitCode = Artisan::call('migrate:fresh');
    expect($freshExitCode)->toBe(0);

    $rollbackExitCode = Artisan::call('migrate:rollback');
    expect($rollbackExitCode)->toBe(0);
});
