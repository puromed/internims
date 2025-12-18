<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\EmailDomainValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Supported OAuth providers
     */
    protected array $providers = ['google', 'microsoft'];

    /**
     * Redirect to the OAuth provider
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->providers)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback
     */
    public function callback(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->providers)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to authenticate with ' . ucfirst($provider) . '. Please try again.');
        }

        // Check if this social account already exists
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            // Social account exists, log in the user
            Auth::login($socialAccount->user);

            return redirect()->intended($this->getRedirectPath($socialAccount->user));
        }

        // Check if a user with this email already exists
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Link the social account to the existing user
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
            ]);

            Auth::login($user);

            return redirect()->intended($this->getRedirectPath($user));
        }

        // For new users, validate email domain
        if (!EmailDomainValidator::isAllowed($socialUser->getEmail())) {
            return redirect()->route('login')
                ->with('error', EmailDomainValidator::getErrorMessage());
        }

        // Create a new user
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => null, // OAuth-only user, no password
            'role' => 'student', // Default role for OAuth registrations
            'email_verified_at' => now(), // OAuth emails are pre-verified
        ]);

        // Link the social account
        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
        ]);

        Auth::login($user);

        // Send welcome notification to new users
        $user->notify(new WelcomeNotification());

        return redirect()->intended($this->getRedirectPath($user));
    }

    /**
     * Get the redirect path based on user role
     */
    protected function getRedirectPath(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'faculty' => route('faculty.dashboard'),
            default => route('dashboard'),
        };
    }
}
