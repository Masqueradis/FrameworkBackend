@extends('layouts.admin')

@section('title', 'Products Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Products</h4>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Add Product</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td class="ps-4 text-muted">{{ $product->id }}</td>
                        <td class="fw-bold">{{ $product->name }}</td>
                        <td>
                            @if($product->category)
                                <span class="badge bg-secondary">{{ $product->category->name }}</span>
                            @else
                                <span class="text-muted small">Uncategorized</span>
                            @endif
                        </td>
                        <td class="fw-bold text-success">${{ number_format($product->price, 2) }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No products found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="card-footer bg-white border-0 pt-3">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
