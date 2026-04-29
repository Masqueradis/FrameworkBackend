@props(['product'])

<div class="card h-100 shadow-sm border-0">
    @php
        // Ищем главную картинку (is_primary = true), либо берем первую доступную
        $mainImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
    @endphp

    @if($mainImage)
        <div class="position-relative bg-light rounded-top" style="height: 180px; border-bottom: 1px solid #f8f9fa; overflow: hidden;">
            <img src="{{ Storage::disk('minio')->url($mainImage->path) }}"
                 alt="{{ $product->name }}"
                 class="w-100 h-100"
                 style="object-fit: cover; object-position: center;">
        </div>
    @else
        <div class="bg-light d-flex align-items-center justify-content-center text-secondary rounded-top" style="height: 180px; border-bottom: 1px solid #f8f9fa;">
            <div class="text-center opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-image mb-2" viewBox="0 0 16 16">
                    <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                    <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                </svg>
                <div class="small">Photo coming soon</div>
            </div>
        </div>
    @endif

    <div class="card-body d-flex flex-column">
        <small class="text-primary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">
            {{ $product->category->name ?? 'Uncategorized' }}
        </small>

        <h5 class="card-title fw-bold text-dark flex-grow-1 fs-6 mb-3">
            {{ $product->name }}
        </h5>

        @if($product->attributes)
            <ul class="list-unstyled small text-muted mb-3 border-start border-2 border-primary ps-2">
                @foreach(array_slice($product->attributes, 0, 3) as $key => $value)
                    <li class="mb-1 text-truncate">
                        <span class="fw-semibold">{{ $key }}:</span> {{ $value }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mb-3">
            @if($product->available && $product->stock > 0)
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                    In Stock ({{ $product->stock }})
                </span>
            @else
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                    Out of Stock
                </span>
            @endif
        </div>

        <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top">
            <span class="fs-5 fw-bolder text-dark mb-0">
                ${{ number_format($product->price, 2, '.', ',') }}
            </span>

            <button class="btn btn-outline-primary btn-sm fw-medium" @disabled(!$product->available || $product->stock <= 0)>
                Add to Cart
            </button>
        </div>

        @canany(['update', 'delete'], $product)
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="small text-muted fw-bold">Management:</span>
                <div class="btn-group">
                    @can('update', $product)
                        <a href="{{ url('/admin/products/'.$product->id.'/edit') }}" class="btn btn-outline-warning btn-sm" style="margin-right: 5px">Edit</a>
                    @endcan

                    @can('delete', $product)
                        <form action="{{ url('/admin/products/'.$product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                    @endcan
                </div>
            </div>
        @endcanany

    </div>
</div>
