<?php

namespace App\Http\Controllers;

use App\Mail\ContactEmail;
use App\Models\Pages;
use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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

    public function addToWishlist( Request $request){
        if(Auth::check()==false){

            session(['url.intended' => url()->previous()]);

            return response()->json([
                'status' => false,
            ]);
        }

        $product = Product::where('id', $request->id)->first();

        if($product == null){
            return response()->json([
                'status' => false,
                'message' => '<div class="alert alert-danger">Product not found</div>',
            ]);
        }

        Wishlist::updateOrCreate([
            'user_id' => Auth::user()->id,
            'product_id' => $request->id,
        ],
        [
            'user_id' => Auth::user()->id,
            'product_id' => $request->id,
        ]
        );
//        $wishlist = new Wishlist;
//        $wishlist->user_id = Auth::user()->id;
//        $wishlist->product_id = $request->id;
//        $wishlist->save();

        return response()->json([
            'status' => true,
            'message' => '<div class="alert alert-success"><strong>"'.$product->title.'"</strong> added in your wishlist</div>',
        ]);

    }

    public function page($slug)
    {
        $page = Pages::where('slug', $slug)->first();
        if($page == null){
            return redirect()->route('front.home');
        }
        return view('front.page',compact('page'));
    }

    public function sendContactEmail()
    {
        $validated = Validator::make(request()->all(),[
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if($validated->passes()){
            //send email here
            $mailData = [
                'name' => request()->name,
                'email' => request()->email,
                'subject' => request()->subject,
                'message' => request()->message,
                'mail_subject' => 'You have received a contact email'
            ];

            $admin = User::where('id', '1')->first();

            Mail::to($admin->email)->send(new ContactEmail($mailData));

            session()->flash('success', 'Thanks for contacting us, we will get back to you soon.');

            return response()->json([
                'status' => true,
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validated->errors(),
            ]);
        }
    }
}
