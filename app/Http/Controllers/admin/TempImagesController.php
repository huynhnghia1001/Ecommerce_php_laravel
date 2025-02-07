<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TempImage;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;



class TempImagesController extends Controller
{
    public function create(Request $request)
    {
        $image = $request->image;

        if(!empty($image)){
            $ext = $image->getClientOriginalExtension();
            $newName = time().'.'.$ext;

            $tempImage = new TempImage();
            $tempImage->name = $newName;
            $tempImage->save();

            $image->move(public_path().'/temp', $newName);
            $manager = new ImageManager(Driver::class);
            //Generate thumbnail
            $sourcePath = public_path().'/temp/'.$newName;
            $destPath = public_path().'/temp/thumb/'.$newName;
            $image = $manager->read($sourcePath);
            $image->scale(300,275);
            $image->save($destPath);

            return response()->json([
                'status' =>true,
                'image_id' => $tempImage->id,
                'ImagePath' =>asset('/temp/thumb/'.$newName),
                'message' => 'Image upload successfully'
            ]);
        }
    }
}
