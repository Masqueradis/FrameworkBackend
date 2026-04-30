<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $productsCount = Product::count();
            $categoriesCount = Category::count();
            $usersCount = User::count();
        } else {
            $productsCount = Product::where('user_id', $user->id)->count();
            $categoriesCount = 0;
            $usersCount = 0;
        }

        return view('admin.dashboard', compact('productsCount', 'categoriesCount', 'usersCount'));
    }
}
