<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::latest();
        if(!empty($request->get('keyword'))){
            $brands = Brand::where('brand_name','like','%'.$request->get('keyword').'%');
        }
        $brands = $brands->paginate(10);
        return view('admin.brands.list', compact('brands'));
    }
    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'status' => 'required',
        ]);

        if($validator->passes())
        {
            $brand = new Brand();
            $brand->name = $request->input('name');
            $brand->slug = $request->input('slug');
            $brand->status = $request->input('status');
            $brand->save();

            $request->session()->flash('success', 'Brand added successfully.');
            return response()->json([
                'status' => true,
                'message' => 'Brand added successfully.',
            ]);
        }
        $request->session()->flash('error', 'Something went wrong.');
        return response()->json(['error'=>$validator->errors()]);
    }
    public function edit($id)
    {
        $brands = Brand::find($id);
        return view('admin.brands.edit', compact('brands'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,'.$id,
            'status' => 'required',
        ]);

        $brand = Brand::find($id);
        if($validator->passes())
        {
            $brand->name = $request->input('name');
            $brand->slug = $request->input('slug');
            $brand->status = $request->input('status');
            $brand->save();

            $request->session()->flash('success', 'Brand updated successfully.');
            return response()->json([
                'status' => true,
                'message' => 'Brand updated successfully.',
            ]);
        }
        $request->session()->flash('error', 'Something went wrong.');
        return response()->json(['error'=>$validator->errors()]);
    }

    public function destroy($id,Request $request)
    {
        $brand = Brand::find($id);
        if(!empty($brand)){
            $brand->delete();
            $request->session()->flash('success', 'Brand deleted successfully.');
            return response()->json([
                'status' => true,
                'message' => 'Brand deleted successfully.',
            ]);
        }
        $request->session()->flash('error', 'Something went wrong.');
        return response()->json([
            'status' => true,
            'notFound' => true,
            'message' => 'Brand not found'
        ]);
    }
}
