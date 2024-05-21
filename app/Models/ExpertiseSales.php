<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertiseSales extends Model
{
    protected $table = 'sales_expertise';
    protected $fillable = [
        'user_id',
        'salesmanager',
        'salescompensation',
        'salessuperstars',
        'salestraining',
        'salesprospecting',
        'salesclients',
        'salestrade',
        'salesdm',
        'salesclosing',
        'salesorder',
        'salesremorse'
    ];
    
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
