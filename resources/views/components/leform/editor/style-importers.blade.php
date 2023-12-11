<iframe
    data-loading="false"
    id="leform-import-style-iframe"
    name="leform-import-style-iframe"
    src="about:blank"
    onload="leform_stylemanager_imported(this);"
></iframe>

<form
    id="leform-import-style-form"
    enctype="multipart/form-data"
    method="post"
    target="leform-import-style-iframe"
    action="{{ route('import-style') }}"
>
    @csrf
    <input
        id="leform-import-style-file"
        type="file"
        accept=".json"
        name="leform-file"
        onchange="jQuery('#leform-import-style-iframe').attr('data-loading', 'true'); jQuery('#leform-import-style-form').submit();"
    >
</form>
