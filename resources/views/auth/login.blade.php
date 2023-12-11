<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" placeholder="E-Mail-Adresse" :value="old('email')" required autofocus />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
								placeholder="Passwort"
                                required autocomplete="current-password" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="remember">
                    <span class="ml-2 text-sm text-gray-600">Angemeldet bleiben</span>
                </label>
            </div>
                
            <div>
                {{--
                <a
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                    href="{{ route('register') }}"
                >
                    Create an account
                </a>
                --}}

                <div class="mb-3">
                    <x-button class="w-full text-center">
                        Anmelden
                    </x-button>
                </div>

				<p>
					@if (Route::has('password.request'))
						<a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
							Passwort vergessen?
						</a>
					@endif
				</p>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
