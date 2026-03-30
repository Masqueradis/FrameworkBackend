<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href=" {{ asset('app.css') }}">
</head>
<body>
    <div class="container">
        <h1>Welcome, {{auth()->user()->name}}</h1>
        <p>Your email: {{auth()->user()->email }}</p>

        <hr>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</body>
</html>
