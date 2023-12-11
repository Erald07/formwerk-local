<x-app-layout>
    @section('custom-head')
        <link rel="stylesheet" href="{{ asset("css/halfdata-plugin/admin.css") }}">

        <script>
            let leform_translations = @json(__("frontend_translations"));
        </script>
        <script src="{{ asset("js/jquery.min.js") }}"></script>
        <script src="{{ asset("js/halfdata-plugin/admin.js") }}"></script>
        <script>
            function deleteUser(id) {
                leform_dialog_open({
                    echo_html: function () {
                        this.html(`
                            <div class='leform-dialog-message'>
                                {{ __("Are you sure you want to delete this user?") }}
                            </div>
                        `);
                        this.show();
                    },
                    ok_label: leform_esc_html__('Delete'),
                    ok_function: function (e) {
                        var post_data = { "_token": "{{ csrf_token() }}" };

                        jQuery.ajax({
                            type: "POST",
                            url: `/settings/delete-user/${id}`,
                            data: post_data,
                            success: function (return_data) {
                                window.location.reload();
                                leform_dialog_close();
                            },
                            error: function (XMLHttpRequest, textStatus, errorThrown) {
                                leform_dialog_close();
                                leform_global_message_show("danger", leform_esc_html__("Could not delete user"));
                            }
                        });
                    }
                });
            }
        </script>

        <script>
            function changeUserRole(id, role) {
                var post_data = { "_token": "{{ csrf_token() }}", role };
                jQuery.ajax({
                    type: "POST",
                    url: `/settings/change-user-role/${id}`,
                    data: post_data,
                    success: function () {
                        leform_global_message_show("success", leform_esc_html__("User role changed successfully."));
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        leform_global_message_show("danger", leform_esc_html__("Could not change user role"));
                    }
                });
            }
        </script>

        <script>
            function toggleResetPassword(id) {
                const row = document.querySelector(`#users-table tr[data-user-id='${id}']`);
                const actions = row.querySelector(".actions");
                const resetPasswordInput = row.querySelector(".reset-password-input");
                actions.classList.toggle("hidden");
                resetPasswordInput.classList.toggle("hidden");
            }

            function resetOtherUserPassword(eventElement, id) {
                const input = eventElement.parentElement.querySelector("input");
                const data = new FormData();
                data.append("_token", "{{ csrf_token() }}");
                data.append("password", input.value);
                fetch(`/settings/change-user-password/${id}`, {
                    method: "POST",
                    body: data,
                })
                    .then(async (res) => {
                        if (res.status < 400) {
                            toggleResetPassword(id);
                            leform_global_message_show("success", leform_esc_html__("User password changed"));
                        } else {
                            const error = await res.text();
                            leform_global_message_show("danger", error);
                        }
                    })
            }
        </script>
    @endsection('custom-head')

    <x-slot name="header">
        <div class="py-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div
                    x-data="{
                        section: 'general',
                        changePasswordUserId: @if (old('user-id')) {{ old('user-id') }} @else false @endif,
                    }"
                    x-init="section = window.location.hash.replace('#', '')"
                    class="p-6 bg-white border-b border-gray-200 flex w-full flex-col md:flex-row"
                >

                    <!-- Settings sections -->
                    <x-settings.section-selector />

                    <!-- Settings editor -->
                    <div class="pl-3 md:pl-12 w-full">
                        <x-settings.sections.general
                            :errors="$errors"
                            :settings="$settings"
                        />

                        <x-settings.sections.smtp
                            :errors="$errors"
                            :settings="$settings"
                        />

                        <x-settings.sections.sftp
                            :errors="$errors"
                            :settings="$settings"
                        />

                        <x-settings.sections.sms
                            :errors="$errors"
                            :settings="$settings"
                        />

                        <x-settings.sections.user-management
                            :users="$users"
                            :errors="$errors"
                            :roles="$roles"
                        />

                        <x-settings.sections.api
                            :accessTokens="$accessTokens"
                        />

                        <x-settings.sections.change-password
                            :users="$users"
                            :errors="$errors"
                        />

                        <x-settings.sections.predefined-values
                            :errors="$errors"
                            :settings="$settings"
                        />
                    </div>

                </div>
            </div>
        </div>
    </div>

    <x-leform.editor.dialog-overlay />

    <div id="leform-global-message"></div>
</x-app-layout>
