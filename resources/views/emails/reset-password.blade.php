@component('mail::message')
# Reset Password

Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

Link ini akan kadaluarsa dalam {{ config('auth.passwords.users.expire', 60) }} menit.

Jika Anda tidak meminta reset password, abaikan email ini.

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent