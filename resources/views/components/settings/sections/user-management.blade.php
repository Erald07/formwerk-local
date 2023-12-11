@props([
    "users",
    "errors",
    "roles",
])

<div x-show="section === 'user-management'" class="w-full">
    <form
        action="{{ route('create-user') }}"
        method="POST"
        class="md:mr-8 mb-8"
    >
        <h2>{{ __('Create user') }}</h2>
        @csrf

        <div class="grid grid-cols-4 gap-4">
            <div class="mb-4">
                <label for="name" class="mb-1 inline-block">
                   {{ __('Name') }}
                </label>
                <br />
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    class="w-full"
                />
                <br />
                {{ $errors->userManagement->first('name') }}
            </div>

            <div class="mb-4">
                <label for="email" class="mb-1 inline-block">
                   {{ __('Email') }}
                </label>
                <br />
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="w-full"
                />
                <br />
                {{ $errors->userManagement->first('email') }}
            </div>

            <div class="mb-4">
                <label for="password" class="mb-1 inline-block">
                   {{ __('Password') }}
                </label>
                <br />
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="user-password"
                    class="w-full"
                />
                <br />
                {{ $errors->userManagement->first('password') }}
            </div>

            <div class="mb-4">
                <label for="role" class="mb-1 inline-block">
                   {{ __('Role') }}
                </label>
                <br />
                <x-role-select :roles="$roles" />
                <br />
                {{ $errors->userManagement->first('role') }}
            </div>
        </div>

        <button
            class="outline-none px-6 bg-blue-300 text-white py-2 rounded-sm w-full md:w-auto"
            type="submit"
        >
           {{ __('Submit') }}
        </button>
    </form>

    <div class="overflow-x-auto w-full">
        <h1 class="font-bold text-2xl">{{ __('Users') }}</h1>

        <table id="users-table" class="table-auto w-full">
            <thead>
                <tr class="border-b-2 border-gray-400">
                    <th class="text-left p-2">{{ __('Id') }}</th>
                    <th class="text-left p-2">{{ __('Name') }}</th>
                    <th class="text-left p-2">{{ __('Email') }}</th>
                    <th class="text-left p-2">{{ __('Role') }}</th>
                    <th class="text-left p-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr
                        class="border-b-2 border-gray-300"
                        data-user-id="{{ $user['id'] }}"
                        x-data="{ isSettingPassword: false }"
                    >
                        <td class="text-left p-2">
                            {{ $user['id'] }}
                        </td>
                        <td class="text-left p-2">
                            {{ $user['name'] }}
                        </td>
                        <td class="text-left p-2">
                            {{ $user['email'] }}
                        </td>
                        <td class="text-left p-2">
                            <x-role-select
                                :roles="$roles"
                                :value="$user['roleName']"
                                onchange="changeUserRole({{ $user['id'] }}, this.value)"
                            />
                        </td>
                        <td class="text-left p-2">
                            <div class="reset-password-input hidden">
                                <input
                                    id="new-password"
                                    name="new-password"
                                    type="password"
                                />
                                <button
                                    type="button"
                                    class="outline-none px-2 border-2 border-green-200 text-green-200 rounded-sm"
                                    onclick="resetOtherUserPassword(this, {{ $user['id'] }})"
                                >
                                    {{ __("Save") }}
                                </button>
                            </div>
                            <div class="actions">
                                <button
                                    type="button"
                                    class="outline-none px-2 border-2 border-gray-200 text-gray-400 rounded-sm reset-password-toggle"
                                    onclick="toggleResetPassword('{{ $user["id"] }}')"
                                >
                                   {{ __('Reset password') }}
                                </button>

                                <button
                                    type="button"
                                    class="outline-none px-2 border-2 border-red-200 text-red-400 rounded-sm"
                                    onclick="deleteUser({{ $user["id"] }})"
                                >
                                   {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if (count($users) === 0)
                    <tr class="border-b-2 border-gray-300">
                        <td class="text-center" colspan="4">
                           {{ __('No users') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

