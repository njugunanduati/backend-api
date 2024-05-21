<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The LeadGenUserScripts Class
 */
class LeadGenUserScripts extends Model
{
    use HasFactory;
    /**
     * Summary of table
     * 
     * @var string
     */
    protected $table = 'leadgen_user_scripts';
    /**
     * Summary of timestamps
     * 
     * @var boolean
     */
    public $timestamps = true;

    /**
     * Summary of fillable
     * 
     * @var array
     */
    protected $fillable = [
        'user_id',
        'script_name',
        'content'
    ];

    /**
     * Relationship with User table
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with Scripts Table
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function script()
    {
        return $this->belongsTo(LeadGenScripts::class, 'script_id');
    }

    /**
     * Strip HTML tags form the script's content
     * 
     * @param mixed $value Value of the Content Attribute
     * 
     * @return void
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = trimSpecial(strip_tags($value));
    }
}