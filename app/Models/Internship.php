<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_id',
        'company_name',
        'supervisor_name',
        'start_date',
        'end_date',
        'status',
        'faculty_supervisor_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function facultySupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_supervisor_id');
    }
}
