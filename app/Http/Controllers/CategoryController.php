<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryRecourse;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        $attributes = $request->input('attributes_id', []); // Массив идентификаторов атрибутов
        $search = $request->input('name_like');         // Поисковый запрос
        $parentId = $request->input('parent_id');        // parentId

        $query = Category::query();

        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $attributeId) {
                $query->whereHas('attributes', function($q) use ($attributeId) {
                    $q->where('attributes.id', $attributeId);
                });
            }
        }

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (empty($attributes) && empty($search)) {
            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id'); // Корневые категории
            }
        }

        $categories = $query->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found', 'code' => 204], 204);
        }

        return new CategoryCollection($categories);


    }


    // public function filter(Request $request)
    // {
        
    // }


    public function store(Request $request)
    {
        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()-> json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if (Category::create($input)) {
            return response()->json(['message' => 'Category created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'Category not created',
                'code' => 500], 
                500);
        }
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found',
                'code' => 404],
                404);
        }

        return new CategoryRecourse($category);
    }

    public function update(Request $request)
    {
        $category = Category::find($request->id);

        if (!$category) {
            return response()->json(['message' => 'Category not found',
                'code' => 404],
                404);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        if ($category->update($input)) {
            return response()->json(['message' => 'Category updated',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Category not updated',
                'code' => 500],
                500);
        }
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found',
                'code' => 404],
                404);
        }

        if ($category->delete()) {
            return response()->json(['message' => 'Category deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Category not deleted',
                'code' => 500],
                500);
        }
    }

    private function validator_create(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['required', 'integer'],
            'status' => ['required', 'string|in:PROCESS,APPROVED,REJECTED' ],
            'is_final' => ['required', 'boolean'],
        ]);
    }
   
}
