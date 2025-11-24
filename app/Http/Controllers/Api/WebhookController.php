<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request, Quiz $quiz)
    {
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $quiz->webhooks;
    }

    public function store(Request $request, Quiz $quiz)
    {
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'event' => 'required|string|in:quiz.started,quiz.completed,quiz.graded',
            'url' => 'required|url',
            'secret' => 'nullable|string',
        ]);

        $webhook = $quiz->webhooks()->create($validated);

        return response()->json($webhook, 201);
    }

    public function destroy(Request $request, Webhook $webhook)
    {
        $quiz = $webhook->quiz;
        
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook->delete();
        return response()->noContent();
    }
}
