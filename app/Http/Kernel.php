protected $routeMiddleware = [
    'is_admin' => \App\Http\Middleware\EnsureIsAdmin::class,
    protected $middlewareAliases = [
    'role' => \App\Http\Middleware\CheckRole::class,
];
];