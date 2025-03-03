<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest();

        $keyword = request()->input('keyword');

        if (!empty($keyword)) {
            $users = $users->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }


        $users = $users->paginate(10);
        return view('admin.user.list',
        [
            'users' => $users,

        ]);
    }

    public function create(){
        return view('admin.user.create');
    }

    public function store(Request $request){

        $validated = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric',
        ]);

        if($validated->passes()) {
            $user = new User();
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->phone = $request->input('phone');
            $user->save();

            session()->flash('success', 'User created successfully');
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'errors' => $validated->errors()
            ]);
        }
    }

    public function edit($id){
        $user = User::find($id);
        if($user == null){
            session()->flash('error', 'User not found');
            return redirect()->route('users.index');
        }
        return view('admin.user.edit', compact('user'));
    }
    public function update(Request $request, $id){
        $validated = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id.',id',
            'phone' => 'required|numeric',
        ]);
        if($validated->passes()) {
            $user = User::find($id);
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            if($request->input('password') != null){
                $user->password = Hash::make($request->input('password'));
            }
            $user->phone = $request->input('phone');
            $user->save();

            session()->flash('success', 'User updated successfully');
            return response()->json(['status' => true]);
        }
        return response()->json(['status' => false,'errors' => $validated->errors()]);
    }

    public function destroy($id){
        $user = User::find($id);
        if ($user == null){
            session()->flash('error', 'User not found');
            return redirect()->route('users.index');
        }
        $user->delete();
        return response()->json(['status' => true,'success' => 'User deleted successfully']);
    }
}
