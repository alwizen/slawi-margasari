<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionPlanItem extends Model
{
    //

    protected $fillable = ['nutrition_plan_id', 'nutrient_id', 'amount'];

    public function nutrient():BelongsTo
    {
        return $this->belongsTo(Nutrient::class);
    }
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
