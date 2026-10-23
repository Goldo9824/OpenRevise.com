<?php

namespace App\Content;

use App\Models\Content\Image;
use Closure;
use Illuminate\Support\Str;

/**
 * Turns lesson text into HTML. Lessons are written as plain text with a few
 * short tags, never HTML:
 *
 *   # Page title              ## Heading            ### Sub-heading
 *   [KEYTERM] Term: definition
 *   [TABLE] Optional caption   ... rows of cells split by | ...   [/TABLE]
 *   [IMAGE] image-name | Caption
 *   [FORMULA] magnification = image size / real size   (or LaTeX)
 *   [TIP] An exam tip or warning.
 *   - bullet points, 1. numbered steps, **bold**, *italic*, x^2, CO_2, \( inline maths \)
 *
 * Everything is escaped, so the text can never inject markup.
 */
class LessonParser
{
    public const TAGS = ['KEYTERM', 'TABLE', 'IMAGE', 'FORMULA', 'TIP'];

    /** Bump when the HTML output changes, so cached lesson pages are rebuilt. */
    public const VERSION = 3;

    private const BULB = '<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>';

    /** @var Closure(list<string>): array<string, array{url: string, width: ?int, height: ?int, alt: ?string}> */
    protected Closure $imageResolver;

    /** @var list<array{line: int, message: string}> */
    protected array $errors = [];

    /** @var list<array{line: int, message: string}> */
    protected array $warnings = [];

    /** @var array<string, int> */
    protected array $usedIds = [];

    protected bool $hasFormulas = false;

    public function __construct(?Closure $imageResolver = null)
    {
        $this->imageResolver = $imageResolver ?? static function (array $names): array {
            if ($names === []) {
                return [];
            }

            return Image::query()->whereIn('name', $names)->get()
                ->mapWithKeys(fn (Image $image) => [$image->name => [
                    'url' => $image->url(),
                    'width' => $image->width,
                    'height' => $image->height,
                    'alt' => $image->alt,
                ]])->all();
        };
    }

