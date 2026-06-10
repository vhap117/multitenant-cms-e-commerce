<?php

namespace VHAP\Core\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use VHAP\Core\Http\Requests\ResetTenantPasswordRequest;
use VHAP\Core\Actions\Auth\ResetTenantPasswordAction;

class ResetTenantPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $action): JsonResponse
    {
        $status = $action->execute(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        return $status === Password::PASSWORD_RESET
                    ? response()->json(['message' => __($status)], 200)
                    : response()->json(['message' => __($status)], 400);
    }
}
