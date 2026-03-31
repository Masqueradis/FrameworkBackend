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
                <form action="{{ route('catalog.index') }}" method="GET" id="filter-form">

                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white fw-bold">Categories</div>
                        <div class="list-group list-group-flush">
                            <a href="{{ route('catalog.index') }}" class="list-group-item list-group-item-action {{ !request('category_id') ? 'active' : '' }}">All Products</a>
                            @foreach($categories as $category)
                                <a href="{{ route('catalog.index', ['category_id' => $category->id]) }}" class="list-group-item list-group-item-action fw-bold {{ request('category_id') == $category->id ? 'active' : '' }}">{{ $category->name }}</a>
                                @if($category->children->isNotEmpty())
                                    @foreach($category->children as $child)
                                        <a href="{{ route('catalog.index', ['category_id' => $child->id]) }}" class="list-group-item list-group-item-action border-0 py-1 ps-4 {{ request('category_id') == $child->id ? 'text-primary fw-bold' : 'text-secondary' }}" style="font-size: 0.9rem;">— {{ $child->name }}</a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white fw-bold">Price Range</div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" name="min_price" class="form-control form-control-sm"
                                       placeholder="From {{ $filtersData['min_price'] }}"
                                       value="{{ request('min_price') }}" min="0">
                                <span>-</span>
                                <input type="number" name="max_price" class="form-control form-control-sm"
                                       placeholder="To {{ $filtersData['max_price'] }}"
                                       value="{{ request('max_price') }}" min="0">
                            </div>
                        </div>
                    </div>

                    @foreach($filtersData['attributes'] as $key => $values)
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white fw-bold">{{ $key }}</div>
                            <div class="card-body py-2">
                                @foreach($values as $value)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox"
                                               name="attributes[{{ $key }}][]"
                                               value="{{ $value }}"
                                               id="attr_{{ str_replace(' ', '_', $key) }}_{{ $loop->index }}"
                                            @checked(in_array($value, request('attributes.'.$key, [])))>
                                        <label class="form-check-label small" for="attr_{{ str_replace(' ', '_', $key) }}_{{ $loop->index }}">
                                            {{ $value }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="{{ route('catalog.index', request()->has('category_id') ? ['category_id' => request('category_id')] : []) }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </aside>

            <section class="col-md-9">
                <h1 class="h3 fw-bold mb-4">Products Catalog</h1>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                        @forelse($products as $product)
                            <div class="col">
                                <x-product-card :product="$product" />
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
