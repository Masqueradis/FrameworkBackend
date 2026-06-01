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
                                    {{ $product->comments->count() }} new
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-3" data-bs-toggle="modal" data-bs-target="#reviewsModal-{{ $product->id }}">
                                    Moderate Reviews &rarr;
                                </button>
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

            <div class="p-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- ВЫНОСИМ МОДАЛЬНЫЕ ОКНА ИЗ ТАБЛИЦЫ СЮДА --}}
    @foreach($products as $product)
        <div class="modal fade" id="reviewsModal-{{ $product->id }}" tabindex="-1" aria-labelledby="modalLabel-{{ $product->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="modalLabel-{{ $product->id }}">
                            Reviews for: {{ $product->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-light">
                        @forelse($product->comments as $comment)
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="fw-bold fs-6">{{ $comment->user->name }}</span>
                                            <span class="badge bg-warning text-dark ms-2">★ {{ $comment->rating }}</span>
                                            <div class="text-muted small mt-1">{{ $comment->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 bg-white p-3 rounded border">
                                        @if(\Illuminate\Support\Str::length($comment->content) > 100)
                                            <div id="short-text-{{ $comment->id }}" class="text-dark mb-0" style="word-break: break-word;">
                                                {{ \Illuminate\Support\Str::limit($comment->content, 100) }}
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none fw-bold" onclick="toggleText({{ $comment->id }})">Read more</button>
                                            </div>
                                            {{-- ВАЖНО: Весь текст и кнопка написаны в одну строку, чтобы pre-line не добавлял пустых абзацев --}}
                                            <div id="full-text-{{ $comment->id }}" class="text-dark mb-0 d-none" style="white-space: pre-line; word-break: break-word;">{{ $comment->content }} <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none text-danger fw-bold" onclick="toggleText({{ $comment->id }})">Hide</button></div>
                                        @else
                                            {{-- ВАЖНО: В одну строку --}}
                                            <p class="text-dark mb-0" style="white-space: pre-line; word-break: break-word;">{{ $comment->content }}</p>
                                        @endif
                                    </div>

                                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                                        <form action="{{ route('admin.comments.approve', $comment) }}" method="POST" class="m-0">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success fw-bold px-4">Approve</button>
                                        </form>
                                        <form action="{{ route('admin.comments.reject', $comment) }}" method="POST" class="m-0">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-4">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No reviews to moderate.</div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    @endforeach

    <script>
        function toggleText(id) {
            document.getElementById('short-text-' + id).classList.toggle('d-none');
            document.getElementById('full-text-' + id).classList.toggle('d-none');
        }
    </script>
@endsection
