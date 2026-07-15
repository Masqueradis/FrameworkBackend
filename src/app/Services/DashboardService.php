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
     * @return array<string, int>
     */
    public function getStatsForDashboard(User $user): array
    {
        $stats = [
            'productsCount' => $this->productRepository->countByUserId($user->id),
        ];

        if ($user->isAdmin()) {
            $stats['categoriesCount'] = $this->categoryRepository->countAll();
            $stats['usersCount'] = $this->userRepository->countAll();
        }

        return $stats;
    }
}
