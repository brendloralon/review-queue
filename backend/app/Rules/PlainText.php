<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects characters that have no place in typed text: control characters (null bytes,
 * escape sequences), and invisible or reordering characters. Bidirectional overrides and
 * isolates can make text display in a different order than it is stored, so a reviewer
 * could read something other than what was submitted.
 *
 * HTML characters such as < and > are allowed on purpose: they appear in real reports
 * ("score < 30", pasted errors), and XSS is prevented where text is output, since Vue
 * escapes everything it renders.
 */
final class PlainText implements ValidationRule
{
    /**
     * Zero-width space, word joiner, byte order mark, and the bidirectional embedding,
     * override and isolate controls. Zero-width (non-)joiners and the left-to-right /
     * right-to-left marks stay allowed: emoji sequences and Hebrew or Arabic text need them.
     */
    public const string INVISIBLE_CHARACTERS = '\x{200B}\x{2060}\x{FEFF}\x{202A}-\x{202E}\x{2066}-\x{2069}';

    private function __construct(private readonly bool $allowsLineBreaks) {}

    public static function singleLine(): self
    {
        return new self(false);
    }

    public static function multiline(): self
    {
        return new self(true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! $this->allowsLineBreaks && preg_match('/[\r\n]/', $value) === 1) {
            $fail('The :attribute must be a single line.');

            return;
        }

        if ($this->containsDisallowedCharacters($value)) {
            $fail('The :attribute contains characters that are not allowed.');
        }
    }

    private function containsDisallowedCharacters(string $value): bool
    {
        // Tab, line feed and carriage return are the only control characters typed text uses.
        $controlCharacters = $this->allowsLineBreaks ? '[^\P{Cc}\t\n\r]' : '\p{Cc}';
        $pattern = '/'.$controlCharacters.'|['.self::INVISIBLE_CHARACTERS.']/u';

        // preg_match returns false for invalid UTF-8, which is rejected as well.
        return preg_match($pattern, $value) !== 0;
    }
}
