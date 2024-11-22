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
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/farmer', [UserController::class, 'getFarmerByProductId']);
    Route::post('users/create', [UserController::class, 'store'])->middleware('auth:sanctum');
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('users/{id}', [UserController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('users/{id}', [UserController::class, 'delete'])->middleware('auth:sanctum');
    Route::get('/farmers', [UserController::class, 'getFarmers']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/filter', [ProductController::class, 'filter']);
    Route::post('products/create', [ProductController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/farmer/{farmerId}/products', [ProductController::class, 'getProductsByFarmer']);
    Route::get('/products/selfHarvesting/{selfHarvestingId}', [ProductController::class, 'getProductsBySelfHarvesting']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::put('products/{id}', [ProductController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('products/{id}', [ProductController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->middleware('auth:sanctum');

    Route::get('categories/filter', [CategoryController::class, 'filter']); // cahnged route
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/toApprove', [CategoryController::class, 'getToApprove'])->middleware('auth:sanctum');
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update'])->middleware('auth:sanctum');
    // Route::patch('categories/{id}', [CategoryController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('categories/create', [CategoryController::class, 'store'])->middleware('auth:sanctum');
    Route::put('categories/{categoryId}/approve', [CategoryController::class, 'approveCategory'])->middleware('auth:sanctum');
    Route::put('categories/{categoryId}/reject', [CategoryController::class, 'rejectCategory'])->middleware('auth:sanctum');


    Route::get('attributes', [AttributeController::class, 'index']); 
    Route::get('attributes/categoryOrProduct', [AttributeController::class, 'getByCategoryOrProduct']); // cahnged
    Route::get('attributes/filter', [AttributeController::class, 'filter']);
    Route::post('attributes/create', [AttributeController::class, 'store'])->middleware('auth:sanctum');
    Route::get('attributes/{id}', [AttributeController::class, 'show']);
    Route::put('attributes/{id}', [AttributeController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('attributes/{id}', [AttributeController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('attributes/{id}', [AttributeController::class, 'destroy'])->middleware('auth:sanctum');

    Route::get('attribute_values', [AttributeValueController::class, 'index']);
    Route::get('attribute_values/product/{productId}', [AttributeValueController::class, 'getByProduct']);
    Route::get('attribute_values/attribute', [AttributeValueController::class, 'getByAttribute']); // changed route
    Route::post('attribute_values/create', [AttributeValueController::class, 'store'])->middleware('auth:sanctum');
    Route::get('attribute_values/{id}', [AttributeValueController::class, 'show']);
    Route::put('attribute_values/{id}', [AttributeValueController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('attribute_values/{id}', [AttributeValueController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('attribute_values/{id}', [AttributeValueController::class, 'destroy'])->middleware('auth:sanctum');

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/user/{id}', [OrderController::class, 'getByUser'])->middleware('auth:sanctum');
    Route::get('orders/user/{id}/unordered', [OrderController::class, 'getUnorderedOrder'])->middleware('auth:sanctum');
    Route::post('orders/create', [OrderController::class, 'store'])->middleware('auth:sanctum');
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::put('orders/{id}', [OrderController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('orders/{id}', [OrderController::class, 'update'])->middleware('auth:sanctum');
    Route::put('orders/{id}/placeOrder', [OrderController::class, 'updateOrderStatusToOrdered'])->middleware('auth:sanctum');
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);

    Route::get('orderProductQuantity', [OrderProductQuantityController::class, 'index']);
    Route::get('orderProductQuantity/farmer/{farmerId}', [OrderProductQuantityController::class, 'getByFarmerId'])->middleware('auth:sanctum');
    Route::get('orderProductQuantity/order/{orderId}', [OrderProductQuantityController::class, 'getByOrderId'])->middleware('auth:sanctum');
    Route::post('orderProductQuantity/create', [OrderProductQuantityController::class, 'store'])->middleware('auth:sanctum');
    Route::get('orderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'show']);
    Route::put('orderProductQuantity/{id}', [OrderProductQuantityController::class, 'update'])->middleware('auth:sanctum');
    Route::put('orderProductQuantity/updateStatus/{id}', [OrderProductQuantityController::class, 'updateStatus'])->middleware('auth:sanctum');
    Route::patch('orderProductQuantity/{order_id}/{product_id}', [OrderProductQuantityController::class, 'update']);
    Route::delete('orderProductQuantity/{id}', [OrderProductQuantityController::class, 'destroy'])->middleware('auth:sanctum');

    Route::get('SelfHarvesting/user/{userId}', [SelfHarvestingController::class, 'getUserSelfHarvestings'])->middleware('auth:sanctum');
    Route::get('SelfHarvesting/product/{productId}', [SelfHarvestingController::class, 'getProductSelfHarvestings'])->middleware('auth:sanctum');
    Route::get('SelfHarvesting/farmer/{farmerId}', [SelfHarvestingController::class, 'getFarmerSelfHarvestings']);
    Route::get('SelfHarvesting', [SelfHarvestingController::class, 'index']);
    Route::post('SelfHarvesting/create', [SelfHarvestingController::class, 'store'])->middleware('auth:sanctum');
    Route::get('SelfHarvesting/{id}', [SelfHarvestingController::class, 'show']);
    Route::put('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update']);
    Route::patch('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update']);
    Route::post('/users/attach-self-harvesting', [UserController::class, 'attachSelfHarvesting'])->middleware('auth:sanctum');
    Route::post('/users/detach-self-harvesting', [UserController::class, 'detachSelfHarvesting'])->middleware('auth:sanctum');
    Route::delete('SelfHarvesting/{id}', [SelfHarvestingController::class, 'destroy'])->middleware('auth:sanctum');

    Route::get('reviews', [ReviewController::class, 'index']);
    Route::get('reviews/product/{productId}', [ReviewController::class, 'getByProduct']);
    Route::get('reviews/average/{productId}', [ReviewController::class, 'getAverageRating']);
    Route::post('reviews/create', [ReviewController::class, 'store']);
    Route::get('reviews/{id}', [ReviewController::class, 'show']);
    Route::put('reviews/{id}', [ReviewController::class, 'update']);
    Route::patch('reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);


    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    // Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');


    Route::get('/csrf-token', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    });

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    


});
