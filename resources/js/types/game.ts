export type GameStatus = 'in_progress' | 'completed' | 'abandoned';

export type TurnStatus =
    | 'question_opened'
    | 'answered'
    | 'challenge_pending'
    | 'completed';

export type ChallengeOutcome = 'accepted' | 'declined';

export type GameTeam = {
    id: number;
    name: string;
    position: number;
    score: number;
};

export type QuestionOption = {
    id: number;
    letter: string;
    text: string;
};

export type GameTurn = {
    id: number;
    turn_number: number;
    team_id: number;
    team_turn_number: number;
    status: TurnStatus;
    points: number;
    can_take_challenge: boolean;
    question: {
        number: number;
        prompt: string;
        options: QuestionOption[];
    };
    selected_option_id: number | null;
    is_correct: boolean | null;
    correct_option_id: number | null;
    explanation: string | null;
    challenge: {
        number: number;
        title: string | null;
        description: string | null;
    } | null;
    challenge_outcome: ChallengeOutcome | null;
};

export type LeaderboardEntry = {
    team_id: number;
    name: string;
    score: number;
    rank: number;
    is_tied: boolean;
};

export type GameState = {
    id: number;
    status: GameStatus;
    is_completed: boolean;
    total_questions: number;
    questions_per_team: number;
    points_per_question: number;
    completed_turns: number;
    /** The team part-way through its questions; null while the host picks the next team. */
    current_team_id: number | null;
    /** Teams the host may pick next; empty while a team is in play. */
    selectable_team_ids: number[];
    active_turn_id: number | null;
    teams: GameTeam[];
    turns: GameTurn[];
    leaderboard: LeaderboardEntry[];
    started_at: string | null;
    completed_at: string | null;
};
