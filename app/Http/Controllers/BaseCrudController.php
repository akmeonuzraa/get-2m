<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class BaseCrudController extends Controller
{
    protected string $modelClass;

    protected function baseQuery(Request $request)
    {
        return $this->modelClass::query();
    }

    protected function storeRules(): array
    {
        return [];
    }

    protected function updateRules(): array
    {
        return [];
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 15), 100));
        $page = max(1, (int) $request->query('page', 1));
        $items = $this->baseQuery($request)->paginate($limit, ['*'], 'page', $page);

        return response()->json($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = $this->baseQuery($request)->findOrFail($id);
        return response()->json($item);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());
        $item = $this->modelClass::create($validated);
        return response()->json($item, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->baseQuery($request)->findOrFail($id);
        $validated = $request->validate($this->updateRules());
        $item->update($validated);
        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = $this->baseQuery($request)->findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Supprimé avec succès.']);
    }
}