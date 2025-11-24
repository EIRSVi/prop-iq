<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\QuizAttempt;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    public function generate(Request $request, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($attempt->status !== 'completed') {
            return response()->json(['message' => 'Quiz not completed'], 400);
        }

        // Check if certificate already exists
        $existing = Certificate::where('attempt_id', $attempt->id)->first();
        if ($existing) {
            return response()->json($existing);
        }

        $certificate = $this->certificateService->generateCertificate($attempt);

        if (!$certificate) {
            return response()->json(['message' => 'Did not meet passing criteria'], 400);
        }

        return response()->json($certificate, 201);
    }

    public function verify(Request $request, $code)
    {
        $certificate = Certificate::where('certificate_code', $code)
            ->with(['user:id,name', 'quiz:id,title'])
            ->first();

        if (!$certificate) {
            return response()->json(['message' => 'Certificate not found'], 404);
        }

        return response()->json($certificate);
    }
}
