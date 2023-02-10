<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
	public function productVariantInfoOne(){
		return $this->hasOne("\App\Models\ProductVariant","id","product_variant_one");
	}

	public function productVariantInfoTwo(){
		return $this->hasOne("\App\Models\ProductVariant","id","product_variant_two");
	}

	public function productVariantInfoThree(){
		return $this->hasOne("\App\Models\ProductVariant","id","product_variant_three");
	}

}
