<?php

namespace App\Content;

/** The result of turning lesson text into a page. */
final class ParsedDocument
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $headings
     * @param  list<array{term: string, definition: string, id: string}>  $keyTerms
     * @param  list<string>  $images  Image names used in [IMAGE] tags.
     * @param  list<array{line: int, message: string}>  $errors
     * @param  list<array{line: int, message: string}>  $warnings
     */
    public function __construct(
        public readonly string $html,
        public readonly ?string $title,
        public readonly array $headings,
        public readonly array $keyTerms,
        public readonly array $images,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly int $wordCount,
        public readonly bool $hasFormulas,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function readingMinutes(): int
    {
        return max(1, (int) round($this->wordCount / 200));
    }

    /** @return list<string> e.g. "Line 4: [TABLE] is never closed with [/TABLE]." */
    public function errorMessages(): array
    {
        return array_map(fn ($e) => 'Line '.$e['line'].': '.$e['message'], $this->errors);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
