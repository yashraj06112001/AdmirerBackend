<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\description;
class productListingController extends Controller
{
    //
    public function ShowProducts(Request $request)
    {
        $productCompleteDetails=Product::leftJoin("description","products.id","=","description.p_id")
        ->select("products.*","description.*")
        ->get()
        ->map(function ($item) {
            if (isset($item->description)) {
                $cleanText = strip_tags($item->description);          // Remove HTML tags
                $cleanText = html_entity_decode($cleanText);           // Decode HTML entities
                $cleanText = str_replace(["\r", "\n"], '', $cleanText); // Remove \r and \n
                $item->description = $cleanText;
            }
            return $item;
        });
        return response()->json(data: $productCompleteDetails);
    }

    public function productAfterFilterListing(Request $request)
    {
       $category=$request->category;
       $subCategory=$request->subCategory;
       $minPrice=$request->minPrice;
       $maxPrice=$request->maxPrice;
    }
}
