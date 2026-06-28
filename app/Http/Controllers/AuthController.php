<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur d'authentification JWT.
 *
 * Gère : inscription, connexion, déconnexion, refresh token et profil.
 */
class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/auth/register
     *
     * Inscription d'un nouvel utilisateur.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created($result, 'Inscription réussie. Bienvenue !');
    }

    /**
     * POST /api/auth/login
     *
     * Connexion avec email + mot de passe.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated('email'),
                $request->validated('password')
            );

            return $this->success($result, 'Connexion réussie.');
        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    /**
     * POST /api/auth/logout
     *
     * Déconnexion (invalidation du token JWT).
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(null, 'Déconnexion réussie.');
    }

    /**
     * POST /api/auth/refresh
     *
     * Renouvellement du token JWT.
     */
    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return $this->success($result, 'Token renouvelé avec succès.');
    }

    /**
     * GET /api/auth/me
     *
     * Retourne le profil de l'utilisateur authentifié.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(
            $request->user()->load('roles'),
            'Profil récupéré.'
        );
    }

    /**
     * PUT /api/auth/profile
     *
     * Mise à jour du profil de l'utilisateur connecté.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return $this->success($user->load('roles'), 'Profil mis à jour avec succès.');
    }
}
