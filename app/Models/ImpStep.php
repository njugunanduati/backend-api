<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpStep extends Model {

	protected $table = 'imp_steps';

    protected $fillable = [
        'path',
        'step',
        'header',
        'body',
        'student_header',
        'student_body',
    ];
}
