@props([
    "settings",
    "errors",
])

<div x-show="section === 'sms'">
    <form action="{{ route('update-sms-details') }}" method="POST">
        @csrf

        <div class="grid grid-cols-2 mb-4">
            <div class="pr-4">
                <label>
                    {{ __("Where can I find my key!") }}
                    <br />
                    {!! __("You need to create an account to <a href='http://sms77.io/' target='_blank'>sms77</a> and follow this steps:") !!}
                    <ol>
                        <li>
                            1. {{ __("Login to your account") }}
                        </li>
                        <li>
                            2. {{ __("Navigate to Developer") }}
                        </li>
                        <li>
                            3. {{ __("If You Don't have an API key create a live API key") }}
                        </li>
                        <li>
                            4. {{ __("Copy Key and paste it here") }}
                        </li>
                    <ol>
                </label>
            </div>
            <div>
                <label for="sms-api-key" class="mb-1 inline-block">
                   {{ __('Sms api key') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="sms-api-key"
                    name="sms-api-key"
                    @isset ($settings['sms-api-key'])
                        value="{{ $settings['sms-api-key'] }}"
                    @endisset
                    @if (old('sms-api-key'))
                        value="{{ old('sms-api-key') }}"
                    @else
                        value="{{ $settings['sms-api-key'] }}"
                    @endif
                />
                <br />
                {{ $errors->sms->first('sms-api-key') }}
            </div>
        </div>

        <div class="md:flex md:justify-end">
            <button
                class="outline-none px-6 bg-blue-300 text-white py-2 rounded-sm w-full md:w-auto"
                type="submit"
            >
               {{ __('Submit') }} 
            </button>
        </div>
    </form>
</div>

