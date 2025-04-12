use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Customer routes
    Route::middleware('can:customer')->group(function () {
        // Add customer-specific routes here
    });

    // Admin routes
    Route::middleware('is_admin')->group(function () {
        // Add admin-specific routes here
    });
});
