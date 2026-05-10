<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Repositories\PolicyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function __construct(private readonly PolicyRepository $policies)
    {
    }

    public function index(): JsonResponse
    {
        $items = $this->policies->all()->map(fn (Policy $p) => $this->present($p));

        return response()->json(['policies' => $items->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rule_text' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $policy = $this->policies->create([
            'name' => $data['name'],
            'rule_text' => $data['rule_text'],
            'active' => $data['active'] ?? true,
        ]);

        return response()->json(['policy' => $this->present($policy)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $policy = $this->policies->find($id);
        if (! $policy) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'rule_text' => ['sometimes', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $this->policies->update($policy, $data);

        return response()->json(['policy' => $this->present($policy->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $policy = $this->policies->find($id);
        if (! $policy) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->policies->delete($policy);

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Policy $policy): array
    {
        return [
            'id' => $policy->id,
            'name' => $policy->name,
            'rule_text' => $policy->rule_text,
            'active' => (bool) $policy->active,
            'created_at' => $policy->created_at?->toIso8601String(),
            'updated_at' => $policy->updated_at?->toIso8601String(),
        ];
    }
}
