<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function creating(Product $product)
    {
        

        if (!app()->runningInConsole()) {
            $branch_id = session('branch')->id;
            $product->branch_id = $branch_id;
            $product->order = Product::where('branch_id', $branch_id)->max('order') + 1;
        }else{
            $product->order = Product::where('branch_id', $product->branch_id)->max('order') + 1;
        }
    }
}
