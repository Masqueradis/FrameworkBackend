@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <meta http-equiv="refresh" content="5;url={{ url('/') }}">

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 text-center">
                <div class="card shadow-sm border-0 mt-5">
                    <div class="card-body p-5">
                        @if($status === 'success')
                            <div class="text-success mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                                </svg>
                            </div>
                            <h2 class="fw-bold mb-3">Payment Successful!</h2>
                        @else
                            <div class="text-danger mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </div>
                            <h2 class="fw-bold mb-3">Payment Failed</h2>
                        @endif

                        <p class="text-muted fs-5 mb-5">{{ session('message') }}</p>

                        <div class="progress mb-4" style="height: 5px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100" role="progressbar"></div>
                        </div>

                        <p class="text-muted small mb-4">You will be redirected in 5 seconds</p>

                        <a href="{{ url('/') }}" class="btn btn-primary w-100">
                            Continue Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
