<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMenuItem extends Model
{
    protected $fillable = ['daily_menu_id', 'nutrient_id', 'menu_name','qty'];

    public function dailyMenu():BelongsTo
    {
        return $this->belongsTo(DailyMenu::class);
    }

    public function nutrient():BelongsTo
    {
        return $this->belongsTo(Nutrient::class);
    }
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
