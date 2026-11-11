<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 15 challenges shown on the challenge wheel, exactly as written in
 * "Opsynvi_Spin_the_Wheel_Challenges.docx".
 *
 * The challenge number is the stable identifier, so the seeder can be re-run
 * safely: existing challenges are updated in place, never duplicated.
 */
class ChallengeSeeder extends Seeder
{
    public const int TOTAL_CHALLENGES = 15;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::challenges() as $number => [$title, $description]) {
                Challenge::updateOrCreate(
                    ['number' => $number],
                    ['title' => $title, 'description' => $description],
                );
            }
        });
    }

    /**
     * @return array<int, array{0: string, 1: string}> Title and instruction, keyed by challenge number.
     */
    public static function challenges(): array
    {
        return [
            1 => ['Sing It!', 'Sing the last song/track you played for 10 seconds.'],
            2 => ['Duet Time', 'Choose one teammate and sing one short chorus line together as a duet.'],
            3 => ['In just 5 seconds', 'Say your full name backwards as best you can.'],
            4 => ['Air Orchestra', 'Conduct an imaginary orchestra for 10 seconds while your team follows your movements.'],
            5 => ['Finish the Lyrics', 'Start a well-known song line and let your teammate finish it.'],
            6 => ['Backwards Challenge', 'Say: ‘Harmony brings us together’ backwards, one word at a time.'],
            7 => ['Tongue Twister', 'Say “A duet in one tablet” three times quickly without making a mistake.'],
            8 => ['Fast Five', 'In 10 seconds, name five musical instruments.'],
            9 => ['Rapid Rhythm', 'Clap a short rhythm. Your whole team must repeat it exactly.'],
            10 => ['One-word Duet', 'With a partner, alternate one word each to create a 10-word sentence about teamwork.'],
            11 => ['Complete the Harmony', 'Say “Unique duet, unified harmony” 3 times quickly.'],
            12 => ['Two Become One', 'Name 3 pairs that belong together in 5 seconds.'],
            13 => ['Beat the Buzzer', 'Complete “Actions speak louder than...”'],
            14 => ['Say It Differently', 'Say ‘A duet with one purpose’ in three different tones: serious, excited and dramatic.'],
            15 => ['Masterpieces', 'Name 3 famous musicians or composers.'],
        ];
    }
}
