<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $appends = ['total_variants','variant_price'];

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantPrices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariantPrice::class);
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getTotalVariantsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->variants()->with(['variant.productVariants'=>function($query){
            $query->where('product_id',$this->id)->select('variant_id','variant');
        }])->groupBy('variant_id')->get();
    }

    public function getVariantPriceAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->variantPrices()->with('variantOne','variantTwo','variantThree')->get();
    }
}
