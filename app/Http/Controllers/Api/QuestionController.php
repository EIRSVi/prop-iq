<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string',
            'content' => 'required|string',
            'points' => 'integer|min:0',
            'options' => 'nullable|array',
            'options.*.content' => 'required|string',
            'options.*.is_correct' => 'boolean',
        ]);

        $question = $quiz->questions()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'points' => $validated['points'] ?? 1,
            'order' => $quiz->questions()->count() + 1,
        ]);

        if (isset($validated['options'])) {
            foreach ($validated['options'] as $index => $optionData) {
                $question->options()->create([
                    'content' => $optionData['content'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'order' => $index + 1,
                ]);
            }
        }

        return response()->json($question->load('options'), 201);
    }

    public function update(Request $request, Question $question)
    {
        $quiz = $question->quiz;
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'sometimes|string',
            'points' => 'sometimes|integer|min:0',
            'options' => 'nullable|array',
        ]);

        $question->update($validated);

        if (isset($validated['options'])) {
            // Simple strategy: delete all and recreate (for prototype)
            // Or update existing. Let's do delete and recreate for simplicity now.
            $question->options()->delete();
            foreach ($validated['options'] as $index => $optionData) {
                $question->options()->create([
                    'content' => $optionData['content'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'order' => $index + 1,
                ]);
            }
        }

        return $question->load('options');
    }

    public function destroy(Request $request, Question $question)
    {
        $quiz = $question->quiz;
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $question->delete();
        return response()->noContent();
    }
}
