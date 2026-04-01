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
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="{{ route('catalog.index') }}">MyStore</a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">

                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Log in</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary btn-sm mt-1" href="{{ route('register') }}">Register</a>
                    </li>
                @endguest

                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Hello, {{ auth()->user()->name }}
                            @if(auth()->user()->hasRole('admin'))
                                <span class="badge bg-danger ms-1">Admin</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/dashboard">My Dashboard</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth

            </ul>
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

                    <div class="mb-4">
                        <input type="search" name="search" id="search-input" class="form-control form-control-lg shadow-sm border-0"
                               placeholder="Search by name or SKU..." value="{{ request('search') }}">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Filters</h5>
                        <a href="{{ route('catalog.index', request()->has('category_id') ? ['category_id' => request('category_id')] : []) }}" class="text-decoration-none small text-muted">Reset All</a>
                    </div>

                    <div class="accordion shadow-sm" id="filtersAccordion">

                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header" id="headingCategories">
                                <button class="accordion-button bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCategories" aria-expanded="true" aria-controls="collapseCategories">
                                    Categories
                                </button>
                            </h2>
                            <div id="collapseCategories" class="accordion-collapse collapse show" aria-labelledby="headingCategories">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush">
                                        <a href="{{ route('catalog.index') }}" class="list-group-item list-group-item-action border-0 {{ !request('category_id') ? 'text-primary fw-bold' : '' }}">All Products</a>
                                        @foreach($categories as $category)
                                            <a href="{{ route('catalog.index', ['category_id' => $category->id]) }}" class="list-group-item list-group-item-action border-0 {{ request('category_id') == $category->id ? 'text-primary fw-bold' : '' }}">{{ $category->name }}</a>
                                            @if($category->children->isNotEmpty())
                                                @foreach($category->children as $child)
                                                    <a href="{{ route('catalog.index', ['category_id' => $child->id]) }}" class="list-group-item list-group-item-action border-0 py-1 ps-4 {{ request('category_id') == $child->id ? 'text-primary fw-bold' : 'text-secondary' }}" style="font-size: 0.9rem;">— {{ $child->name }}</a>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header" id="headingPrice">
                                <button class="accordion-button bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrice" aria-expanded="true" aria-controls="collapsePrice">
                                    Price Range
                                </button>
                            </h2>
                            <div id="collapsePrice" class="accordion-collapse collapse show" aria-labelledby="headingPrice">
                                <div class="accordion-body">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="From {{ $filtersData['min_price'] ?? 0 }}" value="{{ request('min_price') }}" min="0">
                                        <span>-</span>
                                        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="To {{ $filtersData['max_price'] ?? 0 }}" value="{{ request('max_price') }}" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($filtersData['attributes']))
                            @foreach($filtersData['attributes'] as $key => $values)
                                @php
                                    $hasActiveFilters = request()->has('attributes.'.$key);
                                    $collapseId = 'collapse_'.str_replace(' ', '_', $key);
                                @endphp
                                <div class="accordion-item border-0 border-bottom">
                                    <h2 class="accordion-header" id="heading_{{ $collapseId }}">
                                        <button class="accordion-button {{ $hasActiveFilters ? '' : 'collapsed' }} bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                            {{ $key }}
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $hasActiveFilters ? 'show' : '' }}" aria-labelledby="heading_{{ $collapseId }}">
                                        <div class="accordion-body pt-1 pb-3">
                                            @foreach($values as $value)
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="attributes[{{ $key }}][]"
                                                           value="{{ $value }}"
                                                           id="attr_{{ $collapseId }}_{{ $loop->index }}"
                                                        @checked(in_array($value, request('attributes.'.$key, [])))>
                                                    <label class="form-check-label small" for="attr_{{ $collapseId }}_{{ $loop->index }}">
                                                        {{ $value }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                    </div>
                </form>
            </aside>

            <section class="col-md-9">
                <h1 class="h3 fw-bold mb-4">Products Catalog</h1>
                    @can('create', App\Models\Product::class)
                        <a href="{{ url('/products/create') }}" class="btn btn-success shadow-sm">
                            + Add New Product
                        </a>
                    @endcan
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/filters.js') }}"></script>
</body>
</html>
