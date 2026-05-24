<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 20px 0;
        }
        .email-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #4F46E5;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .order-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .order-details ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .order-details li {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .order-details li:last-child {
            border-bottom: none;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-card">

        <div class="header">
            <h1>Thank You For Your Order!</h1>
        </div>

        <div class="content">
            <p>Hi <strong>{{ $order->customer_name }}</strong>,</p>
            <p>We're getting your order ready to be shipped. We will notify you when it has been sent.</p>

            <div class="order-details">
                <h3>Order Summary (Order #{{ $order->id }})</h3>
                <ul>
                    <li><strong>Date:</strong> {{ $order->created_at->format('F j, Y') }}</li>
                    <li><strong>Status:</strong> {{ ucfirst($order->status->value ?? $order->status) }}</li>
                    <li><strong>Total Paid:</strong> ${{ number_format($order->total_amount_cents->getCents() / 100, 2) }}</li>
                </ul>
            </div>

            <p>If you have any questions, simply reply to this email. We're here to help!</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>

    </div>
</div>
</body>
</html>
