<?php

declare(strict_types=1);

namespace App\Moderation\Rules;

use App\Moderation\ModerationRule;
use App\Moderation\RuleResult;

final class LinksRule implements ModerationRule
{
    public const string FLAG_SOME_LINKS = 'contains_links';

    public const string FLAG_MANY_LINKS = 'many_links';

    public const int SCORE_SOME_LINKS = 15;

    public const int SCORE_MANY_LINKS = 35;

    public const int MANY_LINKS_THRESHOLD = 3;

    private const string URL_PATTERN = '~\bhttps?://\S+|\bwww\.\S+~i';

    public function evaluate(string $content): RuleResult
    {
        $linkCount = preg_match_all(self::URL_PATTERN, $content);

        return match (true) {
            $linkCount >= self::MANY_LINKS_THRESHOLD => new RuleResult(self::SCORE_MANY_LINKS, [self::FLAG_MANY_LINKS]),
            $linkCount > 0 => new RuleResult(self::SCORE_SOME_LINKS, [self::FLAG_SOME_LINKS]),
            default => RuleResult::clean(),
        };
    }
}
