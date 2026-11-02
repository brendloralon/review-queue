<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Enums\ItemStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BulkReviewItemsRequest extends FormRequest
{
    /** Keeps one request bounded; matches what a reviewer can select on one screen. */
    public const int MAX_ITEMS = 50;

    /**
     * Unknown or repeated ids make the whole request malformed (422). Whether each item may
     * move to the decision is checked per item by ItemStatus, like the single review endpoint.
     *
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:items,id'],
            'status' => ['required', Rule::enum(ItemStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return list<int>
     */
    public function itemIds(): array
    {
        return array_map(intval(...), $this->validated('ids'));
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
