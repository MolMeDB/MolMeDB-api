<x-mail::message>
# Upload email verification

Use this code to verify your email address and start managing your MolMeDB upload:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code expires in 15 minutes.

Regards,<br>
{{ config('app.name') }} team.
</x-mail::message>
