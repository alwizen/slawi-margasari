<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionPlan extends Model
{
    //

    // add fillable
    protected $fillable = ['date'];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function nutrient()
    {
        return $this->belongsTo(Nutrient::class);
    }
    public function items():HasMany
    {
        return $this->hasMany(NutritionPlanItem::class);
    }
}
