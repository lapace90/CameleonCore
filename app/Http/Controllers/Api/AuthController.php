<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     *  INSCRIPTION PUBLIQUE - Création compte utilisateur
     */
    public function register(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'firstName.required' => 'Le prénom est requis.',
            'lastName.required' => 'Le nom est requis.',
        ]);

        try {
            // Créer l'utilisateur avec un nom composé
            $user = User::create([
                'name' => trim($validated['firstName'] . ' ' . $validated['lastName']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => 3, // Rôle "user" par défaut (ajuste selon ta table roles)
                'status' => 'active',
            ]);

            Log::info('✅ Nouvel utilisateur inscrit', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ]);

            // Marquer la connexion initiale
            $user->markAsLoggedIn();
            $user->refresh();
            $user->load('role');

            // Créer le token d'authentification
            $token = $user->createToken('camping-token')->plainTextToken;

            // Retourner le même format que login
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
                    'last_login_at' => $user->last_login_at?->toISOString(),
                    'last_login_ip' => $user->last_login_ip,
                ],
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur inscription utilisateur', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du compte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔐 CONNEXION
     */
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

        // Tracker la connexion
        $user->markAsLoggedIn();
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
                'last_login_at' => $user->last_login_at?->toISOString(),
                'last_login_ip' => $user->last_login_ip,
            ],
            'token' => $user->createToken('camping-token')->plainTextToken,
        ]);
    }

    /**
     * 🚪 DÉCONNEXION
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * ✅ VÉRIFICATION TOKEN
     */
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
                'last_login_at' => $user->last_login_at?->toISOString(),
                'last_login_ip' => $user->last_login_ip,
            ]
        ]);
    }
}