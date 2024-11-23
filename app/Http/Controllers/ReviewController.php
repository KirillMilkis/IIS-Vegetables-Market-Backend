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
    /**
     * Display a listing of the review.
     *
     * @return ReviewCollection
     */
    public function index()
    {
        $reviews = Review::all();
        return response()->json($reviews);
    }

    /**
     * Get reviews that are related to a product.
     * 
     *
     * @param int $productId
     * @return ReviewCollection
     */
    public function getByProduct($productId)
    {
        $reviews = Review::where('product_id',$productId)->get();


        return new ReviewCollection($reviews);
    }

    /**
     * Get the average rating of a product.
     * 
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAverageRating($productId)
    {
        $averageRating = Review::where('product_id', $productId)->avg('rating');

        return response()->json([
            'product_id' => $productId,
            'average_rating' => round($averageRating, 2) // Round to 2 decimal places
        ]);
    }


    /**
     * Store a newly created review in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Display the specified review.
     *
     * @param int $id
     * @return ReviewResource
     */
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

    /**
     * Update the specified review in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified review from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Validate the input for creating a review.
     *
     * @param array $input
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create($input) {
        return Validator::make($input, [
            'username' => 'required|string',
            'product_id' => 'required|integer|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:255',
        ]);
    }

}
