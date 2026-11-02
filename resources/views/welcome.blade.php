<x-foundation::guest-shell
    title="Welcome"
    :heading="config('app.name')"
    subheading="Laravel 13 modular-monolith composition foundation."
>
    <x-foundation::alert type="warning">
        No sign-in is available yet. Install an identity/auth module to continue:
        <code>php artisan module:install identity</code>
    </x-foundation::alert>
</x-foundation::guest-shell>
