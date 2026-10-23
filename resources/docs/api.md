# OpenRevise API

This API lets Claude add GCSE revision content to OpenRevise: topics, chapters, lessons, diagrams, exam questions and mock exams. Lessons are sent as plain text with a few short tags, never HTML; the site turns them into the finished page.

- Base URL: `{{api}}/v1`
- This page as Markdown: `{{api}}/v1/docs`
- Every request needs an API key, made in the admin panel: `Authorization: Bearer or_...`
- Send JSON (`Content-Type: application/json`) and read JSON back. Successful answers are `{"data": ...}`, sometimes with a `warnings` list. Errors are `{"message": ..., "errors": {"field": ["..."]}}` with status 422, or `{"error": "..."}` with 401, 403, 404 or 429.

## Keys and rights

| Right | Lets the key |
| --- | --- |
| read | List the tree, boards, images; read a lesson; check lesson text |
| write | Create and edit content. New content is saved as a **draft** |
| publish | Also put content live straight away (`"status": "published"`) |

A key without the publish right that asks for `published` gets a draft and a warning; an admin publishes it from the admin panel. Keys are limited to 120 requests a minute.

## The content tree

Subject → Topic → Chapter → Lesson. Subjects and exam boards are set up by the admin; topics, chapters and lessons can be made here. Each lesson belongs to one or more boards, and has exam questions. Mock exams belong to a subject and a board.

Addresses are built from slugs: `{{site}}/gcse/biology/cell-biology/cell-structure/eukaryotic-and-prokaryotic-cells/`. Slugs are made from titles; leave `slug` out unless you need a specific one. A lesson for only some of a subject's boards gets the board names at the end of its slug (`required-practical-microscopy-aqa`).

Start with `GET /v1/tree` to see what exists, and `GET /v1/boards` for board slugs and paper templates.

## Lesson text format

Write lessons as plain text. One tag per line.

| Write | Becomes |
| --- | --- |
| `# Title` | The page title (optional; the lesson title is used otherwise) |
| `## Heading`, `### Sub-heading` | Headings |
| `[KEYTERM] Term: definition` | A highlighted key term. Lines next to each other are grouped in one box |
| `[TABLE] Optional caption` … rows … `[/TABLE]` | A table. Cells split by `\|`, first row is the header |
| `[IMAGE] image-name \| Caption` | An uploaded diagram (upload it first with `POST /v1/images`) |
| `[FORMULA] magnification = image size / real size` | A displayed formula. Plain text or LaTeX (`E_k = \frac{1}{2} m v^2`) |
| `[TIP] Text` | A callout for exam tips and warnings. Runs to the next blank line |
| `- item` or `1. step` | Bullet or numbered lists |
| `**bold**`, `*italic*` | Emphasis |
| `x^2`, `10^-3`, `CO_2` | Powers and subscripts |
| `\( v = f \lambda \)` | A formula inside a sentence (LaTeX) |

Blank lines separate paragraphs. Anything else is shown as text. Unknown tags, tables without `[/TABLE]` and images that were never uploaded are rejected with the line number, so nothing broken goes live.

In plain-text formulas, a slash with spaces round it is a fraction, words become upright text and `×`, `÷`, `Δ`, `λ`, `ρ`, `²` are understood: `efficiency = useful output / total input × 100%`.

Example:

```
## Cell structure

[KEYTERM] Nucleus: contains the cell's genetic material.
[KEYTERM] Ribosome: where proteins are made.

[TABLE] Parts of a cell
Part | Function
Nucleus | Controls the cell
Ribosome | Makes proteins
[/TABLE]

[IMAGE] animal-cell | Labelled animal cell
[FORMULA] magnification = image size / real size
[TIP] Always show your working in calculations.
```

## Endpoints

### GET /v1/tree

Every subject with its topics, chapters, lessons (drafts included) and mock exams. Filter with `?subject=biology`.

### GET /v1/boards

Exam boards, the subjects each one examines (with spec codes) and their paper templates (slug, time, total marks, sections).

### POST /v1/topics

```json
{"subject": "biology", "title": "Cell biology", "description": "Optional", "position": 1}
```

`subject` is a slug or ID. Topics are published by default (a topic with no published lessons does not show).

### POST /v1/chapters

```json
{"topic_id": 3, "title": "Cell structure"}
```

### POST /v1/lessons

