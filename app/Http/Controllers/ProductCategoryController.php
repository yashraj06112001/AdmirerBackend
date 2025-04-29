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
    {  $subcatId = $request->query('subcatId');
        $query = DB::table('products')
        ->where('status', '!=', 'inactive')
        ->whereNotNull('discount')
        ->where('discount', '!=', '');

    // Add subcat_id condition if provided
    if (!empty($subcatId)) {
        $query->where('subcat_id', $subcatId);
    }

    // Clone the query to get min and max separately
    $minPrice = (clone $query)->selectRaw('MIN(CAST(discount AS SIGNED)) as min_discount')->value('min_discount') ?? 0;
    $maxPrice = (clone $query)->selectRaw('MAX(CAST(discount AS SIGNED)) as max_discount')->value('max_discount') ?? 0;

    $priceRange = [
        "minPrice" => $minPrice,
        "maxPrice" => $maxPrice
    ];

    return response()->json($priceRange);

    }



    public function categoryBasedSubcategory(Request $request)
    {
        $categoryID = $request->input('category');

        // Fetch the category by name and status active
        $category = DB::table('category')
            ->where('id', '=',$categoryID)
            ->where('status', 'Active')
            ->first();
    
        if (!$category) {
            return response()->json([
                'error' => 'Category not found or inactive',
                'category id'=>$categoryID,
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
