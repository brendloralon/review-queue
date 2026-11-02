<?php

declare(strict_types=1);

namespace App\Moderation\Rules;

use App\Moderation\ModerationRule;
use App\Moderation\RuleResult;

final class BlocklistedTermsRule implements ModerationRule
{
    public const string FLAG = 'blocklisted_terms';

    public const int SCORE_PER_TERM = 40;

    /**
     * Matched case-insensitively as substrings. Phrases are preferred over single
     * words to keep false positives low.
     */
    public const array TERMS = [
        'free money',
        'click here',
        'buy now',
        'limited time offer',
        'crypto giveaway',
    ];

    public function evaluate(string $content): RuleResult
    {
        $matchedTerms = $this->matchedTerms(mb_strtolower($content));

        if ($matchedTerms === []) {
            return RuleResult::clean();
        }

        return new RuleResult(count($matchedTerms) * self::SCORE_PER_TERM, [self::FLAG]);
    }

    /**
     * @return list<string>
     */
    private function matchedTerms(string $lowercaseContent): array
    {
        return array_values(array_filter(
            self::TERMS,
            fn (string $term): bool => str_contains($lowercaseContent, $term),
        ));
    }
}
