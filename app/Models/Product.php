<?php

namespace App\Models;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{   use Searchable;
    protected $table = 'products'; 
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_id'=>$this->id
        ];
    }
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
