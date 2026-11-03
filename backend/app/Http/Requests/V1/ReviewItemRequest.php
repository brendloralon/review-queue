<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Enums\ItemStatus;
use App\Rules\PlainText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ReviewItemRequest extends FormRequest
{
    /**
     * Any known status passes validation; whether the move is allowed is decided by
     * ItemStatus::canTransitionTo(), so the review flow is defined in one place.
     *
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ItemStatus::class)],
            'note' => ['nullable', 'string', 'max:1000', PlainText::multiline()],
        ];
    }

    public function decision(): ItemStatus
    {
        return ItemStatus::from($this->validated('status'));
    }

    public function note(): ?string
    {
        return $this->validated('note');
    }
}
