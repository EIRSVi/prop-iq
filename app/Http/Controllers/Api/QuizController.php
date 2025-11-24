<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'admin') {
            return Quiz::with('author')->paginate(20);
        }

        if ($user->role === 'teacher') {
            return Quiz::where('author_id', $user->id)->paginate(20);
        }

        // For students/guests, return published public quizzes
        return Quiz::where('status', 'published')
            ->whereHas('settings', function ($query) {
                $query->where('access_mode', 'public');
            })
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:classic,exam,survey',
            'settings' => 'nullable|array',
            'settings.time_limit' => 'nullable|integer',
            'settings.passing_score' => 'nullable|integer',
            'settings.access_mode' => 'required|string|in:public,private,password',
        ]);

        $quiz = $this->quizService->createQuiz($validated, $request->user()->id);

        return response()->json($quiz->load('settings'), 201);
    }

    public function show(Request $request, Quiz $quiz)
    {
        // Access control logic can be moved to Policy later
        $user = $request->user();
        
        if ($user->id !== $quiz->author_id && $user->role !== 'admin') {
            // If student, check if published and accessible
            if ($quiz->status !== 'published') {
                return response()->json(['message' => 'Quiz not available'], 404);
            }
            // Hide correct answers if student
            return $quiz->load(['questions.options' => function ($query) {
                $query->select('id', 'question_id', 'content', 'order'); // Exclude is_correct
            }, 'settings']);
        }

        return $quiz->load(['questions.options', 'settings']);
    }

    public function update(Request $request, Quiz $quiz)
    {
        // Policy check: update
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,published,archived',
            'settings' => 'nullable|array',
        ]);

        $quiz->update($validated);

        if (isset($validated['settings'])) {
            $quiz->settings()->update($validated['settings']);
        }

        return $quiz->load('settings');
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        if ($request->user()->id !== $quiz->author_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quiz->delete();
        return response()->noContent();
    }
}
