<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'user_id',
        'import_type',
        'file_key', 
        'original_filename',
        'file_names',
        'status',
        'total_rows',
        'inserted_rows',
        'updated_rows',
        'skipped_rows',
        'error_count',
        'started_at',
        'finished_at',
        'processed_at',
        'error_message'
    ];

    protected $casts = [
        'file_names' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'processed_at' => 'datetime'
    ];

    /**
     * Get the user that initiated the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get import errors
     */
    public function importErrors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }
}
