<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertiseJS12 extends Model
{

    protected $table = 'js_12_expertise';
    protected $fillable = [
            'user_id', 
            'costs', 
            'mdp', 
            'offer', 
            'prices', 
            'upsell', 
            'bundling', 
            'downsell', 
            'products', 
            'alliances', 
            'campaign', 
            'leads', 
            'internet'
        ];
        
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
