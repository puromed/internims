<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposedCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'name',
        'website',
        'address',
        'job_scope',
        'status',
        'admin_remarks',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
