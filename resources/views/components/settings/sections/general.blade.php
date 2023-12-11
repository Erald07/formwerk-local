@props([
    "settings",
    "errors",
])

<div x-show="section === 'general'">
    <form
        action="{{ route('update-site-details') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="flex flex-col md:flex-row">
            <div class="mb-4 md:mr-8">
                <label for="page-title" class="mb-1 inline-block">
                   {{ __('Page title') }}
                </label>
                <br />
                <input
                    id="page-title"
                    name="page-title"
                    type="text"
                    @if (old('page-title'))
                        value="{{ old('page-title') }}"
                    @else
                        value="{{ $settings['page-title'] }}"
                    @endif
                    maxlength="145"
                    class="w-full md:w-auto"
                />
            </div>

            <div class="mb-4 md:mr-8">
                <label for="logo" class="mb-1 inline-block">
                   {{ __('Logo') }}
                </label>
                <br />
                <img
                    src="{{ $settings['logo-url'] }}"
                    alt="logo"
                    height="50"
                    width="50"
                    class="inline-block mr-4"
                />
                <input
                    id="logo"
                    name="logo"
                    type="file"
                    accept="image/png, image/jpg, image/jpeg"
                    class="w-full md:w-10/12"
                />
            </div>

            <div class="mb-4 md:mr-8">
                <label for="favicon" class="mb-1 inline-block">
                   {{ __('Favicon') }}
                </label>
                <br />
                <img
                    src="{{ $settings['favicon-url'] }}"
                    alt="favicon"
                    height="50"
                    width="50"
                    class="inline-block mr-4"
                />
                <input
                    id="favicon"
                    name="favicon"
                    type="file"
                    accept="image/png"
                    class="w-full md:w-10/12"
                />
            </div>
        </div>

        <div class="mb-4">
            <h2>{{ __("File handling") }}</h2>

            <div class="text-white bg-red-400 mb-3 py-2 px-4 rounded-lg">
                {{ __("Attention! Already deleted files can not be restored.") }}
            </div>

            <div>
                <div
                    class="mb-2"
                    @if (old('automatic-file-delete-active') !== null)
                        x-data="{ value: {{ old('automatic-file-delete-active') }} }"
                    @elseif ($settings['automatic-file-delete-active'])
                        x-data="{ value: {{ $settings['automatic-file-delete-active'] }} }"
                    @else
                        x-data="{ value: 0 }"
                    @endif
                >
                    <input
                        type="checkbox"
                        id="automatic-file-delete-active"
                        name="automatic-file-delete-active"
                        value="1"
                        x-on:change="value = !value"
                        x-bind:checked="value ? true : false"
                    />
                    <label for="automatic-file-delete-active" class="mr-2 inline">
                       {{ __('Automatically delete all data (XML, CSV, custom report) from the Storage, that is older then') }}
                        <input
                            class="inline border-gray-300 h-6 w-20"
                            min="30"
                            max="3650"
                            type="number"
                            id="automatic-file-delete-interval"
                            name="automatic-file-delete-interval"
                            @isset ($settings["automatic-file-delete-interval"])
                                value="{{ $settings['automatic-file-delete-interval'] }}"
                            @endisset
                            @if (old("automatic-file-delete-interval"))
                                value="{{ old('automatic-file-delete-interval') }}"
                            @else
                                value="{{ $settings['automatic-file-delete-interval'] }}"
                            @endif
                        />
                       {{ __('days.') }}
                    </label>
                    <input
                        type="hidden"
                        name="automatic-file-delete-active"
                        x-bind:value="value ? 1 : 0"
                    />
                    <div>
                        {{ $errors->general->first('automatic-file-delete-active') }}
                        {{ __($errors->general->first('automatic-file-delete-interval')) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="md:flex md:justify-end">
            <button
                class="outline-none px-6 bg-blue-300 text-white py-2 rounded-sm"
                type="submit"
            >
               {{ __('Submit') }}
            </button>
        </div>
    </form>
</div>
