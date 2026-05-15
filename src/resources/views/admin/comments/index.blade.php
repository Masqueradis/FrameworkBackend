@extends('layouts.admin')

@section('title', 'Review Moderation')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Pending Reviews</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th style="width: 40%;">Review Text</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($comments as $comment)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('web.products.show', $comment->product) }}" target="_blank" class="fw-bold text-decoration-none">
                                    {{ Str::limit($comment->product->name, 30) }}
                                </a>
                            </td>
                            <td>
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
                                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                                        Approve
                                    </button>
                                </form>

                                <form action="{{ route('admin.comments.reject', ['comment' => $comment->id]) }}" method="POST" class="d-inline-block ms-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                        Reject
                                    </button>
                                </form>
                                <form action="{{ route('admin.comments.destroy', ['comment' => $comment->id]) }}" method="POST" class="d-inline-block ms-1" onsubmit="return confirm('Are you sure you want to permanently delete this review?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger px-3">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
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
