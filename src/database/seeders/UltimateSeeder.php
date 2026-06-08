<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UltimateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $categories = [
            'GPU NVIDIA' => Category::firstOrCreate(
                ['slug' => 'gpu-nvidia'],
                ['name' => 'NVIDIA Graphics Cards', 'description' => 'Team Green graphics accelerators']
            ),
            'GPU AMD' => Category::firstOrCreate(
                ['slug' => 'gpu-amd'],
                ['name' => 'AMD Graphics Cards', 'description' => 'Team Red graphics accelerators']
            ),
            'CPU Intel' => Category::firstOrCreate(
                ['slug' => 'cpu-intel'],
                ['name' => 'Intel Processors', 'description' => 'Team Blue high-performance CPUs']
            ),
            'CPU AMD' => Category::firstOrCreate(
                ['slug' => 'cpu-amd'],
                ['name' => 'AMD Processors', 'description' => 'Legendary Ryzen processors']
            ),
            'Case NZXT' => Category::firstOrCreate(
                ['slug' => 'case-nzxt'],
                ['name' => 'NZXT Cases', 'description' => 'Stylish and minimalistic PC cases']
            ),
            'Case Deepcool' => Category::firstOrCreate(
                ['slug' => 'case-deepcool'],
                ['name' => 'Deepcool Cases', 'description' => 'Excellent cooling and airflow design']
            ),
        ];

        $products = [

            ['category' => $categories['GPU NVIDIA'],
                'name' => 'GIGABYTE GeForce RTX 4090',
                'price' => 1600, 'color' => '76b900',
                'attr' => [
                    'GPU' => 'RTX 4090',
                    'VRAM' => '24 GB',
                    'Memory Type' => 'GDDR6X',
                    'TDP' => '450W'
                ]
            ],
            ['category' => $categories['GPU NVIDIA'],
                'name' => 'MSI GeForce RTX 4070 Ti',
                'price' => 850,
                'color' => '76b900',
                'attr' => [
                    'GPU' => 'RTX 4070 Ti',
                    'VRAM' => '12 GB',
                    'Memory Type' => 'GDDR6X',
                    'TDP' => '285W'
                ]
            ],

            ['category' => $categories['GPU AMD'],
                'name' => 'Sapphire Radeon RX 7900 XTX',
                'price' => 1000,
                'color' => 'ed1c24',
                'attr' => [
                    'GPU' => 'RX 7900 XTX',
                    'VRAM' => '24 GB',
                    'Memory Type' => 'GDDR6',
                    'TDP' => '355W'
                ]
            ],
            ['category' => $categories['GPU AMD'],
                'name' => 'PowerColor Radeon RX 7800 XT',
                'price' => 550,
                'color' => 'ed1c24',
                'attr' => [
                    'GPU' => 'RX 7800 XT',
                    'VRAM' => '16 GB',
                    'Memory Type' => 'GDDR6',
                    'TDP' => '263W'
                ]
            ],

            ['category' => $categories['CPU Intel'],
                'name' => 'Intel Core i9-14900K',
                'price' => 600,
                'color' => '0071c5',
                'attr' => [
                    'CPU' => 'i9-14900K',
                    'RAM type' => 'DDR5',
                    'Frequency' => '6.0 GHz'
                ]
            ],
            ['category' => $categories['CPU Intel'],
                'name' => 'Intel Core i5-13600K',
                'price' => 320,
                'color' => '0071c5',
                'attr' => [
                    'CPU' => 'i5-13600K',
                    'RAM type' => 'DDR4/DDR5',
                    'Frequency' => '5.1 GHz'
                ]
            ],

            ['category' => $categories['CPU AMD'],
                'name' => 'AMD Ryzen 7 7800X3D',
                'price' => 400,
                'color' => 'ed1c24',
                'attr' => [
                    'CPU' => 'Ryzen 7 7800X3D',
                    'RAM type' => 'DDR5',
                    'Frequency' => '5.0 GHz'
                ]
            ],
            ['category' => $categories['CPU AMD'],
                'name' => 'AMD Ryzen 5 7600X',
                'price' => 250,
                'color' => 'ed1c24',
                'attr' => [
                    'CPU' => 'Ryzen 5 7600X',
                    'RAM type' => 'DDR5',
                    'Frequency' => '5.3 GHz'
                ]
            ],

            ['category' => $categories['Case NZXT'],
                'name' => 'NZXT H9 Flow Black',
                'price' => 160,
                'color' => '8a2be2',
                'attr' => [
                    'Form Factor' => 'ATX',
                    'Color' => 'Black',
                    'Material' => 'Steel/Glass'
                ]
            ],
            ['category' => $categories['Case NZXT'],
                'name' => 'NZXT H5 Elite White',
                'price' => 140,

                'color' => '8a2be2',
                'attr' => [
                    'Form Factor' => 'ATX',
                    'Color' => 'White',
                    'Material' => 'Steel/Glass'
                ]
            ],

            ['category' => $categories['Case Deepcool'],
                'name' => 'Deepcool CH560 Digital',
                'price' => 110,
                'color' => '00a896',
                'attr' => [
                    'Form Factor' => 'E-ATX',
                    'Color' => 'Black',
                    'Material' => 'Steel/Glass'
                ]
            ],
            ['category' => $categories['Case Deepcool'],
                'name' => 'Deepcool Macube 110',
                'price' => 50,
                'color' => '00a896',
                'attr' => [
                    'Form Factor' => 'Micro-ATX',
                    'Color' => 'White',
                    'Material' => 'Steel'
                ]
            ],
        ];

        foreach ($products as $p) {
            $product = Product::create([
                'user_id' => $admin?->id,
                'category_id' => $p['category']->id,
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'sku' => strtoupper(Str::random(8)),
                'description' => 'High-performance hardware: ' . $p['name'],
                'price' => $p['price'],
                'stock' => rand(10, 50),
                'available' => true,
                'attributes' => $p['attr'],
            ]);

            try {
                $text = urlencode($p['name']);
                $imageUrl = "https://placehold.co/800x600/{$p['color']}/FFF/png?text={$text}";
                $imageContent = file_get_contents($imageUrl);

                if ($imageContent) {
                    $filename = 'products/' . Str::random(40) . '.png';
                    Storage::disk('minio')->put($filename, $imageContent);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $filename,
                        'is_primary' => true,
                        'position' => 1,
                    ]);
                }
            } catch (\Exception $e) {
                $this->command->warn('Failed to download image for: ' . $p['name']);
            }
        }
    }
}
