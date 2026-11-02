<?php

namespace App\Enums;

enum ChallengeOutcome: string
{
    case Accepted = 'accepted';
    case Declined = 'declined';
}
