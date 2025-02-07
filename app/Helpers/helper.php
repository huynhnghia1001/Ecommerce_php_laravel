<?php

use App\Models\Category;

function getCategories()
{
    return Category::latest('name', 'asc')->with("sub_category")
                                        ->orderBy('id', 'desc')
                                        ->where('showHome', 'yes')
                                        ->where('status',1)
                                        ->get();
}

?>
