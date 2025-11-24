<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Str;

class QuizService extends BaseService
{
    public function createQuiz(array $data, $authorId)
    {
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        $data['author_id'] = $authorId;
        
        $quiz = Quiz::create($data);
        
        if (isset($data['settings'])) {
            $quiz->settings()->create($data['settings']);
        } else {
            $quiz->settings()->create([]); // Create default settings
        }

        return $quiz;
    }
}
