<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    //
    public function categorySubcategary(Request $request)
    {
        $productCategory=[
            "Ring"=>[
               "Couple Ring",
               "Corner Ring",
            ],
            
            ];
            return response()->json($productCategory);
    }

    public function PriceCategory(Request $request)
    {   $minPrice = DB::table('products')
        ->where('status', '!=', 'inactive') // Exclude inactive products
        ->whereNotNull('discount') // Ensure discount is not null
        ->where('discount', '!=', '') // Ensure discount is not empty
        ->selectRaw('MIN(CAST(discount AS SIGNED)) as min_discount') // Convert VARCHAR to integer
        ->value('min_discount'); 
        // If you want to return 0 when no discounts exist instead of null:
       $minPrice = $minPrice ?? 0;
       $maxPrice = DB::table('products')
    ->where('status', '!=', 'inactive') // Skip inactive products
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
}
