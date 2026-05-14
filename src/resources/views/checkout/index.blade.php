@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="mb-4">
            <h1 class="h2 fw-bold">Checkout</h1>
        </div>

        @if(session('error'))
            <div class="alert alert-danger mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-8">

                <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
                    @csrf
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">Contact Information</h4>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name', auth()->user()?->name) }}" required>
                                    @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email', auth()->user()?->email) }}" required>
                                    @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" placeholder="+1 (555) 000-00-00" required>
                                    @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Shipping Address</label>
                                    <input type="text" name="shipping_address" class="form-control @error('shipping_address') is-invalid @enderror" value="{{ old('shipping_address') }}" placeholder="123 Street Name, City, Country" required>
                                    @error('shipping_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">Payment Method</h4>

                            <div class="mb-4">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_provider" value="stripe" checked id="prov-stripe">
                                    <label class="form-check-label" for="prov-stripe">Stripe</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_provider" value="paddle" id="prov-paddle">
                                    <label class="form-check-label" for="prov-paddle">Paddle</label>
                                </div>

                            </div>

                            @error('payment_provider')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror

                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="btn btn-primary btn-lg w-100 fw-bold">
                        Pay Now
                    </button>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 2rem;">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4">Your Order</h4>

                        <ul class="list-group list-group-flush mb-4 overflow-auto" style="max-height: 400px;">
                            @forelse($cart->items as $item)
                                @php
                                    $subtotal = new \App\ValueObjects\Cart\Money($item->price->getCents() * $item->quantity);
                                @endphp
                                <li class="list-group-item px-0 d-flex justify-content-between lh-sm">
                                    <div>
                                        <h6 class="my-0">{{ $item->product->name }}</h6>
                                        <small class="text-muted">{{ $item->quantity }} x {{ $item->price->getFormated() }}</small>
                                    </div>
                                    <span class="text-muted">{{ $subtotal->getFormated() }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">Your cart is empty</li>
                            @endforelse
                        </ul>

                        <div class="d-flex justify-content-between align-items-center fw-bold fs-5 border-top pt-3">
                            <span>Total:</span>
                            <span>{{ $total->getFormated() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Paddle.Environment.set('sandbox');
            Paddle.Initialize({
                token: '{{ config('services.paddle.client_token') }}',

                eventCallback: function(data) {
                    if (data.name === 'checkout.completed') {
                        window.location.href = "{{ route('checkout.result') }}?status=success";
                    }
                }
            });

            const form = document.getElementById('checkout-form');
            const submitBtn = document.getElementById('submit-btn');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Validation error');
                    }

                    if (data.provider === 'stripe') {
                        window.location.href = data.action;
                    }
                    else if (data.provider === 'paddle') {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Pay Now';

                        Paddle.Checkout.open({
                            transactionId: data.action
                        });
                    }

                } catch (error) {
                    alert('Error: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Pay Now';
                }
            });
        });
    </script>

@endsection
