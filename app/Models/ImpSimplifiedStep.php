<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpSimplifiedStep extends Model {

	protected $table = 'imp_simplified_steps';

    protected $fillable = [
        'path',
        'step',
        'header',
        'body',
        'student_header',
        'student_body',
    ];
}
