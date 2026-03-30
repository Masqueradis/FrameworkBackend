<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH", crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a href="{{ route('catalog.index') }}" class="navbar-brand fw-bold text-primary">PC-Store</a>
            <div>
                @auth
                    <a href="/dashboard" class="text-decoration-none text-secondary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-decoration-none text-secondary me-3">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="row">

            <aside class="col-md-3 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        Categories
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('catalog.index') }}"
                           class="list-group-item list-group-item-action {{ !request('category_id') ? 'active' : '' }}">
                            All Products
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ route('catalog.index', ['category_id' => $category->id]) }}"
                               class="list-group-item list-group-item-action {{ request('category_id') == $category->id ? 'active' : '' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <section class="col-md-9">
                <h1 class="h3 fw-bold mb-4">Products Catalog</h1>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                    @forelse($products as $product)
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                    [Image {{ $product->sku }}]
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <small class="text-primary text-uppercase fw-semibold mb-1" style="font-size: 0.75rem;">
                                        {{ $product->category->name ?? 'Uncategorized' }}
                                    </small>
                                    <h5 class="card-title fw-bold text-dark flex-grow-1 fs-6">
                                        {{ $product->name }}
                                    </h5>

                                    <div class="d-flex align-items-center justify-content-between mt-3">
                                        <span class="fs-5 fw-bolder text-dark">
                                            ${{ number_format($product->price, 2, '.', ',') }}
                                        </span>
                                        <button class="btn btn-outline-primary btn-sm">
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted bg-white rounded shadow-sm w-100">
                            Sorry, no products found.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </section>

        </div>
    </main>
</body>
</html>
