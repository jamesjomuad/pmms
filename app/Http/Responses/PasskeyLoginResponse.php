<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): Response
    {
        $redirect = Route::has('dashboard') ? route('dashboard') : config('fortify.redirects.login', '/');

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => redirect()->intended($redirect)->getTargetUrl()], 200)
            : redirect()->intended($redirect);
    }
}
