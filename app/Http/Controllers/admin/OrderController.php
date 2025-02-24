<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::latest('orders.created_at')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.*', 'users.name', 'users.email');

        if ($request->has('keyword') && $request->get('keyword') != "") {
            $keyword = '%' . $request->get('keyword') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('users.name', 'LIKE', $keyword)
                    ->orWhere('users.email', 'LIKE', $keyword)
                    ->orWhere('orders.id', 'LIKE', $keyword);
            });
        }

        $orders = $query->paginate(10);

        return view('admin.orders.list', compact('orders'));
    }

    public function show($orderId)
    {
        $order = Order::select('orders.*', 'countries.name as countryName')
            ->join('countries', 'countries.id', '=', 'orders.country_id')
            ->where('orders.id',$orderId)
            ->first();

        $orderItems = OrderItem::where('order_id',$orderId)->get();

        return view('admin.orders.detail', [
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }
    public function changeOrderStatus(Request $request,$orderId)
    {
        $order = Order::find($orderId);
        $order->status = $request->status;
        $order->shipped_date = $request->shipped_date;
        $order->save();

        $message = "Update order status successfully";
        session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }

    public function sendInvoiceEmail(Request $request,$orderId){
        orderEmail($orderId,$request->userType);

        $message = "Invoice sent successfully";
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }
}
