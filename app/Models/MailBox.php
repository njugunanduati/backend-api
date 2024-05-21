<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailBox extends Model {

	protected $table = 'group_coaching_mailbox';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'parent', 'from', 'to', 'from_id', 'to_id', 'subject', 'body', 'read', 'attachments', 'group'];

    /**
     * Set the subject. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setSubjectAttribute($value)
    {
        $this->attributes['subject'] = trimSpecial(strip_tags($value));
		
    }

    /**
     * Set the full_desc. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setBodyAttribute($value)
    {
        $this->attributes['body'] = trimSpecial(strip_tags($value));
		
    }

}
