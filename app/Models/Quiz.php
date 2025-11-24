<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'author_id',
        'status',
        'type',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function settings()
    {
        return $this->hasOne(QuizSettings::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'quiz_groups');
    }
}
