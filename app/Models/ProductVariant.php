<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
	public function variantInfo(){
		return $this->hasOne("\App\Models\Variant","id","variant_id");
	}

}
