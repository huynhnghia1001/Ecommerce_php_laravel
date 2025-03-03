<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){
        return view('front.account.register');
    }

    public function login(Request $request){
        return view('front.account.login');
    }

    public function processRegister(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:5|confirmed',
        ]);

        if ($validator->passes()) {

            $user = new User();
            $user->name = $request->input('name');
            $user->phone = $request->input('phone');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->save();

            session()->flash('success', 'You have been registered successfully');

            return response()->json([
                'status' => true,
            ]);
        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function authenticate(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:5',
        ]);

        if ($validator->passes()) {

            if (Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $request->input('remember'))) {

                if(session()->has('url.intended')){
                    return redirect(session()->get('url.intended'));
                }

                return redirect()->route('account.profile');
            }else{
//                session()->flash('error', 'Either email/password is incorrect');
                return redirect()->route('account.login')
                    ->withInput($request->only('email'))
                    ->with('error', 'Either email/password is incorrect');
            }
        }else{
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
    }

    public function profile(Request $request)
    {
        $data = [];
        $userId = Auth::id();
        $countries = Country::orderBy('name','ASC')->get();
        $user = User::where('id', Auth::id())->first();
        $address = CustomerAddress::where('user_id', $userId)->first();
        $data['address'] = $address;
        $data['user'] = $user;
        $data['countries'] = $countries;
        return view('front.account.profile', $data);
    }

    public function updateProfile(Request $request)
    {
        $userId = Auth::user()->id;
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required|email|unique:users,email,'.$userId.',id',
        ]);
        if ($validator->passes()) {
            $user = User::find($userId);
            $user->name = $request->input('name');
            $user->phone = $request->input('phone');
            $user->email = $request->input('email');
            $user->save();

             session()->flash('success', 'You have been updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function updateAddress(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:5',
            'last_name' => 'required',
            'email' => 'required|email',
            'country_id' => 'required',
            'address' => 'required|min:3',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'mobile' => 'required',
        ]);

        if ($validator->passes()) {

            CustomerAddress::updateOrCreate(
                ['user_id' => $userId],
                [
                    'user_id' => $userId,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'country_id' => $request->country_id,
                    'address' => $request->address,
                    'apartment' => $request->apartment,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip,
                ]
            );
            session()->flash('success', 'You have been updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('account.login')->with('success', 'You have been logged out');
    }

    public function orders()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để xem đơn hàng.');
        }
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->latest()
            ->select('id', 'created_at', 'status', 'grand_total')
            ->get();

//        $data['orders'] = $orders;
        return view('front.account.order', compact('orders'));
    }

    public function orderDetails($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $order = Order::where('user_id', Auth::id())
            ->with('items')
            ->findOrFail($id);

        $countOrder = $order->items->count();
        $orderDetails = $order->items;

        return view('front.account.order-details', compact('order', 'countOrder', 'orderDetails'));
    }

    public function wishlist()
    {
        $data = [];
        $wishlists = Wishlist::where('user_id', Auth::user()->id)->with('product')->get();
        $data['wishlists'] = $wishlists;
        return view('front.account.wishlist', $data);
    }

    public function wishlistDelete(Request $request){
        $wishlist = Wishlist::where('user_id', Auth::user()->id)->where('product_id',$request->id)->first();
        if($wishlist == null){
            session()->flash('error','Product doesn\'t exist');
            return response()->json([
                'status' => true,
            ]);
        }else{
            Wishlist::where('user_id', Auth::user()->id)->where('product_id', $request->id)->delete();
            session()->flash('success','Product deleted successfully');
            return response()->json([
                'status' => true,
            ]);
        }

    }
}