    public function parse(string $text): ParsedDocument
    {
        $this->errors = [];
        $this->warnings = [];
        $this->usedIds = [];
        $this->hasFormulas = false;

        $text = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], $text);
        $lines = explode("\n", $text);

        // First pass: find the images so they can be looked up in one query.
        $imageNames = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*\[IMAGE\]\s*([^|]+)/i', $line, $m)) {
                $imageNames[] = trim($m[1]);
            }
        }
        $images = ($this->imageResolver)(array_values(array_unique($imageNames)));

        $html = [];
        $title = null;
        $headings = [];
        $keyTerms = [];
        $pendingTerms = [];
        $paragraph = [];
        $list = null;

        $flushParagraph = function () use (&$paragraph, &$html) {
            if ($paragraph) {
                $html[] = '<p>'.$this->inline(implode(' ', $paragraph)).'</p>';
                $paragraph = [];
            }
        };
        $flushList = function () use (&$list, &$html) {
            if ($list) {
                $items = array_map(fn ($item) => '<li>'.$this->inline($item).'</li>', $list['items']);
                $html[] = '<'.$list['type'].'>'.implode('', $items).'</'.$list['type'].'>';
                $list = null;
            }
        };
        $flushTerms = function () use (&$pendingTerms, &$html) {
            if ($pendingTerms) {
                $rows = array_map(
                    fn ($t) => '<li id="'.$t['id'].'"><dfn>'.$this->inline($t['term']).'</dfn>: '.$this->inline($t['definition']).'</li>',
                    $pendingTerms,
                );
                $html[] = '<div class="keyterms"><p class="keyterms-title">'.self::BULB.'Key terms</p><ul>'.implode('', $rows).'</ul></div>';
                $pendingTerms = [];
            }
        };
        $flushAll = function () use ($flushParagraph, $flushList, $flushTerms) {
            $flushParagraph();
            $flushList();
            $flushTerms();
        };

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $lineNo = $i + 1;
            $trim = trim($lines[$i]);

            if ($trim === '') {
                $flushParagraph();
                $flushList();

                continue;
            }

            // Headings.
            if (preg_match('/^(#{1,3})\s+(.+)$/u', $trim, $m)) {
                $flushAll();
                $level = strlen($m[1]);
                $textValue = trim($m[2]);

                if ($level === 1 && $title === null) {
                    $title = $textValue;

                    continue;
                }

                $level = max(2, $level);
                $id = $this->uniqueId($textValue);
                $headings[] = ['id' => $id, 'text' => $textValue, 'level' => $level];
                $html[] = "<h$level id=\"$id\">".$this->inline($textValue)."</h$level>";

                continue;
            }

            // Tags.
            if (preg_match('/^\[(\/?[A-Za-z]+)\]\s*(.*)$/u', $trim, $m)) {
                $tag = strtoupper($m[1]);
                $rest = trim($m[2]);

                if ($tag !== 'KEYTERM') {
                    $flushAll();
                } else {
                    $flushParagraph();
                    $flushList();
                }

                switch ($tag) {
                    case 'KEYTERM':
                        $parts = preg_split('/\s*(?::|\s[-–—]\s)\s*/u', $rest, 2);
                        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                            $this->error($lineNo, '[KEYTERM] needs a term and a definition, like "[KEYTERM] Nucleus: contains the genetic material."');
                            break;
                        }
                        $term = ['term' => $parts[0], 'definition' => $parts[1], 'id' => 'term-'.$this->uniqueId($parts[0])];
                        $pendingTerms[] = $term;
                        $keyTerms[] = $term;
                        break;

                    case 'TABLE':
                        $rows = [];
                        $closed = false;
                        for ($j = $i + 1; $j < $count; $j++) {
                            $row = trim($lines[$j]);
                            if (preg_match('/^\[\/TABLE\]$/i', $row)) {
                                $closed = true;
                                break;
                            }
                            if ($row === '' || preg_match('/^\|?\s*:?-{2,}/', $row)) {
                                continue;
                            }
                            $rows[] = ['line' => $j + 1, 'cells' => array_map('trim', explode('|', trim($row, '| ')))];
                        }
                        if (! $closed) {
                            $this->error($lineNo, '[TABLE] is never closed with [/TABLE].');
                            $i = $count;
                            break;
                        }
                        $i = $j;
                        $html[] = $this->table($rows, $rest, $lineNo);
                        break;

                    case 'IMAGE':
                        [$name, $caption] = array_pad(array_map('trim', explode('|', $rest, 2)), 2, '');
                        if ($name === '') {
                            $this->error($lineNo, '[IMAGE] needs the name of an uploaded image, like "[IMAGE] animal-cell | Labelled animal cell".');
                            break;
                        }
                        $html[] = $this->image($name, $caption, $images[$name] ?? null, $lineNo);
                        break;

                    case 'FORMULA':
                        if ($rest === '') {
                            $this->error($lineNo, '[FORMULA] is empty.');
                            break;
                        }
                        $this->hasFormulas = true;
                        $tex = FormulaConverter::toTex($rest);
                        $html[] = '<div class="formula" data-tex="'.e($tex).'"><span class="formula-text">'.e($rest).'</span></div>';
                        break;

                    case 'TIP':
                        // A tip runs on until the next blank line.
                        $tip = [$rest];
                        while ($i + 1 < $count && trim($lines[$i + 1]) !== '' && ! preg_match('/^\s*(\[|#|[-*•]\s|\d{1,2}[.)]\s)/u', $lines[$i + 1])) {
                            $tip[] = trim($lines[++$i]);
                        }
                        $tip = trim(implode(' ', $tip));
                        if ($tip === '') {
                            $this->error($lineNo, '[TIP] is empty.');
                            break;
                        }
                        $html[] = '<aside class="tip"><p>'.$this->inline($tip).'</p></aside>';
                        break;

                    case '/TABLE':
                        $this->error($lineNo, '[/TABLE] without an opening [TABLE].');
                        break;

                    default:
                        $this->error($lineNo, "Unknown tag [{$m[1]}]. Allowed tags: ".implode(', ', array_map(fn ($t) => "[$t]", self::TAGS)).'.');
                        $paragraph[] = $trim;
                }

                continue;
            }

            // Lists.
            if (preg_match('/^(?:[-*•])\s+(.+)$/u', $trim, $m) || preg_match('/^\d{1,2}[.)]\s+(.+)$/u', $trim, $m)) {
                $type = preg_match('/^\d/', $trim) ? 'ol' : 'ul';
                $flushParagraph();
                $flushTerms();
                if ($list && $list['type'] !== $type) {
                    $flushList();
                }
                $list ??= ['type' => $type, 'items' => []];
                $list['items'][] = $m[1];

                continue;
            }

            // A line that continues a list item.
            if ($list && preg_match('/^\s{2,}\S/', $lines[$i])) {
                $list['items'][count($list['items']) - 1] .= ' '.$trim;

                continue;
            }

            $flushList();
            $flushTerms();
            $paragraph[] = $trim;
        }

        $flushAll();

        $body = implode("\n", $html);

        return new ParsedDocument(
            html: $body,
            title: $title,
            headings: $headings,
            keyTerms: $keyTerms,
            images: array_values(array_unique($imageNames)),
            errors: $this->errors,
            warnings: $this->warnings,
            wordCount: str_word_count(strip_tags(str_replace('<', ' <', $body))),
            hasFormulas: $this->hasFormulas,
        );
    }

    /** Bold, italics, powers, subscripts and inline maths inside a line. */
    public function inline(string $text): string
    {
        $maths = [];
        $text = preg_replace_callback('/\\\\\((.+?)\\\\\)/u', function ($m) use (&$maths) {
            $this->hasFormulas = true;
            $maths[] = '<span class="math" data-tex="'.e(trim($m[1])).'">'.e(trim($m[1])).'</span>';

            return "\u{E000}".(count($maths) - 1)."\u{E001}";
        }, $text);

        $html = e($text);
        $html = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/u', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<![*\w])\*(?=\S)([^*]+?)(?<=\S)\*(?![*\w])/u', '<em>$1</em>', $html);
        $html = preg_replace('/(?<=[\w)\]])\^(-?[0-9]+|[a-z])(?![\w])/u', '<sup>$1</sup>', $html);
        $html = preg_replace('/(?<=[A-Za-z)\]])_([0-9]+)/u', '<sub>$1</sub>', $html);

        return preg_replace_callback("/\u{E000}(\d+)\u{E001}/u", fn ($m) => $maths[(int) $m[1]], $html);
    }

    /** @param list<array{line: int, cells: list<string>}> $rows */
    protected function table(array $rows, string $caption, int $lineNo): string
    {
        if (count($rows) < 2) {
            $this->error($lineNo, '[TABLE] needs a header row and at least one row, with cells split by |.');
        }

        if ($rows === []) {
            return '';
        }

        $width = max(array_map(fn ($r) => count($r['cells']), $rows));
        foreach ($rows as $row) {
            if (count($row['cells']) !== $width) {
                $this->warnings[] = ['line' => $row['line'], 'message' => 'This table row has '.count($row['cells'])." cells; the widest row has $width."];
            }
        }

        $header = array_shift($rows);
        $th = implode('', array_map(fn ($c) => '<th scope="col">'.$this->inline($c).'</th>', array_pad($header['cells'], $width, '')));
        $body = implode('', array_map(
            fn ($r) => '<tr>'.implode('', array_map(fn ($c) => '<td>'.$this->inline($c).'</td>', array_pad($r['cells'], $width, ''))).'</tr>',
            $rows,
        ));

        $captionHtml = $caption !== '' ? '<caption>'.$this->inline($caption).'</caption>' : '';

        return '<div class="table-wrap"><table>'.$captionHtml.'<thead><tr>'.$th.'</tr></thead><tbody>'.$body.'</tbody></table></div>';
    }

    /** @param array{url: string, width: ?int, height: ?int, alt: ?string}|null $image */
    protected function image(string $name, string $caption, ?array $image, int $lineNo): string
    {
        if (! $image) {
            $this->error($lineNo, "No uploaded image is called \"$name\". Upload it first (POST /v1/images), then use its name.");

            return '<figure class="figure figure-missing"><div class="figure-placeholder">Diagram "'.e($name).'" is missing</div></figure>';
        }

        $alt = $caption !== '' ? $caption : ($image['alt'] ?? '');
        $size = $image['width'] && $image['height'] ? ' width="'.$image['width'].'" height="'.$image['height'].'"' : '';
        $captionHtml = $caption !== '' ? '<figcaption>'.$this->inline($caption).'</figcaption>' : '';

        return '<figure class="figure"><a href="'.e($image['url']).'" class="figure-link" title="Open the full-size diagram"><img src="'.e($image['url']).'" alt="'.e($alt).'"'.$size.' loading="lazy" decoding="async"></a>'.$captionHtml.'</figure>';
    }

    protected function uniqueId(string $text): string
    {
        $id = Str::slug($text) ?: 'section';
        $n = $this->usedIds[$id] = ($this->usedIds[$id] ?? 0) + 1;

        return $n > 1 ? $id.'-'.$n : $id;
    }

    protected function error(int $line, string $message): void
    {
        $this->errors[] = ['line' => $line, 'message' => $message];
    }
}
