<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Moderation\ModerationService;
use Illuminate\Database\Seeder;

/**
 * Hand-written items so every moderation rule and suggested action shows up in the
 * queue. Moderation runs through the real service, exactly as on submission.
 */
class ItemSeeder extends Seeder
{
    /**
     * @var list<array{title: string, body: string, submitter_name: string, hours_ago: int, review?: array{ItemStatus, ?string}}>
     */
    private const array ITEMS = [
        [
            'title' => 'Typo on the pricing page',
            'body' => 'The word "anually" in the Plans section should be "annually".',
            'submitter_name' => 'Maya Cohen',
            'hours_ago' => 1,
        ],
        [
            'title' => 'Dark mode toggle resets on reload',
            'body' => 'After enabling dark mode and refreshing, the site switches back to light mode.',
            'submitter_name' => 'Daniel Levi',
            'hours_ago' => 3,
        ],
        [
            'title' => 'Great tutorial, see my notes',
            'body' => 'I summarised the lesson here: https://notes.example.com/lesson-4',
            'submitter_name' => 'Noa Peretz',
            'hours_ago' => 5,
        ],
        [
            'title' => 'THE VIDEO PLAYER IS BROKEN AGAIN',
            'body' => 'IT STOPS AFTER TEN SECONDS EVERY SINGLE TIME. PLEASE FIX IT.',
            'submitter_name' => 'Avi Mizrahi',
            'hours_ago' => 8,
        ],
        [
            'title' => 'Limited time offer for students',
            'body' => 'Get premium study guides at half price this week only.',
            'submitter_name' => 'Promo Desk',
            'hours_ago' => 12,
        ],
        [
            'title' => 'Useful resources for the science unit',
            'body' => 'https://a.example.org/cells https://b.example.org/atoms www.example.net/energy',
            'submitter_name' => 'Tamar Shapiro',
            'hours_ago' => 20,
        ],
        [
            'title' => 'FREE MONEY CRYPTO GIVEAWAY',
            'body' => 'CLICK HERE NOW: https://win.example.biz https://win2.example.biz https://win3.example.biz',
            'submitter_name' => 'Winner Team',
            'hours_ago' => 26,
        ],
        [
            'title' => 'Quiz answer key is wrong',
            'body' => 'Question 3 in the fractions quiz marks the correct answer as incorrect.',
            'submitter_name' => 'Yossi Biton',
            'hours_ago' => 48,
            'review' => [ItemStatus::Approved, 'Confirmed with the content team; fix scheduled.'],
        ],
        [
            'title' => 'Buy now and save 90%',
            'body' => 'Best deal on followers, visit www.cheap-followers.example today.',
            'submitter_name' => 'Deals Bot',
            'hours_ago' => 60,
            'review' => [ItemStatus::Rejected, 'Advertising spam.'],
        ],
        [
            'title' => 'Captions out of sync',
            'body' => 'In the volcano video the captions are about two seconds behind the audio.',
            'submitter_name' => 'Rina Katz',
            'hours_ago' => 72,
            'review' => [ItemStatus::Approved, null],
        ],
    ];

    public function run(ModerationService $moderation): void
    {
        foreach (self::ITEMS as $sample) {
            $this->createItem($moderation, $sample);
        }
    }

    /**
     * @param  array{title: string, body: string, submitter_name: string, hours_ago: int, review?: array{ItemStatus, ?string}}  $sample
     */
    private function createItem(ModerationService $moderation, array $sample): void
    {
        $moderationResult = $moderation->moderate($sample['title'], $sample['body']);
        $submittedAt = now()->subHours($sample['hours_ago']);

        $item = Item::create([
            'title' => $sample['title'],
            'body' => $sample['body'],
            'submitter_name' => $sample['submitter_name'],
            ...$moderationResult->toAttributes(),
        ]);

        // Timestamps are not fillable, so backdate them directly to give the queue a realistic spread.
        $item->forceFill(['created_at' => $submittedAt, 'updated_at' => $submittedAt])->save();

        if (isset($sample['review'])) {
            [$decision, $note] = $sample['review'];
            $item->review($decision, $note);
        }
    }
}
