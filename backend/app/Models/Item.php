<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\SuggestedAction;
use App\Exceptions\InvalidStatusTransition;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * Review fields (status, review_note, reviewed_at) are deliberately not fillable:
     * they change only through the review action, which enforces transitions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'submitter_name',
        'risk_score',
        'flags',
        'suggested_action',
    ];

    /**
     * Mirrors the column default so a freshly created model reports its status
     * without being reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @throws InvalidStatusTransition when the flow forbids the move, including
     *                                 when another request reviewed the item first
     */
    public function review(ItemStatus $decision, ?string $note): void
    {
        if (! $this->status->canTransitionTo($decision)) {
            throw InvalidStatusTransition::for($this, $decision);
        }

        // Conditional update instead of save(): if another reviewer changed the status
        // after this model was loaded, no row matches and their review is kept.
        $updatedRows = static::query()
            ->whereKey($this->getKey())
            ->where('status', $this->status->value)
            ->update([
                'status' => $decision->value,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

        $this->refresh();

        if ($updatedRows === 0) {
            throw InvalidStatusTransition::for($this, $decision);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ItemStatus::class,
            'suggested_action' => SuggestedAction::class,
            'risk_score' => 'integer',
            'flags' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
