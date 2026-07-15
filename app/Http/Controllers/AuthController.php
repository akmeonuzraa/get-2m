<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //  Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Vérifier si l'utilisateur existe et le mot de passe est correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        // Mettre à jour la date de dernière connexion
        $user->update(['last_login_at' => now()]);

        // Créer le token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role'    => $user->role,
                'service' => $user->service,
                'avatar'  => $user->avatar,
            ]
        ]);
    }

    //  Logout
    public function logout(Request $request)
    {
        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.'
        ]);
    }

    //  Profil de l'utilisateur connecté
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'role'           => $user->role,
            'service'        => $user->service,
            'avatar'         => $user->avatar,
            'is_active'      => $user->is_active,
            'last_login_at'  => $user->last_login_at,
            'created_at'     => $user->created_at,
        ]);
    }

    //  Mot de passe oublié
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Lien de réinitialisation envoyé par email.'
            ]);
        }

        return response()->json([
            'message' => 'Impossible d\'envoyer le lien. Vérifiez l\'email.'
        ], 400);
    }

    //  Réinitialiser le mot de passe
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Mot de passe réinitialisé avec succès.'
            ]);
        }

        return response()->json([
            'message' => 'Token invalide ou expiré.'
        ], 400);
    }

    //  Rafraîchir le token d'accès
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Révoquer le token actuel
        $user->currentAccessToken()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;
        $expiresAt = now()->addHours(24)->toIso8601String();

        return response()->json([
            'message'    => 'Token rafraîchi avec succès.',
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);
    }
}