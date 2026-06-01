<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;

class DashboardService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @param User $user
     * @return array<string, int>
     */
    public function getStatsForDashboard(User $user): array
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return [
                'productsCount' => $this->productRepository->countAll(),
                'categoriesCount' => $this->categoryRepository->countAll(),
                'usersCount' => $this->userRepository->countAll(),
            ];
        }

        return [
            'productsCount' => $this->productRepository->countByUserId($user->id),
            'categoriesCount' => $this->categoryRepository->countAll(),
            'usersCount' => $this->userRepository->countAll(),
        ];
    }
}
