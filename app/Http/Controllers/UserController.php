<?php



namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //  Lister tous les users
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'service', 'is_active', 'last_login_at', 'created_at')
                     ->paginate(15);

        return response()->json($users);
    }

    //  Créer un user
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role'     => 'required|in:admin,responsable,user',
            'service'  => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'service'   => $request->service,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'user'    => $user
        ], 201);
    }

    //  Afficher un user
    public function show(User $user)
    {
        return response()->json($user);
    }

    //  Modifier un user
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $user->id,
            'role'    => 'sometimes|in:admin,responsable,user',
            'service' => 'sometimes|string|max:255',
        ]);

        $user->update($request->only('name', 'email', 'role', 'service'));

        return response()->json([
            'message' => 'Utilisateur modifié avec succès.',
            'user'    => $user
        ]);
    }

    //  Activer / Désactiver un user
    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Compte activé.' : 'Compte désactivé.',
            'is_active' => $user->is_active
        ]);
    }

    //  Supprimer un user
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès.'
        ]);
    }
}

