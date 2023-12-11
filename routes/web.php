<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Templates;
use App\Http\Controllers\Generator;
use App\Http\Controllers\FormController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AccessTokenController;
use App\Http\Controllers\EntriesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormSignatureController;
use App\Http\Controllers\XMLController;
use App\Http\Controllers\CSVController;
use App\Http\Controllers\CustomReportController;
use App\Http\Controllers\FolderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    $user = $request->user();
	if ($user) {
        if ($user->hasRole("clerk")) {
            return redirect('entries');
        } else {
            return redirect('dashboard');
        }
	} else {
		return redirect('login');
	}
});

Route::get('/html-input', function (Request $request) {
	$fields = $request->all();
	return json_encode($fields);
});
Route::group(['middleware' => 'auth'], function () {
	// Company Admin Routes
	Route::group(
		[
			'namespace' => 'Admin',
			'middleware' => ['role:admin']
		],
		function () {
			Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
			Route::post('/settings/update-site-details', [AdminSettingsController::class, 'updateSiteDetails'])->name('update-site-details');
			Route::post('/settings/update-smtp-details', [AdminSettingsController::class, 'updateSMTPDetails'])->name('update-smtp-details');
			Route::post('/settings/update-sftp-details', [AdminSettingsController::class, 'updateSFTPDetails'])->name('update-sftp-details');
			Route::post('/settings/update-sms-details', [AdminSettingsController::class, 'updateSmsDetails'])->name('update-sms-details');
			Route::post('/settings/update-predefined-values-details', [AdminSettingsController::class, 'updatePredefinedValuesDetails'])->name('update-predefined-values-details');
			Route::post('/settings/create-user', [UserController::class, 'createEmployee'])->name('create-user');
            Route::post('/settings/delete-user/{id}', [UserController::class, 'deleteEmployee'])
                ->name('delete-user')
                ->whereNumber('id');
            Route::post('/settings/change-user-role/{id}', [UserController::class, 'changeUserRole'])
                ->name('change-user-role')
                ->whereNumber('id');
            Route::post('/settings/change-user-password/{id}', [UserController::class, 'changeOtherUserPassword'])
                ->name('change-user-password')
                ->whereNumber('id');
			Route::put('/settings/change-password', [UserController::class, 'changePassword'])->name('change-password');
			Route::post('/access-token', [AccessTokenController::class, 'store'])->name('save-access-token');
			Route::put('/access-token/{tokenId}', [AccessTokenController::class, 'update'])->name('update-access-token');
			Route::delete('/access-token/{tokenId}', [AccessTokenController::class, 'delete'])->name('delete-access-token');
		}
	);
	Route::group([
		'namespace' => 'Admin',
		'middleware' => ['role:admin|employee']
	], function () {
		Route::get('/dashboard/{folder_id?}', [FormController::class, 'index'])->name('dashboard');
		Route::get('/templates', [Templates::class, 'index'])->name('templates');
		Route::get('/generator', [Generator::class, 'index'])->name('generator');
		Route::post('/copy-template', [Templates::class, 'copy'])->name('copy-template');
		Route::post('/forms/use', [FormController::class, 'useForm'])->name('use-form');
		Route::get('/forms/create', [FormController::class, 'create'])->name('create-form');
		Route::post('/forms/create', [FormController::class, 'store'])->name('store-form');
		Route::get('/forms/preview', [FormController::class, 'preview'])->name('preview-form');
		Route::post('/forms/upload-progress', [FormController::class, 'uploadProgress'])->name('upload-progress');

		Route::post('/forms/upload-select-image-file', [FormController::class, 'uploadSelectImageFieldImages'])->name('upload-select-image-file');

		Route::post('/forms/toogle-form-status', [FormController::class, 'toogleFormStatus'])->name('toogle-form-status');
		Route::post('/forms/duplicate-form', [FormController::class, 'duplicateForm'])->name('duplicate-form');
		Route::post('/forms/toggle-form-shareable', [FormController::class, 'toggleFormShareable'])->name('toggle-form-shareable');
		Route::post('/forms/delete-form', [FormController::class, 'deleteForm'])->name('delete-form');
		Route::post('/forms/create/form-background-pdf-upload', [FormController::class, 'formBackgroundPdfUpload'])->name('form-background-pdf-upload');

		Route::put('/forms/{id}/parent/{newParent}', [FormController::class, 'updateParent'])->name('update-parent-folder');

		Route::post('/admin-style-load', [FormController::class, 'adminStyleLoad'])->name('admin-style-load');
		Route::post('/admin-style-save', [FormController::class, 'adminStyleSave'])->name('admin-style-save');
		Route::post('/rename-theme', [ThemeController::class, 'renameTheme'])->name('rename-theme');
		Route::post('/delete-theme', [ThemeController::class, 'deleteTheme'])->name('delete-theme');
		Route::get('/export-style', [ThemeController::class, 'exportStyle'])->name('export-style');
		Route::post('/import-style', [ThemeController::class, 'importStyle'])->name('import-style');

		Route::post('/folder', [FolderController::class, 'create'])->name('create-folder');
		Route::delete('/folder/{id}', [FolderController::class, 'delete'])->name('delete-folder');
		Route::put('/folder/{id}/parent/{newParent}', [FolderController::class, 'updateParent'])->name('update-parent-folder');
		Route::put('/folder/{id}', [FolderController::class, 'update'])->name('update-folder');
		Route::get('/forms/{folder_id?}', [FormController::class, 'index'])->name('forms');

	});
	Route::group([
		'namespace' => 'Admin',
		'middleware' => ['role:admin|employee|clerk']
	], function () {
		Route::get('/entries/download/xml', [XMLController::class, 'downloadXMLFiles'])->name('record-xml-zip-download');
		Route::get('/entries', [EntriesController::class, 'entries'])->name('entries');
		Route::post('/entries/details', [EntriesController::class, 'entriesDetails'])->name('entries-details');
		Route::post('/entries/actions', [EntriesController::class, 'entriesActions'])->name('entries-actions');
		Route::post('/entries/delete-entry', [EntriesController::class, 'deleteEntry'])->name('delete-entry');
		Route::post('/entries/record-field-load-editor', [EntriesController::class, 'recordFieldLoadEditor'])->name('record-field-load-editor');
		Route::post('/entries/record-field-save', [EntriesController::class, 'recordFieldSave'])->name('record-field-save');
		Route::post('/entries/record-field-empty', [EntriesController::class, 'recordFieldEmpty'])->name('record-field-empty');
		Route::post('/entries/record-field-remove', [EntriesController::class, 'recordFieldRemove'])->name('record-field-remove');
		Route::get('/entries/{id}/pdf', [EntriesController::class, 'recordPdfDownload'])->name('record-pdf-download');
		Route::get('/entries/{id}/xml', [XMLController::class, 'downloadXMLFile'])
			->name('record-xml-download')
			->whereNumber('id');
		Route::get('/entries/{id}/csv', [CSVController::class, 'downloadCSVFile'])
			->name('record-csv-download')
			->whereNumber('id');
		Route::get('/entries/{id}/custom_report', [CustomReportController::class, 'downloadFile'])
			->name('record-custom-report-download')
			->whereNumber('id');
    });
});

