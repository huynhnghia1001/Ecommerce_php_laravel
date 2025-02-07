<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, $categorySlug = null, $subCategorySlug = null)
    {
        $categorySelected = null;
        $subCategorySelected = null;
        $brandsArray =[];


        $categories = Category::orderBy('name', 'asc')->with('sub_category')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $products= Product::where('status', 1);

        if(!empty($categorySlug)){
            $category = Category::where('slug', $categorySlug)->first();
            $products = $products->where('category_id', $category->id);
            $categorySelected = $category->id;
        }
        if(!empty($subCategorySlug)){
            $subCategory = SubCategory::where('slug', $subCategorySlug)->first();
            $products = $products->where('sub_category_id', $subCategory->id);
            $subCategorySelected = $subCategory->id;
        }


        if(!empty($request->get('brand'))){
            $brandsArray = explode(',',$request->get('brand'));
            $products = $products->whereIn('brand_id', $brandsArray);
        }

        if($request->get('price_max') != '' && $request->get('price_min') != ''){
            if($request->get('price_max') == 1000){
                $products = $products->whereBetween('price', [intval($request->get('price_min')), 100000]);
            }else{
                $products = $products->whereBetween('price', [intval($request->get('price_min')), [intval($request->get('price_max'))]]);
                $products = $products->whereBetween('price', [intval($request->get('price_min')),intval($request->get('price_max'))]);

            }
        }

        $sort = $request->get('sort');

        switch ($sort) {
            case 'price_asc':
                $products = $products->orderBy('price', 'asc');
                break;
            case 'latest':
            default:
                $products = $products->orderBy('id', 'desc');
                break;
        }


        $products = $products->paginate(6);
        $data['products'] = $products;
        $data['categories'] = $categories;
        $data['brands'] = $brands;
        $data['subCategorySelected'] = $subCategorySelected;
        $data['categorySelected'] = $categorySelected;
        $data['brandsArray'] = $brandsArray;
        $data['priceMax'] = (intval($request->get('price_max'))==0)? 1000 : intval($request->get('price_max'));
        $data['priceMin'] = intval($request->get('price_min'));
        $data['sort'] = $request->get('sort');


        return view('front.shop', $data);
    }

    public function product($slug)
    {
        $product = Product::where('slug', $slug)->with('product_images')->first();
        if($product == null){
            abort(404);
        }

        $relatedProducts = [];
        //fetch related products
        if($product->related_products != ''){
            $productArray = explode(',', $product->related_products);
            $relatedProducts = Product::whereIn('id', $productArray)->with('product_images')->get();
        }
        $data['product'] = $product;
        $data['relatedProducts'] = $relatedProducts;
        return view('front.product', $data);
    }
}
