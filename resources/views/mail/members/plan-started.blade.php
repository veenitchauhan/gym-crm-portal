<x-mail::message>
# Welcome to {{ $subscription->membershipPlan->name }}

Hi {{ $member->name }},

Your membership at **{{ $subscription->gym->name }}** is ready.

<x-mail::panel>
**Plan:** {{ $subscription->membershipPlan->name }}  
**Starts:** {{ $subscription->starts_at->format('d M Y') }}  
**Ends:** {{ $subscription->ends_at->format('d M Y') }}  
**Plan amount:** ₹{{ number_format((float) $subscription->price, 2) }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
Open member portal
</x-mail::button>

Use **{{ $member->email }}** to sign in. If your password has not been created yet, use the separate secure password setup email sent to you.

We’re glad to have you with us.

Thanks,<br>
{{ $subscription->gym->name }}
</x-mail::message>
