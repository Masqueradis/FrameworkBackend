@extends('layouts.admin')

@section('title', 'Review Moderation (Products)')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Products Awaiting Moderation</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Product Name</th>
                        <th>Pending Reviews</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold">{{ $product->name }}</span>
                            </td>
                            <td>
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    {{ $product->pending_count }} new
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.comments.show', $product) }}" class="btn btn-sm btn-primary fw-bold px-3">
                                    Moderate Reviews &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check2-circle mb-2" viewBox="0 0 16 16">
                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>
                                </svg>
                                <br>
                                All caught up! No pending reviews.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
