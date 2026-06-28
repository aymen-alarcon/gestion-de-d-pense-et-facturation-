<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn ($q) =>
                $q->where('nom', 'like', "%{$request->search}%")
                  ->orWhere('prenom', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->role, fn ($q) =>
                $q->role($request->role)
            )
            ->when($request->has('is_active'), fn ($q) =>
                $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN))
            )
            ->orderByDesc('created_at')
            ->paginate((int) ($request->per_page ?? 20));

        return $this->success($users, 'Utilisateurs récupérés.');
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'depenses', 'factures'])->findOrFail($id);

        return $this->success($user, 'Utilisateur récupéré.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => ['required', Password::min(8)->mixedCase()->numbers()],
            'telephone' => 'nullable|string|max:20',
            'role'      => 'required|in:user,gestionnaire,admin',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            ...$data,
            'password'  => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        return $this->created($user->load('roles'), 'Utilisateur créé avec succès.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'email'     => "sometimes|email|unique:users,email,{$id}",
            'password'  => ['sometimes', Password::min(8)->mixedCase()->numbers()],
            'telephone' => 'nullable|string|max:20',
            'role'      => 'sometimes|in:user,gestionnaire,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);

        return $this->success($user->fresh('roles'), 'Utilisateur mis à jour.');
    }

    public function toggleActif(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $statut = $user->is_active ? 'activé' : 'désactivé';

        return $this->success(
            ['is_active' => $user->is_active],
            "Compte utilisateur {$statut} avec succès."
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $this->noContent();
    }
}
