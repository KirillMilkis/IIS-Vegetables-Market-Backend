<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SelfHarvestingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderProductQuantityController;
use App\Models\OrderProductQuantity;
use App\Models\CategoryAttribute;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::group(['middleware' => ['web'],'namespace' => 'App\Http\Controllers'], function () {
    // Get all users 
    Route::get('users', [UserController::class, 'index']);
    // Get farmer by product id
    Route::get('users/farmer', [UserController::class, 'getFarmerByProductId']);
    // Create new user
    Route::post('users/create', [UserController::class, 'store'])->middleware('auth:sanctum');
    // Get user by id
    Route::get('users/{id}', [UserController::class, 'show']);
    // Update user by id (put)
    Route::put('users/{id}', [UserController::class, 'update'])->middleware('auth:sanctum');
    // Update user by id (patch)
    Route::patch('users/{id}', [UserController::class, 'update'])->middleware('auth:sanctum');
    // Delete user by id
    Route::delete('users/{id}', [UserController::class, 'delete'])->middleware('auth:sanctum');
    // Attach self harvesting to reg user, who want to visit
    Route::post('/users/attach-self-harvesting', [UserController::class, 'attachSelfHarvesting'])->middleware('auth:sanctum');
    // Detach self harvesting from reg user, who dont want to visit
    Route::post('/users/detach-self-harvesting', [UserController::class, 'detachSelfHarvesting'])->middleware('auth:sanctum');
    // Get all farmers
    Route::get('/farmers', [UserController::class, 'getFarmers']);

    // Get all products
    Route::get('products', [ProductController::class, 'index']);
    // Filter products
    Route::get('products/filter', [ProductController::class, 'filter']);
    // Create new product
    Route::post('products/create', [ProductController::class, 'store'])->middleware('auth:sanctum');
    // Get products by farmer id, all products of specific farmer
    Route::get('/farmer/{farmerId}/products', [ProductController::class, 'getProductsByFarmer']);
    // Get products by self harvesting id, all products of specific self harvesting
    Route::get('/products/selfHarvesting/{selfHarvestingId}', [ProductController::class, 'getProductsBySelfHarvesting']);
    // Get product by id
    Route::get('products/{id}', [ProductController::class, 'show']);
    // Update product by id (put)
    Route::put('products/{id}', [ProductController::class, 'update'])->middleware('auth:sanctum');
    // Update product by id (patch)
    Route::patch('products/{id}', [ProductController::class, 'update'])->middleware('auth:sanctum');
    // Delete product by id
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->middleware('auth:sanctum');

    // Filter categories
    Route::get('categories/filter', [CategoryController::class, 'filter']); 
    // Get all categories
    Route::get('categories', [CategoryController::class, 'index']);
    // Get all categories to approve for moderator
    Route::get('categories/toApprove', [CategoryController::class, 'getToApprove'])->middleware('auth:sanctum');
    // Get category by id
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    // Update category by id (put)
    Route::put('categories/{id}', [CategoryController::class, 'update'])->middleware('auth:sanctum');
    // Update category by id (patch)
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->middleware('auth:sanctum');
    // Create new category
    Route::post('categories/create', [CategoryController::class, 'store'])->middleware('auth:sanctum');
    // Approve category (method for moderator)
    Route::put('categories/{categoryId}/approve', [CategoryController::class, 'approveCategory'])->middleware('auth:sanctum');
    // Reject category (method for moderator)
    Route::put('categories/{categoryId}/reject', [CategoryController::class, 'rejectCategory'])->middleware('auth:sanctum');

    // Get all attributes
    Route::get('attributes', [AttributeController::class, 'index']); 
    // Get all attributes by category or product (not both)
    Route::get('attributes/categoryOrProduct', [AttributeController::class, 'getByCategoryOrProduct']); 
    // Filter attributes
    Route::get('attributes/filter', [AttributeController::class, 'filter']);
    // Get attribute by id
    Route::get('attributes/{id}', [AttributeController::class, 'show']);
    // Update attribute (put)
    Route::put('attributes/{id}', [AttributeController::class, 'update'])->middleware('auth:sanctum');
    // Update attribute (patch)
    Route::patch('attributes/{id}', [AttributeController::class, 'update'])->middleware('auth:sanctum');
    // Delete attribute by id
    Route::delete('attributes/{id}', [AttributeController::class, 'destroy'])->middleware('auth:sanctum');

    // Get all attribute values
    Route::get('attribute_values', [AttributeValueController::class, 'index']);
    // Get all attribute values for specific product by product id
    Route::get('attribute_values/product/{productId}', [AttributeValueController::class, 'getByProduct']);
    // Get all attribute values for specific attribute by attribute id
    Route::get('attribute_values/attribute', [AttributeValueController::class, 'getByAttribute']); 
    // Get attribute value by id
    Route::get('attribute_values/{id}', [AttributeValueController::class, 'show']);
    // Update attribute value (put)
    Route::put('attribute_values/{id}', [AttributeValueController::class, 'update'])->middleware('auth:sanctum');
    // Delete attribute value by id
    Route::delete('attribute_values/{id}', [AttributeValueController::class, 'destroy'])->middleware('auth:sanctum');

    // Get all orders
    Route::get('orders', [OrderController::class, 'index']);
    // Get all orders that have specific user by user id
    Route::get('orders/user/{id}', [OrderController::class, 'getByUser'])->middleware('auth:sanctum');
    // Get unordered cart that have specific user by user id
    Route::get('orders/user/{id}/unordered', [OrderController::class, 'getUnorderedOrder'])->middleware('auth:sanctum');
    // Create new order
    Route::post('orders/create', [OrderController::class, 'store'])->middleware('auth:sanctum');
    // Get order by id
    Route::get('orders/{id}', [OrderController::class, 'show']);
    // Update order by id (put)
    Route::put('orders/{id}', [OrderController::class, 'update'])->middleware('auth:sanctum');
    // Update order by id (patch)
    Route::patch('orders/{id}', [OrderController::class, 'update'])->middleware('auth:sanctum');
    // Update order status to ordered
    Route::put('orders/{id}/placeOrder', [OrderController::class, 'updateOrderStatusToOrdered'])->middleware('auth:sanctum');
    // Delete order by id
    Route::delete('orders/{id}', [OrderController::class, 'destroy'])->middleware('auth:sanctum');

    // Get all order product quantities
    Route::get('orderProductQuantity', [OrderProductQuantityController::class, 'index']);
    // Get all order product quantities that have specific farmer by farmer id
    Route::get('orderProductQuantity/farmer/{farmerId}', [OrderProductQuantityController::class, 'getByFarmerId'])->middleware('auth:sanctum');
    // Get all order product quantities that have specific order by order id
    Route::get('orderProductQuantity/order/{orderId}', [OrderProductQuantityController::class, 'getByOrderId'])->middleware('auth:sanctum');
    // Create new order product quantity
    Route::post('orderProductQuantity/create', [OrderProductQuantityController::class, 'store'])->middleware('auth:sanctum');
    // Get order product quantity by id
    Route::get('orderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'show']);
    // Update order product quantity by id (put)
    Route::put('orderProductQuantity/{id}', [OrderProductQuantityController::class, 'update'])->middleware('auth:sanctum');
    // Update status of order product quantity by id (put)
    Route::put('orderProductQuantity/updateStatus/{id}', [OrderProductQuantityController::class, 'updateStatus'])->middleware('auth:sanctum');
    // Update order product quantity by id (patch)
    Route::patch('orderProductQuantity/{order_id}/{product_id}', [OrderProductQuantityController::class, 'update'])->middleware('auth:sanctum');
    // Delete order product quantity by id
    Route::delete('orderProductQuantity/{id}', [OrderProductQuantityController::class, 'destroy'])->middleware('auth:sanctum');

    // Get all self harvestings
    Route::get('SelfHarvesting', [SelfHarvestingController::class, 'index']);
    // Get all self harvestings that have specific user by user id
    Route::get('SelfHarvesting/user/{userId}', [SelfHarvestingController::class, 'getUserSelfHarvestings'])->middleware('auth:sanctum');
    // Get all self harvestings that have specific product by product id
    Route::get('SelfHarvesting/product/{productId}', [SelfHarvestingController::class, 'getProductSelfHarvestings']);
    // Get all self harvestings that have specific farmer by farmer id
    Route::get('SelfHarvesting/farmer/{farmerId}', [SelfHarvestingController::class, 'getFarmerSelfHarvestings']);
    // Create new self harvesting
    Route::post('SelfHarvesting/create', [SelfHarvestingController::class, 'store'])->middleware('auth:sanctum');
    // Get self harvesting by id
    Route::get('SelfHarvesting/{id}', [SelfHarvestingController::class, 'show']);
    // Update self harvesting by id (put)
    Route::put('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update'])->middleware('auth:sanctum');
    // Update self harvesting by id (patch)
    Route::patch('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update'])->middleware('auth:sanctum');
    // Delete self harvesting by id
    Route::delete('SelfHarvesting/{id}', [SelfHarvestingController::class, 'destroy'])->middleware('auth:sanctum');

    // Get all reviews
    Route::get('reviews', [ReviewController::class, 'index']);
    // Get all reviews for specific product by product id
    Route::get('reviews/product/{productId}', [ReviewController::class, 'getByProduct']);
    // Get average rating for specific product by product id
    Route::get('reviews/average/{productId}', [ReviewController::class, 'getAverageRating']);
    // Create new review
    Route::post('reviews/create', [ReviewController::class, 'store'])->middleware('auth:sanctum');
    // Get review by id
    Route::get('reviews/{id}', [ReviewController::class, 'show']);
    // Update review by id (put)
    Route::put('reviews/{id}', [ReviewController::class, 'update'])->middleware('auth:sanctum');
    // Update review by id (patch)
    Route::patch('reviews/{id}', [ReviewController::class, 'update'])->middleware('auth:sanctum');
    // Delete review by id
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

    // Login and logout
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/register', [AuthController::class, 'register']);

    // Get token for session
    Route::get('/csrf-token', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    });

});
