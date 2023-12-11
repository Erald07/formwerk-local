@props([
    "users",
    "errors",
])

@push('custom-scripts')
    <script>
        const changePasswordForm = document
            .querySelector("#change-password-form");

        const newPasswordField = changePasswordForm
            .querySelector("#new-password");
        const newPasswordErrorField = newPasswordField
            .parentElement
            .querySelector(".input-error");

        const repeatNewPasswordField = changePasswordForm
            .querySelector("#repeat-new-password");
        const repeatNewPasswordErrorField = repeatNewPasswordField
            .parentElement
            .querySelector(".input-error");

        changePasswordForm.addEventListener("submit", (event) => {
            newPasswordErrorField.textContent = "";
            repeatNewPasswordErrorField.textContent = "";

            if (newPasswordField.value.length < 6) {
                event.preventDefault();
                newPasswordErrorField.textContent = "{{ __('Password length should be at least 6 characters') }}";
                return;
            }

            if (newPasswordField.value !== repeatNewPasswordField.value) {
                event.preventDefault();
                repeatNewPasswordErrorField.textContent = "{{ __('Repeat new password field should be the same as new password field') }}";
                return;
            }
        });
    </script>
@endpush

<div x-show="section === 'change-password'">
    <h2>{{ __('Change password') }}</h2>
    <form
        action="{{ route('change-password') }}"
        method="POST"
        id="change-password-form"
    >
        @csrf
        <input type="hidden" name="_method" value="PUT" />

        <div class="md:flex flex-col md:flex-row">
            <div class="mb-2 md:mr-4">
                <label for="old-password">
                   {{ __('Old password') }} 
                </label>
                <br />
                <input
                    class="mb-1 w-full md:w-auto"
                    name="old-password"
                    id="old-password"
                    type="password"
                    required
                />
                <div class="input-error text-red-500 text-sm">
                    {{ $errors->changePassword->first("old-password") }}
                </div>
            </div>

            <div class="mb-2 md:mr-4">
                <label for="new-password">
                   {{ __('New password') }} 
                </label>
                <br />
                <input
                    class="mb-1 w-full md:w-auto"
                    name="new-password"
                    id="new-password"
                    type="password"
                    value="{{ old('new-password') }}"
                    required
                />
                <div class="input-error text-red-500 text-sm">
                    {{ $errors->changePassword->first("new-password") }}
                </div>
            </div>

            <div class="mb-3 md:mr-4">
                <label for="repeat-new-password">
                   {{ __('Repeat new password') }} 
                </label>
                <br />
                <input
                    class="mb-1 w-full md:w-auto"
                    name="repeat-new-password"
                    id="repeat-new-password"
                    type="password"
                    required
                />
                <div class="input-error text-red-500 text-sm">
                    {{ $errors->changePassword->first("repeat-new-password") }}
                </div>
            </div>
        </div>

        <div class="md:flex md:justify-end">
            <button
                class="outline-none px-6 bg-blue-300 text-white py-2 rounded-sm w-full md:w-auto"
                type="submit"
            >
               {{ __('Change password') }} 
            </button>
        </div>
    </form>
</div>

