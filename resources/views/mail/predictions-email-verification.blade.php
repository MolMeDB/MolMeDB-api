<x-mail::message>
# Predictions email verification

Use this code to verify your email address and submit prediction calculations on MolMeDB:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code expires in 15 minutes.

Regards,<br>
{{ config('app.name') }} team.
</x-mail::message>
