@props([
    "formId",
    "formPages",
    "toolbarTools",
    "faSolid",
    "faRegular",
    "faBrands",
    "fontAwesomeBasic",
    "options",
    "predefinedOptions",
    "elementPropertiesMeta",
    "validatorsMeta",
    "filtersMeta",
    "confirmationsMeta",
    "notificationsMeta",
    "integrationsMeta",
    "paymentGatewaysMeta",
    "mathMeta",
    "logicRules",
    "formOptions",
    "formElements",
    "styles",
    "webfonts",
    "localFonts",
    "customFonts",
    "longLink",
    "shortLink"
])

<div class="leform-form-editor">
    <x-leform.editor.toolbars
        :formId="$formId"
        :formPages="$formPages"
        :toolbarTools="$toolbarTools"
        :longLink="$longLink"
        :shortLink="$shortLink"
    />
    <x-leform.editor.builder :formPages="$formPages" />
</div>

<x-leform.editor.style-importers />

<x-leform.editor.element-properties />

<x-leform.editor.fa-selector
    :options="$options"
    :faSolid="$faSolid"
    :faRegular="$faRegular"
    :faBrands="$faBrands"
    :fontAwesomeBasic="$fontAwesomeBasic"
/>

<x-leform.editor.bulk-options :predefinedOptions="$predefinedOptions" />

<x-leform.editor.more-using />

<x-leform.editor.style-manager />

<x-leform.editor.preview />

<div id="leform-global-message"></div>

@if (empty($formId))
    <x-leform.editor.create-new />
@endif

<x-leform.editor.dialog-overlay />

<input type="hidden" id="leform-id" value="{{ $formId }}" />

@section('custom-js')
    <script>
        let leform_webfonts = @json($webfonts); {{-- fetched from the db --}}
        let leform_localfonts = @json($localFonts); {{-- feature galore --}}
        let leform_customfonts = @json($customFonts); {{-- feature galore x2 --}}
        let leform_toolbar_tools = @json($toolbarTools);
        let leform_meta = @json($elementPropertiesMeta);
        let leform_validators = @json($validatorsMeta);
        let leform_filters = @json($filtersMeta);
        let leform_confirmations = @json($confirmationsMeta);
        let leform_notifications = @json($notificationsMeta);
        let leform_integrations = @json($integrationsMeta);
        let leform_payment_gateway = @json($paymentGatewaysMeta);
        let leform_math_expressions_meta = @json($mathMeta);
        let leform_logic_rules = @json($logicRules);
        let leform_predefined_options = @json($predefinedOptions);
        let leform_form_options = @json($formOptions);
        let leform_form_pages_raw = @json($formPages);
        let leform_form_elements_raw = @json($formElements);
        let leform_integration_providers = {};
        let leform_payment_providers = [];
        let leform_styles = @json($styles); {{-- awaiting for a fairly good idea on organising the styles --}}

        jQuery(document).ready(function(){leform_form_ready();});
    </script>
@endsection

