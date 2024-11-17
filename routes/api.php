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
use App\Models\OrderProductQuantity;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::group(['middleware' => ['web'],'namespace' => 'App\Http\Controllers'], function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/role/{role}', 'UserController@index_specified');
    Route::post('users/create', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/filter', [ProductController::class, 'filter']);
    Route::post('products/create', [ProductController::class, 'store']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::put('products/', [ProductController::class, 'update']);
    Route::patch('products/', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/filter', [CategoryController::class, 'filter']);
    Route::post('categories/create', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::patch('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('attributes', [AttributeController::class, 'index']);
    Route::get('attributes/filter', [AttributeController::class, 'filter']);
    Route::post('attributes/create', [AttributeController::class, 'store']);
    Route::get('attributes/{id}', [AttributeController::class, 'show']);
    Route::put('attributes/{id}', [AttributeController::class, 'update']);
    Route::patch('attributes/{id}', [AttributeController::class, 'update']);
    Route::delete('attributes/{id}', [AttributeController::class, 'destroy']);

    Route::get('attribute_values', [AttributeValueController::class, 'index']);
    Route::post('attribute_values/create', [AttributeValueController::class, 'store']);
    Route::get('attribute_values/{id}', [AttributeValueController::class, 'show']);
    Route::put('attribute_values/{id}', [AttributeValueController::class, 'update']);
    Route::patch('attribute_values/{id}', [AttributeValueController::class, 'update']);
    Route::delete('attribute_values/{id}', [AttributeValueController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders/create', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::put('orders/{id}', [OrderController::class, 'update']);
    Route::patch('orders/{id}', [OrderController::class, 'update']);
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);

    Route::get('OrderProductQuantity', [OrderProductQuantity::class, 'index']);
    Route::post('OrderProductQuantity/create', [OrderProductQuantity::class, 'store']);
    Route::get('OrderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'show']);
    Route::put('OrderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'update']);
    Route::patch('OrderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'update']);
    Route::delete('OrderProductQuantity/{order_id}/{product_id}', [OrderProductQuantity::class, 'destroy']);

    Route::get('SelfHarvesting', [SelfHarvestingController::class, 'index']);
    Route::post('SelfHarvesting/create', [SelfHarvestingController::class, 'store']);
    Route::get('SelfHarvesting/{id}', [SelfHarvestingController::class, 'show']);
    Route::put('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update']);
    Route::patch('SelfHarvesting/{id}', [SelfHarvestingController::class, 'update']);
    Route::delete('SelfHarvesting/{id}', [SelfHarvestingController::class, 'destroy']);

    Route::get('Reviews', [ReviewController::class, 'index']);
    Route::post('Reviews/create', [ReviewController::class, 'store']);
    Route::get('Reviews/{id}', [ReviewController::class, 'show']);
    Route::put('Reviews/{id}', [ReviewController::class, 'update']);
    Route::patch('Reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('Reviews/{id}', [ReviewController::class, 'destroy']);

    Route::get('/csrf-token', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    });
    


});
