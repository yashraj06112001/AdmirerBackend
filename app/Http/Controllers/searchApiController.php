<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
class searchApiController extends Controller
{
    //
    public function search(Request $request)
    {
        $query = $request->input('q');

        // Fuzzy search
        $results = Product::search($query, function ($tnt, $query) {
            $tnt->fuzziness = true;
            return $tnt->search($query);
        })->get();

        // Return only product names
        $productNames = $results->pluck('product_name');

        return response()->json($productNames);
    }
    
}
