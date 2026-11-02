<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 15 game questions exactly as written in the Figma file
 * ("UAE_J&J OPSYNVI® Internal Launch", frames "Desktop - 3 · Question 01–15").
 *
 * The question number is the stable identifier, so the seeder can be re-run
 * safely: existing questions and options are updated in place, never duplicated.
 */
class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::questions() as $number => $data) {
                $question = Question::updateOrCreate(
                    ['number' => $number],
                    ['prompt' => $data['prompt'], 'explanation' => $data['explanation']],
                );

                foreach ($data['options'] as $letter => $text) {
                    $question->options()->updateOrCreate(
                        ['letter' => $letter],
                        ['text' => $text, 'is_correct' => $letter === $data['correct']],
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array{prompt: string, options: array<string, string>, correct: string, explanation: string}>
     */
    public static function questions(): array
    {
        return [
            1 => [
                'prompt' => 'What are the two active pharmaceutical ingredients combined in Opsynvi and their respective pharmacological classes?',
                'options' => [
                    'A' => 'Macitentan (PDE-5 inhibitor) and tadalafil (endothelin receptor antagonist)',
                    'B' => 'Macitentan (endothelin receptor antagonist) and tadalafil (PDE-5 inhibitor)',
                    'C' => 'Bosentan (endothelin receptor antagonist) and sildenafil (PDE-5 inhibitor)',
                    'D' => 'Macitentan (prostacyclin analogue) and tadalafil (PDE-5 inhibitor)',
                ],
                'correct' => 'B',
                'explanation' => 'Opsynvi combines macitentan, an endothelin receptor antagonist (ERA), and tadalafil, a phosphodiesterase 5 (PDE-5) inhibitor.',
            ],
            2 => [
                'prompt' => 'What is the maintenance dose of Opsynvi used to facilitate double combination therapy in alignment with the 2022 ESC/ERS guidelines?',
                'options' => [
                    'A' => 'Macitentan 5 mg + tadalafil 20 mg once daily',
                    'B' => 'Macitentan 10 mg + tadalafil 40 mg once daily',
                    'C' => 'Macitentan 10 mg + tadalafil 20 mg twice daily',
                    'D' => 'Macitentan 20 mg + tadalafil 40 mg once daily',
                ],
                'correct' => 'B',
                'explanation' => 'Opsynvi delivers macitentan 10 mg + tadalafil 40 mg in a once-daily single tablet.',
            ],
            3 => [
                'prompt' => 'For which patient groups is the lower Opsynvi 10 mg/20 mg dose specifically used during initiation/titration?',
                'options' => [
                    'A' => 'Patients transitioning from PDE-5i monotherapy only',
                    'B' => 'Patients who are treatment-naïve to any PAH-specific therapy or transitioning from ERA monotherapy',
                    'C' => 'Patients with severe hepatic impairment (Child-Pugh Class C)',
                    'D' => 'Patients undergoing dialysis or with severe renal impairment',
                ],
                'correct' => 'B',
                'explanation' => 'The 10 mg/20 mg dose is used for patients who are treatment-naïve to any PAH-specific therapy or transitioning from ERA monotherapy.',
            ],
            4 => [
                'prompt' => 'In the COMPERA registry, what percentage of all PAH patients remained on monotherapy at 3 months and at 1 year after diagnosis?',
                'options' => [
                    'A' => '50.6% at 3 months and 25.0% at 1 year',
                    'B' => '60.9% at 3 months and 40.0% at 1 year',
                    'C' => '75.0% at 3 months and 52.4% at 1 year',
                    'D' => '85.0% at 3 months and 62.9% at 1 year',
                ],
                'correct' => 'C',
                'explanation' => 'In the COMPERA registry, 75.0% of all PAH patients were on monotherapy at 3 months and 52.4% at 1 year after diagnosis.',
            ],
            5 => [
                'prompt' => 'Even among PAH patients without comorbidities in the COMPERA registry, what proportion were still receiving monotherapy at 3 months post-diagnosis?',
                'options' => [
                    'A' => '25.0%',
                    'B' => '39.3%',
                    'C' => '50.6%',
                    'D' => '75.0%',
                ],
                'correct' => 'C',
                'explanation' => '50.6% of PAH patients without comorbidities remained on monotherapy at 3 months in the COMPERA registry.',
            ],
            6 => [
                'prompt' => 'According to real-world systematic review data cited in the detail aid, approximately what percentage of PAH patients do NOT adhere to their PAH-targeted medications?',
                'options' => [
                    'A' => '~15%',
                    'B' => '~25%',
                    'C' => '~40%',
                    'D' => '~60%',
                ],
                'correct' => 'C',
                'explanation' => 'Approximately 40% of patients do not adhere to their PAH-targeted medications in the real world (overall pooled adherence was 60.9%).',
            ],
            7 => [
                'prompt' => 'In the retrospective database analysis of PAH adherence at 180 days, what was the mean number of hospitalisations per patient for those with ≥ 80% adherence compared to those with < 20% adherence?',
                'options' => [
                    'A' => '0.1 vs. 0.8 hospitalisations',
                    'B' => '0.4 vs. 1.3 hospitalisations',
                    'C' => '0.8 vs. 2.1 hospitalisations',
                    'D' => '1.0 vs. 3.0 hospitalisations',
                ],
                'correct' => 'B',
                'explanation' => 'Patients with ≥ 80% adherence had a mean of 0.4 hospitalisations compared to 1.3 hospitalisations for patients with < 20% adherence at 180 days.',
            ],
            8 => [
                'prompt' => 'What class and level of recommendation does initial macitentan + tadalafil combination therapy hold in the 2022 ESC/ERS guidelines for idiopathic, heritable, or drug-associated PAH patients without cardiopulmonary comorbidities at low or intermediate risk?',
                'options' => [
                    'A' => 'Class I, Level A',
                    'B' => 'Class I, Level B',
                    'C' => 'Class IIa, Level B',
                    'D' => 'Class IIb, Level C',
                ],
                'correct' => 'B',
                'explanation' => 'Macitentan + tadalafil holds the highest class of recommendation (Class I, Level B) in the 2022 ESC/ERS guidelines.',
            ],
            9 => [
                'prompt' => 'Which of the following is NOT one of the 5 foundational benefits of initial ERA + PDE-5i combination therapy listed on the 2022 ESC/ERS Guidelines slide?',
                'options' => [
                    'A' => 'Relieves symptoms and increases exercise capacity',
                    'B' => 'Improves long-term outcomes (reduced morbidity and mortality)',
                    'C' => 'Reverses genetic mutations in heritable PAH',
                    'D' => 'Improves cardiac biomarkers and reduces hospitalisations',
                ],
                'correct' => 'C',
                'explanation' => 'The 5 listed benefits are: relieves symptoms, improves long-term outcomes (reduced morbidity and mortality), improves cardiac biomarkers, increases exercise capacity, and reduces hospitalisations.',
            ],
            10 => [
                'prompt' => 'For PAH patients currently receiving monotherapy, why do the 2022 ESC/ERS guidelines strongly recommend adding macitentan to PDE-5is or prostacyclin analogues?',
                'options' => [
                    'A' => 'To eliminate the need for right heart catheterisation',
                    'B' => 'To reduce the risk of mortality/morbidity events',
                    'C' => 'To allow immediate discontinuation of background PDE-5i therapy',
                    'D' => 'To treat severe hepatic impairment',
                ],
                'correct' => 'B',
                'explanation' => 'Adding macitentan to PDE-5is or prostacyclin analogues is strongly recommended (Class I, Level B) to reduce the risk of mortality/morbidity events.',
            ],
            11 => [
                'prompt' => 'According to the 2022 ESC/ERS guidelines footnote, what initial regimen is recommended for high-risk PAH patients without cardiopulmonary comorbidities?',
                'options' => [
                    'A' => 'ERA monotherapy only',
                    'B' => 'PDE-5i monotherapy only',
                    'C' => 'ERA + PDE-5i + parenteral prostacyclin analogues',
                    'D' => 'Oral prostacyclin analogue monotherapy',
                ],
                'correct' => 'C',
                'explanation' => 'The guidelines recommend ERA + PDE-5i for low- or intermediate-risk patients, and ERA + PDE-5i + parenteral prostacyclin analogues for high-risk patients.',
            ],
            12 => [
                'prompt' => 'In the A DUE study, Opsynvi reduced PVR by up to what percentage from baseline at Week 16?',
                'options' => [
                    'A' => 'Up to 23%',
                    'B' => 'Up to 29%',
                    'C' => 'Up to 45%',
                    'D' => 'Up to 60%',
                ],
                'correct' => 'C',
                'explanation' => 'Opsynvi reduced PVR by up to 45% (-45% in the comparison group vs. macitentan and -44% in the comparison group vs. tadalafil) after 16 weeks vs. baseline.',
            ],
            13 => [
                'prompt' => 'In the A DUE study, Opsynvi improved 6-Minute Walk Distance (6MWD) by up to how many metres from baseline at Week 16?',
                'options' => [
                    'A' => 'Up to +15.9 m',
                    'B' => 'Up to +25.4 m',
                    'C' => 'Up to +38.5 m',
                    'D' => 'Up to +52.9 m',
                ],
                'correct' => 'D',
                'explanation' => 'Opsynvi improved 6MWD by up to +52.9 m at Week 16 vs. baseline (+52.9 m in the macitentan comparison arm and +43.4 m in the tadalafil comparison arm).',
            ],
            14 => [
                'prompt' => 'What percentage of all patients randomised to Opsynvi in the A DUE study completed the 16-week study treatment?',
                'options' => [
                    'A' => '76.6%',
                    'B' => '84.0%',
                    'C' => '91.7%',
                    'D' => '98.2%',
                ],
                'correct' => 'C',
                'explanation' => '91.7% of all patients randomised to Opsynvi completed the study treatment.',
            ],
            15 => [
                'prompt' => 'How does Opsynvi achieve the “3 Into 1 Daily” pill burden reduction compared to loose combination therapy?',
                'options' => [
                    'A' => 'By replacing 3 daily tablets of macitentan 3.3 mg with 1 tablet',
                    'B' => 'By replacing 1x macitentan 10 mg tablet + 2x tadalafil 20 mg tablets with 1 daily Opsynvi 10 mg/40 mg tablet',
                    'C' => 'By replacing morning, afternoon, and evening doses with a once-weekly tablet',
                    'D' => 'By combining an ERA, a PDE-5i, and a prostacyclin analogue into 1 tablet',
                ],
                'correct' => 'B',
                'explanation' => 'Loose combination therapy requires 1 tablet of macitentan 10 mg plus 2 tablets of tadalafil 20 mg (3 tablets daily), whereas Opsynvi combines macitentan 10 mg + tadalafil 40 mg into 1 daily tablet.',
            ],
        ];
    }
}
