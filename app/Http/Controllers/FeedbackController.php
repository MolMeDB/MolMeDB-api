<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\RequestFeedbackEmailVerificationRequest;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Http\Requests\Feedback\VerifyFeedbackEmailRequest;
use App\Mail\FeedbackEmailVerificationMail;
use App\Models\FeedbackEmailVerification;
use App\Models\FeedbackSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    private const CODE_ATTEMPTS_LIMIT = 5;

    public function requestEmailVerification(RequestFeedbackEmailVerificationRequest $request): JsonResponse
    {
        $email = Str::lower($request->validated('email'));
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(15);

        FeedbackEmailVerification::query()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Mail::to($email)->send(new FeedbackEmailVerificationMail($code, $expiresAt));

        return response()->json([
            'message' => 'Verification code has been sent.',
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function verifyEmail(VerifyFeedbackEmailRequest $request): JsonResponse
    {
        $data = $request->validated();
        $email = Str::lower($data['email']);

        $verification = FeedbackEmailVerification::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'code' => ['Verification code is invalid or has expired.'],
            ]);
        }

        if ($verification->attempts >= self::CODE_ATTEMPTS_LIMIT) {
            throw ValidationException::withMessages([
                'code' => ['Too many invalid attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($data['code'], $verification->code_hash)) {
            $verification->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['Verification code is invalid or has expired.'],
            ]);
        }

        $submitToken = Str::random(64);
        $verification->forceFill([
            'submit_token_hash' => Hash::make($submitToken),
            'verified_at' => now(),
        ])->save();

        return response()->json([
            'verification_id' => $verification->id,
            'verification_token' => $submitToken,
            'expires_at' => $verification->expires_at->toISOString(),
        ]);
    }

    public function storeGuest(StoreFeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();

        foreach (['email', 'verification_id', 'verification_token'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    $field => ['This field is required.'],
                ]);
            }
        }

        $email = Str::lower($data['email']);
        $verification = FeedbackEmailVerification::query()
            ->whereKey($data['verification_id'])
            ->where('email', $email)
            ->whereNotNull('verified_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if (
            ! $verification ||
            ! $verification->submit_token_hash ||
            ! Hash::check($data['verification_token'], $verification->submit_token_hash)
        ) {
            throw ValidationException::withMessages([
                'verification_token' => ['Email verification is invalid or has expired.'],
            ]);
        }

        DB::transaction(function () use ($request, $email, $data, $verification): void {
            $this->createSubmission(
                request: $request,
                email: $email,
                message: $data['message'],
                context: $data['context'],
                user: null,
                verification: $verification,
            );

            $verification->forceFill([
                'consumed_at' => now(),
            ])->save();
        });

        return response()->json([
            'message' => 'Feedback has been sent.',
        ], 201);
    }

    public function storeAuthenticated(StoreFeedbackRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $data = $request->validated();

        $this->createSubmission(
            request: $request,
            email: $user->email,
            message: $data['message'],
            context: $data['context'],
            user: $user,
            verification: null,
        );

        return response()->json([
            'message' => 'Feedback has been sent.',
        ], 201);
    }

    private function createSubmission(
        StoreFeedbackRequest $request,
        string $email,
        string $message,
        string $context,
        ?User $user,
        ?FeedbackEmailVerification $verification,
    ): FeedbackSubmission {
        return FeedbackSubmission::query()->create([
            'user_id' => $user?->id,
            'feedback_email_verification_id' => $verification?->id,
            'email' => $email,
            'message' => $message,
            'context' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
