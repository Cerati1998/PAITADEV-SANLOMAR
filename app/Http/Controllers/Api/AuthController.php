<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:jwt', except: ['register','login']),
        ];
    }

    public function register(Request $request)
    {
        //Validar, crear y devolver token
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        //Devolver token
        return $this->login($request);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $credentials = request(['email', 'password']);

        if (! $token = auth('jwt')->attempt($credentials)) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(auth('jwt')->user());
    }

    public function logout()
    {
        auth('jwt')->logout();

        return response()->json(['mensaje' => 'Cierre de sesión exitoso']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('jwt')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('jwt')->factory()->getTTL() * 60
        ]);
    }
}
