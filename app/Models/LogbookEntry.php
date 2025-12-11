<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_number',
        'entry_text',
        'file_path',
        'status',
        'supervisor_status',
        'supervisor_comment',
        'reviewed_at',
        'reviewed_by',
        'ai_analysis_json',
        'submitted_at',
    ];

    protected $casts = [
        'ai_analysis_json' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
