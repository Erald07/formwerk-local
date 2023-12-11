@props(["accessTokens"])

@push('custom-scripts')
    <script>
        function onRemoveDomainButtonClick(e) {
            e.preventDefault();
            const domain = e.target.parentElement;
            domain.parentElement.removeChild(domain);
        }

        function buildRestrictedDomainInput(domain = "") {
            const domainContainer = document.createElement("div");
            domainContainer.classList.add("restricted-domain", "mb-2");

            const domainInput = document.createElement("input");
            domainInput.type = "text";
            domainInput.name = "restricted-domains[]";
            domainInput.setAttribute("required", "true");
            domainInput.value = domain;

            const removeDomainButton = document.createElement("button");
            removeDomainButton.classList.add("rounded-full", "bg-red-500", "px-2", "text-white", "ml-2");
            removeDomainButton.setAttribute("type", "button");
            removeDomainButton.innerText = "x";
            removeDomainButton.addEventListener("click", onRemoveDomainButtonClick);

            domainContainer.appendChild(domainInput);
            domainContainer.appendChild(removeDomainButton);
            return domainContainer;
        }
    </script>

    <script>
        const accessTokenModal = document.querySelector("#access-token-modal");
        const accessTokens = @json($accessTokens);

        function clearForm() {
            const form = accessTokenModal.querySelector("form");
            form.action = "{{ route('save-access-token') }}";

            const putMethodHiddenInput = form
                .querySelector("input[name='_method']");

            if (putMethodHiddenInput) {
                putMethodHiddenInput.parentElement
                    .removeChild(putMethodHiddenInput);
            }

            const nameInput = form.querySelector("input[name='name']");
            nameInput.value = "";

            const restrictedDomains = form
                .querySelectorAll(".restricted-domains .restricted-domain");
            for (const domain of restrictedDomains) {
                domain.parentElement.removeChild(domain);
            }
        }

        function prepareFormForTokenUpdate(form, id) {
            const requestMethodHiddenInput = document.createElement("input");
            requestMethodHiddenInput.type = "hidden";
            requestMethodHiddenInput.value = "PUT";
            requestMethodHiddenInput.name = "_method";

            form.prepend(requestMethodHiddenInput);
            form.action = `/access-token/${id}`;
        }

        const addNewAccessTokenButton = document.querySelector("#add-access-token");
        addNewAccessTokenButton.addEventListener("click", () => {
            accessTokenModal.classList.remove("hidden");
        });

        const closeAccessTokenModal = document.querySelector("#close-access-token-modal");
        closeAccessTokenModal.addEventListener("click", () => {
            accessTokenModal.classList.add("hidden");
            clearForm();
        });

        const editAccessTokenActions = document
            .querySelectorAll("#access-token-table tbody tr .edit-access-token");

        const restrictedDomainsContainer = document.querySelector(".restricted-domains");

        for (const editAccessTokeButton of editAccessTokenActions) {
            const tokenIndex = editAccessTokeButton.dataset.index;
            const token = accessTokens[tokenIndex];
            editAccessTokeButton.addEventListener("click", () => {
                const form = accessTokenModal.querySelector("form");
                prepareFormForTokenUpdate(form, token.id);

                const nameInput = form.querySelector("input[name='name']");
                nameInput.value = token.name;

                accessTokenModal.classList.remove("hidden");
                for (const restrictedDomain of token["restricted_domains"]) {
                    const domainElement = buildRestrictedDomainInput(restrictedDomain.domain);
                    restrictedDomainsContainer.appendChild(domainElement);
                }
            });
        }

        const restrictedDomainsElements = restrictedDomainsContainer
            .querySelectorAll(".restricted-domain");

        const addRestrictedDomainsElements = restrictedDomainsContainer
            .querySelector(".add-domain");

        for (const domain of restrictedDomainsElements) {
            const removeDomainButton = domain.querySelector("button");
            removeDomainButton.addEventListener(
                "click",
                onRemoveDomainButtonClick
            );
        }

        addRestrictedDomainsElements.addEventListener("click", (e) => {
            e.preventDefault();
            restrictedDomainsContainer
                .appendChild(buildRestrictedDomainInput());
        });
    </script>
@endpush

<div x-show="section === 'api'">
    <button id="add-access-token">
        + {{ __('Add access token') }} 
    </button>

    <div class="overflow-x-auto">
        <table id="access-token-table" class="table-auto w-full">
            <thead>
                <tr class="border-b-2 border-gray-400">
                    <th class="text-left p-2">{{ __('Site') }}</th>
                    <th class="text-left p-2">{{ __('Token') }}</th>
                    <th class="text-left p-2">{{ __('Restricted domains') }}</th>
                    <th class="text-left p-2">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($accessTokens as $token)
                    <tr class="border-b-2 border-gray-300">
                        <td class="text-left p-2">
                            {{ $token['name'] }}
                        </td>
                        <td class="text-left p-2">
                            <button @click="navigator.clipboard.writeText('{{ $token['token'] }}')" >
                                {{ $token['token'] }}
                            </button>
                        </td>
                        <td class="text-left p-2">
                            @foreach($token['restrictedDomains'] as $restrictedDomains)
                                <a
                                    href="{{ $restrictedDomains['domain'] }}"
                                    target="_blank"
                                    class="block"
                                >
                                    {{ $restrictedDomains['domain'] }}
                                </a>
                            @endforeach
                        </td>
                        <td class="text-left p-2">
                            <button
                                class="text-blue-500 edit-access-token mr-2"
                                data-index="{{ $loop->index }}"
                            >
                                <i class="fas fa-pen"></i>
                            </button>
                            <form
                                class="inline-block"
                                action="{{ route('delete-access-token', [
                                    'tokenId' => $token['id']
                                ]) }}"
                                method="POST"
                            >
                                @csrf
                                <input type="hidden" name="_method" value="DELETE" />
                                <button class="text-red-500">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div
        id="access-token-modal"
        class="fixed z-10 inset-0 overflow-y-auto hidden"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                aria-hidden="true"
            ></div>

            <span
                class="hidden sm:inline-block sm:align-middle sm:h-screen"
                aria-hidden="true"
            >
                &#8203;
            </span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form
                    action="{{ route('save-access-token') }}"
                    method="POST"
                >
                    @csrf

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h2 class="mb-4">{{ __('Generate access token') }}</h2>

                        <div>
                            <label class="block">
                               {{ __('Name') }} 
                            </label>
                            <input type="text" name="name" required />
                        </div>

                        <div class="restricted-domains">
                            <label class="block">
                               {{ __('Restricted domains') }} 
                            </label>

                            <button class="add-domain" type="button">
                                + {{ __('Add restricted domains') }} 
                            </button>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                           {{ __('Submit') }} 
                        </button>
                        <button
                            id="close-access-token-modal"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                           {{ __('Cancel') }} 
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

