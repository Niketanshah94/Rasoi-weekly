<x-mail::message>
# {{ $signup ? 'Welcome to Rasoi' : 'Log in to Rasoi' }}

{{ $signup ? 'Tap the button to activate your account and plan your first week.' : 'Tap the button to log in.' }} The link works once, for {{ $minutes }} minutes.

<x-mail::button :url="$url">
{{ $signup ? 'Activate my account' : 'Log in' }}
</x-mail::button>

If you didn’t ask for this, you can ignore this email. Nobody can get into your account without it.

Thanks,<br>
The Rasoi team
</x-mail::message>
