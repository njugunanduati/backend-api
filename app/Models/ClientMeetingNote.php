<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMeetingNote extends Model {

	protected $table = 'client_meeting_notes';
    protected $fillable = [ 'user_id', 'notes'];
	public $timestamps = true;

    public function __toString() {
		return $this->note;
	}

}