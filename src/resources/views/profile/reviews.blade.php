@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <h2 class="mb-4">My Reviews</h2>

        @if($comments->isEmpty())
            <div class="alert alert-info">You haven't left any reviews yet.</div>
        @else
            <div class="row">
                @foreach($comments as $comment)
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body d-flex">
                                <div class="me-4 flex-shrink-0">
                                    @if(isset($comment->product->image_url))
                                        <img src="{{ $comment->product->image_url }}" alt="{{ $comment->product->name }}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center text-secondary rounded border" style="width: 100px; height: 100px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-image opacity-50" viewBox="0 0 16 16">
                                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            @if($comment->product)
                                                <a href="{{ route('web.products.show', $comment->product) }}" class="text-decoration-none fw-bold">
                                                    {{ $comment->product->name }}
                                                </a>
                                            @else
                                                <span class="text-muted fw-bold">Deleted Product</span>
                                            @endif
                                        </h5>
                                        <div>
                                            @if($comment->status === \App\Enums\CommentStatus::Approved)
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($comment->status === \App\Enums\CommentStatus::Pending)
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @else
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                    <span class="badge bg-warning text-dark fs-6">
                                        ★ {{ $comment->rating }} / 5
                                    </span>
                                    </div>

                                    <p class="card-text mb-3" style="white-space: pre-line;">{{ $comment->content }}</p>

                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editReviewModal{{ $comment->id }}">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('partials.review-modal', [
                        'id' => 'editReviewModal' . $comment->id,
                        'action' => route('profile.reviews.update', $comment),
                        'method' => 'PATCH',
                        'comment' => $comment,
                        'title' => 'Edit Review'
                    ])

                @endforeach
            </div>
        @endif
    </div>
@endsection
