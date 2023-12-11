<div class="leform-admin-create-overlay">
    <div class="leform-admin-create">
        <div class="leform-admin-create-content">
            <div>
                <input
                    type="text"
                    id="leform-create-name"
                    value=""
                    placeholder="{{ __('Please enter the form name') }}..."
                />
            </div>
            <div class="leform-admin-buttons-create">
                <a
                    class="leform-admin-button leform-admin-button-create"
                    onclick="return leform_create();"
                >
                    <i class="fas fa-check"></i>
                   {{ __('Create New Form') }} 
                </a>
                <a
                    class="leform-admin-button leform-admin-button-create"
                    href="{{ route('forms') }}"
                    style="background-color: #dc3545; border-color: #dc3545;"
                >
                    <i class="fas fa-times"></i>
                   {{ __('Cancel') }} 
                </a>
            </div>
        </div>
    </div>
</div>
