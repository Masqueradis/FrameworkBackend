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
                    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST">
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
                                <option value="">-- Select Category --</option>
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
                        @endphp

                        <div id="attributes-container">
                            @foreach($currentAttributes as $key => $value)
                                <div class="row mb-2 attribute-row">
                                    <div class="col-5">
                                        <input type="text" name="attribute_keys[]" class="form-control" value="{{ $key }}" placeholder="Name " required>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="attribute_values[]" class="form-control" value="{{ $value }}" placeholder="Value " required>
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-attribute">Delete</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" id="add-attribute-btn" class="btn btn-sm btn-outline-secondary mt-2">
                            + Add attribute
                        </button>

                        <button type="submit" class="btn btn-primary px-4">
                            {{ isset($product) ? 'Update Product' : 'Save Product' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(isset($product))
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="mb-3 text-primary">Photo</h5>

                        <div id="drop-zone" class="border rounded-3 p-4 text-center bg-light" style="border-style: dashed !important; border-width: 2px !important; cursor: pointer; transition: 0.2s;">
                            <i class="bi bi-cloud-arrow-up display-6 text-secondary mb-2"></i>
                            <p class="mb-1">Drug photo</p>
                            <small class="text-muted">or click for selection</small>
                            <input type="file" id="file-input" class="d-none" multiple accept="image/png, image/jpeg, image/jpg">
                        </div>

                        <div id="upload-status" class="mt-3 text-sm d-none"></div>

                        <div id="image-gallery" class="mt-4 row g-2">
                            @if($product->images && $product->images->count() > 0)
                                @foreach($product->images as $image)
                                    <div class="col-6">
                                        <div class="position-relative">
                                            <img src="{{ Storage::disk('minio')->url($image->path) }}" class="img-fluid rounded border w-100" style="object-fit: cover; height: 100px;" alt="Product Image">
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12 text-center text-muted small">No photo</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@if(isset($product))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dropZone = document.getElementById('drop-zone');
                const fileInput = document.getElementById('file-input');
                const uploadStatus = document.getElementById('upload-status');
                const productId = {{ $product->id }};

                dropZone.addEventListener('click', () => fileInput.click());

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, e => {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.replace('bg-light', 'bg-primary');
                        dropZone.classList.add('bg-opacity-10', 'border-primary');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.replace('bg-primary', 'bg-light');
                        dropZone.classList.remove('bg-opacity-10', 'border-primary');
                    }, false);
                });

                dropZone.addEventListener('drop', (e) => uploadFiles(e.dataTransfer.files));
                fileInput.addEventListener('change', (e) => uploadFiles(e.target.files));

                async function uploadFiles(files) {
                    if (!files || files.length === 0) return;

                    uploadStatus.innerHTML = '<span class="text-primary spinner-border spinner-border-sm me-2" role="status"></span>Download';
                    uploadStatus.classList.remove('d-none');

                    let hasErrors = false;

                    for (let i = 0; i < files.length; i++) {
                        let formData = new FormData();
                        formData.append('image', files[i]);
                        formData.append('is_primary', false);
                        formData.append('position', i);

                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                            const response = await fetch(`/admin/products/${productId}/images`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            if (!response.ok) hasErrors = true;
                        } catch (error) {
                            console.error(error);
                            hasErrors = true;
                        }
                    }

                    if (hasErrors) {
                        uploadStatus.innerHTML = '<span class="text-danger"> Download error, check photo type</span>';
                    } else {
                        uploadStatus.innerHTML = '<span class="text-success"> Success</span>';
                        setTimeout(() => window.location.reload(), 1000);
                    }
                    fileInput.value = '';
                }
            });


        </script>
    @endpush
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('attributes-container');
            const addBtn = document.getElementById('add-attribute-btn');

            if (addBtn && container) {
                addBtn.addEventListener('click', function() {
                    const row = document.createElement('div');
                    row.className = 'row mb-2 attribute-row';
                    row.innerHTML = `
                <div class="col-5">
                    <input type="text" name="attribute_keys[]" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-5">
                    <input type="text" name="attribute_values[]" class="form-control" placeholder="Value" required>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-attribute">Delete</button>
                </div>
            `;
                    container.appendChild(row);
                });

                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-attribute')) {
                        e.target.closest('.attribute-row').remove();
                    }
                });
            }
        });
    </script>
@endpush
