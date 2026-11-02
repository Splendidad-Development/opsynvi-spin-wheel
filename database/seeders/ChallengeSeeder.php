<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

/**
 * Seeds the 15 challenge slots shown on the challenge wheel.
 *
 * The Figma file only contains placeholder copy ("Lorem Ipsum…") for the
 * challenges, so descriptions are left empty rather than invented. Existing
 * rows are never overwritten, so descriptions filled in later survive re-seeding.
 */
class ChallengeSeeder extends Seeder
{
    public const int TOTAL_CHALLENGES = 15;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (range(1, self::TOTAL_CHALLENGES) as $number) {
            Challenge::firstOrCreate(['number' => $number]);
        }
    }
}
