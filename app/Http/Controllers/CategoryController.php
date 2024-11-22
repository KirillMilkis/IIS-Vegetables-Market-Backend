<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryRecourse;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth; 
use App\Models\User; 
use Illuminate\Support\Facades\Log;
use App\Models\CategoryAttribute; 

class CategoryController extends Controller
{

    public function index()
    {
        $categories = Category::all();

        return new CategoryCollection($categories);
    }

    public function filter(Request $request)
    {
        $attributes = $request->input('attributes_id', []); // Массив идентификаторов атрибутов
        $search = $request->input('name_like');         // Поисковый запрос
        $parentId = $request->input('parent_id');        // parentId

        $query = Category::query();
        $query->where('status', 'APPROVED');

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

    public function getToApprove()
    {
        
        $user_initator = Auth::user();
        if($user_initator['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $categories = Category::where('status', 'PROCESS')->get();

        // Check if no categories were found
        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories pending approval', 'code' => 204], 204);
        }
    
        // Return the categories in a structured response (optional: use a resource/collection if needed)

        $categories->transform(function ($category) {
           
            $category->parent_name = $category->parent ? $category->parent->name : null;
            return $category;
        });

        return new CategoryCollection($categories);

    }

    public function approveCategory($categoryId){

        $user_initator = Auth::user();
        if($user_initator['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $category = Category::find($categoryId);

        if (!$category){
            return response()->json(['message' => 'Category not found'], 404);
        }

        if ($category->status == 'PROCESS'){
            $category->status = 'APPROVED';
        } else{
            return response()->json(['message' => 'Category cannot be approved'], 422);
        }

        $category->save();


    }

    public function rejectCategory($categoryId){

        $user_initator = Auth::user();
        if($user_initator['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $category = Category::find($categoryId);

        if (!$category){
            return response()->json(['message' => 'Category not found'], 404);
        }

        if ($category->status == 'PROCESS'){
            $category->status = 'REJECTED';
        } else{
            return response()->json(['message' => 'Category cannot be approved'], 422);
        }

        $category->save();


    }

    public function store(Request $request)
    {

        $user_initiator = Auth::user();

        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);


        if ($validator->fails()) {
            return response()-> json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400], 400);
        }

        if ($input['parent_id'] != null){
            $parent = Category::find($input['parent_id']);

            if (!$parent){
                return response()->json(['message' => 'Parent category not found'], 404);
            }

            $category = Category::create($input);
            if(!$category){
                return response()->json(['message' => 'Category not created',
                'code' => 500], 
                500);
            }

            if($parent->isFinal == 'true'){
                $parent->update([ 'is_final' => false ]);
                $category->setAttribute('is_final', true);
            } else{
                $category->setAttribute('is_final', false);
            }
            
        } else {

            $category = Category::create($input);
            $category->is_final = false;

            if(!$category){
                return response()->json(['message' => 'Category not created',
                'code' => 500], 
                500);
            }
        }

        switch ($user_initiator['role']) {
            case 'moderator':
                $category->status = 'APPROVED';
                break;
            case 'reg_user':
                $category->status = 'PROCESS';
                break;
            default:
                return response()->json(['message' => 'Cannot create category with your role'], 403);
        }

        $category->save(); 

        
        if ($request->input('attributes')) {
            $attributesData = $request->input('attributes');
        
            // Проверяем, что каждый атрибут содержит необходимые данные
            foreach ($attributesData as $attribute) {
                if (!isset($attribute['id'])) {
                    return response()->json(['message' => 'Each attribute must have an id', 'code' => 400], 400);
                }
            }
        
            // Удаляем существующие связи для текущей категории, если требуется
            CategoryAttribute::where('category_id', $category->id)->delete();
        
            // Создаем новые записи в `category_attributes`
            foreach ($attributesData as $attribute) {
                CategoryAttribute::create([
                    'category_id' => $category->id,
                    'attribute_id' => $attribute['id'],
                    'is_required' => $attribute['required'] ?? false, // Значение по умолчанию — false
                ]);
            }
        }
        

        $category->save(); 


        return response()->json(['message' => 'Category created',
            'code' => 201], 
            201);

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
        $category = Category::find($request->route('id'));
        $userAuth = Auth::user();
    
        Log::info('Full request:', $request->all());


        // Проверяем, что пользователь аутентифицирован
        if (!$userAuth) {
            return response()->json(['message' => 'User not authenticated', 'code' => 401], 401);
        }
    
        // Проверяем роль пользователя
        if ($userAuth['role'] != 'moderator') {
            return response()->json(['message' => 'You dont have access with your role', 'code' => 403], 403);
        }

        if (!$category) {
            return response()->json(['message' => 'Category not found',
                'code' => 404],
                404);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_update($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        CategoryAttribute::where('category_id', $category->id)->delete();
        if ($category->update($input)) {
            // Если были переданы атрибуты для синхронизации
            if ($request->input('attributes')) {
                $attributesData = $request->input('attributes');
                // Проверяем, что каждый атрибут содержит необходимые данные
                foreach ($attributesData as $attribute) {
                    Log::info('Popal v 1 ');
                    if (!isset($attribute['id'])) {
                        return response()->json(['message' => 'Each attribute must have an id', 'code' => 400], 400);
                    }
                }
            
                // Удаляем существующие связи для текущей категории, если требуется
            
                // Создаем новые записи в `category_attributes`
                foreach ($attributesData as $attribute) {
                    Log::info('Popal v 2 ');
                    CategoryAttribute::create([
                        'category_id' => $category->id,
                        'attribute_id' => $attribute['id'],
                        'is_required' => $attribute['required'] ?? false, // Значение по умолчанию — false
                    ]);
                }
            }
            

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
        $user_initiator = Auth::user();
        if($user_initiator['role'] != 'moderator'){
            return response()->json(['message' => 'Cannot delete category with your role'], 403);
        }

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

    public function getDescendantCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $childCategories = Category::where('parent_id', $categoryId)->get();

        foreach ($childCategories as $childCategory) {
            $categoryIds = array_merge($categoryIds, $this->getDescendantCategoryIds($childCategory->id));
        }

        return $categoryIds;
    }

    public function getParentsCategoryIds($categoryId)
    {
        $categoryIds = [];
        $category = Category::find($categoryId);

        while ($category) {
            $categoryIds[] = $category->id;
            $category = $category->parent_id ? Category::find($category->parent_id) : null;
        }

        return $categoryIds;
    }

    private function validator_create(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'exists:categories,id', 'integer'],
            'status' => ['string','in:PROCESS,APPROVED,REJECTED'],
            'is_final' => ['boolean'],
        ]);
    }

    private function validator_update(array $data)
    {
        return Validator::make($data, [
            'name' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'exists:categories,id', 'integer'],
            'status' => ['nullable','string','in:PROCESS,APPROVED,REJECTED'],
            'is_final' => ['nullable','boolean'],
        ]);
    }
   
   
}
