<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'time_limit',
        'passing_score',
        'shuffle_questions',
        'show_results',
        'access_mode',
        'access_code',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'show_results' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
