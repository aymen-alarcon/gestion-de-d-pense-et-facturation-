<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Depense\StoreDepenseRequest;
use App\Http\Requests\Depense\UpdateDepenseRequest;
use App\Services\DepenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur REST pour la gestion des dépenses.
 *
 * Endpoints :
 *   GET    /api/depenses           → lister (avec filtres)
 *   POST   /api/depenses           → créer
 *   GET    /api/depenses/{id}      → détail
 *   PUT    /api/depenses/{id}      → modifier
 *   DELETE /api/depenses/{id}      → supprimer (soft)
 *   GET    /api/depenses/totaux    → statistiques par période
 */
class DepenseController extends BaseApiController
{
    public function __construct(private readonly DepenseService $depenseService) {}

    /**
     * GET /api/depenses
     *
     * Liste paginée des dépenses de l'utilisateur connecté.
     *
     * Query params optionnels :
     *   - categorie_id  : int
     *   - date_debut    : Y-m-d
     *   - date_fin      : Y-m-d
     *   - montant_min   : float
     *   - montant_max   : float
     *   - per_page      : int (défaut 15, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $depenses = $this->depenseService->listerDepenses(
            $request->user(),
            $request->only(['categorie_id', 'date_debut', 'date_fin', 'montant_min', 'montant_max', 'per_page'])
        );

        return $this->success($depenses, 'Dépenses récupérées.');
    }

    /**
     * POST /api/depenses
     *
     * Enregistre une nouvelle dépense.
     * Accepte un fichier justificatif optionnel (multipart/form-data).
     */
    public function store(StoreDepenseRequest $request): JsonResponse
    {
        $depense = $this->depenseService->creerDepense(
            $request->user(),
            $request->validated(),
            $request->file('justificatif')
        );

        return $this->created(
            $depense->load('categorie'),
            'Dépense enregistrée avec succès.'
        );
    }

    /**
     * GET /api/depenses/{id}
     *
     * Détail d'une dépense appartenant à l'utilisateur.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $depense = $this->depenseService->trouverDepense($request->user(), $id);

        return $this->success($depense, 'Dépense récupérée.');
    }

    /**
     * PUT /api/depenses/{id}
     *
     * Modifie une dépense existante de l'utilisateur.
     */
    public function update(UpdateDepenseRequest $request, int $id): JsonResponse
    {
        $depense = $this->depenseService->modifierDepense(
            $request->user(),
            $id,
            $request->validated(),
            $request->file('justificatif')
        );

        return $this->success($depense, 'Dépense mise à jour avec succès.');
    }

    /**
     * DELETE /api/depenses/{id}
     *
     * Supprime (soft delete) une dépense de l'utilisateur.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->depenseService->supprimerDepense($request->user(), $id);

        return $this->noContent();
    }

    /**
     * GET /api/depenses/totaux?date_debut=&date_fin=
     *
     * Calcule les totaux de dépenses par catégorie pour une période.
     */
    public function totaux(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
        ]);

        $stats = $this->depenseService->calculerTotaux(
            $request->user(),
            $request->date_debut,
            $request->date_fin
        );

        return $this->success($stats, 'Statistiques calculées.');
    }
}
