<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DiscountCodeController extends Controller
{
    public function index(Request $request)
    {
        $discountCoupons = DiscountCoupon::latest();

        if (!empty($request->get('keyword'))) {
            $discountCoupons = DiscountCoupon::where('name', 'LIKE', '%' . $request->get('keyword') . '%')
                ->orWhere('code', 'LIKE', '%' . $request->get('keyword') . '%');

        }
        $discountCoupons = $discountCoupons->paginate(10);


        return view('admin.coupon.list', compact('discountCoupons'));
    }

    public function create()
    {
        return view('admin.coupon.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'type' => 'required',
            'discount_amount' => 'required|numeric',
            'status' => 'required',
        ]);

        if (!empty($request->starts_at)) {
            $now = Carbon::now();
            $startsAt = Carbon::createFromFormat('Y-m-d H:i:s', $request->starts_at);

            if ($startsAt->lte($now) == true) {
                return response()->json([
                    'status' => false,
                    'errors' => ['starts_at' => 'Start date can not be less than current date time']
                ]);
            }
        }

        if (!empty($request->starts_at) && !empty($request->expires_at)) {
            $startsAt = Carbon::createFromFormat('Y-m-d H:i:s', $request->starts_at);
            $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s', $request->expires_at);


            if ($expiresAt->gt($startsAt) == false) {
                return response()->json([
                    'status' => false,
                    'errors' => ['expires_at' => 'Expires date must be greater than start date']
                ]);
            }
        }

        if ($validator->passes()) {
            $discountCode = new DiscountCoupon();
            $discountCode->code = $request->code;
            $discountCode->name = $request->name;
            $discountCode->description = $request->description;
            $discountCode->max_uses = $request->max_uses;
            $discountCode->max_uses_user = $request->max_uses_user;
            $discountCode->type = $request->type;
            $discountCode->discount_amount = $request->discount_amount;
            $discountCode->min_amount = $request->min_amount;
            $discountCode->status = $request->status ?? 1;
            $discountCode->starts_at = $request->starts_at;
            $discountCode->expires_at = $request->expires_at;

            $discountCode->save();

            $message = 'Discount coupon Added Successfully.';
            session()->flash('success', $message);

            return response()->json([
                'status' => true,
                'message' => 'Discount coupon Added Successfully',
            ]);
        } else {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
    }

    public function edit($id)
    {
        $discountCoupon = DiscountCoupon::find($id);
        if (empty($discountCoupon)) {
            session()->flash('error', 'Discount coupon not found.');
            return redirect()->route('admin.coupon.list');
        }
        return view('admin.coupon.edit', compact('discountCoupon'));
    }

    public function update(Request $request, $id)
    {

        $discountCode = DiscountCoupon::find($id);
        if (empty($discountCode)) {
            session()->flash('error', 'Discount coupon not found.');
            return response()->json([
                'status' => true
            ]);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'type' => 'required',
            'discount_amount' => 'required|numeric',
            'status' => 'required',
        ]);


        if (!empty($request->starts_at) && !empty($request->expires_at)) {
            $startsAt = Carbon::createFromFormat('Y-m-d H:i:s', $request->starts_at);
            $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s', $request->expires_at);


            if ($expiresAt->gt($startsAt) == false) {
                return response()->json([
                    'status' => false,
                    'errors' => ['expires_at' => 'Expires date must be greater than start date']
                ]);
            }
        }

        if ($validator->passes()) {
            $discountCode->code = $request->code;
            $discountCode->name = $request->name;
            $discountCode->description = $request->description;
            $discountCode->max_uses = $request->max_uses;
            $discountCode->max_uses_user = $request->max_uses_user;
            $discountCode->type = $request->type;
            $discountCode->discount_amount = $request->discount_amount;
            $discountCode->min_amount = $request->min_amount;
            $discountCode->status = $request->status ?? 1;
            $discountCode->starts_at = $request->starts_at;
            $discountCode->expires_at = $request->expires_at;

            $discountCode->save();

            $message = 'Discount coupon Added Successfully.';
            session()->flash('success', $message);

            return response()->json([
                'status' => true,
                'message' => 'Discount coupon Added Successfully',
            ]);
        } else {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
    }

    public function destroy($id)
    {
        $discountCode = DiscountCoupon::find($id);
        if (empty($discountCode)) {
            session()->flash('error', 'Discount coupon not found.');
            return response()->json([
                'status' => true
            ]);
        }
        $discountCode->delete();
        $message = 'Discount coupon Deleted Successfully.';
        session()->flash('success', $message);
        return response()->json([
            'status' => true,
        ]);
    }
}
