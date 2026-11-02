<?php

declare(strict_types=1);

namespace Tests\Unit\Moderation;

use App\Enums\SuggestedAction;
use App\Moderation\ModerationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModerationServiceTest extends TestCase
{
    public function test_clean_content_scores_zero_and_is_suggested_for_approval(): void
    {
        $result = (new ModerationService)->moderate('Typo on the help page', 'The word "recieve" is misspelled.');

        $this->assertSame(0, $result->riskScore);
        $this->assertSame([], $result->flags);
        $this->assertSame(SuggestedAction::Approve, $result->suggestedAction);
    }

    /**
     * @return array<string, array{int, SuggestedAction}>
     */
    public static function thresholds(): array
    {
        return [
            'zero' => [0, SuggestedAction::Approve],
            'just below review' => [29, SuggestedAction::Approve],
            'review threshold' => [30, SuggestedAction::Review],
            'just below reject' => [69, SuggestedAction::Review],
            'reject threshold' => [70, SuggestedAction::Reject],
            'maximum' => [100, SuggestedAction::Reject],
        ];
    }

    #[DataProvider('thresholds')]
    public function test_suggested_action_follows_thresholds(int $riskScore, SuggestedAction $expected): void
    {
        $this->assertSame($expected, (new ModerationService)->suggestedActionFor($riskScore));
    }
}
