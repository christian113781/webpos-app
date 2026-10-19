<main>
    <h1>User dashboard</h1>
    <p>Welcome to your dashboard!</p>
    <p>This is your user dashboard.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</main>
