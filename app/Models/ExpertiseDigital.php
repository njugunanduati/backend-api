<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertiseDigital extends Model
{

    protected $table = 'digital_expertise';
    protected $fillable = [
            'user_id', 
            'dgcontent',
            'dgwebsite',
            'dgemail',
            'dgseo',
            'dgadvertising',
            'dgsocial',
            'dgvideo',
            'dgmetrics'
        ];
        
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
