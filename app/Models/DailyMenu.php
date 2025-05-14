<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMenu extends Model
{
    //
    protected $fillable = ['nutrition_plan_id', 'date'];

    public function nutritionPlan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class);
    }

    public function items():HasMany
    {
        return $this->hasMany(DailyMenuItem::class);
    }

    // add guaded
//    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
