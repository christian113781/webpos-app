<main>
    <h1>Admin dashboard</h1>
    <p>Welcome to your dashboard!</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</main>
