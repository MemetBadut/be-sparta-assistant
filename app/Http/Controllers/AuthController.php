<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            ...$request->safe()->except('password_confirmation'),
            'role' => \App\Enums\Role::Employee,
        ]);
        $user->refresh();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new UserResource($user->fresh()))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $identity = $request->string('email')->toString();
        $user = User::where('email', $identity)
            ->orWhere('employee_id', $identity)
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            abort(response()->json(['message' => 'The provided credentials are incorrect.', 'error_code' => 'INVALID_CREDENTIALS'], 422));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        // Clear the cached guard user too, otherwise Sanctum's AuthenticateSession re-stores the old user's password hash after invalidate().
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
