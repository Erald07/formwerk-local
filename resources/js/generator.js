var CryptoJS = require('crypto-js') 
var QRCode = require('qrcode')
var QRCode_options = {
	errorCorrectionLevel: 'H',
	quality: 0.8,
	scale: 30
}
var QRCode_canvas = document.getElementById('generator-qr')

/**
 * Form
 */

let generator = document.getElementById('generator-config')
let generator_forms = document.getElementById('generator-forms')
let generator_variables = document.getElementById('generator-variables');
let generator_variables_template = document.getElementById('generator-variable-template')
let generator_variables_add = document.getElementById('generator-variable-add')
let generator_variable_remove = document.querySelectorAll('.generator-variable-remove');
let generator_uri = document.getElementById('generator-uri')
let generator_review = document.getElementById('generator-review')
var generator_variables_iteration = 0

var generator_init = function() {
	var storage = localstorage_get()
	
	if(storage === null)
		return
	
	storage = JSON.parse(storage);

	if (typeof storage['generator_variables'] != undefined) {
		for (let entry in storage['generator_variables']) {
			generator_variables_add_block(entry, storage['generator_variables'][entry]);
		}
	}

	if (typeof storage['generator_form_uri'] != undefined) {
		generator_forms.value = storage['generator_form_uri']

		var uri = generate_uri_with_token(storage['generator_variables'], storage['generator_form_uri']);

		generator_uri.value = uri
		generator_review.innerHTML = JSON.stringify(storage['generator_variables'], null, 4)
		generate_qr_code(uri)
	}
}

generator_forms.addEventListener("change", function() {
	let form_id = generator_forms.selectedOptions[0].dataset.form_id;
	
	generator.querySelectorAll('.generator-form-variables-available').forEach((el) => {
		el.classList.add('hidden');
	})
	generator.querySelector('.generator-form-variables-available[data-form_id="' + form_id + '"]').classList.remove('hidden');
})

generator.addEventListener("submit", function (e) {
	e.preventDefault();

	var generator_data = process_form_submit()
	localstorage_set(JSON.stringify(generator_data))

	if (typeof generator_data['generator_form_uri'] == 'undefined') {
		alert('Bitte Formular auswählen')
		return
	}

	var uri = generate_uri_with_token(generator_data['generator_variables'], generator_data['generator_form_uri']);

	generator_uri.value = uri
	generator_review.innerHTML = JSON.stringify(generator_data['generator_variables'], null, 4)
	generate_qr_code(uri)
})

var generate_uri_with_token = function(body, form_uri) {
	var token = generate_jwt_token(body)

	return [form_uri, token].join('?token=')
}

var process_form_submit = function() {
	let generator_form_data = formDataToObject(new FormData(generator));
	var generator_data = {};

	for (let entry in generator_form_data) {
		if (entry.includes('|')) {
			var name = entry.split('|')

			if (typeof generator_data[name[0]] == 'undefined')
				generator_data[name[0]] = {}

			generator_data[name[0]][generator_form_data[entry]] = generator_form_data['generator_variables|value|' + name[2]]
			delete generator_form_data['generator_variables|value|' + name[2]]
		} else {
			generator_data[entry] = generator_form_data[entry]
		}
	}

	return generator_data;
}

function formDataToObject(formData) {
	const object = {};
	for (let pair of formData.entries()) {
		const key = pair[0];
		const value = pair[1];
		const isArray = key.endsWith('[]');
		const name = key.substring(0, key.length - (2 * isArray));
		const path = name.replaceAll(']', '');
		const pathParts = path.split('[');
		const partialsCount = pathParts.length;
		let iterationObject = object;
		for (let i = 0; i < partialsCount; i++) {
			let part = pathParts[i];
			let iterationObjectElement = iterationObject[part];
			if (i !== partialsCount - 1) {
				if (!iterationObject.hasOwnProperty(part) || typeof iterationObjectElement !== "object") {
					iterationObject[part] = {};
				}
				iterationObject = iterationObject[part];
			} else {
				if (isArray) {
					if (!iterationObject.hasOwnProperty(part)) {
						iterationObject[part] = [value];
					} else {
						iterationObjectElement.push(value);
					}
				} else {
					iterationObject[part] = value;
				}
			}
		}
	}

	return object;
}

document.body.addEventListener('click', function (e) {
	if (e.target.classList.contains('generator-variable-remove')) {
		e.preventDefault();
		e.target.parentNode.remove();
	}

	if (e.target.classList.contains('generator-form-variable-available')) {
		e.preventDefault();
		generator_variables_add_block(e.target.innerHTML, '')
	}
});

generator_variable_remove.forEach(function(item) {
	item.addEventListener('click', function(e) {
		e.preventDefault();

		this.parentNode.remove();
	})
})

generator_variables_add.addEventListener("click", function(e) {
	e.preventDefault();

	generator_variables_add_block();
})

var generator_variables_add_block = function(name = '', value = '') {
	var clone = generator_variables_template.cloneNode(true);

	clone.removeAttribute('id')
	clone.classList.remove('hidden');

	var input_name = clone.querySelector('.generator_variables_name');
	var input_value = clone.querySelector('.generator_variables_value')

	input_name.setAttribute('name', 'generator_variables|name|' + generator_variables_iteration)
	input_value.setAttribute('name', 'generator_variables|value|' + generator_variables_iteration)
	input_name.setAttribute('value', name)
	input_value.setAttribute('value', value)

	generator_variables.insertAdjacentHTML('beforeend', clone.outerHTML)
	generator_variables_iteration++;
}

/**
 * Storage
 */

var localstorage_get = function() {
	return localStorage.getItem('formwerk_generator')
}

var localstorage_set = function(data) {
	localStorage.setItem('formwerk_generator', data);
}

/**
 * QR Code
 */

var generate_qr_code = function(content) {
	QRCode.toCanvas(QRCode_canvas, content, QRCode_options, function (error) {
		if (error) console.error(error)
		console.log('success!');
	})
}

/**
 * JWT
 */

var generate_jwt_token = function(body) {
	var header = {
		"alg": "RS256",
		"version": false,
		"typ": "JWT"
	};
	var stringifiedHeader = CryptoJS.enc.Utf8.parse(JSON.stringify(header));
	var encodedHeader = base64url(stringifiedHeader);

	var stringifiedData = CryptoJS.enc.Utf8.parse(JSON.stringify(body));
	var encodedData = base64url(stringifiedData);

	var token = encodedHeader + "." + encodedData;
	var secret = "jadhf279haoFÖKJf9pe21IQPfjweigh13rf";
	var signature = CryptoJS.HmacSHA256(token, secret);
	signature = base64url(signature);
	var signedToken = token + "." + signature;

	return signedToken;
}

var base64url= function(source) {
	// Encode in classical base64
	encodedSource = CryptoJS.enc.Base64.stringify(source);

	// Remove padding equal characters
	encodedSource = encodedSource.replace(/=+$/, '');

	// Replace characters according to base64url specifications
	encodedSource = encodedSource.replace(/\+/g, '-');
	encodedSource = encodedSource.replace(/\//g, '_');

	return encodedSource;
}

generator_init()