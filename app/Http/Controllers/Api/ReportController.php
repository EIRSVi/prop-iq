<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function quizStatistics(Request $request, Quiz $quiz)
    {
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attempts = $quiz->attempts()->where('status', 'completed')->get();
        
        $totalAttempts = $attempts->count();
        $averageScore = $totalAttempts > 0 ? $attempts->avg('score') : 0;
        $highestScore = $totalAttempts > 0 ? $attempts->max('score') : 0;
        $lowestScore = $totalAttempts > 0 ? $attempts->min('score') : 0;

        // Pass rate (assuming passing score is in settings)
        $passingScore = $quiz->settings->passing_score ?? 0;
        $passedCount = $attempts->where('score', '>=', $passingScore)->count();
        $passRate = $totalAttempts > 0 ? ($passedCount / $totalAttempts) * 100 : 0;

        return response()->json([
            'total_attempts' => $totalAttempts,
            'average_score' => round($averageScore, 2),
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'pass_rate' => round($passRate, 2) . '%',
            'passing_score' => $passingScore,
        ]);
    }
}
