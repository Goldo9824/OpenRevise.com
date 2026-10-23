<?php

namespace Tests\Unit;

use App\Content\FormulaConverter;
use App\Content\LessonParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LessonParserTest extends TestCase
{
    protected function parser(): LessonParser
    {
        return new LessonParser(fn (array $names) => in_array('animal-cell', $names, true)
            ? ['animal-cell' => ['url' => '/media/animal-cell.svg', 'width' => 640, 'height' => 400, 'alt' => 'Animal cell']]
            : []);
    }

    public function test_it_turns_the_tags_into_a_page(): void
    {
        $doc = $this->parser()->parse(<<<'TXT'
# Cells

## Cell structure

[KEYTERM] Nucleus: contains the cell's genetic material.
[KEYTERM] Ribosome - makes proteins.

[TABLE] Parts
Part | Function
Nucleus | Controls the cell
[/TABLE]

[IMAGE] animal-cell | Labelled animal cell
[FORMULA] magnification = image size / real size
[TIP] Always show your working.
- one
- two
TXT);

        $this->assertTrue($doc->isValid(), implode("\n", $doc->errorMessages()));
        $this->assertSame('Cells', $doc->title);
        $this->assertSame([['id' => 'cell-structure', 'text' => 'Cell structure', 'level' => 2]], $doc->headings);
        $this->assertCount(2, $doc->keyTerms);
        $this->assertSame('Ribosome', $doc->keyTerms[1]['term']);
        $this->assertStringContainsString('<div class="keyterms">', $doc->html);
        $this->assertStringContainsString('<caption>Parts</caption>', $doc->html);
        $this->assertStringContainsString('<th scope="col">Function</th>', $doc->html);
        $this->assertStringContainsString('src="/media/animal-cell.svg"', $doc->html);
        $this->assertStringContainsString('width="640" height="400"', $doc->html);
        $this->assertStringContainsString('data-tex="\text{magnification} = \dfrac{\text{image size}}{\text{real size}}"', $doc->html);
        $this->assertStringContainsString('<aside class="tip">', $doc->html);
        $this->assertStringContainsString('<ul><li>one</li><li>two</li></ul>', $doc->html);
        $this->assertTrue($doc->hasFormulas);
    }

    public function test_html_in_the_text_is_escaped(): void
    {
        $doc = $this->parser()->parse('Hello <script>alert(1)</script> & **bold**');

        $this->assertStringContainsString('&lt;script&gt;', $doc->html);
        $this->assertStringNotContainsString('<script>', $doc->html);
        $this->assertStringContainsString('<strong>bold</strong>', $doc->html);
    }

    public function test_powers_subscripts_and_inline_maths(): void
    {
        $html = $this->parser()->parse('CO_2 is 3 × 10^8 and \( v = f \lambda \)')->html;

        $this->assertStringContainsString('CO<sub>2</sub>', $html);
        $this->assertStringContainsString('10<sup>8</sup>', $html);
        $this->assertStringContainsString('class="math" data-tex="v = f \lambda"', $html);
    }

    public function test_broken_tags_are_reported_with_their_line(): void
    {
        $doc = $this->parser()->parse("Fine\n[TABLE]\na | b\n\n[IMAGE] missing-one | Nope\n[FOO] bar");

        $this->assertFalse($doc->isValid());
        $messages = implode("\n", $doc->errorMessages());
        $this->assertStringContainsString('Line 2: [TABLE] is never closed', $messages);

        $doc = $this->parser()->parse("[IMAGE] missing-one | Nope\n[FOO] bar\n[KEYTERM] no definition");
        $messages = implode("\n", $doc->errorMessages());
        $this->assertStringContainsString('Line 1: No uploaded image is called "missing-one"', $messages);
        $this->assertStringContainsString('Line 2: Unknown tag [FOO]', $messages);
        $this->assertStringContainsString('Line 3: [KEYTERM] needs a term and a definition', $messages);
    }

    #[DataProvider('formulas')]
    public function test_plain_formulas_become_latex(string $plain, string $tex): void
    {
        $this->assertSame($tex, FormulaConverter::toTex($plain));
    }

    public static function formulas(): array
    {
        return [
            ['magnification = image size / real size', '\text{magnification} = \dfrac{\text{image size}}{\text{real size}}'],
            ['efficiency = useful output / total input × 100%', '\text{efficiency} = \dfrac{\text{useful output}}{\text{total input}} \times 100\%'],
            ['ρ = m / V', '\rho = \dfrac{m}{V}'],
            ['E_k = \frac{1}{2} m v^2', 'E_k = \frac{1}{2} m v^2'],
        ];
    }
}
