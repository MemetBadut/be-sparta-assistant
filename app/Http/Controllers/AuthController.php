<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user->fresh()))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $login = $request->string('login')->toString();
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_id';
        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            abort(422, 'The provided credentials are incorrect.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
