@props([
    "settings",
    "errors",
])

<div x-show="section === 'predefined-values'">
    <div class="md:grid md:grid-cols-3">
        <form
            action="{{ route('update-predefined-values-details') }}"
            method="POST"
        >
            @csrf

            <div class="mb-2">
                <label for="predefined-values-secret" class="mb-1 inline-block">
                   {{ __("Predefined values secret") }} 
                </label>

                <input
                    class="block w-full"
                    type="text"
                    id="predefined-values-secret"
                    name="predefined-values-secret"
                    @isset ($settings["predefined-values-secret"])
                        value="{{ $settings['predefined-values-secret'] }}"
                    @endisset
                    @if (old("predefined-values-secret"))
                        value="{{ old('predefined-values-secret') }}"
                    @else
                        value="{{ $settings['predefined-values-secret'] }}"
                    @endif
                />
            </div>

            @if ($errors->predefinedValues->first("predefined-values-secret"))
                <div class="mb-2">
                    {{ $errors->predefinedValues->first("predefined-values-secret") }}
                </div>
            @endif

            <div class="mb-2">
                <label for="moodle-base-url" class="mb-1 inline-block">
                   {{ __("Moodle base url") }} 
                </label>

                <input
                    class="block w-full"
                    type="text"
                    id="moodle-base-url"
                    name="moodle-base-url"
                    @isset ($settings["moodle-base-url"])
                        value="{{ $settings['moodle-base-url'] }}"
                    @endisset
                    @if (old("moodle-base-url"))
                        value="{{ old('moodle-base-url') }}"
                    @endif
                />
            </div>

            @if ($errors->predefinedValues->first("moodle-base-url"))
                <div class="mb-2">
                    {{ $errors->predefinedValues->first("moodle-base-url") }}
                </div>
            @endif

            <button
                class="outline-none px-6 bg-blue-300 text-white py-2 rounded-sm w-full"
                type="submit"
            >
               {{ __("Submit") }} 
            </button>
        </form>
    </div>
</div>

