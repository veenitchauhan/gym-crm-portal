<x-mail::message>
# Welcome to {{ $gym->name }}

Hi {{ $administrator->name }},

Your administrator workspace on {{ config('app.name') }} is ready. You can manage members, plans, payments, trainers, and gym operations from one place.

<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

Your sign-in email is **{{ $administrator->email }}**.

For your security, passwords are never included in email. If you did not expect this account, please contact the platform administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
