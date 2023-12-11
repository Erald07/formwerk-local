<div class="leform-admin-popup-overlay" id="leform-preview-overlay"></div>
<div class="leform-admin-popup" id="leform-preview" data-width="1600">
    <div class="leform-admin-popup-inner">
        <div class="leform-admin-popup-title">
            <a
                href="#"
                title="{{ __('Close') }}"
                onclick="return leform_preview_close();"
            >
                <i class="fas fa-times"></i>
            </a>
            <span
                class="leform-preview-size-mobile"
                data-width="480"
                onclick="leform_preview_size(this);"
            >
                <i class="fas fa-mobile-alt"></i>
            </span>
            <span
                class="leform-preview-size-tablet"
                data-width="960"
                onclick="leform_preview_size(this);"
            >
                <i class="fas fa-tablet-alt"></i>
            </span>
            <span
                class="leform-preview-size-desktop leform-preview-size-active"
                data-width="1600"
                onclick="leform_preview_size(this);"
            >
                <i class="fas fa-tv"></i>
            </span>
            <h3>
                <i class="far fa-eye"></i>
               {{ __('Preview') }} 
                <span></span>
            </h3>
        </div>
        <div class="leform-admin-popup-content">
            <iframe
                data-loading="false"
                id="leform-preview-iframe"
                name="leform-preview-iframe"
                src="about:blank"
                onload="leform_preview_loaded(this);"
            ></iframe>
        </div>
    </div>
</div>
