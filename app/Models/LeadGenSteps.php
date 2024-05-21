<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadGenSteps extends Model
{
    use HasFactory;

    protected $table = 'leadgen_steps';
    public $timestamps = true;

    protected $fillable = [
        'section',
        'tab',
        'size',
        'step',
        'title',
        'description',
        'links',
        'advanced',
        'img',
    ];
}