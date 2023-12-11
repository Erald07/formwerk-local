@props(["formPages"])

<div class="leform-builder">
    <div class="leform-form-global-style"></div>
    @foreach ($formPages as $formPage)
        <div
            id="leform-form-{{ $formPage['id'] }}"
            class="leform-form leform-elements"
            _data-parent="{{ $formPage['id'] }}"
            _data-parent-col="0"
        ></div>
    @endforeach
</div>
