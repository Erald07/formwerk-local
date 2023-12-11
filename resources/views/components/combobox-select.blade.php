@props(['name', 'label', 'placeholder', 'options', 'selectedItem', 'labelPropertyName', 'valuePropertyName'])

<div class="flex flex-col">
    <label>{{ $label }}</label>
    <div class="custom-combobox-select" style="max-width: 250px;" data-submit-on-select="true">
        <div class="value-container py-2 px-3 bg-white rounded border-2 border-gray-300 mb-1 flex justify-between items-center cursor-pointer"
            style="min-width: 200px;">
            <input name="{{ $name }}" type="hidden" @if ($selectedItem)
            @if (is_string($selectedItem))
                value="{{ $selectedItem }}"
            @else
                value="{{ $selectedItem[$valuePropertyName] }}"
            @endif
        @else
            value=""
            @endif
            />
            <div class="value-box truncate">
                @if ($selectedItem)
                    @if (is_string($selectedItem))
                        {{ $selectedItem }}
                    @else
                        {{ $selectedItem[$labelPropertyName] }}
                    @endif
                @else
                    {{ $placeholder }}
                @endif
            </div>
            <i class="fa fa-chevron-down text-xs ml-2"></i>
        </div>
        <div class="menu absolute z-10 bg-white p-2 border-2 border-gray-300 rounded hidden flex flex-col max-w-52">
            <input class="search mb-2" type="text" style="margin-right: 0px !important;" />
            <div class="options max-h-40 overflow-y-scroll mt-4">
                <div class="option cursor-pointer pl-2 py-1 mr-2 hover:bg-gray-200 @if ($selectedItem === null) bg-gray-200 @endif"
                    data-label="{{ $placeholder }}" data-value="">
                    {{ $placeholder }}
                </div>
                @foreach ($options as $option)
                    <div class="option cursor-pointer pl-2 py-1 mr-2 hover:bg-gray-200
                            @if ($selectedItem)
                                @if ((is_string($selectedItem) && $selectedItem == $option) || (is_array($selectedItem) && $selectedItem[$valuePropertyName] == $option[$valuePropertyName]))
                                    bg-gray-200
                                @endif
                            @endif
                        "
                        data-label="@if (is_string($option)){{ $option }}@else{{ $option[$labelPropertyName] }}@endif" data-value="@if (is_string($option)){{ $option }}@else{{ $option[$valuePropertyName] }}@endif">
                        @if (is_string($option))
                            {{ $option }}
                        @else
                            {{ $option[$labelPropertyName] }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
