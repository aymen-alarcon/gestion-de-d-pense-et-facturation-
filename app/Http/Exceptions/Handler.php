<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Gestionnaire global d'exceptions pour l'API REST.
 *
 * Intercepte toutes les exceptions et retourne des réponses
 * JSON cohérentes avec les codes HTTP appropriés.
 */
class Handler extends ExceptionHandler
{
    /**
     * Exceptions non rapportées dans les logs.
     */
    protected $dontReport = [];

    /**
     * Exceptions non flashées en session.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Convertit toutes les exceptions en réponse JSON pour l'API.
     */
    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response
    {
        // Toujours retourner du JSON pour les routes API
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($e);
        }

        return parent::render($request, $e);
    }

    /**
     * Gère les exceptions spécifiques à l'API.
     */
    private function handleApiException(Throwable $e): JsonResponse
    {
        // Erreurs de validation (422)
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Modèle non trouvé (404)
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'success' => false,
                'message' => "Ressource {$model} introuvable.",
            ], 404);
        }

        // Non authentifié (401)
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié. Veuillez vous connecter.',
            ], 401);
        }

        // Exceptions HTTP génériques (403, 404, 405, ...)
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Erreur HTTP.',
            ], $e->getStatusCode());
        }

        // Erreur serveur interne (500)
        $message = config('app.debug')
            ? $e->getMessage()
            : 'Une erreur interne est survenue. Veuillez réessayer.';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
