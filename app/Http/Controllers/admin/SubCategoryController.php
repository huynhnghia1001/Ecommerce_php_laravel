<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{

//    public function index(Request $request)
//    {
//        $subCategories = SubCategory::select('sub_categories.*','categories.name as CategoryName')
//                                    ->latest('sub_categories.id')
//            ->leftJoin('categories', 'categories.id', '=', 'sub_categories.category_id');
//
//        if(!empty($request->get('keyword'))){
//            $subCategories = $subCategories::where('sub_categories.name','like','%'.$request->get('keyword').'%');
//            $subCategories = $subCategories::orWhere('categories.name','like','%'.$request->get('keyword').'%');
//
//        }
//        $subCategories = $subCategories->paginate(10);
//
//        return view('admin.sub_category.list',compact('subCategories'));
//
//    }

    public function index(Request $request)
    {
        $subCategories = SubCategory::select('sub_categories.*', 'categories.name as CategoryName')
            ->leftJoin('categories', 'categories.id', '=', 'sub_categories.category_id')
            ->latest('sub_categories.id');

        if (!empty($request->get('keyword'))) {
            $keyword = $request->get('keyword');
            $subCategories = $subCategories->where(function ($query) use ($keyword) {
                $query->where('sub_categories.name', 'like', '%' . $keyword . '%')
                    ->orWhere('categories.name', 'like', '%' . $keyword . '%');
            });
        }

        // Phân trang
        $subCategories = $subCategories->paginate(10);

        // Trả về view với dữ liệu
        return view('admin.sub_category.list', compact('subCategories'));
    }


    public function create()
    {
        $categories = Category::OrderBy('name','ASC')->get();
        $data['categories'] = $categories;
        return view('admin.sub_category.create',$data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required|unique:sub_categories',
            'category' => 'required',
            'status' => 'required'
        ]);

        if($validator->passes())
        {
            $subCategory = new SubCategory();
            $subCategory->name = $request->name;
            $subCategory->slug = $request->slug;
            $subCategory->category_id = $request->category;
            $subCategory->status = $request->status;
            $subCategory->showHome = $request->showHome;
            $subCategory->save();

            $request->session()->flash('success','Subcategory Added Successfully');
            return response()->json([
                'status' => true,
                'message'=> 'Subcategory Added Successfully'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message'=> $validator->errors(),
            ]);
        }
    }

    public function edit($subCategoryId)
    {
        $subCategory = SubCategory::find($subCategoryId);
        if(empty($subCategory))
        {
            session()->flash('error','Subcategory Not Found');
            return redirect()->route('sub_categories.index');
        }
        $categories = Category::orderBy('name','ASC')->get();
        $data['categories'] = $categories;
        $data['subCategory'] = $subCategory;

        return view('admin.sub_category.edit',$data);
    }

    public function update(Request $request,$subCategoryId)
    {
        $subCategory = SubCategory::find($subCategoryId);

        $validator = Validator::make($request->all(),[
            'name' => 'required',
//            'slug' => "required|unique:sub_categories,slug,'.$subCategory->id.',id",
            'slug' => "required|unique:sub_categories,slug,{$subCategory->id},id",

            'category' => 'required',
            'status' => 'required'
        ]);

        if($validator->passes())
        {
            $subCategory->name = $request->name;
            $subCategory->slug = $request->slug;
            $subCategory->category_id = $request->category;
            $subCategory->showHome = $request->showHome;
            $subCategory->status = $request->status;
            $subCategory->save();

            $request->session()->flash('success','Subcategory Updated Successfully');
            return response()->json([
                'status' => true,
                'message'=> 'Subcategory Updated Successfully'
            ]);
        }
        $request->session()->flash('error','Subcategory Not Found');
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ]);
    }

    public function destroy(Request $request,$subCategoryId)
    {
        $subCategory = SubCategory::find($subCategoryId);

        if (empty($subCategory)) {
            $request->session()->flash('error','Subcategory Not Found');

            return response()->json([
                'status' => true,
                'notFound' => true,
                'message' => 'SubCategory not found'
            ]);

        }
        $subCategory->delete();
        $request->session()->flash('success','Subcategory Deleted Successfully');
        return response()->json([
            'status' => true,
            'message'=> 'Subcategory Deleted Successfully'
        ]);
    }
}
