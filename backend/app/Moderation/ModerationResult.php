<?php

declare(strict_types=1);

namespace App\Moderation;

use App\Enums\SuggestedAction;

final readonly class ModerationResult
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public int $riskScore,
        public array $flags,
        public SuggestedAction $suggestedAction,
    ) {}

    /**
     * @return array{risk_score: int, flags: list<string>, suggested_action: SuggestedAction}
     */
    public function toAttributes(): array
    {
        return [
            'risk_score' => $this->riskScore,
            'flags' => $this->flags,
            'suggested_action' => $this->suggestedAction,
        ];
    }
}
