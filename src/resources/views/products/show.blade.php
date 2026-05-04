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
