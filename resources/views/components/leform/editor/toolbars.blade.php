@props(["formId", "formPages", "toolbarTools", "longLink", "shortLink"])

<div class="leform-toolbars">
    <x-leform.editor.toolbars.header :formId="$formId" :longLink="$longLink" :shortLink="$shortLink" />

    <x-leform.editor.toolbars.pages-bar :formPages="$formPages" />

    <x-leform.editor.toolbars.toolbar-list :toolbarTools="$toolbarTools" />
</div>
