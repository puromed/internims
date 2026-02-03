<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'theme_preference',
        'student_id',
        'program_code',
    ];

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function eligibilityDocs()
    {
        return $this->hasMany(EligibilityDoc::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function internships()
    {
        return $this->hasMany(Internship::class);
    }

    public function logbookEntries()
    {
        return $this->hasMany(LogbookEntry::class);
    }

    public function supervisedInternships(): HasMany
    {
        return $this->hasMany(Internship::class, 'faculty_supervisor_id');
    }

    public function isFaculty(): bool
    {
        return $this->role === 'faculty';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Is this user the supervising faculty for the given logbook entry's student?
     */
    public function supervisesLogbookEntry(LogbookEntry $entry): bool
    {
        if (! $this->isFaculty()) {
            return false;
        }

        return $this->supervisedInternships()
            ->where('user_id', $entry->user_id)
            ->exists();
    }
}
