@extends('layouts.admin')

@section('title', isset($category) ? 'Edit Category' : 'Create Category')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.categories.index') }}" class="text-decoration-none">&larr; Back to Categories</a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 600px;">
        <div class="card-body p-4">
            <form action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST">
                @csrf
                @if(isset($category))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $category->name ?? '') }}" required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="parent_id" class="form-label fw-bold">Parent Category</label>
                    <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">-- No Parent (Root Category) --</option>
                        @foreach($categories as $parent)
                            <option value="{{ $parent->id }}"
                                {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Select a parent category if this is a subcategory.</div>
                    @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    {{ isset($category) ? 'Update Category' : 'Save Category' }}
                </button>
            </form>
        </div>
    </div>
@endsection
