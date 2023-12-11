@props([
    "filters",
    "displayedFilter",
    "displayedFilterValue"
])

<form action="{{ route('entries') }}" method="get">
    @foreach($filters as $key => $value)
        @if ($value !== null || $key == $displayedFilter)
            <input
                type="hidden"
                name="{{ $key }}"
                value="{{ $key == $displayedFilter ? $displayedFilterValue : $value }}"
            />
        @endif
    @endforeach

    <button type="submit" title="{{ __('Filter entries list by this element') }}" style="font-size: 9px; border: 1px solid #ccc; padding: 2px; margin-left: 5px; line-height: 1;">
        <i class="fas fa-filter"></i>
    </button>
</form>
