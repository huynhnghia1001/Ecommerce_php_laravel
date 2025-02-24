<?php

use App\Models\Category;
use App\Mail\OrderEmail;
use App\Models\Country;
use App\Models\Order;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Mail;

function getCategories()
{
    return Category::latest('name', 'asc')->with("sub_category")
        ->orderBy('id', 'desc')
        ->where('showHome', 'yes')
        ->where('status', 1)
        ->get();
}

function getProductImage($productId)
{
    return ProductImage::where('product_id', $productId)->first();
}

function orderEmail($orderId, $user)
{
    $order = Order::where('id', $orderId)->with('items')->first();

    if($user == 'customer'){
        $subject = 'Thanks for your order';
        $email = $order->email;
    }else{
        $subject = 'You have received an order';
        $email = env('ADMIN_EMAIL');
    }

    $mailData = [
        'subject' => $subject,
        'order' => $order,
        'userType' => $user,
    ];

    Mail::to($order->email)->send(new OrderEmail($mailData));
//  dd($order);
}

function getCountryInfo($id)
{
    return Country::where('id', $id)->first();
}

?>
