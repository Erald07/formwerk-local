@props(["predefinedOptions"])

<div class="leform-admin-popup-overlay" id="leform-bulk-options-overlay"></div>
<div class="leform-admin-popup" id="leform-bulk-options">
    <div class="leform-admin-popup-inner">
        <div class="leform-admin-popup-title">
            <a
                href="#"
                title="{{ __('Close') }}"
                onclick="return leform_bulk_options_close();"
            >
                <i class="fas fa-times"></i>
            </a>
            <h3>
                <i class="fas fa-list-ul"></i>
               {{ __('Add Bulk Options') }} 
            </h3>
        </div>
        <div class="leform-admin-popup-content">
            <div class="leform-admin-popup-content-form">
                <div class="leform-bulk-options-text">
                   {{ __('Click a category on the left side to insert predefined options.  You can edit the options on the right side or enter your own options. One option per line!') }} 
                </div>
                <div class="leform-bulk-options-container">
                    <div class="leform-bulk-categories">
                        <ul>
                            <li
                                data-category="existing"
                                onclick="return leform_bulk_category_add(this);"
                            >
                                <i class="fas fa-plus"></i>
                               {{ __('Existing Options') }} 
                            </li>
                            @foreach ($predefinedOptions as $key => $value)
                                <li
                                    data-category="{{ $key }}"
                                    onclick="return leform_bulk_category_add(this);"
                                >
                                    <i class="fas fa-plus"></i>
                                    {{ $value['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="leform-bulk-editor">
                        <textarea></textarea>
                    </div>
                </div>
                <div class="leform-bulk-options-text">
                    <input
                        class="leform-checkbox-toggle"
                        type="checkbox"
                        id="leform-bulk-options-overwrite"
                    >
                    <label for="leform-bulk-options-overwrite"></label>
                   {{ __('Overwrite existing options') }} 
                </div>
            </div>
        </div>
        <div class="leform-admin-popup-buttons">
            <a
                class="leform-admin-button"
                href="#"
                onclick="return leform_bulk_options_add();"
            >
                <i class="fas fa-plus"></i>
                <label>{{ __('Add Options') }}</label>
            </a>
        </div>
    </div>
</div>

