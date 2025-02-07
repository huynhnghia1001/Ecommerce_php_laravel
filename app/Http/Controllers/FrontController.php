<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index()
    {
        $products = Product::where('is_featured', 'yes')->where('status',1)->get();
        $latestProducts = Product::orderBy('id','asc')->where('status',1)->take(8)->get();
        $data['latestProducts'] = $latestProducts;
        $data['products'] = $products;
        return view('front.home',$data);
    }
}
