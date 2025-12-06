<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_number',
        'entry_text',
        'file_path',
        'status',
        'ai_analysis_json',
        'submitted_at',
    ];

    protected $casts = [
        'ai_analysis_json' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
