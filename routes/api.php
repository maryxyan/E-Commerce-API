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

// Public product routes (browsing products doesn't require authentication)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Protected product management routes (admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});

// Protected order routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Customer order routes
    Route::get('/orders/my', [OrderController::class, 'customerOrders']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('can:view,order');
    
    // Admin order routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
});