<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function cart()
    {
        return $this->hasOne(AddCart::class, 'pid', 'id')->where('status', 'Active');
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'p_id', 'product_code')->where('status', 'Active');
    }

    public function sizeClass()
    {
        return $this->belongsTo(SizeClass::class, 'class0', 'id');
    }
}
