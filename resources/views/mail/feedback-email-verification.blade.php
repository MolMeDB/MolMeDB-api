<x-mail::message>
# Email verification

Use this code to verify your email address and send feedback to MolMeDB:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code expires in 15 minutes.

Regards,<br>
{{ config('app.name') }} team.
</x-mail::message>
