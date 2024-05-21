<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAccess extends Model {

	protected $table = 'training_user';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
            'user_id', 
            'training_software', 
            'training_100k', 
            'prep_roleplay', 
            'training_jumpstart', 
            'training_lead_gen', 
            'group_coaching', 
            'flash_coaching', 
            'coaching_action_steps', 
            'licensee_onboarding', 
            'licensee_advisor',
            'lead_generation',
            'quotum_access',
            'simulator',
        ];
	public function user() {
        return $this->hasOne(User::class);
    }

}
