/**
 * Footnotes and references for each question, copied verbatim from the Figma
 * file ("UAE_J&J OPSYNVI® Internal Launch", frame "Desktop - 7 · References").
 */
export type QuestionReference = {
    question: number;
    /** Superscript shown after the answer explanation in the question pop-up. */
    marker: string;
    /** Footnote copy shown above the references, if any. */
    note?: string;
    /** One entry per numbered reference. */
    references: string[];
    /** Render the references on one line instead of one per line. */
    inline?: boolean;
};

const SMPC = 'Opsynvi UAE SmPC April 2025.';
const GRUNIG = 'Grunig E et al. J Am Coll Cardiol 2024; 83(4):473–484.';
const HUMBERT = 'Humbert M et al. Eur Heart J 2022; 43(38):3618–3731.';
const ESC_ERS_NOTE =
    '*Class I level B recommendation by the 2022 ESC/ERS guidelines. In patients with idiopathic, heritable or drug-associated pulmonary arterial hypertension. ERA + PDE-5i for low- or intermediate-risk patients, and ERA + PDE-5i + parenteral prostacyclin analogues for high-risk patients.¹';

export const questionReferences: QuestionReference[] = [
    { question: 1, marker: '1', references: [SMPC] },
    {
        question: 2,
        marker: '1–5',
        references: [
            SMPC,
            GRUNIG,
            'Paoli CJ et al. J Health Econ Outcomes Res 2024; 11(1):8–22.',
            'Davis S et al. Presented at the International Society for Pharmacoeconomics and Outcomes Research (ISPOR) Europe, 12–15 November 2023; Copenhagen, Denmark (Poster no. PCR57).',
            HUMBERT,
        ],
    },
    { question: 3, marker: '1', references: [SMPC] },
    {
        question: 4,
        marker: '*,†,‡,§,1',
        note: '*Data from the COMPERA Registry, a large European pulmonary hypertension registry used to assess temporal trends in the use of combination therapy and survival of patients with newly diagnosed PAH between 2010 and 2019 (N=2531).²˒⁴ †In 2019.² ‡Between 2015 and 2019.⁴ §For patients with idiopathic/heritable/drug-associated PAH, no comorbidities and diffusing capacity for carbon monoxide >45%.⁴',
        references: ['Hoeper MM et al. Eur Respir J 2022; 59(6):2102024.'],
    },
    {
        question: 5,
        marker: '*,†,1',
        note: '*Between 2015 and 2019.¹ †For patients with idiopathic/heritable/drug-associated PAH, no comorbidities and diffusing capacity for carbon monoxide >45%.¹',
        references: [
            'Hoeper MM et al. Eur Respir J 2022; 59(6):2102024 (supplementary appendix).',
        ],
    },
    {
        question: 6,
        marker: '*,1',
        note: '*Systematic review of 14 studies including MEDLINE, EMBASE, and the Cochrane Library databases from their inception to 4 March 2022, for observational studies reporting on adherence to and persistence with PAH-targeted therapies. 10 of these studies reported on adherence rates, involving a total of 11,303 patients.¹',
        references: ['Qadus S et al. Am J Cardiovasc Drugs 2023; 23(1):19–33.'],
    },
    {
        question: 7,
        marker: '*,1',
        note: '*Adherence defined by proportion of days covered at 180 days. The mean number of hospitalisations per patient is presented.²',
        references: ['Frantz RP et al. Pulm Circ 2020; 10(1):1–11.'],
    },
    { question: 8, marker: '*,1', note: ESC_ERS_NOTE, references: [HUMBERT] },
    { question: 9, marker: '1', references: [HUMBERT] },
    { question: 10, marker: '*,1', note: ESC_ERS_NOTE, references: [HUMBERT] },
    { question: 11, marker: '1', references: [HUMBERT] },
    {
        question: 12,
        marker: '*,1',
        note: '*Primary endpoint: change in PVR expressed as the ratio of geometric means of end of double-blind treatment (Week 16) to baseline.¹',
        references: [GRUNIG],
    },
    {
        question: 13,
        marker: '*,1',
        note: '*Secondary endpoint: change in 6MWD from baseline to Week 16.¹',
        references: [GRUNIG],
    },
    { question: 14, marker: '1', references: [GRUNIG] },
    {
        question: 15,
        marker: '1–3',
        references: [
            SMPC,
            GRUNIG,
            'ADCIRCA SmPC, September 2023. Available at: https://www.ema.europa.eu/en/documents/product-information/adcirca-epar-product-information_en.pdf (last accessed March 2024).',
        ],
    },
];

export function referenceMarker(questionNumber: number): string | null {
    return (
        questionReferences.find((entry) => entry.question === questionNumber)
            ?.marker ?? null
    );
}
