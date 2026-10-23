<?php

namespace Database\Seeders;

use App\Models\Content\Board;
use App\Models\Content\Subject;
use Illuminate\Database\Seeder;

/**
 * The launch exam boards and subjects. The installer offers to add them; they
 * are ordinary data and can be edited or removed in the admin panel.
 */
class LaunchSeeder extends Seeder
{
    public function run(): void
    {
        $boards = collect([
            ['name' => 'AQA', 'slug' => 'aqa', 'full_name' => 'AQA', 'website' => 'https://www.aqa.org.uk', 'position' => 1],
            ['name' => 'Edexcel', 'slug' => 'edexcel', 'full_name' => 'Pearson Edexcel', 'website' => 'https://qualifications.pearson.com', 'position' => 2],
            ['name' => 'OCR', 'slug' => 'ocr', 'full_name' => 'OCR', 'website' => 'https://www.ocr.org.uk', 'position' => 3],
        ])->mapWithKeys(fn ($b) => [$b['slug'] => Board::firstOrCreate(['slug' => $b['slug']], $b)]);

        // Specification codes as published by each board.
        $subjects = [
            ['Biology', 'biology', '#2f6b4f', ['aqa' => '8461', 'edexcel' => '1BI0', 'ocr' => 'J247']],
            ['Chemistry', 'chemistry', '#24508f', ['aqa' => '8462', 'edexcel' => '1CH0', 'ocr' => 'J248']],
            ['Physics', 'physics', '#a83a32', ['aqa' => '8463', 'edexcel' => '1PH0', 'ocr' => 'J249']],
            ['Combined Science', 'combined-science', '#1f6f78', ['aqa' => '8464', 'edexcel' => '1SC0', 'ocr' => 'J250']],
            ['Mathematics', 'mathematics', '#5b3f8c', ['aqa' => '8300', 'edexcel' => '1MA1', 'ocr' => 'J560']],
            ['Business', 'business', '#b8641b', ['aqa' => '8132', 'edexcel' => '1BS0', 'ocr' => 'J204']],
            ['Computer Science', 'computer-science', '#4d5560', ['aqa' => '8525', 'edexcel' => '1CP2', 'ocr' => 'J277']],
            ['Geography', 'geography', '#8a6d12', ['aqa' => '8035', 'edexcel' => '1GA0', 'ocr' => 'J383']],
        ];

        foreach ($subjects as $i => [$name, $slug, $colour, $codes]) {
            $subject = Subject::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'colour' => $colour,
                'status' => 'published',
                'position' => $i + 1,
            ]);

            foreach ($codes as $board => $code) {
                if (! $subject->boards()->where('boards.id', $boards[$board]->id)->exists()) {
                    $subject->boards()->attach($boards[$board]->id, ['spec_code' => $code]);
                }
            }
        }
    }
}
