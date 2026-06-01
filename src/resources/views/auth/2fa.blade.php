<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-sm p-4" style="width: 100%; max-width: 400px;">

        <div class="mb-3">
            <a href="{{ route('login') }}" class="text-decoration-none text-muted small">
                &larr; Back to Login
            </a>
        </div>

        <div class="text-center mb-4">
            <h2 class="fw-bold">Two-Factor Auth</h2>
            <p class="text-muted">Enter the 6-digit code from your authenticator app</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.2fa.post') }}">
            @csrf

            <div class="mb-4">
                <label class="form-label text-center w-100 fw-bold">Authentication Code</label>
                <input type="text" name="otp" class="form-control text-center fs-4" placeholder="000 000" style="letter-spacing: 5px;" required autofocus autocomplete="off">
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3 fw-bold">Verify</button>
        </form>
    </div>
</div>
</body>
</html>
