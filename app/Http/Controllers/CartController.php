<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\DiscountCoupon;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\ShippingCharges;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use function Symfony\Component\String\s;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $product = Product::with('product_images')->find($request->id);
        if ($product == null) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ]);
        }

        if (Cart::count() > 0) {
            $cartContent = Cart::content();
            $productAlreadyExist = false;

            foreach ($cartContent as $item) {
                if ($item->id == $product->id) {
                    $productAlreadyExist = true;
                }
            }

            if ($productAlreadyExist == false) {
                Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => (!empty
                ($product->product_images)) ? $product->product_images->first() : '']);

                $status = true;
                $message = '<strong>' . $product->title . '</strong> added in your cart successfully.';
                session()->flash('success', $message);
            } else {
                $status = false;
                $message = $product->title . ' already added in cart';
            }
        } else {
            //Cart is empty
            Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']);
            $status = true;
            $message = '<strong>' . $product->title . '</strong> added in your cart successfully.';
            session()->flash('success', $message);
        }
        return response()->json([
            'status' => $status,
            'message' => $message
        ]);
    }

    public function cart()
    {
        $cartContent = Cart::content();

        $data['cartContent'] = $cartContent;
        return view('front.cart', $data);
    }

    public function updateCart(Request $request)
    {
        $rowId = $request->rowId;
        $qty = $request->qty;

        $itemInfo = Cart::get($rowId);

        $product = Product::find($itemInfo->id);
        // check qty available in stock

        if ($product->track_qty == 'yes') {
            if ($qty <= $product->qty) {
                Cart::update($rowId, $qty);
                $message = 'Cart updated successfully';
                $status = true;
                session()->flash('success', $message);
            } else {
                $message = 'Request qty(' . $qty . ') not available in stock';
                $status = false;
                session()->flash('error', $message);
            }
        } else {
            Cart::update($rowId, $qty);
            $message = 'Cart updated successfully';
            $status = true;
        }
        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully'
        ]);
    }

    public function deleteItem(Request $request)
    {
        $itemInfo = Cart::get($request->rowId);
        if ($itemInfo == null) {
            $message = 'Item not found';
            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        Cart::remove($request->rowId);
        $message = 'Item deleted successfully';
        session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function checkout(Request $request)
    {
        $discount = 0;
        if (Cart::count() == 0) {
            return redirect()->route('front.cart');
        }

        if (Auth::check() == false) {
            if (!session()->has('url.intended')) {
                session(['url.intended' => url()->current()]);
            }
            return redirect()->route('account.login');
        }

        $customerAddress = CustomerAddress::where('user_id', Auth::id())->first();

        session()->forget('url.intended');
        $countries = Country::orderBy('name', 'asc')->get();

        $subTotal = Cart::subtotal(2, '.', '');


        //Apply Discount Here
        if (session()->has('code')) {
            $code = session()->get('code');

            if ($code->type == 'percent') {
                $discount = ($code->discount_amount / 100) * $subTotal;
            } else {
                $discount = $code->discount_amount;
            }
        }

        //Calculate shipping here
        if ($customerAddress != '') {
            $userCountry = $customerAddress->country_id;
            $shippingInfo = ShippingCharges::where('country_id', $userCountry)->first();

            $totalQty = 0;
            $totalShippingCharges = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }
            $totalShippingCharges = $totalQty * $shippingInfo->amount;

            $grandTotal = ($subTotal - $discount) + $totalShippingCharges;
        } else {
            $grandTotal = ($subTotal - $discount);
            $totalShippingCharges = 0;
        }

        return view('front.checkout', [
            'countries' => $countries,
            'customerAddress' => $customerAddress,
            'totalShippingCharges' => $totalShippingCharges,
            'discount' => $discount,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function processCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:5',
            'last_name' => 'required',
            'email' => 'required|email',
            'country' => 'required',
            'address' => 'required|min:3',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'mobile' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the errors',
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $user = Auth::user();

        CustomerAddress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'country_id' => $request->country,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
            ]
        );

        if ($request->payment_method == 'cod') {

            $discountCodeId = NULL;
            $promoCode = '';
            $shipping = 0;
            $discount = 0;
            $subTotal = Cart::subtotal(2, '.', '');

            //Apply Discount Here
            if (session()->has('code')) {
                $code = session()->get('code');

                if ($code->type == 'percent') {
                    $discount = ($code->discount_amount / 100) * $subTotal;
                } else {
                    $discount = $code->discount_amount;
                }

                $discountCodeId = $code->id;
                $promoCode = $code->code;
            }

            //Calculate Shipping

            $shippingInfo = ShippingCharges::where('country_id', $request->country)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if ($shippingInfo != null) {
                $shipping = $totalQty * $shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shipping;

            } else {
                $shippingInfo = ShippingCharges::where('country_id', 'reset_of_world')->first();

                $shipping = $totalQty * $shippingInfo->amount;
                $grandTotal = ($subTotal-$discount) + $shipping;


            }


            $order = new Order;
            $order->subtotal = $subTotal;
            $order->shipping = $shipping;
            $order->grand_total = $grandTotal;
            $order->discount = $discount;
            $order->coupon_code_id = $discountCodeId;
            $order->coupon_code = $promoCode;
            $order->payment_status = 'not paid';
            $order->status = 'pending';
            $order->user_id = $user->id;
            $order->first_name = $request->first_name;
            $order->last_name = $request->last_name;
            $order->email = $request->email;
            $order->mobile = $request->mobile;
            $order->address = $request->address;
            $order->apartment = $request->apartment;
            $order->state = $request->state;
            $order->city = $request->city;
            $order->zip = $request->zip;
            $order->notes = $request->notes;
            $order->country_id = $request->country;
            $order->save();

            foreach (Cart::content() as $item) {
                $orderItem = new OrderItem;
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->name = $item->name;
                $orderItem->qty = $item->qty;
                $orderItem->price = $item->price;
                $orderItem->total = $item->price * $item->qty;
                $orderItem->save();
            }

            orderEmail($order->id,'customer');

            session()->flash('success', 'You have successfully placed your order!');

            Cart::destroy();

            session()->forget('code');

            return response()->json([
                'message' => 'Order saved successfully',
                'orderId' => $order->id,
                'status' => true,
            ]);

        } else {

        }
    }

    public function thankyou($id)
    {
        return view('front.thanks', [
            'id' => $id
        ]);
    }

    public function getOrderSummary(Request $request)
    {
        $subTotal = Cart::subtotal(2, '.', '');
        $discount = 0;
        $discountString = '';
        //Apply Discount Here
        if (session()->has('code')) {
            $code = session()->get('code');

            if ($code->type == 'percent') {
                $discount = ($code->discount_amount / 100) * $subTotal;
            } else {
                $discount = $code->discount_amount;
            }
            $discountString = '<div class="mt-4" id="discount-response">
                                <strong>' . session()->get('code')->code . '</strong>
                                <a class="btn btn-sm btn-danger" id="remove-discount"><i class="fa fa-times"></i> </a>
                            </div>';
        }

        if ($request->country_id > 0) {
            $shippingInfo = ShippingCharges::where('country_id', $request->country_id)->first();
            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }
            if ($shippingInfo != null) {
                $shippingCharges = $totalQty * $shippingInfo->amount;
                $grandTotal = ($subTotal - $discount) + $shippingCharges;

                return response()->json([
                    'status' => true,
                    'grandTotal' => number_format($grandTotal, 2),
                    'discount' => number_format($discount, 2),
                    'discountString' => $discountString,
                    'shippingCharges' => number_format($shippingCharges, 2),
                ]);
            } else {
                $shippingInfo = ShippingCharges::where('country_id', 'reset_of_world')->first();

                $shippingCharges = $totalQty * $shippingInfo->amount;
                $grandTotal = ($subTotal - $discount) + $shippingCharges;

                return response()->json([
                    'status' => true,
                    'grandTotal' => number_format($grandTotal, 2),
                    'discount' => number_format($discount, 2),
                    'discountString' => $discountString,
                    'shippingCharges' => number_format($shippingCharges, 2),
                ]);
            }
        } else {
            return response()->json([
                'status' => true,
                'grandTotal' => number_format(($subTotal - $discount), 2, '.', ''),
                'discount' => number_format($discount, 2),
                'discountString' => $discountString,
                'shippingCharges' => number_format(0, 2),
            ]);
        }
    }

    public function applyDiscount(Request $request)
    {
        $code = DiscountCoupon::where('code', $request->code)->first();
//        dd($code);
        if ($code == null) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter a valid code',
            ]);
        }

        $now = Carbon::now();

        if ($code->starts_at != "") {
            $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $code->starts_at);

            if ($now->lt($startDate)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon',
                ]);
            }
        }

        if ($code->expires_at != "") {
            $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $code->expires_at);

            if ($now->gt($endDate)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon',
                ]);
            }
        }

        if($code->max_uses > 0){
            $couponUsed = Order::where('coupon_code_id', $code->id)->count();
            if($couponUsed > $code->max_uses){
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon',
                ]);
            }
        }

        if($code->max_uses_user > 0){
            $couponUsedByUser = Order::where(['coupon_code_id'=> $code->id, 'user_id'=>Auth::user()->id])->count();
            if($couponUsedByUser > $code->max_uses_user){
                return response()->json([
                    'status' => false,
                    'message' => 'You already used this coupon',
                ]);
            }
        }

        $subTotal = Cart::subtotal(2, '.', '');

        if($code->min_amount > 0){
            if($subTotal < $code->min_amount){
                return response()->json([
                    'status' => false,
                    'message' => 'Your min amount is ' . $code->min_amount,
                ]);
            }
        }

        session()->put('code', $code);

        return $this->getOrderSummary($request);
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('code');
        return $this->getOrderSummary($request);
    }
}
