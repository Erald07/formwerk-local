@props(['formId', 'longLink', 'shortLink'])

<div class="leform-header">
    <div class="leform-header-settings">
        <span data-type="settings" onclick="return leform_properties_open(this);">
            <i class="fas fa-cogs"></i>
        </span>
    </div>
    <div class="leform-header-longlink">
        <a href="{{ $longLink }}" target="_blank">
            <i class="fa fa-link"></i> 
            {{ __('Public url') }}
        </a>
    </div>
    <div class="leform-header-shortlink">
        <a href="{{ $shortLink }}" target="_blank">
            <i class="fa fa-link"></i> 
            {{ __('Short url') }}
        </a>
    </div>
    <div class="leform-header-save">
        <span onclick="return custom_leform_save(this);">
            <i class="far fa-save"></i>
           {{ __('Save') }} 
        </span>
    </div>
    <div class="leform-header-preview">
        <span
            @if($formId)
                data-id="{{ $formId }}"
            @else
                style="display: none;"
            @endif
            onclick="custom_leform_preview(this);"
        >
            <i class="far fa-eye"></i>
        </span>
    </div>
    <div class="leform-header-using">
        <span
            @if($formId)
                data-id="{{ $formId }}"
            @else
                style="display: none;"
            @endif
            onclick="leform_more_using_open(this);"
        >
            <i class="fas fa-code"></i>
        </span>
    </div>
</div>
