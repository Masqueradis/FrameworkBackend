@extends('layouts.admin')

@section('title', isset($product) ? 'Edit Product' : 'Create Product')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.products.index') }}" class="text-decoration-none">&larr; Back to Products</a>
    </div>

    <div class="row">
        <div class="col-lg-{{ isset($product) ? '8' : '12' }} mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($product))
                            @method('PUT')
                        @endif

                        <h5 class="mb-4 text-primary">General Info</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name ?? '') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label fw-bold">Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror"
                                           value="{{ old('price', $product->price ?? '') }}" required>
                                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="stock" class="form-label fw-bold">Stock <span class="text-danger">*</span></label>
                                <input type="number" name="stock" id="stock" class="form-control @error('stock') is-invalid @enderror"
                                       value="{{ old('stock', $product->stock ?? 0) }}" required min="0">
                                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description ?? '') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">Product attributes</h5>

                        @php
                            $currentAttributes = old('attribute_keys')
                                ? array_combine(old('attribute_keys'), old('attribute_values'))
                                : ($product->attributes ?? []);

                            $emptyRows = 3;
                        @endphp

                        <div id="attributes-container">
                            @foreach($currentAttributes as $key => $value)
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <input type="text" name="attribute_keys[]" class="form-control" value="{{ $key }}" placeholder="Name (e.g. Color)">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="attribute_values[]" class="form-control" value="{{ $value }}" placeholder="Value (e.g. Red)">
                                    </div>
                                </div>
                            @endforeach

                            @for($i = 0; $i < $emptyRows; $i++)
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <input type="text" name="attribute_keys[]" class="form-control" placeholder="New attribute name">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="attribute_values[]" class="form-control" placeholder="New attribute value">
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="form-text mb-4">
                            Fill in the blank fields to add new attributes. Empty rows will be ignored. <strong>To delete an attribute, just clear its Name and Value.</strong>
                        </div>
                        <hr class="my-4">

                        <h5 class="mb-3 text-primary">Upload Photos</h5>
                        <div class="mb-4">
                            <input class="form-control @error('images.*') is-invalid @enderror" type="file" name="images[]" id="images" multiple accept="image/png, image/jpeg, image/jpg">
                            <div class="form-text">You can select multiple photos. Allowed formats: png, jpeg, jpg.</div>
                            @error('images.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-grid d-md-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                {{ isset($product) ? 'Update Product' : 'Save Product' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(isset($product))
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="mb-3 text-primary">Existing Photos</h5>

                        <div id="image-gallery" class="row g-2">
                            @if($product->images && $product->images->count() > 0)
                                @foreach($product->images as $image)
                                    <div class="col-6">
                                        <div class="position-relative">
                                            <img src="{{ Storage::disk('minio')->url($image->path) }}" class="img-fluid rounded border w-100" style="object-fit: cover; height: 100px;" alt="Product Image">
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12 text-center text-muted small">No photos uploaded yet</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
