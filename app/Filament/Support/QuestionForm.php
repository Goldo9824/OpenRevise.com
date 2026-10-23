<?php

namespace App\Filament\Support;

use App\Content\LessonParser;
use App\Models\Content\QuestionType;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/** The fields of an exam question, for lessons and for mock exams. */
class QuestionForm
{
    /** @param array<int, string>|null $boards Boards a lesson question may be written for. */
    public static function fields(bool $mock = false, ?array $boards = null): array
    {
        return [
            Grid::make(3)->schema(array_values(array_filter([
                $mock ? TextInput::make('number')->maxLength(12)->placeholder('01.1')->helperText('Leave empty to number in order.') : null,
                $mock ? TextInput::make('section')->maxLength(120)->placeholder('Section A') : null,
                Select::make('type')->options(QuestionType::options())->default('short-answer')->required()->live(),
                TextInput::make('marks')->numeric()->minValue(1)->maxValue(100)->required(),
                ! $mock ? Select::make('board_id')->label('Written for')->options($boards ?? [])->placeholder('All of the lesson\'s boards') : null,
            ]))),
            Textarea::make('question')->required()->rows(4)->rules([self::textRule()])
                ->helperText('Plain text. The lesson tags work here too: [TABLE], [IMAGE], [FORMULA], **bold**, \( maths \).'),
            TagsInput::make('options')->label('Choices')
                ->visible(fn (Get $get) => QuestionType::hasChoices($get('type')))
                ->required(fn (Get $get) => QuestionType::hasChoices($get('type')))
                ->helperText('Type each choice and press Enter.'),
            TextInput::make('answer')->label('Correct choice')
                ->visible(fn (Get $get) => QuestionType::hasChoices($get('type')))
                ->required(fn (Get $get) => QuestionType::hasChoices($get('type')))
                ->rules([fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                    if ($value && ! in_array($value, (array) $get('options'), true)) {
                        $fail('The correct choice must be exactly one of the choices.');
                    }
                }]),
            Textarea::make('mark_scheme')->required()->rows(4)->rules([self::textRule()]),
            Textarea::make('model_answer')->required()->rows(4)->rules([self::textRule()]),
        ];
    }

    /** Rejects text with broken tags, the same check the API runs. */
    public static function textRule(): Closure
    {
        return fn () => function (string $attribute, $value, Closure $fail) {
            $doc = app(LessonParser::class)->parse((string) $value);
            foreach ($doc->errorMessages() as $message) {
                $fail($message);
            }
        };
    }
}
