@props([
    "settings",
    "errors",
])

<div x-show="section === 'sftp'">
    <form action="{{ route('update-sftp-details') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="sftp-host" class="mb-1 inline-block">
                   {{ __('Host') }}
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="sftp-host"
                    name="sftp-host"
                    value="{{ $settings['sftp-host'] }}"
                    @if (old('sftp-host'))
                        value="{{ old('sftp-host') }}"
                    @else
                        value="{{ $settings['sftp-host'] }}"
                    @endif
                />
                <br />
                {{ $errors->sftp->first('sftp-host') }}
            </div>

            <div>
                <label for="sftp-port" class="mb-1 inline-block">
                   {{ __('Port') }}
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="sftp-port"
                    name="sftp-port"
                    pattern="[0-9]+"
                    value="{{ $settings['sftp-port'] }}"
                />
                <br />
                {{ $errors->sftp->first('sftp-port') }}
            </div>

            <div>
                <label for="sftp-username" class="mb-1 inline-block">
                   {{ __('Username') }}
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="sftp-username"
                    name="sftp-username"
                    value="{{ $settings['sftp-username'] }}"
                />
                <br />
                {{ $errors->sftp->first('sftp-username') }}
            </div>

            <div>
                <label for="sftp-password" class="mb-1 inline-block">
                   {{ __('Password') }}
                </label>
                <br />
                <input
                    class="w-full"
                    type="password"
                    id="sftp-password"
                    name="sftp-password"
                    autocomplete="sftp-password"
                    value="{{ $settings['sftp-password'] }}"
                />
                <br />
                {{ $errors->sftp->first('sftp-password') }}
            </div>

            <div class="md:col-span-2">
                <label for="sftp-path" class="mb-1 inline-block">
                   {{ __('Path') }}
                </label>
                <br />
                <input
                    class="w-full"
                    type="text"
                    id="sftp-path"
                    name="sftp-path"
                    autocomplete="sftp-path"
                    value="{{ $settings['sftp-path'] }}"
                />
                <br />
                {{ $errors->sftp->first('sftp-path') }}
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
