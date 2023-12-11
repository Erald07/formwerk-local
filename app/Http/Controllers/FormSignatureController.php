<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use App\Models\Form;
use App\Models\SignatureHash;
use App\Mail\SignatureLink;
use App\Service\SettingsService;
use Sms77\Api\Client;
use Sms77\Api\Params\SmsParams;

use Faker\Factory as Faker;

class FormSignatureController extends Controller
{
    public function addFormSignatureToken(Request $request, $formId)
    {
        $form = Form::firstWhere("id", $formId);

        if (!$form) {
            return response(__("Form not found"), 404);
        }

        $signature = implode("-", [
            microtime(),
            "form_id_$formId",
            Faker::create()->bothify('???##'),
        ]);

        $signatureToken = Hash::make($signature);

        $options = ["height" => $request->query("height", 150)];

        SignatureHash::create([
            "hash" => $signatureToken,
            "options" => json_encode($options),
        ]);

        return ["token" => $signatureToken];
    }

    public function sendSignatureLinkViaEmail(Request $request)
    {
        $hash = $request->input("signature_token");
        if ($hash === null) {
            return response(__("Signature token is required"), 400);
        }

        $signatureToken = SignatureHash::firstWhere("hash", $hash);

        if ($signatureToken === null) {
            return response("", 404);
        }

        $email = $request->input("email");
        if ($email === null) {
            return response(__("Email address is required"), 400);
        }

        $formId = $request->input("form-id");
        $companyId = Form::where("id", $formId)->first()["company_id"];
        if (!SettingsService::areSmtpSettingsConfigured($companyId)) {
            return response(__("Smtp settings are not configured"), 400);
        }

        Mail::to($email)
            ->send(new SignatureLink($signatureToken->hash));

        return response(__("Email sent successfully"), 200);
    }

    public function sendSignatureLinkViaSms(Request $request)
    {
        $hash = $request->input("signature_token");
        if ($hash === null) {
            return response(__("Signature token is required"), 400);
        }

        $phoneNumber = $request->input("phone-number");
        if ($phoneNumber === null) {
            return response(__("Phonenumber is required"), 400);
        }

        $signatureToken = SignatureHash::firstWhere("hash", $hash);

        if ($signatureToken === null) {
            return response("", 404);
        }

        $link = route("get-signature-input", [
            "signatureToken" => $signatureToken->hash
        ]);

        $formId = $request->input("form-id");
        $companyId = Form::where("id", $formId)->first()["company_id"];

        if (!SettingsService::areSmsSettingsConfigured($companyId)) {
            return response(__("Sms settings are not configured"), 200);
        }

        $smsApiKey = SettingsService::getSmsSettings($companyId)["sms-api-key"];

        $client = new Client($smsApiKey);
        $params = new SmsParams();
        $client->sms($params
            ->setFrom('FormWerk')
            ->setTo($phoneNumber)
            ->setText(__("Your link for signing") . ": " . $link));

        return response(__("Message sent successfully"), 200);
    }

    public function getSignatureInput(Request $request, $hash)
    {
        $signatureToken = SignatureHash::firstWhere("hash", $hash);

        if ($signatureToken === null) {
            return response("", 404);
        }

        $options = [];
        if ($signatureToken->options) {
            $options = json_decode($signatureToken->options, true);
        }

        return view("components.leform.components.signature", [
            "signatureToken" => $hash,
            "options" => $options,
            "frontendTranslations" => __("frontend_translations")
        ]);
    }

    public function submitSignature(Request $request)
    {
        $hash = $request->input("signature_token");
        $signature = $request->file("signature");

        if (!$signature || !$signature->isValid()) {
            return response("", 400);
        }

        $signatureToken = SignatureHash::firstWhere("hash", $hash);
        if ($signatureToken === null) {
            return response("", 404);
        }

        if (
            $signatureToken->filename !== null
            && Storage::disk("public")->exists($signatureToken->filename)
        ) {
            Storage::disk("public")
                ->delete($signatureToken->filename);
        }

        $filename = $signature->store("signatures", "public");
        SignatureHash::where("hash", $hash)
            ->update(["filename" => $filename]);

        return response(Storage::url($filename));
    }

    public function getSignature(Request $request, $hash)
    {
        $signatureToken = SignatureHash::firstWhere("hash", $hash);

        if (isset($signatureToken) && $signatureToken->filename) {
            return Storage::url($signatureToken->filename);
        } else {
            return response(null, 404);
        }
    }

    public function deleteSignature(Request $request, $hash)
    {
        if ($hash === null) {
            return response("", 400);
        }

        $signatureHash = SignatureHash::firstWhere("hash", $hash);

        if (!$signatureHash) {
            return response("", 404);
        }

        if (
            $signatureHash->filename !== null
            && Storage::disk("public")->exists($signatureHash->filename)
        ) {
            Storage::disk("public")
                ->delete($signatureHash->filename);
            SignatureHash::where("hash", $hash)
                ->update(["filename" => null]);
        }

        return response("", 201);
    }
}