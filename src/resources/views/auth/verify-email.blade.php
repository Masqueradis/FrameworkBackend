<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email verification</title>
</head>
<body>
    <h1>Verify your email</h1>
@if (session('message'))
    <div style="color:green">
        {{ session('message') }}
    </div>
@endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Send email again</button>
    </form>

    <hr>
    <form method="POST" action="/logout">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
