@extends('layouts.admin')

@section('title', 'Moderating: ' . $product->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.comments.index') }}" class="text-decoration-none fw-bold">&larr; Back to Products</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Pending Reviews for "{{ $product->name }}"</h6>
            <a href="{{ route('web.products.show', $product) }}" target="_blank" class="btn btn-sm btn-outline-secondary">View Product on Site</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Rating</th>
                        <th style="width: 50%;">Review Text</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($comments as $comment)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-semibold">{{ $comment->user->name }}</span><br>
                                <small class="text-muted">{{ $comment->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark">★ {{ $comment->rating }}</span>
                            </td>
                            <td>
                                <small class="text-wrap d-block text-muted" style="max-height: 80px; overflow-y: auto;">
                                    {{ $comment->content }}
                                </small>
                            </td>
                            <td class="text-end pe-4">
                                <form action="{{ route('admin.comments.approve', ['comment' => $comment->id]) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">Approve</button>
                                </form>

                                <form action="{{ route('admin.comments.reject', ['comment' => $comment->id]) }}" method="POST" class="d-inline-block ms-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-3">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check2-circle mb-2" viewBox="0 0 16 16">
                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>
                                </svg>
                                <br>
                                All pending reviews for this product have been moderated!
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
