<?php

namespace App\Models\Content;

/**
 * The question types the site knows how to lay out. Any type found in real
 * exams is allowed: an unknown type is shown as a written answer.
 */
enum QuestionType: string
{
    case MultipleChoice = 'multiple-choice';
    case ShortAnswer = 'short-answer';
    case Calculation = 'calculation';
    case Extended = 'extended';
    case TrueFalse = 'true-false';
    case Labelling = 'labelling';
    case Graph = 'graph';
    case Practical = 'practical';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple choice',
            self::ShortAnswer => 'Short answer',
            self::Calculation => 'Calculation',
            self::Extended => 'Extended response',
            self::TrueFalse => 'True or false',
            self::Labelling => 'Labelling',
            self::Graph => 'Graph or data',
            self::Practical => 'Required practical',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }

    public static function hasChoices(?string $type): bool
    {
        return in_array($type, [self::MultipleChoice->value, self::TrueFalse->value], true);
    }
}
