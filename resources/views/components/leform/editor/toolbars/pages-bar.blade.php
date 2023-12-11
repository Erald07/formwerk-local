@props(['formPages'])

<div class="leform-pages-bar">
    <ul class="leform-pages-bar-items">
        @foreach($formPages as $formPage)
            <li
                @if ($formPage['id'] == 'confirmation')
                    class="leform-pages-bar-item-confirmation"
                @else
                    class="leform-pages-bar-item"
                @endif
                data-id="{{ $formPage['id'] }}"
                data-name="{{ $formPage['name'] }}"
            >
                <label onclick="return leform_pages_activate(this);">
                    {{ $formPage['name'] }}
                </label>
                <span>
                    <a
                        href="#"
                        @if ($formPage['id'] == 'confirmation')
                            data-type="page-confirmation"
                        @else {
                            data-type="page"
                        @endif
                        onclick="return leform_properties_open(this);"
                    >
                        <i class="fas fa-cog"></i>
                    </a>
                    @if ($formPage['id'] != 'confirmation')
                        <a
                            href="#"
                            @if (sizeof($formPages) <= 1)
                                class="leform-pages-bar-item-delete leform-pages-bar-item-delete-disabled"
                            @else
                                class="leform-pages-bar-item-delete"
                            @endif
                            onclick="return leform_pages_delete(this);"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    @endif
                </span>
            </li>
        @endforeach
        <li class="leform-pages-add" onclick="return leform_pages_add();">
            <label>
                <i class="fas fa-plus"></i>
               {{ __('Add Page') }} 
            </label>
        </li>
    </ul>
</div>
