<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAllCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function getRootCategories(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }
}
