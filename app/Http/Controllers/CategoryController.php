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
    /**
     * Display a listing of the categories.
     *
     * @return CategoryCollection
     */
    public function index()
    {
        $categories = Category::all();

        return new CategoryCollection($categories);
    }

    /**
     * Filter categories by attributes and search query
     * There is an option to filter by name and attributes among all categories or 
     * filter just by parent id, and get child categories or root categories if parent_id is not specified
     * 
     * @param Request $request
     * @return CategoryCollection
     */
    public function filter(Request $request)
    {
        $attributes = $request->input('attributes_id', []); // array of attribute ids
        $search = $request->input('name_like');         // search query
        $parentId = $request->input('parent_id');        // parent category id

        $query = Category::query();
        $query->where('status', 'APPROVED');

        // if attributes are specified, we filter by them
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $attributeId) {
                $query->whereHas('attributes', function($q) use ($attributeId) {
                    $q->where('attributes.id', $attributeId);
                });
            }
        }
        // if search query is specified, we filter by it
        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        // if search query and attributes are not specified, we filter by parent_id, or get root categories
        if (empty($attributes) && empty($search)) {
            if ($parentId !== null) {
                $query->where('parent_id', $parentId); // get child categories
            } else {
                $query->whereNull('parent_id'); // get root categories
            }
        }

        
        $categories = $query->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found', 'code' => 204], 204);
        }

        return new CategoryCollection($categories);


    }

    /**
     * Get categories that users proposed for moderator approval
     * 
     * @return CategoryCollection
     */
    public function getToApprove()
    {
        
        $authUser = Auth::user();
        if($authUser['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $categories = Category::where('status', 'PROCESS')->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories pending approval', 'code' => 204], 204);
        }
    
        // For convinience, we add the parent_name field to each category
        // Thats for clarity for the moderator, he will see in which category the new proposed category is located

        $categories->transform(function ($category) {
           
            $category->parent_name = $category->parent ? $category->parent->name : null;
            return $category;
        });

        return new CategoryCollection($categories);

    }

    /**
     * Approve category by moderator
     * 
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveCategory($categoryId){

        // Check if the user is a moderator. Because only a moderator can approve categories
        $authUser = Auth::user();
        if($authUser['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $category = Category::find($categoryId);

        if (!$category){
            return response()->json(['message' => 'Category not found'], 404);
        }

        // If the category is in the process of approval, then we can approve it by changing the status to APPROVED
        if ($category->status == 'PROCESS'){
            $category->status = 'APPROVED';
        } else{
            return response()->json(['message' => 'Category cannot be approved'], 422);
        }

        $category->save();


    }

    /**
     * Reject category by moderator
     * 
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectCategory($categoryId){

        // Check if the user is a moderator. Because only a moderator can reject categories
        $authUser = Auth::user();
        if($authUser['role'] != 'moderator'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $category = Category::find($categoryId);

        if (!$category){
            return response()->json(['message' => 'Category not found'], 404);
        }

        // If the category is in the process of approval, then we can reject it by changing the status to REJECTED
        if ($category->status == 'PROCESS'){
            $category->status = 'REJECTED';
        } else{
            return response()->json(['message' => 'Category cannot be approved'], 422);
        }

        $category->save();

    }

    /**
     * Create a new category. 
     * If it is by a moderator, then the category will be immediately approved
     * If it is by a regular user, then the category will be in the process of approval
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $authUser = Auth::user();

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

        // Case when category to create is gonna be a child category
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

            // If the parent category is final, then the child category will also be final
            // Parent category will be changed to non-final
            if($parent->isFinal == 'true'){
                $parent->update([ 'is_final' => false ]);
                $category->setAttribute('is_final', true);
            } else{
                $category->setAttribute('is_final', false);
            }
            
        } else {
            // Case when category to create is gonna be a root category
            $category = Category::create($input);
            // Root category is always non-final
            $category->is_final = false;

            if(!$category){
                return response()->json(['message' => 'Category not created',
                'code' => 500], 
                500);
            }
        }

        // Set the status of the category depending on the role of the user
        switch ($authUser['role']) {
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

        // If in request there are attributes, then we create a record in the category_attributes table
        if ($request->input('attributes')) {
            $attributesData = $request->input('attributes');
        
            // Check that each attribute contains the necessary data
            foreach ($attributesData as $attribute) {
                if (!isset($attribute['id'])) {
                    return response()->json(['message' => 'Each attribute must have an id', 'code' => 400], 400);
                }
            }
        
        
            // Create new records in `category_attributes`. This is a many-to-many relationship between categories and attributes.
            // It also contains the is_required field.
            foreach ($attributesData as $attribute) {
                CategoryAttribute::create([
                    'category_id' => $category->id,
                    'attribute_id' => $attribute['id'],
                    'is_required' => $attribute['required'] ?? false, 
                ]);
            }
        }
        

        $category->save(); 

        return response()->json(['message' => 'Category created',
            'code' => 201], 
            201);

    }

    /**
     * Display the specified category.
     *
     * @param int $id
     * @return CategoryResource
     */
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

    /**
     * Update the specified category in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $category = Category::find($request->route('id'));
        $userAuth = Auth::user();

    
        // Check if the user is a moderator. Because only a moderator can update categories
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

        // Delete all categories from the category_attributes table. 
        // Every time that someone updates a category, we delete all the attributes and then add them again.
        // In request should be new and old attributes
        CategoryAttribute::where('category_id', $category->id)->delete();
        if ($category->update($input)) {
            if ($request->input('attributes')) {
                $attributesData = $request->input('attributes');
                
                // Check that each attribute contains the necessary data
                foreach ($attributesData as $attribute) {

                    if (!isset($attribute['id'])) {
                        return response()->json(['message' => 'Each attribute must have an id', 'code' => 400], 400);
                    }
                }

                // Create new records in `category_attributes`. This is a many-to-many relationship between categories and attributes.
                foreach ($attributesData as $attribute) {
                    CategoryAttribute::create([
                        'category_id' => $category->id,
                        'attribute_id' => $attribute['id'],
                        'is_required' => $attribute['required'] ?? false, // Default is required value is false
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

    /**
     * Remove the specified category from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Check if the user is a moderator. Because only a moderator can delete categories
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

    /**
     * Get all descendant category ids. 
     *
     * @param int $categoryId
     * @return array
     */
    public function getDescendantCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $childCategories = Category::where('parent_id', $categoryId)->get();

        // Recursive call for each child category
        foreach ($childCategories as $childCategory) {
            $categoryIds = array_merge($categoryIds, $this->getDescendantCategoryIds($childCategory->id));
        }

        return $categoryIds;
    }

    /**
     * Get all parent category ids. 
     *
     * @param int $categoryId
     * @return array
     */
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

    /**
     * Validate the request data for creating a new category.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'exists:categories,id', 'integer'],
            'status' => ['string','in:PROCESS,APPROVED,REJECTED'],
            'is_final' => ['boolean'],
        ]);
    }

    /**
     * Validate the request data for updating a category.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
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
