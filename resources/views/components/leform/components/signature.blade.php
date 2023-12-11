@push("custom-scripts")
    <script type="text/javascript" src="{{ asset('js/halfdata-plugin/signature_pad.js') }}"></script>

    <script>
        function convertSignatureStringToImage(string) {
            return fetch(string)
                .then(string => string.blob())
                .then(blob => new File([blob], "File name", {
                    type: "image/png"
                }))
                .catch(console.log);
        }

        const canvas = document.querySelector("canvas");
        const width = (document.getElementById("leform-signature-form-input").offsetWidth)  - 24;//window.innerWidth - 50;
        canvas.height= width/2;
        canvas.width= width;
        const penColor = canvas.dataset.color;
        const signaturePad = new SignaturePad(canvas, { penColor });

        const clearButton = document.querySelector("#clear-signature");
        clearButton.addEventListener("click", () => {
            signaturePad.clear();
        });

        const form = document.querySelector("form");
        form.addEventListener("submit", (e) => {
            e.preventDefault();

            if (!signaturePad.isEmpty()) {
                convertSignatureStringToImage(
                    signaturePad.toDataURL()
                ).then((file) => {
                    const formData = new FormData();
                    formData.append("_token", "{{ csrf_token() }}");
                    formData.append("signature_token", "{{ $signatureToken }}");
                    formData.append("signature", file);

                    fetch("{{ route('submit-signature') }}", {
                        method: "POST",
                        body: formData,
                    })
                        .then(() => {
                            var form = document.getElementById('leform-signature-form-input');
                            if(form) {
                                form.classList.remove('hidden');
                                form.classList.add('hidden');
                            }
                            var message = document.getElementById('leform-signature-form-message');
                            if(message) {
                                message.classList.remove('hidden');
                            }
                        })
                        .catch(console.log);
                });
            }
        });
    </script>
@endpush

<x-guest-layout>
    <form class="p-3" id="leform-signature-form-input">
        <div class="leform-signature-box mb-3">
            <canvas
                class="border-2 border-black mb-2 w-full md:w-auto signature-pad"
                data-color="#010101"
                {{-- width="800"
                height="400" --}}
                {{--
                data-color="{{ $form_options['input-text-style-color'] }}"
                --}}
            ></canvas>
            <input type="file" name="" class="hidden" />
        </div>

            <div class="flex justify-between">
                <button
                    id="clear-signature"
                    class="px-6 py-2 bg-red-500 text-white rounded-md"
                    type="button"
                >
                    {{ __("Clear") }}
                </button>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-500 text-white rounded-md"
                >
                    {{ __("Submit") }}
                </button>
            </div>
        </form>
    </div>
    <div class="p-5 bg-green-500 flex hidden" id="leform-signature-form-message">
        <p style="color: #fff;">
            {{ __("Success, you can close this site now") }}
        </p>
    </div>
</x-guest-layout>

