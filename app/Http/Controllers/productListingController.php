<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\description;
use Illuminate\Support\Facades\DB;
class productListingController extends Controller
{
    //
    public function ShowProducts($cat = null, $subcat = null)
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
       if($cat)
       {
        $productCompleteDetails = $productCompleteDetails->filter(function ($item) use ($cat) {
            return $item->cat_id == $cat;
        });
       }
       if($subcat)
       {
        $productCompleteDetails = $productCompleteDetails->filter(function ($item) use ($subcat) {
            return $item->subcat_id == $subcat;
        });
       }
       $productCompleteDetails = $productCompleteDetails->values();


        return response()->json(data: $productCompleteDetails);
    }

    public function productAfterFilterListing(Request $request)
    {
        $category = $request->category;
        $subCategory = $request->subCategory;
        $minPrice = $request->minPrice;
        $maxPrice = $request->maxPrice;
        
        // Get Category ID
        $catId = DB::table('category')
            ->where("id",'=',$category)
            ->where('status', 'Active')
            ->value('id'); // use value() for single value
        
        // Initialize subCatId to null
        $subCatId = null;
        if ($subCategory) {
            $subCatId = DB::table("subcategory")
                ->where('id', '=',$subCategory)
                ->where('status', 'Active')
                ->value('id'); // use value() instead of get()
        }
        
        // Build the product query
        $productQuery = Product::leftJoin("description", "products.id", "=", "description.p_id")
        ->leftJoin('subcategory','subcategory.id','=','products.subcat_id')
        ->leftJoin('image as img','img.p_id','=','products.product_code')
        ->select("products.product_name","products.discount","products.price","products.cat_id","subcategory.sub_cat_name","products.id","products.subcat_id", "description.description",DB::raw('MIN(img.image) as image'))
        ->whereRaw('CAST(products.discount AS DECIMAL(10,2)) >= ?', [$minPrice])
        ->whereRaw('CAST(products.discount AS DECIMAL(10,2)) <= ?', [$maxPrice])
        ->where('products.cat_id', '=',$catId)
        ->where('products.status','=','Active')
        ->groupBy(
            "products.product_name",
            "products.discount",
            "products.price",
            "products.cat_id",
            "subcategory.sub_cat_name",
            "products.id",
            "products.subcat_id",
            "description.description"
        );
        
        // Conditionally add subcategory filter
        if ($subCatId) {
            $productQuery->where('products.subcat_id','=', $subCatId);
        }
        
        $productCompleteDetails = $productQuery->get()->map(function ($item) {
            if (isset($item->description)) {
                $cleanText = strip_tags($item->description);
                $cleanText = html_entity_decode($cleanText);
                $cleanText = str_replace(["\r", "\n"], '', $cleanText);
                $item->description = $cleanText;
            }
            return $item;
        });
        
        return response()->json($productCompleteDetails);
    }


    public function getSubcatName(Request $request)
    {
        $subcatId=$request->subcatId;
        $catId=DB::table('subcategory')
        ->where('subcategory.id','=',$subcatId)
        ->value('subcategory.cat_id',);
        $subcatName=DB::table('subcategory')
        ->where('subcategory.id','=',$subcatId)
        ->value('subcategory.sub_cat_name',);
        return response()->json(['subcatName' => $subcatName,
    "catId"=>$catId]);
    }
}
