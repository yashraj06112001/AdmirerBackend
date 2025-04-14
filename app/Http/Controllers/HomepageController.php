<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function getHomepageData()
    {
        $basePath = asset('asset/image/banners');
    
        $banners = DB::table('slider')
            ->where('status', 'Active')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) use ($basePath) {

                $url = null;

                if ($item->click === 'yes' && !empty($item->subcat_id)) {
                    $catInfo = explode('_', $item->subcat_id); // cat_27 or subcat_10

                    if (count($catInfo) == 2 && is_numeric($catInfo[1])) {
                        if ($catInfo[0] === 'cat') {
                            $url = url('api/productListing?cat-' . $catInfo[1]);
                        } elseif ($catInfo[0] === 'subcat') {
                            $url = url('api/productListing?subcat-' . $catInfo[1]);
                        }
                    }
                }
    
                return [
                    'image'       => $basePath . '/' . $item->image,
                    'mobile_img'  => $basePath . '/' . $item->mobile_img,
                    'url'         => $url
                ];
            });
    
        return response()->json([
            'status' => 'success',
            'message' => 'Homepage data fetched successfully!',
            'data' => [
                'banners' => $banners
            ]
        ]);
    }
}
