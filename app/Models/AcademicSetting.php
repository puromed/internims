<?php

namespace App\Models;

use App\Services\SemesterService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'current_semester_code',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function currentSemesterCode(): string
    {
        $code = static::query()->value('current_semester_code');

        return is_string($code) && $code !== '' ? $code : SemesterService::getCurrentSemesterCode();
    }
}
