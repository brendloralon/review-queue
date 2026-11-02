<?php

declare(strict_types=1);

namespace App\Moderation;

use App\Enums\SuggestedAction;
use App\Moderation\Rules\BlocklistedTermsRule;
use App\Moderation\Rules\ShoutingRule;

final class ModerationService
{
    public const int MIN_SCORE = 0;

    public const int MAX_SCORE = 100;

    public const int REVIEW_THRESHOLD = 30;

    public const int REJECT_THRESHOLD = 70;

    public function moderate(string $title, string $body): ModerationResult
    {
        $content = $title."\n".$body;
        $totalScore = 0;
        $flags = [];

        foreach ($this->rules() as $rule) {
            $result = $rule->evaluate($content);
            $totalScore += $result->score;
            $flags = [...$flags, ...$result->flags];
        }

        $riskScore = max(self::MIN_SCORE, min(self::MAX_SCORE, $totalScore));

        return new ModerationResult(
            $riskScore,
            array_values(array_unique($flags)),
            $this->suggestedActionFor($riskScore),
        );
    }

    public function suggestedActionFor(int $riskScore): SuggestedAction
    {
        return match (true) {
            $riskScore >= self::REJECT_THRESHOLD => SuggestedAction::Reject,
            $riskScore >= self::REVIEW_THRESHOLD => SuggestedAction::Review,
            default => SuggestedAction::Approve,
        };
    }

    /**
     * The single registry of active rules. Adding a rule means adding one line here.
     *
     * @return list<ModerationRule>
     */
    private function rules(): array
    {
        return [
            new BlocklistedTermsRule,
            new ShoutingRule,
        ];
    }
}
