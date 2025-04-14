<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddCart extends Model
{
    protected $table = 'add_cart';

    public $timestamps = false; 

    public function product()
    {
        return $this->belongsTo(Product::class, 'pid', 'id');
    }
}
