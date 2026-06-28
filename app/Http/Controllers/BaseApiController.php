<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur de base pour l'API REST.
 *
 * Fournit des méthodes helper pour construire
 * des réponses JSON cohérentes dans toute l'API.
 */
abstract class BaseApiController extends Controller
{
    /**
     * Réponse de succès standard.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $status   Code HTTP (200, 201, ...)
     */
    protected function success(mixed $data = null, string $message = 'Succès', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Réponse d'erreur standard.
     *
     * @param string $message
     * @param int    $status   Code HTTP (400, 401, 403, 404, 422, 500)
     * @param mixed  $errors   Détails des erreurs de validation
     */
    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Réponse 201 Created.
     */
    protected function created(mixed $data, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Réponse 204 No Content (suppression).
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
