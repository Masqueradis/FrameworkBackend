@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4">Profile Settings</h2>

                <div class="card mb-4">
                    <div class="card-header">Personal Information</div>
                    <div class="card-body">
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3 d-flex align-items-center">
                                @if(auth()->user()->avatar_path)
                                    <img src="{{ Storage::disk('minio')->url(auth()->user()->avatar_path) }}" alt="Avatar" class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif

                                <div class="flex-grow-1">
                                    <label for="avatar" class="form-label">Upload new avatar (JPG, PNG up to 2MB)</label>
                                    <div class="d-flex gap-2">
                                        <div class="flex-grow-1">
                                            <input class="form-control @error('avatar') is-invalid @enderror" type="file" id="avatar" name="avatar">
                                            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        @if(auth()->user()->avatar_path)
                                            <button type="submit" form="delete-avatar-form" class="btn btn-outline-danger">Delete</button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nickname</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>

                        <form id="delete-avatar-form" action="{{ route('profile.avatar.destroy') }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Two-Factor Authentication (2FA)</div>
                    <div class="card-body">
                        @if(auth()->user()->google2fa_secret)
                            <div class="alert alert-success">
                                Two-factor authentication is successfully enabled.
                            </div>
                            <form action="{{ route('2fa.disable') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-warning fw-bold">Disable 2FA</button>
                            </form>
                        @else
                            <p>To enhance your account security, enable 2FA using Google Authenticator.</p>

                            @if(session('2fa_qr'))
                                <div class="mb-3 text-center">
                                    {!! QrCode::size(200)->generate(session('2fa_qr')) !!}
                                </div>
                                <form action="{{ route('2fa.enable') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="otp" class="form-label">Enter the 6-digit code from the app</label>
                                        <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" required>
                                        @error('otp')
                                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-success fw-bold">Verify and Enable</button>
                                </form>
                            @else
                                <form action="{{ route('2fa.generate') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary fw-bold">Generate QR Code</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="card border-danger">
                    <div class="card-header text-danger">Danger Zone</div>
                    <div class="card-body">
                        <p>Once you delete your account, all your data will be permanently erased. This action cannot be undone.</p>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            Delete Account
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('profile.destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please enter your password to confirm account deletion.</p>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, delete account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
