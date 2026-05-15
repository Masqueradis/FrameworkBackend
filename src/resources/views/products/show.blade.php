@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <div class="container my-5">
        <div class="mb-4">
            <a href="{{ route('catalog.index') }}" class="text-decoration-none">&larr; Back to Catalog</a>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                @if($images->isNotEmpty())
                    <div class="swiper mainSwiper mb-2 rounded border" style="--swiper-navigation-color: #0d6efd;">
                        <div class="swiper-wrapper">
                            @foreach($images as $image)
                                <div class="swiper-slide bg-light d-flex justify-content-center align-items-center" style="height: 400px;">
                                    <img src="{{ Storage::disk('minio')->url($image->path) }}"
                                         alt="{{ $product->name }}"
                                         class="img-fluid"
                                         style="max-height: 100%; object-fit: contain;">
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>

                    @if($images->count() > 1)
                        <div class="swiper thumbSwiper">
                            <div class="swiper-wrapper">
                                @foreach($images as $image)
                                    <div class="swiper-slide rounded border opacity-50" style="height: 80px; cursor: pointer; overflow: hidden;">
                                        <img src="{{ Storage::disk('minio')->url($image->path) }}"
                                             class="w-100 h-100"
                                             style="object-fit: cover;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center text-secondary rounded border" style="height: 400px;">
                        <div class="text-center opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-image mb-2" viewBox="0 0 16 16">
                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                            </svg>
                            <div>No photo available</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <span class="text-uppercase text-primary fw-bold small">{{ $product->category->name ?? 'Uncategorized' }}</span>
                <h1 class="fw-bolder mb-3">{{ $product->name }}</h1>

                <div class="fs-3 fw-bold text-dark mb-3">
                    ${{ number_format($product->price, 2, '.', ',') }}
                </div>

                <div class="mb-4">
                    @if($product->available && $product->stock > 0)
                        <span class="badge bg-success">In Stock ({{ $product->stock }})</span>
                    @else
                        <span class="badge bg-danger">Out of Stock</span>
                    @endif
                </div>

                @if($product->description)
                    <h5 class="fw-bold">Description</h5>
                    <p class="text-muted">{{ $product->description }}</p>
                @endif

                @if($product->attributes)
                    <h5 class="fw-bold mt-4">Specifications</h5>
                    <table class="table table-sm table-borderless">
                        <tbody>
                        @foreach($product->attributes as $key => $value)
                            <tr>
                                <td class="text-muted" style="width: 40%;">{{ $key }}</td>
                                <td class="fw-semibold">{{ $value }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <hr class="my-4">

                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="d-flex align-items-center mb-4">
                        <label for="quantity" class="fw-bold me-3 text-muted">Quantity:</label>
                        <input type="number"
                               name="quantity"
                               id="quantity"
                               value="1"
                               min="1"
                               max="{{ $product->stock }}"
                               class="form-control text-center fw-bold"
                               style="width: 90px;"
                            @disabled(!$product->available || $product->stock <= 0)>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" @disabled(!$product->available || $product->stock <= 0)>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart-plus me-2 mb-1" viewBox="0 0 16 16">
                            <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9V5.5z"/>
                            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zm3.915 10L3.102 4h10.796l-1.313 7h-8.17zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        Add to Cart
                    </button>
                </form>

            </div>
        </div>
    <div class="row mt-5">
        <div class="col-12">
            <hr class="mb-5">
            <h4 class="fw-bold mb-4">Customer Reviews</h4>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @auth
                <div class="card shadow-sm border-0 bg-light mb-5">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-3">
                            {{ $userComment ? 'Edit Your Review' : 'Write a Review' }}
                        </h5>

                        @if($userComment && $userComment->status->isPending())
                            <div class="alert alert-warning py-2 mb-3">
                                Your current review is pending moderation. You can update it below.
                            </div>
                        @endif

                        <form action="{{ route('comments.store', ['product' => $product->id]) }}" method="POST" id="comment-form">
                            @csrf

                            <div class="mb-3">
                                <label for="rating" class="form-label fw-bold text-muted">Product Rating <span class="text-danger">*</span></label>
                                <select name="rating" id="rating" class="form-select w-auto @error('rating') is-invalid @enderror" required>
                                    <option value="" disabled {{ !$userComment ? 'selected' : '' }}>Select rating...</option>
                                    <option value="5" @selected(old('rating', $userComment?->rating) == 5)>5 - Excellent</option>
                                    <option value="4" @selected(old('rating', $userComment?->rating) == 4)>4 - Good</option>
                                    <option value="3" @selected(old('rating', $userComment?->rating) == 3)>3 - Average</option>
                                    <option value="2" @selected(old('rating', $userComment?->rating) == 2)>2 - Poor</option>
                                    <option value="1" @selected(old('rating', $userComment?->rating) == 1)>1 - Terrible</option>
                                </select>
                                @error('rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="content" class="form-label fw-bold text-muted">Your Review <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" rows="4"
                                          class="form-control @error('content') is-invalid @enderror"
                                          placeholder="Tell us what you liked or disliked about this product..."
                                          required>{{ old('content', $userComment?->content) }}</textarea>
                                @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form> <div class="d-flex align-items-center gap-3 mt-3">
                            <button type="submit" form="comment-form" class="btn btn-primary fw-bold px-4">
                                {{ $userComment ? 'Update Review' : 'Submit for Moderation' }}
                            </button>

                            @if($userComment)
                                <form action="{{ route('comments.destroy', $userComment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your review?');" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger fw-bold px-4">
                                        Delete Review
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary d-flex align-items-center mb-5" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle-fill me-3 text-secondary" viewBox="0 0 16 16">
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                    <div>
                        Please <a href="{{ route('login') }}" class="alert-link">log in</a> or <a href="{{ route('register') }}" class="alert-link">register</a> to leave a review.
                    </div>
                </div>
            @endauth

            <div class="mt-5">
                <h5 class="fw-bold mb-4">Reviews ({{ $comments->count() }})</h5>

                @forelse($comments as $comment)
                    <div class="card mb-3 border-0 shadow-sm bg-white">
                        <div class="card-body p-4">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 d-flex align-items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-circle me-2 text-secondary" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                    {{ $comment->user->name }}
                                </h6>

                                <div class="d-flex align-items-center gap-3">
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>

                                    @if(auth()->check() && auth()->user()->hasRole('admin'))
                                        <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('Delete this comment as Administrator?');" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Review">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-2">
                                <span class="badge bg-warning text-dark fs-6">
                                    ★ {{ $comment->rating }} / 5
                                </span>
                            </div>

                            <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $comment->content }}</p>

                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 bg-light rounded border text-muted">
                        No reviews yet. Be the first to share your thoughts!
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <style>
        .thumbSwiper .swiper-slide-thumb-active {
            opacity: 1 !important;
            border: 2px solid #0d6efd !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var thumbSwiperContainer = document.querySelector(".thumbSwiper");
            var thumbSwiper = null;

            if (thumbSwiperContainer) {
                thumbSwiper = new Swiper(".thumbSwiper", {
                    spaceBetween: 10,
                    slidesPerView: 4,
                    freeMode: true,
                    watchSlidesProgress: true,
                });
            }

            if (document.querySelector(".mainSwiper")) {
                new Swiper(".mainSwiper", {
                    spaceBetween: 10,
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                    thumbs: {
                        swiper: thumbSwiper,
                    },
                });
            }
        });
    </script>
@endsection
