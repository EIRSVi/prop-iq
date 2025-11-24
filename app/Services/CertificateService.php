<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\QuizAttempt;
use Illuminate\Support\Str;

class CertificateService extends BaseService
{
    public function generateCertificate(QuizAttempt $attempt)
    {
        $quiz = $attempt->quiz;
        $quiz->load('settings');

        // Check if user passed
        if ($quiz->settings && $quiz->settings->passing_score) {
            if ($attempt->score < $quiz->settings->passing_score) {
                return null; // Did not pass
            }
        }

        // Generate unique certificate code
        $code = 'CERT-' . strtoupper(Str::random(12));

        $certificate = Certificate::create([
            'attempt_id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'quiz_id' => $attempt->quiz_id,
            'certificate_code' => $code,
            'score' => $attempt->score,
            'issued_at' => now(),
        ]);

        return $certificate;
    }
}
