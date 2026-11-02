<?php

declare(strict_types=1);

namespace App\Enums;

enum SuggestedAction: string
{
    case Approve = 'approve';
    case Review = 'review';
    case Reject = 'reject';
}
