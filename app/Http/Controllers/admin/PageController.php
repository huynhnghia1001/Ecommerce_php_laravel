<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Pages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Pages::orderBy('created_at', 'desc');

        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('slug', 'like', '%' . $keyword . '%');
            });
        }

        $pages = $query->paginate(10);

        return view('admin.pages.list', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validated->errors()->first(), // Chỉ trả lỗi đầu tiên
            ]);
        }

        Pages::create($request->only('name', 'slug', 'content'));
        session()->flash('success', 'Page created successfully');
        return response()->json([
            'status' => true,
            'message' => 'Page created successfully',
        ]);
    }

    public function edit($id)
    {
        $page = Pages::find($id);
        if ($page == null) {
            session()->flash('error', 'Page not found');
            return redirect()->route('pages.index');
        }
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, $id){
        $validated = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([ 'status' => false, 'errors' => $validated->errors()->first() ]);
        }
        $page = Pages::find($id);
        $page->update($request->only('name', 'slug', 'content'));
        session()->flash('success', 'Page updated successfully');
        return response()->json([
            'status' => true,
        ]);
    }

    public function destroy($id){
        $page = Pages::find($id);
        if($page == null){
            session()->flash('error', 'Page not found');
            return redirect()->route('pages.index');
        }
        $page->delete();
        session()->flash('success', 'Page deleted successfully');
        return response()->json([
            'status' => true,
        ]);
    }
}
