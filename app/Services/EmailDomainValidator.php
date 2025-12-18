<?php

namespace App\Services;

class EmailDomainValidator
{
    /**
     * Allowed email domains for student registration
     */
    protected static array $allowedDomains = [
        'isiswa.uitm.edu.my',
        'student.uitm.edu.my',
        'uitm.edu.my',
    ];

    /**
     * Emails that bypass domain validation (for testing)
     */
    protected static array $bypassEmails = [
        'test@example.com',
    ];

    /**
     * Check if an email is allowed for registration
     */
    public static function isAllowed(string $email): bool
    {
        // Check bypass list first
        if (in_array(strtolower($email), self::$bypassEmails)) {
            return true;
        }

        // Extract domain from email
        $domain = self::getDomain($email);

        return in_array($domain, self::$allowedDomains);
    }

    /**
     * Get the domain from an email address
     */
    public static function getDomain(string $email): string
    {
        $parts = explode('@', strtolower($email));
        return $parts[1] ?? '';
    }

    /**
     * Get a user-friendly error message
     */
    public static function getErrorMessage(): string
    {
        return 'Registration is restricted to UiTM accounts only. Please use your UiTM student email (e.g., 2024123456@isiswa.uitm.edu.my).';
    }

    /**
     * Get the list of allowed domains
     */
    public static function getAllowedDomains(): array
    {
        return self::$allowedDomains;
    }
}
