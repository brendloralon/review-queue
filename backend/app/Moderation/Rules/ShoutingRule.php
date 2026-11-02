<?php

declare(strict_types=1);

namespace App\Moderation\Rules;

use App\Moderation\ModerationRule;
use App\Moderation\RuleResult;

final class ShoutingRule implements ModerationRule
{
    public const string FLAG = 'shouting';

    public const int SCORE = 20;

    /** Short texts like "OK" or "FAQ" would otherwise count as shouting. */
    public const int MIN_LETTERS = 20;

    public const float UPPERCASE_RATIO = 0.6;

    public function evaluate(string $content): RuleResult
    {
        $letterCount = preg_match_all('/\p{L}/u', $content);

        if ($letterCount < self::MIN_LETTERS) {
            return RuleResult::clean();
        }

        $uppercaseCount = preg_match_all('/\p{Lu}/u', $content);

        if ($uppercaseCount / $letterCount <= self::UPPERCASE_RATIO) {
            return RuleResult::clean();
        }

        return new RuleResult(self::SCORE, [self::FLAG]);
    }
}
