<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class ModuleSet extends Model {

		protected $table = 'module_sets';
		public $timestamps = true;


        public function assessments()
        {
            return $this->hasMany('App\Models\Assessment');
        }

        public function users()
		{
			return $this->belongsToMany('App\Models\User');
        }

        public function modules()
        {
            return $this->hasMany('App\Models\ModuleSetModule');
        }


	}
