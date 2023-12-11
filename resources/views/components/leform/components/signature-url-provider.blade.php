<div
    class="leform-element leform-element-{{ $element["id"] }} @if ($properties["label-style-position"]) leform-element-label-{{ $properties["label-style-position"] }} @endif @if ($element["description-style-position"]) leform-element-description-{{ $element["description-style-position"] }} @endif "
    data-type="{{ $element["type"] }}"
    data-deps="@if (array_key_exists($element["id"], $form_dependencies))
        {{ implode(",", $form_dependencies[$element["id"]]) }}
    @endif"
    data-id="{{ $element["id"] }}"
    data-signature-height="{{ $element["height"] }}"
>
    <div class="leform-column-label {{ $column_label_class }}">
        <label style="font-size: 1.4em" class="leform-label {{ 
            $element["label-style-align"]
            && "leform-ta-".$element["label-style-align"]
        }}">
            {!! $properties["required-label-left"] !!}
            {!! $element["label"] !!}
            {!! $properties["required-label-right"] !!}
            {!! $properties["tooltip-label"] !!}
        </label>

		<p class="mb-6">
			<?php echo __('The signature can be made directly on the signature field below or drawn via a mobile device') ?>
		</p>
    </div>
    <div>
        <div class="signature-preview hidden">
			<p class="mb-5">
				<img
					src=""
					alt="signature preview"
				/>
			</p>

            <button
                class="clear-signature hidden mt-2 bg-red-500 py-1 px-3"
                style="color: white;"
            >
                {{ __("Clear signature") }}
            </button>
        </div>

        <form>
			<div class="leform-row leform-element leform-element-3" data-type="columns">
				<div class="leform-col leform-col-7" style="padding-right: 10px">
					<label class="leform-label" style="font-size: 1.2em">{{ __("Sign directly") }}</label>

					<div class="input-for-signature md:w-min" style="width: 100%;">
						<canvas
							class="border-2 border-gray mb-2 w-full md:w-auto signature-pad"
							data-color="#010101"
							data-height="200"
						></canvas>
						<div class="flex justify-between">
							<button
								class="px-6 py-2 clear-signature md:mb-2"
								style="	"
								type="button"
							>
								{{ __("Clear") }}
							</button>
							<button
								class="px-6 py-2 bg-green-500 submit-signature"
								style="color: white; font-weight: bold; font-size: 1.2em"
								type="button"
							>
								{{ __("Submit signature") }}
							</button>
						</div>
					</div>
				</div>
				<div class="leform-col leform-col-5">
					<label class="leform-label" style="font-size: 1.2em">{{ __("Or") }}</label>

					<div class="scan-qr-code-for-signature mb-5">
						<p class="mb-3">{{ __("Scan QR code with a mobile device and sign directly on it") }}</p>

						<div class="qr-code"></div>
					</div>

					<div class="signature-input-methods">
						@if ($smtpSettingsConfigured) 
							<div class="send-email-for-signature ">
								<p class="mb-3">{{ __("Or receive a signature link by email") }}</p>

								<div style="display: flex; flex-direction: row; flex-grow: 1; flex-wrap: wrap">
									<input
										type="email"
										name="email"
										style="min-width: 170px;"
										placeholder="{{ __("Email address") }}"
										class="block px-2 py-1 w-full md:w-auto md:inline-block"
									/>
									<button
										type="button"
										class="px-6 py-2 bg-blue-500 text-base"
										style="color: white; text-align: center;"
									>
										{{ __("Receive email") }}
									</button>
								</div>
								<div
									class="message-box inline-block px-2 mt-2 rounded"
									style="color: white;"
								></div>
							</div>
						@endif

						@if ($smsSettingsConfigured) 
							<div class="send-sms-for-signature ">
								<p class="mb-3">{{ __("Or send a signature link to a mobile device") }}</p>

								<div style="display: flex; flex-direction: row; flex-grow: 1; flex-wrap: wrap">
									<input
										style="min-width: 170px;"
										type="text"
										name="phonenumber"
										placeholder="{{ __("Mobile number") }}"
										class="block w-full md:w-auto md:inline-block"
									/>
									<button
										type="button"
										class="px-6 py-2 bg-blue-500 text-base"
										style="color: white; text-align: center;"
									>
										{{ __("Receive sms") }}
									</button>
								</div>
								<div
									class="message-box inline-block px-2 mt-2 rounded"
									style="color: white;"
								></div>
							</div>
						@endif
					</div>
				</div>
            </div>
        </form>
    </div>

    <div class="leform-column-input {{ $column_input_class }}">
        <div class="leform-input {{ $extra_class }} {{ $properties["tooltip-input"] }}">
            <input
                type="hidden"
                name="leform-{{ $element['id'] }}"
                value=""
            />
        </div>
        <label class="leform-description {{$element['description-style-align'] != "" ? "leform-ta-".$element['description-style-align'] : ""}}">
            {!!replaceWithPredefinedValues(
                $properties["required-description-left"]
                .$element["description"]
                .$properties["required-description-right"]
                .$properties["tooltip-description"],
                $predefinedValues
            )!!}
        </label>
    </div>
</div>

