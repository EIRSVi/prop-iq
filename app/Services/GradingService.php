<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;

class GradingService extends BaseService
{
    public function calculateScore(QuizAttempt $attempt)
    {
        $totalScore = 0;
        $attempt->load('answers.question.options');

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            $points = 0;

            if ($question->type === 'mcq' || $question->type === 'true_false') {
                // Check if selected option is correct
                $selectedOption = $question->options->where('id', $answer->option_id)->first();
                if ($selectedOption && $selectedOption->is_correct) {
                    $points = $question->points;
                    $answer->is_correct = true;
                } else {
                    $answer->is_correct = false;
                }
            } elseif ($question->type === 'open') {
                // Manual grading required usually, but for now mark as pending or 0
                // Or if exact match needed (simple text)
                // $answer->is_correct = null; // Pending
            }

            $answer->points_awarded = $points;
            $answer->save();
            $totalScore += $points;
        }

        $attempt->score = $totalScore;
        $attempt->status = 'completed'; // Or 'graded'
        $attempt->save();

        return $attempt;
    }
}
