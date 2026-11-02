<?php

declare(strict_types=1);

namespace Tests\Unit\Moderation;

use App\Moderation\Rules\ShoutingRule;
use PHPUnit\Framework\TestCase;

class ShoutingRuleTest extends TestCase
{
    public function test_mostly_uppercase_text_is_flagged(): void
    {
        $result = (new ShoutingRule)->evaluate('THIS APP IS BROKEN AND NOBODY CARES');

        $this->assertSame(20, $result->score);
        $this->assertSame(['shouting'], $result->flags);
    }

    public function test_normal_text_is_clean(): void
    {
        $result = (new ShoutingRule)->evaluate('The FAQ page links to an old PDF and the API docs.');

        $this->assertSame(0, $result->score);
        $this->assertSame([], $result->flags);
    }

    public function test_short_uppercase_text_is_clean(): void
    {
        $result = (new ShoutingRule)->evaluate('HELP ME PLEASE');

        $this->assertSame(0, $result->score);
    }

    public function test_exactly_sixty_percent_uppercase_is_clean(): void
    {
        // 12 uppercase out of 20 letters: the ratio must exceed 60% to count.
        $result = (new ShoutingRule)->evaluate('ABCDEFGHIJKLmnopqrst');

        $this->assertSame(0, $result->score);
    }

    public function test_non_latin_uppercase_letters_count(): void
    {
        $result = (new ShoutingRule)->evaluate('ЭТО ПРИЛОЖЕНИЕ НЕ РАБОТАЕТ СОВСЕМ');

        $this->assertSame(['shouting'], $result->flags);
    }
}
