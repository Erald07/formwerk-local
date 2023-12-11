@props([
    "text" => "",
    "filters",
    "sortField",
])

<form action="{{ route('entries') }}" method="get">
    @foreach($filters as $key => $value)
        @if ($value !== null && !in_array($key, ["sort_by", "sort_order"]))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
        @endif
    @endforeach

    @if ($filters["sort_by"] === $sortField)
        @if ($filters["sort_order"] !== "desc")
            <input
                type="hidden"
                name="sort_by"
                value="{{ $sortField }}"
            />
            <input
                type="hidden"
                name="sort_order"
                @switch($filters["sort_order"])
                    @case("asc")
                        value="desc"
                        @break
                    @case("desc")
                        @break
                    @default
                        value="asc"
                        @break
                @endswitch
            />
        @endif
    @else
        <input
            type="hidden"
            name="sort_by"
            value="{{ $sortField }}"
        />
        <input
            type="hidden"
            name="sort_order"
            value="asc"
        />
    @endif

    <button type="submit" class="font-bold">
        {{ __($text) }}

        @if ($filters["sort_by"] === $sortField)
            @if ($filters["sort_order"] === "asc")
                <i class="fas fa-sort-up"></i>
            @else
                <i class="fas fa-sort-down"></i>
            @endif
        @else
            <i class="fas fa-sort"></i>
        @endif
    </button>
</form>
