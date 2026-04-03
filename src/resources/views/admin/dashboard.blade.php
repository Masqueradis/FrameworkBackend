@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h6 class="text-uppercase mb-2 opacity-75">Total Products</h6>
                    <h2 class="display-5 fw-bold mb-0">{{ $productsCount }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white h-100">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h6 class="text-uppercase mb-2 opacity-75">Categories</h6>
                    <h2 class="display-5 fw-bold mb-0">{{ $categoriesCount }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-warning text-dark h-100">
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h6 class="text-uppercase mb-2 opacity-75">Active Users</h6>
                    <h2 class="display-5 fw-bold mb-0">{{ $usersCount }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Recent Activity</h6>
        </div>
        <div class="card-body text-muted text-center py-5">
            <p>Welcome to the new management panel. Select a section from the sidebar to begin.</p>
        </div>
    </div>
@endsection
