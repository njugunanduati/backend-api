<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteSetting extends Model
{

    protected $table = 'meeting_notes_settings';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'label',
        'placeholder',
    ];

     /**
     * Set the note remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trimSpecial(strip_tags($value));
    }

     /**
     * Set the note remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setLabelAttribute($value)
    {
        $this->attributes['label'] = trimSpecial(strip_tags($value));
    }

     /**
     * Set the note remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setPlaceholderAttribute($value)
    {
        $this->attributes['placeholder'] = trimSpecial(strip_tags($value));
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
