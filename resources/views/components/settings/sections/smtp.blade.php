@props([
    "settings",
    "errors",
])

<div x-show="section === 'smtp'">
    <form action="{{ route('update-smtp-details') }}" method="POST">
        @csrf

        <div class="md:grid md:grid-cols-3">
            <div class="mb-4 md:mr-4">
                <label for="smtp-host" class="mb-1 inline-block">
                   {{ __('Host') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="smtp-host"
                    name="smtp-host"
                    value="{{ $settings['smtp-host'] }}"
                    @if (old('smtp-host'))
                        value="{{ old('smtp-host') }}"
                    @else
                        value="{{ $settings['smtp-host'] }}"
                    @endif
                />
                <br />
                {{ $errors->smtp->first('smtp-host') }}
            </div>

            <div class="mb-4 md:mr-4">
                <label for="smtp-username" class="mb-1 inline-block">
                   {{ __('Username') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="smtp-username"
                    name="smtp-username"
                    value="{{ $settings['smtp-username'] }}"
                />
                <br />
                {{ $errors->smtp->first('smtp-username') }}
            </div>

            <div class="mb-4">
                <label for="smtp-password" class="mb-1 inline-block">
                   {{ __('Password') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="password"
                    id="smtp-password"
                    name="smtp-password"
                    autocomplete="smtp-password"
                    value="{{ $settings['smtp-password'] }}"
                />
                <br />
                {{ $errors->smtp->first('smtp-password') }}
            </div>

            <div class="mb-4 md:mr-4">
                <label for="smtp-sender" class="mb-1 inline-block">
                   {{ __('Sender email address') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="smtp-sender"
                    name="smtp-sender"
                    value="{{ $settings['smtp-sender'] }}"
                    @if (old('smtp-sender'))
                        value="{{ old('smtp-sender') }}"
                    @else
                        value="{{ $settings['smtp-sender'] }}"
                    @endif
                />
                <br />
                {{ $errors->smtp->first('smtp-sender') }}
            </div>

            <div class="mb-4 md:mr-4">
                <label for="smtp-protocol" class="mb-1 inline-block">
                   {{ __('Protocol') }} 
                </label>
                <br />
                <input
                    type="radio"
                    id="smtp-protocol-tls"
                    name="smtp-protocol"
                    value="tls"
                    @if ($settings['smtp-protocol'] == 'tls')
                        checked
                    @endif
                />
                <label for="smtp-protocol-tls">{{ __('tls') }}</label>

                <input
                    type="radio"
                    id="smtp-protocol-ssl"
                    name="smtp-protocol"
                    value="ssl"
                    @if ($settings['smtp-protocol'] == 'ssl')
                        checked
                    @endif
                >
                <label for="smtp-protocol-ssl">{{ __('ssl') }}</label>

                <br />
                {{ $errors->smtp->first('smtp-protocol') }}
            </div>

            <div class="mb-4 md:mr-4">
                <label for="smtp-port" class="mb-1 inline-block">
                   {{ __('Port') }} 
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="smtp-port"
                    name="smtp-port"
                    pattern="[0-9]+"
                    value="{{ $settings['smtp-port'] }}"
                />
                <br />
                {{ $errors->smtp->first('smtp-port') }}
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
