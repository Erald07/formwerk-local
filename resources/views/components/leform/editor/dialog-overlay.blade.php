<div class="leform-dialog-overlay" id="leform-dialog-overlay"></div>
<div class="leform-dialog" id="leform-dialog">
    <div class="leform-dialog-inner">
        <div class="leform-dialog-title">
            <a
                href="#"
                title="{{ __('Close') }}"
                onclick="return leform_dialog_close();"
            >
                <i class="fas fa-times"></i>
            </a>
            <h3>
                <i class="fas fa-cog"></i>
                <label></label>
            </h3>
        </div>
        <div class="leform-dialog-content">
            <div class="leform-dialog-content-html"></div>
        </div>
        <div class="leform-dialog-buttons">
            <a class="leform-dialog-button leform-dialog-button-ok" href="#" onclick="return false;">
                <i class="fas fa-check"></i>
                <label></label>
            </a>
            <a class="leform-dialog-button leform-dialog-button-cancel" href="#" onclick="return false;">
                <i class="fas fa-times"></i>
                <label></label>
            </a>
        </div>
        <div class="leform-dialog-loading">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>
</div>

