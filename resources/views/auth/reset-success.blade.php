<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            <div class="text-center mb-6">
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h2 class="mt-6 text-center text-2xl font-extrabold text-gray-900">
                Password Berhasil Direset
            </h2>
            
            <p class="mt-4 text-center">
                Password untuk akun dengan email <strong>{{ $email }}</strong> telah berhasil direset.
                Anda sekarang dapat login menggunakan password baru Anda.
            </p>
        </div>

       
    </x-authentication-card>
</x-guest-layout>