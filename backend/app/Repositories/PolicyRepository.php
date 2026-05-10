<?php

namespace App\Repositories;

use App\Models\Policy;
use Illuminate\Database\Eloquent\Collection;

class PolicyRepository
{
    /**
     * @return Collection<int, Policy>
     */
    public function all(): Collection
    {
        return Policy::query()->orderByDesc('created_at')->get();
    }

    /**
     * @return Collection<int, Policy>
     */
    public function active(): Collection
    {
        return Policy::query()->where('active', true)->orderBy('id')->get();
    }

    public function find(int $id): ?Policy
    {
        return Policy::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Policy
    {
        return Policy::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Policy $policy, array $attributes): Policy
    {
        $policy->fill($attributes)->save();

        return $policy;
    }

    public function delete(Policy $policy): void
    {
        $policy->delete();
    }
}
