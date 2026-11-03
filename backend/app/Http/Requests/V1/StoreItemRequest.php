<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Rules\PlainText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    /**
     * Letters (any script, with accents), spaces, apostrophes, hyphens and periods, starting
     * with a letter: covers names like "Anne-Marie O'Brien" or "Dr. Levi" and nothing else.
     */
    public const string PERSON_NAME_PATTERN = "/^[\p{L}\p{M}][\p{L}\p{M} '’.\-]*$/u";

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120', PlainText::singleLine()],
            'body' => ['required', 'string', 'max:5000', PlainText::multiline()],
            'submitter_name' => ['required', 'string', 'max:80', 'regex:'.self::PERSON_NAME_PATTERN],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'submitter_name.regex' => 'The submitter name may only contain letters, spaces, apostrophes, hyphens and periods.',
        ];
    }
}
