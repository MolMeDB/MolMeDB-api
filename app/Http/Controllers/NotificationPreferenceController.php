<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Services\NotificationPreferenceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $types = NotificationType::eligibleFor($user);

        return response()->json([
            'data' => collect($types)->map(fn (NotificationType $type): array => [
                'key' => $type->value,
                'label' => $type->label(),
                'group' => $type->group(),
                'email_enabled' => $this->preferences->emailEnabled($user, $type->value),
                'push_enabled' => $this->preferences->pushEnabled($user, $type->value),
            ])->values(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.key' => ['required', 'string'],
            'preferences.*.email_enabled' => ['required', 'boolean'],
            'preferences.*.push_enabled' => ['required', 'boolean'],
        ]);

        $eligibleKeys = collect(NotificationType::eligibleFor($user))
            ->map(fn (NotificationType $type): string => $type->value)
            ->all();

        foreach ($data['preferences'] as $preference) {
            if (! in_array($preference['key'], $eligibleKeys, true)) {
                continue;
            }

            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $preference['key']],
                ['email_enabled' => $preference['email_enabled'], 'push_enabled' => $preference['push_enabled']],
            );
        }

        return response()->json(['message' => 'Notification preferences saved.']);
    }
}
