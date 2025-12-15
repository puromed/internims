<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'position',
        'status',
        'submitted_at',
        'eligibility_status',
        'eligibility_reviewed_at',
        'eligibility_reviewed_by',
        'resume_path',
        'transcript_path',
        'advisor_letter_path',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'eligibility_reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function internship()
    {
        return $this->hasOne(Internship::class);
    }

    public function proposedCompanies()
    {
        return $this->hasMany(ProposedCompany::class);
    }
}
