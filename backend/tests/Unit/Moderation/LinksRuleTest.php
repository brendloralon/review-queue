<?php

declare(strict_types=1);

namespace Tests\Unit\Moderation;

use App\Moderation\Rules\LinksRule;
use PHPUnit\Framework\TestCase;

class LinksRuleTest extends TestCase
{
    public function test_content_without_links_is_clean(): void
    {
        $result = (new LinksRule)->evaluate('Contact support by email.');

        $this->assertSame(0, $result->score);
        $this->assertSame([], $result->flags);
    }

    public function test_one_or_two_links_are_flagged_lightly(): void
    {
        $result = (new LinksRule)->evaluate('See https://example.com and www.example.org for details.');

        $this->assertSame(15, $result->score);
        $this->assertSame(['contains_links'], $result->flags);
    }

    public function test_three_or_more_links_are_flagged_heavily(): void
    {
        $result = (new LinksRule)->evaluate('http://a.test http://b.test HTTPS://C.TEST');

        $this->assertSame(35, $result->score);
        $this->assertSame(['many_links'], $result->flags);
    }

    public function test_domain_names_without_scheme_or_www_are_not_links(): void
    {
        $result = (new LinksRule)->evaluate('I use example.com for testing.');

        $this->assertSame(0, $result->score);
    }
}
