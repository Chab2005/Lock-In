<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

use function response;

class WebAuthnRegisterController
{
    public function options(AttestationRequest $request): Responsable
    {
        // secureRegistration() sets userVerification=required — biometric or PIN is always mandatory.
        // This is the correct posture for a password manager.
        return $request->secureRegistration()->toCreate();
    }

    public function register(AttestedRequest $request): Response
    {
        $alias = trim($request->input('alias', ''));

        $request->save($alias !== '' ? ['alias' => $alias] : []);

        return response()->noContent();
    }
}
