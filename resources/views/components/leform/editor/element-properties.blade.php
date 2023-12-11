<div class="leform-admin-popup-overlay" id="leform-element-properties-overlay"></div>
<div class="leform-admin-popup" id="leform-element-properties">
    <div class="leform-admin-popup-inner">
        <div class="leform-admin-popup-title">
            <a
                href="#"
                title="{{ __('Close') }}"
                onclick="return leform_properties_close();"
            >
                <i class="fas fa-times"></i>
            </a>
            <h3>
                <i class="fas fa-cog"></i>
                {{ __('Element Properties') }}
            </h3>
        </div>
        <div class="leform-admin-popup-content">
            <div class="leform-admin-popup-content-form"></div>
        </div>
        <div class="leform-admin-popup-buttons">
            <a
                class="leform-admin-button"
                href="#"
                onclick="return leform_properties_save();"
            >
                <i class="fas fa-check"></i>
                <label>
                   {{ __('Save Details') }} 
                </label>
            </a>
        </div>
        <div class="leform-admin-popup-loading">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>
</div>