Route::group(['namespace' => 'Guest'], function () {
	Route::post('/form/file-upload', [FormController::class, 'fileUpload'])->name('file-upload');
	Route::post('/forms/form-remote-init', [FormController::class, 'formRemoteInit'])
		->name('form-remote-init');

	Route::get('/{shortUrl}', [FormController::class, 'formFromShortUrl'])
		->where('shortUrl', '[a-z]{3}\d{2}')
		->name('form-from-short-url');

	Route::get('/{formName}/{shortUrl}', [FormController::class, 'formFromNameAndId'])
		->where('formName', '.*')
		->where('shortUrl', '[a-z]{3}\d{2}')
		->name('form-from-name-and-id');

	Route::post('/{formId}/signature-hash', [FormSignatureController::class, 'addFormSignatureToken'])
		->name('add-form-signature-token');

	Route::get('/signature-input/{signatureToken}', [FormSignatureController::class, 'getSignatureInput'])
		->name('get-signature-input')
		->where('signatureToken', '.*');

	Route::post('/signature-input/email-signature-input-url', [FormSignatureController::class, 'sendSignatureLinkViaEmail'])
		->name('email-signature-input-url');

	Route::post(
		'/signature-input/sms-signature-input-url',
		[FormSignatureController::class, 'sendSignatureLinkViaSms']
	)->name('sms-signature-input-url');

	Route::post(
		'/signature-input',
		[FormSignatureController::class, 'submitSignature']
	)->name('submit-signature');

	Route::get(
		'/signature/{signatureToken}',
		[FormSignatureController::class, 'getSignature']
	)
		->name('get-signature')
		->where('signatureToken', '.*');

	Route::post(
		'/signature/{signatureToken}/delete',
		[FormSignatureController::class, 'deleteSignature']
	)
		->name('delete-signature')
		->where('signatureToken', '.*');

	Route::post('/forms/submit', [FormController::class, 'submitForm'])
		->name('submit-form');

	Route::get('/record-pdf/{recordId}/{strId}', [FormController::class, 'downloadRecordPdfAfterSubmit'])
		->name('get-pdf-of-submited-record');

	Route::get('/attachment/{hash}', [FormController::class, 'downloadAttachmentFromEmail'])
		->name('download-attachment-from-email');
});

Route::get('/not-found', function () {
	return view('not-found');
})->name('not-found');

require __DIR__ . '/auth.php';
