<?php

namespace App\Content;

/**
 * Formulas may be written as LaTeX or as plain text. Plain text such as
 * "magnification = image size / real size" becomes
 * \text{magnification} = \dfrac{\text{image size}}{\text{real size}}
 * so KaTeX shows it the way a textbook would.
 */
class FormulaConverter
{
    private const SYMBOLS = [
        '×' => '\times ', '÷' => '\div ', '±' => '\pm ', '≈' => '\approx ', '≠' => '\neq ',
        '≤' => '\leq ', '≥' => '\geq ', '→' => '\rightarrow ', '⇌' => '\rightleftharpoons ',
        '∝' => '\propto ', '∞' => '\infty ', '·' => '\cdot ', '√' => '\sqrt ',
        'Δ' => '\Delta ', 'λ' => '\lambda ', 'ρ' => '\rho ', 'Ω' => '\Omega ', 'μ' => '\mu ',
        'θ' => '\theta ', 'π' => '\pi ', 'α' => '\alpha ', 'β' => '\beta ', 'γ' => '\gamma ',
        'σ' => '\sigma ', 'ω' => '\omega ', 'η' => '\eta ', 'ε' => '\varepsilon ',
        '°' => '^\circ ', '²' => '^2', '³' => '^3', '%' => '\%', '#' => '\#', '&' => '\&',
    ];

    public static function toTex(string $formula): string
    {
        $formula = trim($formula);

        // Already LaTeX: leave it alone.
        if (preg_match('/\\\\[a-zA-Z]|[{}]/', $formula)) {
            return $formula;
        }

        $sides = preg_split('/\s*=\s*/u', $formula);

        return implode(' = ', array_map(self::side(...), $sides));
    }

    private static function side(string $side): string
    {
        // A slash with spaces round it is a fraction: "image size / real size".
        $parts = preg_split('/\s+\/\s+/u', trim($side), 2);

        if (count($parts) === 2) {
            // "useful output / total input × 100%": the × 100% sits outside the fraction.
            [$denominator, $after] = array_pad(preg_split('/\s+(?:×|x|\*)\s+/u', $parts[1], 2), 2, null);

            return '\dfrac{'.self::expression($parts[0]).'}{'.self::side($denominator).'}'
                .($after !== null ? ' \times '.self::expression($after) : '');
        }

        return self::expression($side);
    }

    private static function expression(string $text): string
    {
        // Runs of words become \text{...}; single letters stay as variables.
        $pieces = preg_split('/(\p{L}{2,}(?:[ \x{2019}\'-]+\p{L}+)*)/u', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';

        foreach ($pieces as $i => $piece) {
            if ($i % 2 === 1 && ! self::isGreek($piece)) {
                $out .= '\text{'.strtr($piece, ['%' => '\%', '&' => '\&', '#' => '\#']).'}';

                continue;
            }

            $piece = preg_replace('/(?<=\d)\s*[x*]\s*(?=\d)/u', ' \times ', $piece);
            $piece = str_replace('*', ' \times ', $piece);
            $piece = preg_replace('/\^\s*(-?[0-9.]+)/u', '^{$1}', $piece);
            $out .= strtr($piece, self::SYMBOLS);
        }

        return trim(preg_replace('/\s+/u', ' ', $out));
    }

    private static function isGreek(string $word): bool
    {
        return (bool) preg_match('/^[\x{0370}-\x{03FF}]+$/u', $word);
    }
}
