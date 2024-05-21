<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAnalysis extends Model
{

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notification_analysis';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['notification_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

}
