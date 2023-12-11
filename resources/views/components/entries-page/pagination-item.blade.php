@props(["page", "active" => false, "url" => "#"])

@if ($active)
    <span
        class="text-gray-600 px-4 py-2 rounded bg-gray-200"
    >
        {{ $page }}
    </span>
@else
    <a
        href="{{ $url }}"
        class="hover:bg-gray-200 text-gray-600 px-4 py-2 rounded"
        style="color: inherit;"
    >
        {{ $page }}
    </a>
@endif

