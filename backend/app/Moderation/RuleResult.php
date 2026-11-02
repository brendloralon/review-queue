<?php

declare(strict_types=1);

namespace App\Moderation;

final readonly class RuleResult
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public int $score,
        public array $flags,
    ) {}

    public static function clean(): self
    {
        return new self(0, []);
    }
}
