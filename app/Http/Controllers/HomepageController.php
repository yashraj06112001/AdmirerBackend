<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function getHomepageData()
    {

        $basePath = 'https://admirer.in/asset/image/banners';

        // Banners (slider table)
        $banners = DB::table('slider')
            ->where('status', 'Active')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) use ($basePath) {
                $url = null;

                if ($item->click === 'yes' && !empty($item->subcat_id)) {
                    $catInfo = explode('_', $item->subcat_id);

                    if (count($catInfo) == 2 && is_numeric($catInfo[1])) {
                        if ($catInfo[0] === 'cat') {
                            $url = ('cat-' . $catInfo[1]);
                        } elseif ($catInfo[0] === 'subcat') {
                            $url = ('subcat-' . $catInfo[1]);
                        }
                    }
                }

                return [
                    'image'      => $basePath . '/' . $item->image,
                    'mobile_img' => $basePath . '/' . $item->mobile_img,
                    'url'        => $url
                ];
            });

            $category_subcategory = DB::table('category as c')
                ->select('c.id as cat_id', 'c.cat_name as title')
                ->join('products as p', 'c.id', '=', 'p.cat_id')
                ->where('c.status', 'Active')
                ->distinct()
                ->get()
                ->flatMap(function ($cat) {
                    $title = $cat->title;
                    $imageName = strtolower(str_replace(' ', '_', $title)); // slug banaya
                    $extensions = ['jpg', 'jpeg', 'png'];
                    $image = null;

                    // Category image from subcategory folder
                    foreach ($extensions as $ext) {
                        $path = "https://admirer.in/asset/image/subcategory/{$imageName}.{$ext}";
                        $image = $path;
                        break;
                    }

                    // Add category entry
                    $items = [[
                        'title' => $cat->title,
                        'image' => $image,
                        'url'   => 'cat-' . $cat->cat_id
                    ]];

                    // Add subcategories directly below the category (same level)
                    $subcategories = DB::table('subcategory as sub')
                        ->select('sub.id as subcat_id', 'sub.sub_cat_name as title')
                        ->join('products as p', 'sub.id', '=', 'p.subcat_id')
                        ->where('sub.cat_id', $cat->cat_id)
                        ->where('sub.status', 'Active')
                        ->where('sub.id', '!=', 10)
                        ->distinct()
                        ->get()
                        ->map(function ($sub) {
                            $title = $sub->title;
                            $imageName = strtolower(str_replace(' ', '_', $title));
                            $extensions = ['jpg', 'jpeg', 'png'];
                            $image = null;

                            foreach ($extensions as $ext) {
                                $path = "https://admirer.in/asset/image/subcategory/{$imageName}.{$ext}";
                                $image = $path;
                                break;
                            }

                            return [
                                'title' => $title,
                                'image' => $image,
                                'url'   => 'subcat-' . $sub->subcat_id
                            ];
                        });

                    return collect($items)->merge($subcategories);
                })->values();
       

        $offer_basePath = 'https://admirer.in/asset/image/offer';

        // Offer Sliders (offer_slider table)
        $offers = DB::table('offer_slider')
            ->where('status', 'Active')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) use ($offer_basePath) {
                $url = null;

                if ($item->click === 'yes' && !empty($item->cat_id)) {
                    $catInfo = explode('_', $item->cat_id);

                    if (count($catInfo) == 2 && is_numeric($catInfo[1])) {
                        if ($catInfo[0] === 'cat') {
                            $url = ('cat-' . $catInfo[1]);
                        } elseif ($catInfo[0] === 'subcat') {
                            $url = ('subcat-' . $catInfo[1]);
                        }
                    }
                }

                return [
                    'image' => $offer_basePath . '/' . $item->image,
                    'url'   => $url
                ];
            });
        
        $header_basePath = 'https://admirer.in/asset/image/header';

        $advertisements = DB::table('headerimage')
            ->where('status', 'Active')
            ->orderBy('id', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($item) use ($header_basePath) {
                $url = null;

                if (strtolower($item->click) === 'yes' && !empty($item->cat_type_id)) {
                    $catInfo = explode('_', $item->cat_type_id);

                    if (count($catInfo) == 2 && is_numeric($catInfo[1])) {
                        if ($catInfo[0] === 'cat') {
                            $url = ('cat-' . $catInfo[1]);
                        } elseif ($catInfo[0] === 'subcat') {
                            $url = ('subcat-' . $catInfo[1]);
                        }
                    }
                }

                return [
                    'image' => $header_basePath . '/' . $item->header,
                    'url'   => $url
                ];
        });    

        $bottom_banners = DB::table('headerimage')
            ->where('status', 'Active')
            ->orderBy('id', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($item) use ($header_basePath) {
                $url = null;
        
                if (strtolower($item->click) === 'yes' && !empty($item->cat_type_id)) {
                    $catInfo = explode('_', $item->cat_type_id);
        
                    if (count($catInfo) == 2 && is_numeric($catInfo[1])) {
                        if ($catInfo[0] === 'cat') {
                            $url = 'cat-' . $catInfo[1];
                        } elseif ($catInfo[0] === 'subcat') {
                            $url = 'subcat-' . $catInfo[1];
                        }
                    }
                }
        
                return [
                    'image' => $header_basePath . '/' . $item->header,
                    'url'   => $url
                ];
            });
    
        $mobile_banner = $bottom_banners[0] ?? null;
        $desktop_banner = $bottom_banners[1] ?? null;    

      return response()->json([
            'status' => 'success',
            'message' => 'Homepage data fetched successfully!',
            'data' => [
                'banners' => $banners,
                'category_subcategory' => $category_subcategory,
                'offers_slider' => $offers,
                'advertisement' => $advertisements,
                'bottom_banner' => [
                    'mobile_banner' => $mobile_banner,
                    'desktop_banner' => $desktop_banner,
                ]
            ]
        ]);
    
    }


}
