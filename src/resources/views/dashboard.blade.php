@extends('layouts.app')

@section('title', 'My Profile - MyStore')

@section('content')
    <div class="container mb-5">
        <div class="row mt-4">

            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <h4 class="fw-bold mb-1">{{ auth()->user()->name }}</h4>
                        <p class="text-muted mb-3">{{ auth()->user()->email }}</p>

                        @if(auth()->user()->hasRole('admin'))
                            <span class="badge bg-danger px-3 py-2 mb-3">Administrator</span>
                        @elseif(auth()->user()->hasRole('manager'))
                            <span class="badge bg-warning text-dark px-3 py-2 mb-3">Manager</span>
                        @else
                            <span class="badge bg-secondary px-3 py-2 mb-3">Customer</span>
                        @endif

                        <hr class="text-muted">
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm">Edit Profile</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-3">
                        <span>My Recent Orders</span>
                    </div>

                    <div class="card-body text-center py-5 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-box-seam mb-3 opacity-50" viewBox="0 0 16 16">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.434 1.37-2.404-.961L3.596 3.5l2.404.961 5.62-2.248zM8.904.381a1.5 1.5 0 0 0-1.808 0L.904 2.868a.5.5 0 0 0-.25.432v8.384a.5.5 0 0 0 .25.432l6.196 2.478a1.5 1.5 0 0 0 1.808 0l6.196-2.478a.5.5 0 0 0 .25-.432V3.3a.5.5 0 0 0-.25-.432L8.904.381zM15 4.14v8.037l-6.5 2.6v-8.037l6.5-2.6zM7.5 14.777l-6.5-2.6V4.14l6.5 2.6v8.037zM7.5 6.03l-6.5-2.6 6.5-2.6 6.5 2.6-6.5 2.6z"/>
                        </svg>
                        <h5 class="fw-bold text-dark">No orders yet</h5>
                        <p class="small mb-4">Looks like you haven't made any purchases. Time to explore our catalog!</p>
                        <a href="{{ route('catalog.index') }}" class="btn btn-primary px-4">Go to Catalog</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
