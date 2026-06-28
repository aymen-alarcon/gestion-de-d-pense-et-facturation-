<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created($result, 'Inscription réussie. Bienvenue !');
    }

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

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(null, 'Déconnexion réussie.');
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return $this->success($result, 'Token renouvelé avec succès.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            $request->user()->load('roles'),
            'Profil récupéré.'
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return $this->success($user->load('roles'), 'Profil mis à jour avec succès.');
    }
}
