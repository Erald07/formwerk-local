@props([
    'formElement',
    'predefinedValues' => [],
    'leformOptions',
    'options',
    'toolbarTools',
    'rawElements',
    'formLogic',
    'record'
])
@if (isset($formElement->type) && array_key_exists($formElement->type, $toolbarTools))
    @switch($formElement->type)
        @case('file')
            <x-inputs.file :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('email')
            <x-inputs.email :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('text')
            <x-inputs.text :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('number')
            <x-inputs.number :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('numspinner')
            <x-inputs.numspinner :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('password')
            <x-inputs.password :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('date')
            <x-inputs.date :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('time')
            <x-inputs.time :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('textarea')
            <x-inputs.textarea :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('signature')
            <x-inputs.signature :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('rangeslider')
            <x-inputs.rangeslider :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case('select')
            <x-inputs.select :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case('checkbox')
            <x-inputs.checkbox :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case('matrix')
            <x-inputs.matrix :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break
        @case("imageselect")
            <x-inputs.imageselect :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case("multiselect")
            <x-inputs.multiselect :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case("radio")
            <x-inputs.radio :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case("tile")
            <x-inputs.tile :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case("star-rating")
            <x-inputs.star-rating :options="$options" :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions" :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case('html')
            <x-inputs.html :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break


        @case('columns')
            <x-inputs.columns
                :options="$options"
                :predefinedValues="$predefinedValues"
                :leformOptions="$leformOptions"
                :element="$formElement"
                :toolbarTools="$toolbarTools"
                :formLogic="$formLogic"
                :rawElements="$rawElements"
                :record="$record"
                class="mt-4"
            />
        @break

        @case('repeater-input')
            <x-inputs.repeater-input.index :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @case('background-image')
            <x-inputs.background-image :options="$options" :predefinedValues="$predefinedValues" :leformOptions="$leformOptions"
                :element="$formElement" :toolbarTools="$toolbarTools" class="mt-4" />
        @break

        @default
        @break
    @endswitch
@endif
