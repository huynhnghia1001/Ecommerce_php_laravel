<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
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
        return view('front.account.profile');
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

}
