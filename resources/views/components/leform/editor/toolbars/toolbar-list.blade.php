@props(['toolbarTools'])

<div class="leform-toolbar">
    <ul class="leform-toolbar-list">
        @foreach ($toolbarTools as $key => $value)
            <li
                class="leform-toolbar-tool-{{ $value["type"] }}"
                data-type="{{ $key }}"
                @if (array_key_exists("options", $value))
                    data-option="1"
                @endif
            >
                <a href="#" title="{{ __($value["title"]) }}">
                    <i class="{{ $value["icon"] }}"></i>
                </a>
                @if (array_key_exists("options", $value))
                    <ul>
                        @foreach ($value["options"] as $optionKey => $optionValue)
                            <li
                                data-type="{{ $key }}"
                                data-option="{{ $optionKey }}"
                                title=""
                            >
                                <a href="#" title="{{ __($value['title']) }}">
                                    {{ __($optionValue) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
