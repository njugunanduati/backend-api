<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFileUpload extends Model {

	protected $table = 'customs_file_upload';
	public $timestamps = true;
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'name', 'url', 'key', 'type'];

     /**
     * Set the name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trimSpecial(strip_tags($value));
		
    }

}