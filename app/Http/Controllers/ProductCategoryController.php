<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    //
    public function categorySubcategary(Request $request)
    {   // table name category, subcategory
        $categories = DB::table('category')
        ->where('status', 'Active')
        ->get();

    $productCategory = [];

    foreach ($categories as $category) {
        // Fetch active subcategories for this category
        $subcategories = DB::table('subcategory')
            ->where('status', 'Active')
            ->where('cat_id', $category->id)
            ->pluck('sub_cat_name')
            ->toArray();

        // Add to the final array
        $productCategory[$category->cat_name] = $subcategories;
    }

    return response()->json($productCategory);
    }

    public function PriceCategory(Request $request)
    {   $minPrice = DB::table('products')
        ->where('status', '!=', 'inActive') // Exclude inactive products
        ->whereNotNull('discount') // Ensure discount is not null
        ->where('discount', '!=', '') // Ensure discount is not empty
        ->selectRaw('MIN(CAST(discount AS SIGNED)) as min_discount') // Convert VARCHAR to integer
        ->value('min_discount'); 
        // If you want to return 0 when no discounts exist instead of null:
       $minPrice = $minPrice ?? 0;
       $maxPrice = DB::table('products')
    ->where('status', '!=', 'inActive') // Skip inactive products
    ->whereNotNull('discount') // Ensure discount is not NULL
    ->where('discount', '!=', '') // Skip empty strings
    ->selectRaw('MAX(CAST(discount AS SIGNED)) as max_discount') // Convert VARCHAR → INT & get MAX
    ->value('max_discount'); // Retrieve the result directly

// If no valid discounts found, default to 0 (optional)
    $maxPrice = $maxPrice ?? 0;

        $priceRange=[
        "minPrice"=>$minPrice,
        "maxPrice" => $maxPrice
        ];
        return response()->json($priceRange);
    }



    public function categoryBasedSubcategory(Request $request)
    {
        $categoryName = $request->input('category');

        // Fetch the category by name and status active
        $category = DB::table('category')
            ->where('cat_name', $categoryName)
            ->where('status', 'Active')
            ->first();
    
        if (!$category) {
            return response()->json([
                'error' => 'Category not found or inactive',
                'category name'=>$categoryName,
                'request'=>$request
            ], 404);
        }
    
        // Fetch active subcategories for this category
        $subcategories = DB::table('subcategory')
            ->where('status', 'Active')
            ->where('cat_id', $category->id)
            ->pluck('sub_cat_name')
            ->toArray();
    
        $response = [
            $category->cat_name => $subcategories
        ];
    
        return response()->json($response);
    }
}
