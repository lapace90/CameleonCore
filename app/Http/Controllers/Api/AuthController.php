<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        // ✅ UTILISER LA MÉTHODE DU MODÈLE pour tracker la vraie connexion
        $user->markAsLoggedIn();

        // ✅ RECHARGER pour avoir les nouvelles données de connexion
        $user->refresh();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'postal_code' => $user->postal_code, 
                'avatar' => $user->avatar,
                'role' => $user->role ? $user->role->name : 'user',
                'role_id' => $user->role_id,
                'last_login_at' => $user->last_login_at?->toISOString(), // ✅ VRAIE HEURE
                'last_login_ip' => $user->last_login_ip,
            ],
            'token' => $user->createToken('camping-token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function verify(Request $request)
    {
        $user = $request->user()->load('role');
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'postal_code' => $user->postal_code, 
                'avatar' => $user->avatar,
                'role' => $user->role ? $user->role->name : 'user',
                'role_id' => $user->role_id,
                'last_login_at' => $user->last_login_at?->toISOString(), // ✅ AVEC L'HEURE
                'last_login_ip' => $user->last_login_ip,
            ]
        ]);
    }
}