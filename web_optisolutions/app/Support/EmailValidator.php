<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Stricter email check for the chatbot. A syntactically valid address is not
 * enough: random, placeholder, disposable and non-existent-domain addresses
 * are rejected too. Returns an error message, or null when the address is OK.
 *
 * Note: no check can prove a mailbox exists without actually emailing it.
 * This blocks made-up domains, throw-away providers and obvious junk.
 */
class EmailValidator
{
    // Throw-away / temporary inbox providers.
    private const DISPOSABLE = [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org', 'sharklasers.com',
        '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'tempmail.net',
        'tempmailo.com', 'throwawaymail.com', 'yopmail.com', 'yopmail.net', 'trashmail.com', 'trashmail.net',
        'getnada.com', 'nada.email', 'maildrop.cc', 'dispostable.com', 'fakeinbox.com', 'mailnesia.com',
        'mintemail.com', 'mytemp.email', 'moakt.com', 'emailondeck.com', 'spamgourmet.com', 'mohmal.com',
        'burnermail.io', 'discard.email', 'discardmail.com', 'getairmail.com', 'mailcatch.com', 'mail-temp.com',
        'tmail.ws', 'tmpmail.org', 'tmpmail.net', 'inboxkitten.com', 'mailpoof.com', 'emailfake.com',
        'fakemail.net', 'fakemailgenerator.com', 'anonbox.net', '33mail.com', 'spambox.us', 'mailsac.com',
    ];

    // Placeholder / reserved domains nobody actually uses for mail.
    private const PLACEHOLDER_DOMAINS = [
        'example.com', 'example.org', 'example.net', 'test.com', 'test.test', 'domain.com', 'email.com',
        'mail.test', 'yourdomain.com', 'sample.com', 'abc.com', 'xyz.com', 'asdf.com', 'invalid.com',
        'localhost', 'noemail.com', 'none.com', 'fake.com', 'nomail.com',
    ];

    // Local parts that are obviously not a real person's address.
    private const JUNK_LOCALS = [
        'test', 'tester', 'testing', 'test123', 'asdf', 'asdfgh', 'asdfghjkl', 'qwerty', 'qwertyuiop', 'zxcvbn',
        'sample', 'example', 'email', 'mail', 'abc', 'abcd', 'abcde', 'abc123', 'xyz', 'xxx', 'none', 'noemail',
        'nomail', 'na', 'n.a', 'fake', 'fakeemail', 'dummy', 'random', 'sdfsdf', 'aaa', 'aaaa', 'aaaaa',
        'name', 'yourname', 'user', 'username', 'asd', 'asdasd', 'qwe', 'qweqwe', 'wasd', 'hello', 'hi',
        'trash', 'spam', 'temp', 'temporary', 'throwaway', 'nobody', 'noreply', 'no-reply', 'donotreply',
    ];

    public static function validate(string $email): ?string
    {
        $email = trim($email);

        if ($email === '') return 'Email address cannot be empty.';
        if (strlen($email) > 254) return 'That email address is too long.';
        if (preg_match('/\s/', $email)) return 'Email address cannot contain spaces.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Please enter a valid email address.';
        if (str_contains($email, '..')) return 'Email address cannot contain consecutive dots.';

        [$local, $domain] = explode('@', strtolower($email), 2);

        if (strlen($local) > 64) return 'That email address is not valid.';
        if (!str_contains($domain, '.')) return 'Please enter a valid email address with a proper domain (e.g. gmail.com).';
        if (!preg_match('/\.[a-z]{2,}$/', $domain)) return 'That email domain does not look valid.';

        if (in_array($domain, self::PLACEHOLDER_DOMAINS, true)) {
            return 'Please use your real email address, not a sample or placeholder one.';
        }
        if (self::isDisposable($domain)) {
            return 'Temporary or disposable email addresses are not accepted. Please use your real email address.';
        }
        if (self::looksRandom($local)) {
            return 'That doesn\'t look like a real email address. Please use the email you actually check.';
        }
        if (!self::domainCanReceiveMail($domain)) {
            return 'We couldn\'t find that email domain. Please check for typos (e.g. gmail.com).';
        }

        return null;
    }

    private static function isDisposable(string $domain): bool
    {
        foreach (self::DISPOSABLE as $bad) {
            if ($domain === $bad || str_ends_with($domain, '.' . $bad)) return true;
        }
        return false;
    }

    // Keyboard-mash, placeholder words and single-character spam in the part before the @.
    private static function looksRandom(string $local): bool
    {
        $plain = preg_replace('/[^a-z]/', '', $local); // letters only
        $stripped = preg_replace('/\d+$/', '', $local); // "test123" -> "test"

        if (in_array($local, self::JUNK_LOCALS, true) || in_array($stripped, self::JUNK_LOCALS, true)) return true;

        // aaaaa, 11111, a.a.a.a.a
        $compact = preg_replace('/[._\-+]/', '', $local);
        if (strlen($compact) >= 4 && preg_match('/^(.)\1+$/', $compact)) return true;
        if (preg_match('/(.)\1{4,}/', $local)) return true;

        // Only digits (e.g. 09123456789@gmail.com is fine for some, but 1234567@... is junk)
        if (preg_match('/^\d{1,}$/', $compact) && strlen($compact) < 8) return true;

        // Keyboard rows and alphabet runs.
        foreach (['qwertyuiop', 'asdfghjkl', 'zxcvbnm', 'abcdefghij', '1234567890'] as $row) {
            if (strlen($plain) >= 4 && str_contains($row, $plain)) return true;
            if (strlen($plain) >= 5 && str_contains($plain, substr($row, 0, 5))) return true;
        }

        // Long runs of consonants with no vowel at all (e.g. "xkcjhqwz").
        if (strlen($plain) >= 7 && !preg_match('/[aeiouy]/', $plain)) return true;
        if (preg_match('/[^aeiou\W\d_]{7,}/', $plain)) return true;

        return false;
    }

    // The domain must exist and be set up to receive mail (MX, or A as a fallback).
    // Skipped when DNS lookups are switched off or unavailable on the server.
    private static function domainCanReceiveMail(string $domain): bool
    {
        if (!config('clinic.email_dns_check', true) || !function_exists('checkdnsrr')) return true;

        return Cache::remember('email-domain-ok:' . $domain, now()->addHours(12), function () use ($domain) {
            return @checkdnsrr($domain . '.', 'MX') || @checkdnsrr($domain . '.', 'A') || @checkdnsrr($domain . '.', 'AAAA');
        });
    }
}