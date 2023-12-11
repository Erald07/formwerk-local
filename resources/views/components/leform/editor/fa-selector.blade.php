@props([
    "options",
    "faSolid",
    "faRegular",
    "faBrands",
    "fontAwesomeBasic",
])

<div class="leform-fa-selector-overlay"></div>
<div class="leform-fa-selector">
    <div class="leform-fa-selector-inner">
        <div class="leform-fa-selector-header">
            <a
                href="#"
                title="{{ __('Close') }}"
                onclick="return leform_fa_selector_close();"
            >
                <i class="fas fa-times"></i>
            </a>
            <input type="text" placeholder="{{ __('Search') }}...">
        </div>
        <div class="leform-fa-selector-content">
            <span
                title="{{ __('No icon') }}"
                onclick="leform_fa_selector_set(this);"
            >
                <i class=""></i>
            </span>
            @if ($options['fa-enable'] == 'on')
                @if ($options['fa-solid-enable'] == 'on')
                    @foreach ($faSolid as $value)
                        <span
                            title="{{ ucwords(str_replace(["-"], [" "], $value)) }}"
                            onclick="leform_fa_selector_set(this);"
                        >
                            <i class="fas fa-{{ $value }}"></i>
                        </span>
                    @endforeach
                @endif
                @if ($options['fa-regular-enable'] == 'on')
                    @foreach ($faRegular as $value)
                        <span
                            title="{{ ucwords(str_replace(["-"], [" "], $value)) }}"
                            onclick="leform_fa_selector_set(this);"
                        >
                            <i class="far fa-{{ $value }}"></i>
                        </span>
                    @endforeach
                @endif
                @if ($options['fa-brands-enable'] == 'on')
                    @foreach ($faBrands as $value)
                        <span
                            title="{{ ucwords(str_replace(["-"], [" "], $value)) }}"
                            onclick="leform_fa_selector_set(this);"
                        >
                            <i class="fab fa-{{ $value }}"></i>
                        </span>
                    @endforeach
                @endif
            @else
                @foreach ($fontAwesomeBasic as $value)
                    <span
                        title="{{ ucwords(str_replace(["-"], [" "], $value)) }}"
                        onclick="leform_fa_selector_set(this);"
                    >
                        <i class="leform-fa leform-fa-{{ $value }}"></i>
                    </span>
                @endforeach
            @endif
        </div>
    </div>
</div>

