<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ReviewCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;


class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::all();
        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        if(Review::create($input)) {
            return response()->json(['message' => 'Review created',
                'code' => 201],
                201);
        } else {
            return response()->json(['message' => 'Review not created',
                'code' => 500],
                500);
        }

    }

    public function show($id)
    {
        $review = Review::find($id);

        if(!$review) {
            return response()->json(['message' => 'Review not found',
                'code' => 404],
                404);
        }

        return new ReviewResource($review);
    }

    public function update(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
        $review = Review::find($input['id']);

        if(!$review) {
            return response()->json(['message' => 'Review not found',
                'code' => 404],
                404);
        }

        $validator = $this->validator_create($input);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        if($review->update($input)) {
            return response()->json(['message' => 'Review updated',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Review not updated',
                'code' => 500],
                500);
        }
    }

    public function destroy($id)
    {
        $review = Review::find($id);

        if(!$review) {
            return response()->json(['message' => 'Review not found',
                'code' => 404],
                404);
        }

        if($review->delete()) {
            return response()->json(['message' => 'Review deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Review not deleted',
                'code' => 500],
                500);
        }
    }

    private function validator_create($input) {
        return Validator::make($input, [
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:255',
        ]);
    }

}
