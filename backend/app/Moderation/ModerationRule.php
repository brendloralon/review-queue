<?php

declare(strict_types=1);

namespace App\Moderation;

interface ModerationRule
{
    /**
     * Rules are pure: they look only at the submitted text, so each one can be
     * unit tested with a string and reasoned about in isolation.
     */
    public function evaluate(string $content): RuleResult;
}
