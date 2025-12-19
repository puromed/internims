<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\EmailDomainValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                function ($attribute, $value, $fail) {
                    if (! EmailDomainValidator::isAllowed($value)) {
                        $fail(EmailDomainValidator::getErrorMessage());
                    }
                },
            ],
            'password' => $this->passwordRules(),
            'student_id' => [
                'required',
                'digits:10',
                Rule::unique(User::class, 'student_id'),
            ],
            'course_code' => [
                'required',
                Rule::in(array_keys(User::courseOptions())),
            ],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => 'student', // Default role for self-registration
            'student_id' => trim($input['student_id']),
            'course_code' => mb_strtoupper($input['course_code']),
        ]);

        // Send welcome notification
        $user->notify(new WelcomeNotification);

        return $user;
    }
}
