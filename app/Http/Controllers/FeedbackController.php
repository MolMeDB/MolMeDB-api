<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\RequestFeedbackEmailVerificationRequest;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Http\Requests\Feedback\VerifyFeedbackEmailRequest;
use App\Http\Resources\UserResource;
use App\Mail\FeedbackEmailVerificationMail;
use App\Models\BaseModel;
use App\Models\Config;
use App\Models\FeedbackEmailVerification;
use App\Models\FeedbackSubmission;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\FeedbackReceived;
use App\Services\EmailLoginService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

use function Illuminate\Support\defer;

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

        $user = app(EmailLoginService::class)->authenticate($request, $verification, $email);

        return response()->json([
            'data' => UserResource::make($user)->resolve($request),
            'meta' => [
                'session_expires_at' => now()
                    ->addMinutes((int) config('session.lifetime'))
                    ->toISOString(),
            ],
        ]);
    }

    public function storeAuthenticated(StoreFeedbackRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $data = $request->validated();

        $submission = $this->createSubmission(
            request: $request,
            email: $user->email,
            message: $data['message'],
            context: $data['context'],
            user: $user,
        );

        $this->deferFeedbackNotifications(
            submission: $submission,
            confirmationEmail: $user->email,
            authenticatedUser: $user,
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
        User $user,
    ): FeedbackSubmission {
        return FeedbackSubmission::query()->create([
            'user_id' => $user->id,
            'feedback_email_verification_id' => null,
            'email' => $email,
            'message' => $message,
            'context' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function deferFeedbackNotifications(
        FeedbackSubmission $submission,
        string $confirmationEmail,
        ?User $authenticatedUser,
    ): void {
        $notificationData = $this->feedbackNotificationData($submission);
        $userId = $authenticatedUser?->id;

        defer(function () use ($confirmationEmail, $notificationData, $userId, $submission): void {
            if ($userId) {
                $user = User::query()->find($userId);

                if ($user) {
                    app(NotificationService::class)->send(
                        user: $user,
                        template: NotificationTemplate::KEY_FEEDBACK_ACCEPTED,
                        data: $notificationData,
                    );
                }
            } else {
                app(NotificationService::class)->sendEmailOnly(
                    email: $confirmationEmail,
                    template: NotificationTemplate::KEY_FEEDBACK_ACCEPTED,
                    data: $notificationData,
                );
            }

            $this->sendFeedbackAddedNotification($submission);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function feedbackNotificationData(FeedbackSubmission $submission): array
    {
        return [
            'feedback_id' => $submission->id,
            'email' => $submission->email,
            'context' => $submission->context,
            'message' => $submission->message,
            'feedback' => [
                'id' => $submission->id,
                'email' => $submission->email,
                'context' => $submission->context,
                'message' => $submission->message,
            ],
            'user' => [
                'id' => $submission->user?->id,
                'name' => $submission->user?->name,
                'email' => $submission->user?->email,
            ],
        ];
    }

    private function sendFeedbackAddedNotification(FeedbackSubmission $feedback): void
    {
        $fallbackEmail = trim((string) Config::get(Config::KEY_FEEDBACK_EMAIL_FALLBACK, ''));

        if (! filled($fallbackEmail)) {
            return;
        }

        try {
            Notification::route('mail', $fallbackEmail)
                ->notify(new FeedbackReceived($feedback->loadMissing('user')));

            activity(BaseModel::ACTIVITY_LOG_SYSTEM)
                ->event('feedback_fallback_email_sent')
                ->performedOn($feedback)
                ->withProperties([
                    'email' => $fallbackEmail,
                ])
                ->log('Feedback fallback email sent.');
        } catch (Throwable $exception) {
            activity(BaseModel::ACTIVITY_LOG_SYSTEM)
                ->event('feedback_fallback_email_failed')
                ->performedOn($feedback)
                ->withProperties([
                    'email' => $fallbackEmail,
                ])
                ->log(Str::limit($exception->getMessage(), 1000, '...'));
        }
    }
}