```json
{
  "chapter_id": 7,
  "title": "Eukaryotic and prokaryotic cells",
  "summary": "One or two sentences for search results (max 300 characters).",
  "boards": ["aqa", "edexcel", "ocr"],
  "tier": "all",
  "course": "all",
  "status": "draft",
  "body": "## Cell structure\n\n[KEYTERM] Nucleus: ..."
}
```

- `boards`: slugs or IDs. Share one lesson between boards only when their requirements for it are the same; otherwise make one lesson per board.
- `tier`: `all` or `higher` (Higher tier only).
- `course`: `all` or `separate` (Separate Science only).
- `status`: `draft`, `published` or `hidden`.
- Optional: `slug`, `position`.

Answers 201 with the lesson, including its `id` and `url`.

### POST /v1/lessons/bulk

`{"lessons": [ {...}, {...} ]}`, up to 50 lessons in the same shape. All are checked first; if one is wrong, none is saved and the errors name it (`lessons.2.body`).

### POST /v1/lessons/check

`{"body": "..."}`. Checks lesson text without saving it and returns `valid`, `errors`, `warnings`, the key terms and headings found, and the HTML. Needs only the read right.

### GET /v1/lessons/{id}

The lesson with its full text and exam questions.

### PUT /v1/lessons/{id}

Send only what changes, e.g. `{"body": "..."}` or `{"status": "published", "boards": ["aqa"]}`. Changing the slug or moving the lesson to another chapter keeps the old address working as a redirect.

### POST /v1/images

Upload a diagram (PNG, JPEG, WebP, GIF or SVG, up to 4 MB). Three ways:

- JSON with SVG markup: `{"name": "animal-cell", "svg": "<svg ...>...</svg>", "alt": "Labelled animal cell"}`
- JSON with base64: `{"name": "animal-cell", "data": "iVBORw0KGgo...", "alt": "..."}`
- Multipart form with a `file` field (and optional `name`, `alt`).

The answer includes the `tag` to paste into the lesson: `[IMAGE] animal-cell | Labelled animal cell`. A name already in use is refused unless you send `"replace": true`. Scripts and links to other sites are removed from SVGs; large photos are resized.

### GET /v1/images

Every uploaded image with its name and tag.

### POST /v1/questions

Exam questions for one lesson, in the style of its exam board.

```json
{
  "lesson_id": 12,
  "questions": [
    {
      "type": "short-answer",
      "question": "Name the part of the cell where proteins are made.",
      "marks": 1,
      "mark_scheme": "ribosome(s)",
      "model_answer": "Ribosomes."
    },
    {
      "type": "multiple-choice",
      "question": "Which structure is found in a prokaryotic cell but not in an animal cell?",
      "options": ["Nucleus", "Plasmid", "Mitochondrion", "Cell membrane"],
      "answer": "B",
      "marks": 1,
      "mark_scheme": "B: plasmid",
      "model_answer": "Plasmid (B)."
    }
  ]
}
```

- Every question needs `question`, `marks`, `mark_scheme` and `model_answer`. All four accept the lesson text format.
- `type`: `multiple-choice`, `short-answer`, `calculation`, `extended`, `true-false`, `labelling`, `graph` or `practical`. Any other type found in real exams is allowed and shown as a written answer.
- Multiple choice and true/false need `options` and the correct `answer` (its text or letter).
- Optional `board`: when a shared lesson's question is written in one board's style.
- New questions are added after the existing ones. `"replace": true` swaps out the whole batch.
- One question can also be sent on its own, without the `questions` list.

### POST /v1/mock-exams

A full paper written by OpenRevise for one board and subject. Pick a paper template from `GET /v1/boards`; its time and total marks are used, so you never enter them.

```json
{
  "subject": "biology",
  "board": "aqa",
  "paper_template": "paper-1-higher",
  "title": "AQA Biology Paper 1 (Higher) - Set A",
  "status": "draft",
  "questions": [
    {
      "number": "01.1",
      "section": "Cell biology",
      "type": "short-answer",
      "question": "...",
      "marks": 2,
      "mark_scheme": "...",
      "model_answer": "..."
    }
  ]
}
```

`title`, `number` and `section` are optional; numbers are given in order if left out. You get a warning if the marks do not add up to the template's total. Optional `topic_id` for a topic-focused paper.

## Tips for writing content

- Write for 14 to 16 year olds in the UK. British spelling.
- Keep each lesson to one idea a student can learn in one sitting; split long ones.
- Put the key terms near the top, then the explanation, then tips.
- Mark schemes in the board's style: one mark per creditworthy point, with alternatives separated by "/" and "allow"/"do not accept" notes where useful.
