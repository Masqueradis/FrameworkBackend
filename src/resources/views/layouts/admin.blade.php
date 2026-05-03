<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
        }
        .nav-link { color: #cfd8dc; margin-bottom: 5px; border-radius: 5px; }
        .nav-link:hover, .nav-link.active { background-color: #343a40; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <aside class="bg-dark text-white p-3 sidebar shadow">
        <div class="d-flex align-items-center mb-4 mt-2 px-2">
            <h4 class="fw-bold mb-0 text-white">Store Admin</h4>
        </div>
        <hr class="text-secondary">
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
            </li>
            <li class="nav-item mt-3 mb-1 px-2 text-uppercase text-secondary" style="font-size: 0.75rem;">Catalog</li>
            <li class="nav-item">
                <a href="{{ route('admin.products.index') }}"
                   class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    Products
                </a>
            </li>
            @can('manage-categories')
                <li class="nav-item">
                    <a href="{{ route('admin.categories.index') }}"
                       class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            Categories
                    </a>
                </li>
            @endcan
        </ul>
        <hr class="text-secondary mt-auto">
        <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary w-100 btn-sm text-start">
            &larr; Back to Site
        </a>
    </aside>

    <main class="main-content d-flex flex-column min-vh-100">
        <header class="bg-white shadow-sm py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">@yield('title', 'Control Panel')</h5>

            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle border fw-bold" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    @if(auth()->user()->hasRole('admin'))
                        <span class="badge bg-danger rounded-circle" style="width: 10px; height: 10px; padding: 0;"></span>
                    @elseif(auth()->user()->hasRole('seller'))
                        <span class="badge bg-warning rounded-circle" style="width: 10px; height: 10px; padding: 0;"></span>
                    @endif
                    {{ auth()->user()->name ?? 'Manager' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="adminUserDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ url('/profile') }}">My Profile</a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="{{ route('catalog.index') }}">Back to Store</a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger fw-bold">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <div class="p-4 flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
