<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
class searchApiController extends Controller
{
    //
    public function search(Request $request)
    {$query = $request->input('q');

        // Fuzzy search
        $results = Product::search($query, function ($tnt, $query) {
            $tnt->fuzziness = true;
            return $tnt->search($query);
        })->get();
    
        // Return array of product objects
        $products = $results->map(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'product_code' => $product->product_code ?? null, // in case you want to include it
            ];
        });
    
        return response()->json($products);
    }
    
}
