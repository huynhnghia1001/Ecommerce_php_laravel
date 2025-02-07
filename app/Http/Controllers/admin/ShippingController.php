<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShippingCharges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function create()
    {
        $country = Country::all();
        $data['countries'] = $country;

        $shippingCharges = ShippingCharges::select('shipping_charges.*', 'countries.name')
        ->leftJoin('countries', 'countries.id', '=', 'shipping_charges.country_id')->get();

        $data['shippingCharges'] = $shippingCharges;

        return view('admin.shipping.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required|numeric',
        ]);

        if ($validator->passes()) {

            $country = ShippingCharges::where('country_id',$request->get('country'))->count();
            if($country > 0){

                session()->flash('error', 'Country already exist');

                return response()->json([
                    'status' => true,
                ]);
            }
            $shipping = new ShippingCharges();
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success', 'Shipping created successfully.');

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

    public function edit($id)
    {
        $shippingCharge = ShippingCharges::find($id);

        $country = Country::all();
        $data['countries'] = $country;
        $data['shippingCharge'] = $shippingCharge;
        return view('admin.shipping.edit',$data);
    }

    public function update(Request $request, $id)
    {
        $shipping = ShippingCharges::find($id);

        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required|numeric',
        ]);

        if ($validator->passes()) {

            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success', 'Shipping updated successfully.');

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

    public function destroy($id){
        $shipping = ShippingCharges::find($id);

        if($shipping == null){
            session()->flash('error', 'Shipping not found');
            return response()->json([
                'status' => false,
            ]);
        }
        $shipping->delete();
        session()->flash('success', 'Shipping deleted successfully.');
        return response()->json([
            'status' => true,
        ]);
    }
}
