<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, Quiz $quiz)
    {
        // Check if quiz allows showing results
        // Assuming 'settings' relationship is loaded or we load it
        $quiz->load('settings');
        
        if ($quiz->settings && !$quiz->settings->show_results && $request->user()->role !== 'admin' && $request->user()->id !== $quiz->author_id) {
             return response()->json(['message' => 'Leaderboard hidden'], 403);
        }

        $leaderboard = $quiz->attempts()
            ->where('status', 'completed')
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderBy('end_time') // Secondary sort by time (earlier is better usually, but here just end_time)
            ->get()
            ->map(function ($attempt) {
                return [
                    'user' => $attempt->user->name,
                    'score' => $attempt->score,
                    'completed_at' => $attempt->end_time,
                ];
            });
        
        // Assign ranks
        $ranked = $leaderboard->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        return response()->json($ranked);
    }
}
