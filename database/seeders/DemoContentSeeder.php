<?php

namespace Database\Seeders;

use App\Content\ImageStore;
use App\Content\LessonService;
use App\Models\Content\Board;
use App\Models\Content\Chapter;
use App\Models\Content\MockExam;
use App\Models\Content\PaperTemplate;
use App\Models\Content\Subject;
use App\Models\Content\Topic;
use Illuminate\Database\Seeder;

/**
 * A small, realistic sample of content for local development and for
 * checking the design. Not used in production: real content is added
 * through the admin panel or the API.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $boards = Board::pluck('id', 'slug');
        $biology = Subject::where('slug', 'biology')->firstOrFail();
        $physics = Subject::where('slug', 'physics')->firstOrFail();
        $lessons = app(LessonService::class);

        app(ImageStore::class)->store($this->animalCellSvg(), 'animal-cell', 'Labelled diagram of an animal cell', replace: true);

        $cells = Topic::firstOrCreate(['subject_id' => $biology->id, 'slug' => 'cell-biology'], [
            'title' => 'Cell biology',
            'description' => 'What cells are made of, how they are seen with microscopes, and how they divide.',
            'position' => 1,
        ]);
        Topic::firstOrCreate(['subject_id' => $biology->id, 'slug' => 'organisation'], ['title' => 'Organisation', 'position' => 2]);

        $structure = Chapter::firstOrCreate(['topic_id' => $cells->id, 'slug' => 'cell-structure'], ['title' => 'Cell structure', 'position' => 1]);
        $division = Chapter::firstOrCreate(['topic_id' => $cells->id, 'slug' => 'cell-division'], ['title' => 'Cell division', 'position' => 2]);

        $all = $boards->values()->all();

        $eukaryotic = $lessons->create([
            'chapter_id' => $structure->id,
            'title' => 'Eukaryotic and prokaryotic cells',
            'summary' => 'The two kinds of cell, what each contains, and how big they are. With exam questions and mark schemes.',
            'boards' => $all,
            'status' => 'published',
            'body' => <<<'TXT'
All living things are made of cells. There are two kinds: **eukaryotic** cells, which keep their genetic material in a nucleus, and **prokaryotic** cells, which do not.

## Two kinds of cell

[KEYTERM] Eukaryotic cell: a cell with a nucleus. Animal and plant cells are eukaryotic.
[KEYTERM] Prokaryotic cell: a cell with no nucleus. Bacteria are prokaryotic.
[KEYTERM] Plasmid: a small ring of DNA found in many prokaryotic cells, separate from the main loop of DNA.

Prokaryotic cells are much smaller than eukaryotic cells. A typical bacterium is about 1 to 10 micrometres long; a typical animal cell is 10 to 100 micrometres across.

[TABLE] Eukaryotic and prokaryotic cells compared
Feature | Eukaryotic | Prokaryotic
Nucleus | Yes | No
Genetic material | Chromosomes inside the nucleus | A single loop of DNA, plus plasmids
Cell membrane | Yes | Yes
Cell wall | Plants only (cellulose) | Yes (not cellulose)
Typical size | 10 to 100 µm | 1 to 10 µm
[/TABLE]

## Inside an animal cell

[IMAGE] animal-cell | Labelled diagram of an animal cell

- **Nucleus**: contains the genetic material and controls the cell.
- **Cytoplasm**: a jelly where most chemical reactions happen.
- **Cell membrane**: controls what goes into and out of the cell.
- **Mitochondria**: where aerobic respiration happens.
- **Ribosomes**: where proteins are made.

## Orders of magnitude

Cells are measured in micrometres (µm). There are 1000 µm in a millimetre, so 1 µm = 1 × 10^-6 m. Each order of magnitude is ten times bigger than the one before.

[FORMULA] size in µm = size in mm × 1000

[TIP] If a question asks how many times bigger one cell is than another, divide the larger size by the smaller one, and make sure both are in the same units first.
TXT,
        ]);

        $eukaryotic->questions()->delete();
        $eukaryotic->questions()->createMany([
            [
                'type' => 'multiple-choice',
                'question' => 'Which structure is found in a prokaryotic cell but **not** in an animal cell?',
                'options' => ['Nucleus', 'Plasmid', 'Mitochondrion', 'Cell membrane'],
                'answer' => 'Plasmid',
                'marks' => 1,
                'mark_scheme' => 'B: plasmid',
                'model_answer' => 'Plasmid (B). Animal cells keep their DNA in chromosomes inside the nucleus; plasmids are small rings of DNA found in bacteria.',
                'position' => 1,
            ],
            [
                'type' => 'short-answer',
                'question' => 'Name the part of an animal cell where most chemical reactions take place.',
                'marks' => 1,
                'mark_scheme' => 'cytoplasm',
                'model_answer' => 'The cytoplasm.',
                'position' => 2,
            ],
            [
                'type' => 'short-answer',
                'question' => "Give **two** differences between the genetic material of a prokaryotic cell and that of a eukaryotic cell.",
                'marks' => 2,
                'mark_scheme' => <<<'TXT'
Any two from:
- prokaryotic DNA is not in a nucleus / eukaryotic DNA is in a nucleus (1)
- prokaryotic DNA is a single loop / eukaryotic DNA is in chromosomes (1)
- prokaryotic cells may have plasmids (1)
TXT,
                'model_answer' => 'In a prokaryotic cell the DNA is a single loop free in the cytoplasm, but in a eukaryotic cell it is in chromosomes inside a nucleus. Prokaryotic cells can also have extra small rings of DNA called plasmids.',
                'position' => 3,
            ],
            [
                'type' => 'calculation',
                'question' => "A bacterium is 2 µm long. An animal cell is 0.04 mm across.\n\nHow many times wider is the animal cell than the bacterium?",
                'marks' => 2,
                'mark_scheme' => <<<'TXT'
- 0.04 mm = 40 µm (1)
- 40 / 2 = 20 (times) (1)

Allow 20 with no working for 2 marks.
TXT,
                'model_answer' => "First put both sizes in the same units: 0.04 mm × 1000 = 40 µm.\n\nThen divide: 40 ÷ 2 = **20 times** wider.",
                'position' => 4,
            ],
        ]);

        $lessons->create([
            'chapter_id' => $structure->id,
            'title' => 'Animal and plant cells',
            'summary' => 'The parts of animal and plant cells and what each one does.',
            'boards' => $all,
            'status' => 'published',
            'body' => <<<'TXT'
Plant cells have everything an animal cell has, plus three extra parts.

## Parts both have

[KEYTERM] Nucleus: contains the genetic material that controls the cell.
[KEYTERM] Ribosome: where protein synthesis happens.

## Extra parts in plant cells

[TABLE]
Part | What it does
Cell wall | Made of cellulose; strengthens the cell
Chloroplasts | Absorb light for photosynthesis
Permanent vacuole | Filled with cell sap; keeps the cell firm
[/TABLE]

[TIP] Not every plant cell has chloroplasts: root hair cells are underground and have none.
TXT,
        ]);

        $microscopy = $lessons->create([
            'chapter_id' => $structure->id,
            'title' => 'Microscopy and magnification',
            'summary' => 'Light and electron microscopes, and how to use the magnification equation.',
            'boards' => $all,
            'status' => 'published',
            'body' => <<<'TXT'
## Light and electron microscopes

[KEYTERM] Magnification: how many times bigger the image is than the real object.
[KEYTERM] Resolution: the smallest distance between two points that can still be seen as separate points.

Electron microscopes have a much higher magnification and resolution than light microscopes, so they show smaller structures such as ribosomes.

## The magnification equation

[FORMULA] magnification = image size / real size

Rearranged, real size = image size / magnification.

[TIP] The two sizes must be in the same units. The answer has no units, because it is a ratio: write ×400, not 400 mm.
TXT,
        ]);

        $microscopy->questions()->delete();
        $microscopy->questions()->create([
            'type' => 'calculation',
            'question' => "The image of a cell is 12 mm wide. The real cell is 0.03 mm wide.\n\nCalculate the magnification.",
            'marks' => 2,
            'mark_scheme' => "- \\( \\frac{12}{0.03} \\) (1)\n- ×400 (1)",
            'model_answer' => "magnification = image size ÷ real size = 12 ÷ 0.03 = **×400**",
            'position' => 1,
        ]);

        $lessons->create([
            'chapter_id' => $structure->id,
            'title' => 'Required practical: microscopy',
            'summary' => 'How to prepare a slide and look at cells with a light microscope (AQA required practical).',
            'boards' => [$boards['aqa']],
            'status' => 'published',
            'body' => <<<'TXT'
## Method

1. Put a drop of water on a clean slide.
2. Peel a thin layer of onion epidermis and lay it flat on the water.
3. Add a drop of iodine solution to stain it.
4. Lower a cover slip at an angle with a mounted needle, so no air bubbles are trapped.
5. Start with the lowest-power objective lens, focus with the coarse knob, then the fine knob.

[TIP] Draw what you see with a sharp pencil, using clear unbroken lines and no shading, and write the magnification next to it.
TXT,
        ]);

        $lessons->create([
            'chapter_id' => $structure->id,
            'title' => 'Culturing microorganisms',
            'summary' => 'Growing bacteria safely and working out the number after a number of divisions.',
            'boards' => $all,
            'course' => 'separate',
            'status' => 'published',
            'body' => <<<'TXT'
## Binary fission

Bacteria multiply by simple cell division called binary fission, as often as once every 20 minutes when there is enough food and the temperature is right.

[FORMULA] bacteria at end = bacteria at start × 2^{number of divisions}

[TIP] Find the number of divisions first: divide the total time by the time one division takes.
TXT,
        ]);

        $lessons->create([
            'chapter_id' => $division->id,
            'title' => 'Chromosomes and mitosis',
            'summary' => 'What chromosomes are and the stages of the cell cycle.',
            'boards' => $all,
            'status' => 'published',
            'body' => <<<'TXT'
## Chromosomes

[KEYTERM] Chromosome: a long molecule of DNA carrying many genes. Body cells have chromosomes in pairs.

## The cell cycle

1. The cell grows, copies its DNA and makes more ribosomes and mitochondria.
2. **Mitosis**: one set of chromosomes is pulled to each end of the cell.
3. The cytoplasm and cell membrane divide, making two identical cells.
TXT,
        ]);

        // Physics, to show a second subject and a Higher-only lesson.
        $energy = Topic::firstOrCreate(['subject_id' => $physics->id, 'slug' => 'energy'], ['title' => 'Energy', 'position' => 1]);
        $stores = Chapter::firstOrCreate(['topic_id' => $energy->id, 'slug' => 'energy-stores'], ['title' => 'Energy stores and transfers', 'position' => 1]);

        $lessons->create([
            'chapter_id' => $stores->id,
            'title' => 'Kinetic energy',
            'summary' => 'The kinetic energy equation and how to use it.',
            'boards' => $all,
            'status' => 'published',
            'body' => <<<'TXT'
[KEYTERM] Kinetic energy: the energy an object has because it is moving.

[FORMULA] E_k = \frac{1}{2} m v^2

Here \( E_k \) is in joules, \( m \) in kilograms and \( v \) in metres per second.
TXT,
        ]);

        $lessons->create([
            'chapter_id' => $stores->id,
            'title' => 'Elastic potential energy',
            'summary' => 'Energy stored in a stretched spring, and the equation for it.',
            'boards' => $all,
            'tier' => 'higher',
            'status' => 'published',
            'body' => <<<'TXT'
[KEYTERM] Elastic potential energy: energy stored in an object that has been stretched or squashed.

[FORMULA] E_e = \frac{1}{2} k e^2

This only works if the limit of proportionality has not been passed.
TXT,
        ]);

        // A short topic test, to show a mock exam.
        $template = PaperTemplate::firstOrCreate(
            ['board_id' => $boards['aqa'], 'subject_id' => $biology->id, 'slug' => 'topic-test'],
            [
                'name' => 'Topic test',
                'tier' => 'all',
                'duration_minutes' => 25,
                'total_marks' => 12,
                'instructions' => "Answer all questions.\nShow your working in calculations.\nYour answers are saved on this device as you type.",
                'information' => 'You may use a calculator.',
            ]
        );

        PaperTemplate::firstOrCreate(
            ['board_id' => $boards['aqa'], 'subject_id' => $biology->id, 'slug' => 'paper-1-higher'],
            [
                'name' => 'Paper 1 Higher Tier',
                'tier' => 'higher',
                'duration_minutes' => 105,
                'total_marks' => 100,
                'instructions' => "Answer all questions.\nShow your working in calculations.",
                'information' => 'You may use a calculator.',
                'sections' => [['title' => 'Topics 1 to 4', 'description' => 'Cell biology; Organisation; Infection and response; Bioenergetics']],
            ]
        );

        $mock = MockExam::firstOrCreate(
            ['subject_id' => $biology->id, 'slug' => 'aqa-topic-test-cell-biology'],
            [
                'board_id' => $boards['aqa'],
                'paper_template_id' => $template->id,
                'topic_id' => $cells->id,
                'title' => 'Cell biology topic test',
                'status' => 'published',
            ]
        );

        $mock->questions()->delete();
        $mock->questions()->createMany([
            ['number' => '01.1', 'type' => 'multiple-choice', 'question' => 'Which part of a plant cell absorbs light for photosynthesis?', 'options' => ['Cell wall', 'Chloroplast', 'Vacuole', 'Ribosome'], 'answer' => 'Chloroplast', 'marks' => 1, 'mark_scheme' => 'B: chloroplast', 'model_answer' => 'Chloroplast.', 'position' => 1],
            ['number' => '01.2', 'type' => 'short-answer', 'question' => 'Give the function of the cell membrane.', 'marks' => 1, 'mark_scheme' => 'controls what enters and leaves the cell', 'model_answer' => 'It controls which substances go into and out of the cell.', 'position' => 2],
            ['number' => '02.1', 'type' => 'calculation', 'question' => 'An image of a mitochondrion is 30 mm long. The magnification is ×15 000. Calculate the real length in micrometres.', 'marks' => 3, 'mark_scheme' => "- real size = 30 / 15 000 (1)\n- = 0.002 mm (1)\n- = 2 µm (1)", 'model_answer' => "real size = image size ÷ magnification = 30 ÷ 15 000 = 0.002 mm\n\n0.002 × 1000 = **2 µm**", 'position' => 3],
            ['number' => '02.2', 'type' => 'short-answer', 'question' => 'Explain why an electron microscope is used to study ribosomes.', 'marks' => 2, 'mark_scheme' => "- ribosomes are very small (1)\n- electron microscope has higher resolution / magnification (1)", 'model_answer' => 'Ribosomes are too small to see with a light microscope. An electron microscope has a much higher resolution, so it can show them.', 'position' => 4],
            ['number' => '03', 'type' => 'extended', 'question' => 'Describe how to prepare a slide of onion cells and look at it with a light microscope.', 'marks' => 5, 'mark_scheme' => "Level-of-response, or one mark each for:\n- thin layer of onion epidermis on a slide (1)\n- add iodine to stain (1)\n- lower a cover slip at an angle to avoid air bubbles (1)\n- start with the lowest power objective (1)\n- focus with coarse then fine focus (1)", 'model_answer' => 'Peel a thin layer of epidermis from an onion and lay it on a drop of water on a slide. Add a drop of iodine solution to stain it. Lower a cover slip at an angle so no air bubbles are trapped. Put the slide on the stage, start with the lowest-power objective lens and focus with the coarse focus knob, then the fine focus knob.', 'position' => 5],
        ]);
    }

    protected function animalCellSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="-90 0 820 400" width="820" height="400" font-family="Arial, Helvetica, sans-serif" font-size="15">
  <rect x="-90" width="820" height="400" fill="#ffffff"/>
  <path d="M120 200c0-95 70-150 175-150s185 45 185 145-80 160-185 160S120 295 120 200z" fill="#fdf3dc" stroke="#16171b" stroke-width="3"/>
  <ellipse cx="300" cy="200" rx="58" ry="50" fill="#e9d7f5" stroke="#16171b" stroke-width="2.5"/>
  <ellipse cx="300" cy="200" rx="16" ry="14" fill="#c5a6df"/>
  <g fill="#f6c9a8" stroke="#16171b" stroke-width="2">
    <ellipse cx="405" cy="135" rx="26" ry="12" transform="rotate(-20 405 135)"/>
    <ellipse cx="210" cy="285" rx="24" ry="11" transform="rotate(15 210 285)"/>
    <ellipse cx="410" cy="265" rx="24" ry="11" transform="rotate(25 410 265)"/>
  </g>
  <g fill="#16171b">
    <circle cx="200" cy="150" r="3.5"/><circle cx="215" cy="160" r="3.5"/><circle cx="190" cy="168" r="3.5"/>
    <circle cx="360" cy="320" r="3.5"/><circle cx="372" cy="310" r="3.5"/><circle cx="350" cy="306" r="3.5"/>
  </g>
  <g stroke="#16171b" stroke-width="1.5">
    <line x1="300" y1="160" x2="300" y2="30"/>
    <line x1="430" y1="130" x2="560" y2="90"/>
    <line x1="470" y1="210" x2="560" y2="210"/>
    <line x1="372" y1="312" x2="560" y2="330"/>
    <line x1="240" y1="230" x2="70" y2="300"/>
    <line x1="203" y1="157" x2="70" y2="120"/>
  </g>
  <g fill="#16171b">
    <text x="300" y="22" text-anchor="middle">Nucleus</text>
    <text x="566" y="95">Mitochondrion</text>
    <text x="566" y="215">Cell membrane</text>
    <text x="566" y="335">Ribosomes</text>
    <text x="64" y="305" text-anchor="end">Cytoplasm</text>
    <text x="64" y="125" text-anchor="end">Ribosomes</text>
  </g>
</svg>
SVG;
    }
}
