<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    protected $fillable = ['settings'];

    protected $casts = ['settings' => 'array'];

    public static function getInstance(): self
    {
        return static::query()->firstOrCreate([], ['settings' => config('loyalty')]);
    }
}

