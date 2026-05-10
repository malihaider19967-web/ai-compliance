<?php

namespace App\Repositories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;

class ExpenseRepository
{
    /**
     * @return Collection<int, Expense>
     */
    public function all(): Collection
    {
        return Expense::query()->orderByDesc('created_at')->get();
    }

    public function find(int $id): ?Expense
    {
        return Expense::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Expense
    {
        return Expense::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Expense $expense, array $attributes): Expense
    {
        $expense->fill($attributes)->save();

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
