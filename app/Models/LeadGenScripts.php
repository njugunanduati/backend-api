<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadGenScripts extends Model
{
    use HasFactory;
    protected $table = 'leadgen_scripts';
    public $timestamps = true;

    protected $fillable = [
        'slug',
        'content'
    ];


}