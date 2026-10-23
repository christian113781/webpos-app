<html>
    <head>
        <title>Dashboard</title>
        <link rel="icon" type="image/png" href="{{ asset('talipapapos/assets/img/favicon.png') }}">
    </head>
    <body>
        <h1>Dashboard</h1>
        <p>Welcome to your dashboard!</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </body>
</html>