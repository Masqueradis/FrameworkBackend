@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <h1 class="h2 fw-bold mb-4">Your Cart</h1>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        @if($cart->items->isEmpty())
            <p class="text-muted">Your cart is empty. <a href="{{ route('catalog.index') }}" class="text-decoration-none">Continue shopping</a></p>
        @else
            <div class="table-responsive shadow-sm bg-white rounded mb-4">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-start">Product</th>
                        <th scope="col" class="text-center">Quantity</th>
                        <th scope="col" class="text-end">Price</th>
                        <th scope="col" class="text-center">Remove</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cart->items as $item)
                        <tr>
                            <td class="text-start fw-medium">
                                {{ $item->product->name }}
                            </td>

                            <td class="text-center">
                                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="d-flex align-items-center justify-content-center m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock }}" class="form-control form-control-sm text-center me-2" style="width: 70px;">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Update
                                    </button>
                                </form>
                            </td>

                            <td class="text-end fw-semibold">
                                {{ $item->price->getFormated() }}
                            </td>

                            <td class="text-center">
                                <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        ✕
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <div class="bg-light p-4 rounded shadow-sm col-12 col-md-5 col-lg-4">
                    <h3 class="h4 fw-bold text-dark mb-3">Total:</h3>
                    <p class="h3 fw-bold text-primary mb-4">{{ $total->getFormated() }}</p>
                    <button class="btn btn-success btn-lg w-100 fw-bold">
                        Checkout
                    </button>
                </div>
            </div>
        @endif
    </div>
@endsection
