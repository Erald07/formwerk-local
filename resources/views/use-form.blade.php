
<div class="leform-using-details pt-16">
    <div class="ml-20 mr-20">
        <p class="mb-2">{{ __("Copy and paste the snippet code to your website") }}</p>

        <label class="block mb-2 text-lg">{{ __("Iframe integration") }}</label>
        <textarea
            readonly="readonly"
            onclick="this.focus();this.select();"
            readonly="readonly"
            rows="8"
            style="width: 100%;"
        >
<iframe
    title="{{ $form['name'] }}"
    id="formwerk-form-{{ $form['id'] }}"
    name="formwerk-form-{{ $form['id'] }}"
    src="{{ route('form-from-short-url', [
        'shortUrl' => $form['short_link']
    ]) }}"
    style="width: 100%; height: 100%; border: none;"
></iframe></textarea>
    </div>
</div>

