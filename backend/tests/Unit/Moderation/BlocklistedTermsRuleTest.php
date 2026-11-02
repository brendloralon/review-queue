<?php

declare(strict_types=1);

namespace Tests\Unit\Moderation;

use App\Moderation\Rules\BlocklistedTermsRule;
use PHPUnit\Framework\TestCase;

class BlocklistedTermsRuleTest extends TestCase
{
    public function test_content_without_terms_is_clean(): void
    {
        $result = (new BlocklistedTermsRule)->evaluate('Please fix the login button.');

        $this->assertSame(0, $result->score);
        $this->assertSame([], $result->flags);
    }

    public function test_a_term_is_matched_case_insensitively(): void
    {
        $result = (new BlocklistedTermsRule)->evaluate('CLICK HERE for details');

        $this->assertSame(40, $result->score);
        $this->assertSame(['blocklisted_terms'], $result->flags);
    }

    public function test_a_repeated_term_counts_once(): void
    {
        $result = (new BlocklistedTermsRule)->evaluate('buy now, buy now, buy now');

        $this->assertSame(40, $result->score);
    }

    public function test_each_distinct_term_adds_to_the_score(): void
    {
        $result = (new BlocklistedTermsRule)->evaluate('Free money! Click here and buy now.');

        $this->assertSame(120, $result->score);
        $this->assertSame(['blocklisted_terms'], $result->flags);
    }
}
