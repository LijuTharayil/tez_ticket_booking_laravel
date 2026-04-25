<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
class QuestionSeeder extends Seeder
{
    public function run()
    {
        $questions = [
            [
                'question' => 'Who won the ICC Cricket World Cup 2019?',
                'option_a' => 'India',
                'option_b' => 'Australia',
                'option_c' => 'England',
                'option_d' => 'New Zealand',
                'correct_answer' => 'C',
            ],
            [
                'question' => 'Who is known as the "God of Cricket"?',
                'option_a' => 'Virat Kohli',
                'option_b' => 'Sachin Tendulkar',
                'option_c' => 'MS Dhoni',
                'option_d' => 'Ricky Ponting',
                'correct_answer' => 'B',
            ],
            [
                'question' => 'Which player has scored the most international centuries?',
                'option_a' => 'Virat Kohli',
                'option_b' => 'Ricky Ponting',
                'option_c' => 'Sachin Tendulkar',
                'option_d' => 'Jacques Kallis',
                'correct_answer' => 'C',
            ],
            [
                'question' => 'Which country has won the most ICC Cricket World Cups?',
                'option_a' => 'India',
                'option_b' => 'West Indies',
                'option_c' => 'Australia',
                'option_d' => 'England',
                'correct_answer' => 'C',
            ],
            [
                'question' => 'Who was the captain of India in the 2007 T20 World Cup?',
                'option_a' => 'Sourav Ganguly',
                'option_b' => 'Rahul Dravid',
                'option_c' => 'MS Dhoni',
                'option_d' => 'Virat Kohli',
                'correct_answer' => 'C',
            ],
            [
                'question' => 'Which bowler has taken the most wickets in Test cricket?',
                'option_a' => 'Muttiah Muralitharan',
                'option_b' => 'Shane Warne',
                'option_c' => 'Anil Kumble',
                'option_d' => 'James Anderson',
                'correct_answer' => 'A',
            ],
            [
                'question' => 'Who scored the fastest century in ODIs?',
                'option_a' => 'AB de Villiers',
                'option_b' => 'Chris Gayle',
                'option_c' => 'Corey Anderson',
                'option_d' => 'Shahid Afridi',
                'correct_answer' => 'A',
            ],
            [
                'question' => 'Which Indian player is known as "Captain Cool"?',
                'option_a' => 'Virat Kohli',
                'option_b' => 'MS Dhoni',
                'option_c' => 'Rohit Sharma',
                'option_d' => 'KL Rahul',
                'correct_answer' => 'B',
            ],
            [
                'question' => 'Which stadium is known as the largest cricket stadium in the world?',
                'option_a' => 'MCG',
                'option_b' => 'Lords',
                'option_c' => 'Narendra Modi Stadium',
                'option_d' => 'Eden Gardens',
                'correct_answer' => 'C',
            ],
            [
                'question' => 'Who won the IPL 2023 title?',
                'option_a' => 'Mumbai Indians',
                'option_b' => 'Chennai Super Kings',
                'option_c' => 'Gujarat Titans',
                'option_d' => 'Royal Challengers Bangalore',
                'correct_answer' => 'B',
            ],
        ];

        foreach (range(1, 30) as $day) {

            $date = Carbon::create(2026, 4, $day)->toDateString();

            foreach ($questions as $q) {
                Question::create([
                    'question' => $q['question'],
                    'option_a' => $q['option_a'],
                    'option_b' => $q['option_b'],
                    'option_c' => $q['option_c'],
                    'option_d' => $q['option_d'],
                    'correct_answer' => $q['correct_answer'], // ✅ already set
                    'question_date' => $date,
                ]);
            }
        }
    }
}
