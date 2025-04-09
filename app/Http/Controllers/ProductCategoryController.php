<?php

namespace App\Http\Controllers;

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
    {
        $priceRange=[
        [0,999],
        [1000,1999],
        [2000,2999],
        [3000,3299],
        [3300]
      
        ];
        return response()->json($priceRange);
    }
}
