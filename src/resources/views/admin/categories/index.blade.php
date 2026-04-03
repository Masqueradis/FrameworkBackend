@extends('layouts.admin')

@section('title', 'Categories Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Categories</h4>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Add Category</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Name</th>
                    <th>Parent Category</th>
                    <th>Slug</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td class="ps-4 text-muted">{{ $category->id }}</td>
                        <td class="fw-bold">{{ $category->name }}</td>
                        <td>
                            @if($category->parent)
                                <span class="badge bg-info text-dark">{{ $category->parent->name }}</span>
                            @else
                                <span class="badge bg-secondary">None (Root)</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $category->slug }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No categories found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())
            <div class="card-footer bg-white border-0 pt-3">
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
