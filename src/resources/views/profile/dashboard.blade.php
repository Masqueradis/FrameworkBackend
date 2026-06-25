@extends('layouts.app')

@section('title', 'My Profile - MyStore')

@section('content')
    <div class="container mb-5">
        <div class="row mt-4">

            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-4">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ Storage::disk('minio')->url(auth()->user()->avatar_path) }}" alt="Avatar" class="rounded-circle mb-3 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif

                        <h4 class="fw-bold mb-1">{{ auth()->user()->name }}</h4>
                        <p class="text-muted mb-3">{{ auth()->user()->email }}</p>

                        @if(auth()->user()->hasRole('admin'))
                            <span class="badge bg-danger px-3 py-2 mb-3">Administrator</span>
                        @elseif(auth()->user()->hasRole('seller'))
                            <span class="badge bg-warning px-3 py-2 mb-3">Seller</span>
                        @elseif(auth()->user()->hasRole('manager'))
                            <span class="badge bg-warning px-3 py-2 mb-3">Manager</span>
                        @else
                            <span class="badge bg-secondary px-3 py-2 mb-3">Customer</span>
                        @endif

                        <hr class="text-muted">
                        <div class="d-grid gap-2">
                            <a href="{{ url('/profile/edit') }}" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                            <a href="{{ url('/profile/reviews') }}" class="btn btn-outline-secondary btn-sm">My Reviews</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-3">
                        <span>My Recent Orders</span>
                    </div>

                    @if($orders && $orders->count() > 0)
                        <div class="card-body p-0">
                            @php
                                $totalSpentCents = auth()->user()->orders->filter(function($order) {
                                    $statusStr = $order->status instanceof \BackedEnum ? $order->status->value : (string)$order->status;
                                    return strtolower($statusStr) === 'completed';
                                })->sum(function($order) {
                                    return $order->total_amount_cents->getCents();
                                });
                            @endphp

                            <div class="row g-0 border-bottom text-center bg-light">
                                <div class="col-6 p-3 border-end">
                                    <div class="text-muted small text-uppercase fw-bold mb-1">Total Orders</div>
                                    <div class="fs-4 fw-bold text-primary">{{ $orders->total() }}</div>
                                </div>
                                <div class="col-6 p-3">
                                    <div class="text-muted small text-uppercase fw-bold mb-1">Total Spent</div>
                                    <div class="fs-4 fw-bold text-success">${{ number_format($totalSpentCents / 100, 2) }}</div>
                                </div>
                            </div>

                            <div class="table-responsive p-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                    <tr class="text-muted small text-uppercase">
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($orders as $order)
                                        @php
                                            $statusStr = strtolower($order->status instanceof \BackedEnum ? $order->status->value : (string)$order->status);
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-bold">#{{ $order->id }}</span>
                                            </td>
                                            <td>
                                                {{ $order->created_at->format('M d, Y') }}
                                                <div class="text-muted small">{{ $order->created_at->format('H:i') }}</div>
                                            </td>
                                            <td>
                                                @if($statusStr === 'completed')
                                                    <span class="badge bg-success">Completed</span>

                                                @elseif($statusStr === 'pending' || $statusStr === 'processing')
                                                    <div class="d-flex flex-column align-items-start">
                                                        <span class="badge bg-warning text-dark mb-2">Processing</span>

                                                        <div class="d-flex align-items-center gap-2">
                                                            <form action="{{ route('checkout.retry', $order->id) }}" method="POST" class="m-0 retry-payment-form">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary shadow-sm retry-btn" style="padding: 2px 10px; font-size: 12px; line-height: 1.5; border-radius: 4px;">
                                                                    Pay
                                                                </button>
                                                            </form>

                                                            <form action="{{ route('checkout.decline', $order->id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger shadow-sm" style="padding: 2px 10px; font-size: 12px; line-height: 1.5; border-radius: 4px;">
                                                                    Cancel
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @elseif($statusStr === 'cancelled')
                                                    <span class="badge bg-danger">Cancelled</span>

                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($statusStr) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">
                                                ${{ number_format($order->total_amount_cents->getCents() / 100, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-3 border-top justify-content-center">
                                {{ $orders->links('pagination::bootstrap-5') }}
                            </div>

                        </div>
                    @else
                        <div class="card-body text-center py-5 text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-box-seam mb-3 opacity-50" viewBox="0 0 16 16">
                                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.434 1.37-2.404-.961L3.596 3.5l2.404.961 5.62-2.248zM8.904.381a1.5 1.5 0 0 0-1.808 0L.904 2.868a.5.5 0 0 0-.25.432v8.384a.5.5 0 0 0 .25.432l6.196 2.478a1.5 1.5 0 0 0 1.808 0l6.196-2.478a.5.5 0 0 0 .25-.432V3.3a.5.5 0 0 0-.25-.432L8.904.381zM15 4.14v8.037l-6.5 2.6v-8.037l6.5-2.6zM7.5 14.777l-6.5-2.6V4.14l6.5 2.6v8.037zM7.5 6.03l-6.5-2.6 6.5-2.6 6.5 2.6-6.5 2.6z"/>
                            </svg>
                            <h5 class="fw-bold text-dark">No orders yet</h5>
                            <p class="small mb-4">Looks like you haven't made any purchases. Time to explore our catalog!</p>
                            <a href="{{ route('catalog.index') }}" class="btn btn-primary px-4">Go to Catalog</a>
                        </div>
                    @endif
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

            const forms = document.querySelectorAll('.retry-payment-form');

            forms.forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const submitBtn = form.querySelector('.retry-btn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '...';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Payment error');
                        }

                        if (data.provider === 'stripe') {
                            window.location.href = data.action;
                        } else if (data.provider === 'paddle') {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;

                            Paddle.Checkout.open({
                                transactionId: data.action
                            });
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            });
        });
    </script>
@endsection
