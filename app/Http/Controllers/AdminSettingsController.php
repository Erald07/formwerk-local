<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Models\RestrictedDomain;
use App\Models\AccessToken;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    private $defaultSettings = [
        ['name' => 'automatic-file-delete-active', 'value' => 0],
        ['name' => 'automatic-file-delete-interval', 'value' => ''],

        ['name' => 'smtp-host', 'value' => ''],
        ['name' => 'smtp-username', 'value' => ''],
        ['name' => 'smtp-password', 'value' => ''],
        ['name' => 'smtp-protocol', 'value' => 'tls'],
        ['name' => 'smtp-port', 'value' => '587'],
        ['name' => 'smtp-sender', 'value' => ''],

        ['name' => 'sftp-host', 'value' => ''],
        ['name' => 'sftp-username', 'value' => ''],
        ['name' => 'sftp-password', 'value' => ''],
        ['name' => 'sftp-port', 'value' => '22'],
        ['name' => 'sftp-path', 'value' => ''],

        ['name' => 'sms-api-key', 'value' => ''],
        ['name' => 'predefined-values-secret', 'value' => ''],
    ];

    private function createOrUpdateSetting($name, $value, $companyId)
    {
        Setting::updateOrCreate(
            ['name' => $name, 'company_id' => $companyId],
            ['value' => $value]
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $company = company();
        $settings = Setting::where('company_id', $user->company_id)->get();
        $mappedSettings = [];

        foreach ($settings as $setting) {
            $mappedSettings[$setting->name] = $setting->value;
        }
        $mappedSettings['page-title'] = $company->company_name;
        $mappedSettings['logo-url'] = $company->company_logo
            ? $company->company_logo
            : '';
        $mappedSettings['favicon-url'] = $company->company_favicon
            ? $company->company_favicon
            : '';

        $users = User::with('role')
            ->withoutGlobalScope('active')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'roles.name as roleName', 'roles.id as roleId')
            // ->where('roles.name', '<>', 'admin')
            ->get();

        $accessTokens = AccessToken::with('restrictedDomains')->get();
        foreach ($this->defaultSettings as $s) {
            if (!isset($mappedSettings[$s['name']])) {
                $mappedSettings[$s['name']] = $s['value'];
            }
        }

        $roles = Role::where("company_id", $request->user()->company_id)->get();

        return view('settings', [
            'settings' => $mappedSettings,
            'users' => $users,
            'accessTokens' => $accessTokens,
            'roles' => $roles,
        ]);
    }

    public function updateSiteDetails(Request $request)
    {
        $validator = Validator::make($request->input(), [
            'automatic-file-delete-active' => ['required', 'boolean'],
            'automatic-file-delete-interval' => 'required_if:automatic-file-delete-active,1|nullable|integer|between:30,3650',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('settings', ['#general'])
                ->withErrors($validator, 'general')
                ->withInput();
        }

        $company = company();
        $update = [];

        if ($request->has('page-title')) {
            $update['company_name'] =  $request->input('page-title');
        }

        if (
            $request->hasFile('logo')
            && $request->file('logo')->isValid()
        ) {
            $fileExtension = $request->file('logo')->extension();
            $allowedLogoExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array($fileExtension, $allowedLogoExtensions)) {
                return response(__('Logo is only jpg, jpeg or png'), 400);
            }
            $update['company_logo'] = $request
                ->file('logo')
                ->storeAs('/images/$company->id', 'logo.png', 'public');
        }

        if (
            $request->hasFile('favicon')
            && $request->file('favicon')->isValid()
        ) {
            if ($request->file('favicon')->extension() != 'png') {
                return response(__('Favicon is png'), 400);
            }

            $update['company_favicon'] = $request
                ->file('favicon')
                ->storeAs('/images/$company->id', 'favicon.png', 'public');
        }

        if (count(array_values($update)) > 0) {
            Company::where('id', $company->id)->update($update);
        }

        $this->createOrUpdateSetting(
            'automatic-file-delete-active',
            $request->input('automatic-file-delete-active'),
            $company->id
        );
        $this->createOrUpdateSetting(
            'automatic-file-delete-interval',
            $request->input('automatic-file-delete-interval')
                ? $request->input('automatic-file-delete-interval')
                : '',
            $company->id
        );

        return redirect()->route('settings', ['#general']);
    }

    public function updateSMTPDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'smtp-host' => ['required'],
            'smtp-username' => ['required'],
            'smtp-password' => ['nullable', 'min:6'],
            'smtp-protocol' => ['required'],
            'smtp-port' => ['required'],
            'smtp-sender' => ['required'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('settings', ['#smtp'])
                ->withErrors($validator, 'smtp')
                ->withInput();
        }

        $companyId = $request->user()->company_id;
        $smtpSettingsOptions = [
            'smtp-host',
            'smtp-username',
            'smtp-password',
            'smtp-protocol',
            'smtp-port',
            'smtp-sender'
        ];
        $requestBody = $request->only($smtpSettingsOptions);

        foreach ($smtpSettingsOptions as $setting) {
            $this->createOrUpdateSetting(
                $setting,
                $requestBody[$setting],
                $companyId
            );
        }

        return redirect()->route('settings', ['#smtp']);
    }

    public function updateSmsDetails(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'sms-api-key' => ['required'],
        // ]);
        // if ($validator->fails()) {
        //     return redirect()
        //         ->route('settings', ['#sms'])
        //         ->withErrors($validator, 'sms')
        //         ->withInput();
        // }
        $companyId = $request->user()->company_id;
        if (empty($request->input('sms-api-key'))) {
            // Delete row
            Setting::where(['company_id' => $companyId, 'name' => 'sms-api-key'])->delete();
        } else {
            $this->createOrUpdateSetting(
                'sms-api-key',
                $request->input('sms-api-key'),
                $companyId
            );
        }

        return redirect()->route('settings', ['#sms']);
    }

    public function updatePredefinedValuesDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'predefined-values-secret' => ['alpha_num', 'min:12'],
            'moodle-base-url' => ['url'],
        ]);
        if ($validator->fails()) {
            return redirect()
                ->route('settings', ['#predefined-values'])
                ->withErrors($validator, 'predefinedValues')
                ->withInput();
        }

        $companyId = $request->user()->company_id;
        $this->createOrUpdateSetting(
            'predefined-values-secret',
            $request->input('predefined-values-secret'),
            $companyId
        );
        $this->createOrUpdateSetting(
            'moodle-base-url',
            $request->input('moodle-base-url'),
            $companyId
        );

        return redirect()->route('settings', ['#predefined-values']);
    }

    public function updateSFTPDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sftp-host' => ['required'],
            'sftp-username' => ['required'],
            'sftp-password' => ['nullable'],
            'sftp-port' => ['required', 'integer'],
            'sftp-path' => ['required'],
        ]);
        if ($validator->fails()) {
            return redirect()
                ->route('settings', ['#sftp'])
                ->withErrors($validator, 'sftp')
                ->withInput();
        }

        $companyId = $request->user()->company_id;
        $sftpSettingsOptions = [
            'sftp-host',
            'sftp-username',
            'sftp-password',
            'sftp-port',
            'sftp-path'
        ];
        $requestBody = $request->only($sftpSettingsOptions);

        foreach ($sftpSettingsOptions as $setting) {
            $this->createOrUpdateSetting(
                $setting,
                $requestBody[$setting],
                $companyId
            );
        }

        return redirect()->route('settings', ['#sftp']);
    }
}
