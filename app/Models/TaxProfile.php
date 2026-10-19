<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clients\Models\Client;

class TaxProfile extends Model
{
    protected $fillable = ['client_id', 'label', 'npwp', 'taxpayer_name', 'taxpayer_address', 'document_path', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
