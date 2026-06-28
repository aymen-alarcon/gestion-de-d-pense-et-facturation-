<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Depense\StoreDepenseRequest;
use App\Http\Requests\Depense\UpdateDepenseRequest;
use App\Services\DepenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepenseController extends BaseApiController
{
    public function __construct(private readonly DepenseService $depenseService) {}

    public function index(Request $request): JsonResponse
    {
        $depenses = $this->depenseService->listerDepenses(
            $request->user(),
            $request->only(['categorie_id', 'date_debut', 'date_fin', 'montant_min', 'montant_max', 'per_page'])
        );

        return $this->success($depenses, 'Dépenses récupérées.');
    }

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

    public function show(Request $request, int $id): JsonResponse
    {
        $depense = $this->depenseService->trouverDepense($request->user(), $id);

        return $this->success($depense, 'Dépense récupérée.');
    }

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

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->depenseService->supprimerDepense($request->user(), $id);

        return $this->noContent();
    }

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
