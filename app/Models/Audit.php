<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    protected $fillable = [
        'import_id',
        'table',
        'row_pk',
        'column',
        'old_value',
        'new_value'
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
