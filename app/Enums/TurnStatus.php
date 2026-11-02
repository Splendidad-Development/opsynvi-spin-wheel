<?php

namespace App\Enums;

enum TurnStatus: string
{
    /** The question was drawn for the team and is waiting for an answer. */
    case QuestionOpened = 'question_opened';

    /** The team answered; the result is revealed and the turn awaits "Done" (or a challenge after a wrong answer). */
    case Answered = 'answered';

    /** The team took a challenge and the host has not yet recorded the outcome. */
    case ChallengePending = 'challenge_pending';

    /** The turn is resolved and its points are final. */
    case Completed = 'completed';

    public function isResolved(): bool
    {
        return $this === self::Completed;
    }
}
