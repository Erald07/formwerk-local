@extends('layouts.forms')

@section('custom-head')
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/color-picker.min.css') }}">

    <link rel="stylesheet" id="leform" href="{{ asset("css/halfdata-plugin/admin.css") }}">
    <link rel="stylesheet" id="leform-front" href="{{ asset("css/halfdata-plugin/style.css") }}">
    <link rel="stylesheet" id="tooltipster" href="{{ asset("css/halfdata-plugin/tooltipster.bundle.min.css") }}">
    <link rel="stylesheet" id="leform-fa" href="{{ asset("css/halfdata-plugin/leform-fa.css") }}">
    <link rel="stylesheet" id="leform-if" href="{{ asset("css/halfdata-plugin/leform-if.css") }}">
    <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">
    <link rel="stylesheet" id="material-icons-3.0.1" href="{{ asset("css/halfdata-plugin/material-icons.css") }}">
    <link rel="stylesheet" id="airdatepicker" href="{{ asset("css/halfdata-plugin/airdatepicker.css") }}">
    <link rel="stylesheet" id="minicolors" href="{{ asset("css/halfdata-plugin/jquery.minicolors.css") }}">

	<link rel="stylesheet" href="{{ asset('css/generator.css') }}">

    <script>
        let wpColorPickerL10n = {
            "clear": "Clear",
            "defaultString": "Default",
            "pick": "Select Color",
            "current": "Current Color",
        };
    </script>

    <script src="{{ asset("js/jquery.min.js") }}"></script>
    <script src="{{ asset("js/jquery-ui.min.js") }}"></script>
    <script src="{{ asset("js/iris.min.js") }}"></script>
    <script src="{{ asset("js/color-picker.min.js") }}"></script>
    <script src="{{ asset("js/admin.js") }}"></script>
@endsection

@section('content')
    @if (sizeof($forms) > 0)
		@php 
			$forms = $forms->sortBy('name');
		@endphp

		<div class="bg-gray-100 grid grid-cols-3 gap-10">
			<form id="generator-config">
				<h2>Formular</h2>
				<div class="mb-7">
					<select name="generator_form_uri" id="generator-forms" class="w-full">
						<option disabled selected>{{ __('Select form') }}</option>

						@foreach($forms as $form)
							<option value="{{ route('form-from-short-url', ["shortUrl" => $form['short_link']]) }}" data-form_id="{{ $form['id'] }}">{{ $form['name'] }}</option>
						@endforeach
					</select>
				</div>

				<button id="generator-variable-add" class="float-right border rounded-full border-green-400 hover:bg-green-400 hover:text-white px-2 py-1">
					<i class="fas fa-plus"></i>
				</button>

				<h2>Variablen</h2>
				<div id="generator-variables" class="mb-7">
					<div id="generator-variable-template" class="generator-variable grid grid-flow-col auto-cols-max gap-3 hidden mb-3">
						<input type="text" class="generator_variables_name" placeholder="Name">
						<input type="text" class="generator_variables_value" placeholder="Wert">
						<button class="generator-variable-remove block text-red-400 width-1/4 px-2">
							<i class="fas fa-minus pointer-events-none"></i>
						</button>
					</div>
				</div>

				<div class="mb-7">
					<h3><strong>Verfügbare Variablen</strong></h3>
					
					@foreach($forms as $form)
						<div class="generator-form-variables-available hidden" data-form_id="{{ $form['id'] }}">
							@php
								$form_variables = null;
								preg_match_all("/\\{{(.*?)}}/", $form['elements'], $form_variables);
								$form_variables = array_unique($form_variables[0]);
							@endphp

							@if ($form_variables)
								@foreach($form_variables as $variable)
									<a href="#" class="generator-form-variable-available">{{ str_replace(array('{', '}'), '', $variable) }}</a> 
								@endforeach
							@else
								Keine Variablen gefunden.	
							@endif
						</div>
					@endforeach
				</div>

				<button type="submit" class="inline-flex items-center px-6 py-3 bg-gray-800 border border-transparent rounded-md font-semibold text-s text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 bg-green-500 focus:border-green-900 hover:bg-green-600 active:bg-green-900">QR-Code generieren</button>
			</form>

			<div id="generator-qr-container">
				<h2>QR-Code</h2>

				<canvas id="generator-qr" class="max-w-full mb-7">Generieren...</canvas>

				<p>QR-Code speichern: Rechtsklick auf den QR-Code > Bild speichern unter...</p>
			</div>

			<div>
				<div class="mb-7">
					<h2>URI</h2>
				
					<textarea id="generator-uri" class="break-all bg-white py-2 px-3 border-2 border-dashed font-mono w-full" rows="5" onClick="this.select();" readonly>Generieren...</textarea>
				</div>

				<div class="mb-7">
					<h2>Review</h2>

					<pre id="generator-review" class="break-all bg-white py-2 px-3 border-2 border-dashed font-mono w-full text-sm">Generieren...</pre>
				</div>
			</div>
        </div>

		
    @else
        <tr>
            <td colspan="4" class="leform-table-list-empty">
                {{ __('List is empty') }}.
            </td>
        </tr>
    @endif

    <div id="leform-global-message"></div>    
@endsection

@section('custom-js')
	<script src="{{ asset("js/generator.js") }}"></script>
@endsection