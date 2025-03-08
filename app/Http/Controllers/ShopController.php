<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function index(Request $request, $categorySlug = null, $subCategorySlug = null)
    {
        $categorySelected = null;
        $subCategorySelected = null;
        $brandsArray = [];


        $categories = Category::orderBy('name', 'asc')->with('sub_category')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $products = Product::where('status', 1);

        if (!empty($categorySlug)) {
            $category = Category::where('slug', $categorySlug)->first();
            $products = $products->where('category_id', $category->id);
            $categorySelected = $category->id;
        }
        if (!empty($subCategorySlug)) {
            $subCategory = SubCategory::where('slug', $subCategorySlug)->first();
            $products = $products->where('sub_category_id', $subCategory->id);
            $subCategorySelected = $subCategory->id;
        }


        if (!empty($request->get('brand'))) {
            $brandsArray = explode(',', $request->get('brand'));
            $products = $products->whereIn('brand_id', $brandsArray);
        }

        if ($request->get('price_max') != '' && $request->get('price_min') != '') {
            if ($request->get('price_max') == 1000) {
                $products = $products->whereBetween('price', [intval($request->get('price_min')), 100000]);
            } else {
                $products = $products->whereBetween('price', [intval($request->get('price_min')), intval($request->get('price_max'))]);
            }
        }

        if (!empty($request->get('search'))) {
            $products = $products->where('title', 'like', '%' . $request->get('search') . '%');
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
        $data['priceMax'] = (intval($request->get('price_max')) == 0) ? 1000 : intval($request->get('price_max'));
        $data['priceMin'] = intval($request->get('price_min'));
        $data['sort'] = $request->get('sort');


        return view('front.shop', $data);
    }

    public function product($slug)
    {
        $product = Product::where('slug', $slug)->withCount('product_ratings')
                                                ->withSum('product_ratings', 'rating')
                                                ->with('product_images','product_ratings')->first();
        if ($product == null) {
            abort(404);
        }

        $relatedProducts = [];
        //fetch related products
        if ($product->related_products != '') {
            $productArray = explode(',', $product->related_products);
            $relatedProducts = Product::whereIn('id', $productArray)->where('status', 1)->with('product_images')->get();
        }
        $data['product'] = $product;
        $data['relatedProducts'] = $relatedProducts;

        $avgRating = '0.00';
        $avgRatingPer = 0;
        if($product->product_ratings_count > 0){
            $avgRating = number_format(($product->product_ratings_sum_rating / $product->product_ratings_count), 2);
            $avgRatingPer = ($avgRating*100)/5;
        }
        $data['avgRating'] = $avgRating;
        $data['avgRatingPer'] = $avgRatingPer;

        return view('front.product', $data);
    }

    public function saveRating($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5',
            'email' => 'required|email',
            'comment' => 'required|min:10',
            'rating' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $count = ProductRating::where('email',$request->email)->count();
        if($count > 0){
            session()->flash('error', 'You have already rated this product');
            return response()->json([
                'status' => true,
            ]);
        }

        $productRating = new ProductRating;
        $productRating->product_id = $id;
        $productRating->username = $request->name;
        $productRating->email = $request->email;
        $productRating->comment = $request->comment;
        $productRating->rating = $request->rating;
        $productRating->status = 0;
        $productRating->save();

        session()->flash('success', 'Thank you for your feedback!');

        return  response()->json([
            'status' => true,
            'message' => 'Thank you for your feedback!'
        ]);
    }
}
