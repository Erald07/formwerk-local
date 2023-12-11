<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\RestrictedDomain;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class AccessTokenController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response(403);
        }

        $name = $request->input("name");
        $restrictedDomains = $request->input("restricted-domains");

        $token = AccessToken::create([
            "name" => $name,
            "token" => Hash::make("lsd"),
            "user_id" => $user->id,
        ]);

        if ($restrictedDomains) {
            foreach ($restrictedDomains as $domain) {
                RestrictedDomain::create([
                    "domain" => $domain,
                    "access_token_id" => $token->id,
                ]);
            }
        }

        return redirect()->route('settings', ['#api']);
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response(403);
        }

        $res = AccessToken::where("id", $id)->delete();

        return redirect()->route('settings', ['#api']);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response(403);
        }

        $name = $request->input("name");
        $restrictedDomains = $request->input("restricted-domains");

        $token = AccessToken::where("id", $id)
            ->with("restrictedDomains")
            ->first();

        $oldRestrictedDomains = [];
        foreach ($token->restrictedDomains as $restrictedDomain) {
            $oldRestrictedDomains[] = $restrictedDomain["domain"];
        }

        $token->name = $name;
        if ($restrictedDomains !== $oldRestrictedDomains) {
            RestrictedDomain::where("access_token_id", $id)
                ->delete();

            if (
                $restrictedDomains
                && (count($restrictedDomains) > 0)
            ) {
                foreach ($restrictedDomains as $restrictedDomain) {
                    RestrictedDomain::create([
                        "domain" => $restrictedDomain,
                        "access_token_id" => $id,
                    ]);
                }
            }
        }
        $token->save();

        return redirect()->route('settings', ['#api']);
    }

}

