<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
// use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;
use App\Helpers\NewAccessToken;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasFactory, HasRoles, SoftDeletes;

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // Return name and email address...
        return [$this->email => $this->first_name . ' ' . $this->last_name];
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'secondary_email',
        'password',
        'manager',
        'advisor',
        'onboarding',
        'company',
        'company_id',
        'website',
        'profile_pic',
        'title',
        'location',
        'time_zone',
        'phone_number',
        'birthday',
        'facebook',
        'twitter',
        'linkedin',
        'monthly_income',
        'annual_income',
        'licensee_access',
        'onboarding_status',
        'business_onboarding_status',
        'business_advisor',
        'licensee_onboarding_advisor',
        'licensee_onboarding_status',
        'created_by_id'
    ];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords(strtolower(trimSpecial(strip_tags(trimApostrophe($value)))));
    }

    /**
     * Set the last_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords(strtolower(trimSpecial(strip_tags(trimApostrophe($value)))));
    }

    /**
     * Set the title. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trimSpecial(strip_tags(trimApostrophe($value)));
    }

    /**
     * Set the location. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setLocationAttribute($value)
    {
        $this->attributes['location'] = ucwords(strtolower(trimSpecial(strip_tags(trimApostrophe($value)))));
    }

    public function setFacebookAttribute($value)
    {
        $this->attributes['facebook'] = trimSpecial(strip_tags($value));
    }

    public function setTwitterAttribute($value)
    {
        $this->attributes['twitter'] = trimSpecial(strip_tags($value));
    }

    public function setLinkedinAttribute($value)
    {
        $this->attributes['linkedin'] = trimSpecial(strip_tags($value));
    }

    public function setCompanyAttribute($value)
    {
        $this->attributes['company'] = trimSpecial(strip_tags(trimApostrophe($value)));
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = Str::lower($value);
    }
    public function setSecondaryEmailAttribute($value)
    {
        $this->attributes['secondary_email'] = Str::lower($value);
    }

    public function __toString()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function assessments()
    {
        return $this->belongsToMany(Assessment::class)->withPivot('view_rights', 'edit_rights', 'report_rights');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    public function module_sets()
    {
        return $this->belongsToMany(ModuleSet::class)->withPivot('module_set_id', 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function logins()
    {
        return $this->hasMany(LoginTracker::class);
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class);
    }

    public function roleGreaterThan($role_name)
    {
        $role_order = array('Super Administrator', 'Administrator', 'Owner', 'Consultant', 'Single User', 'Restricted', 'Client', 'Inactive');
        $auth_index = array_search($role_name, $role_order);
        $user_index = array_search($this->role->name, $role_order);
        return $user_index <= $auth_index;
    }

    public function isActive()
    {
        return ($this->status()->first()->slug == 'inactive') ? false : true;
    }

    public function trainings()
    {
        return $this->hasMany(TrainingAnalytic::class);
    }
    public function flashanalysis()
    {
        return $this->hasMany(FlashCoachingAnalysis::class);
    }

    public function flashaccess()
    {
        return $this->hasOne(FlashCoachingAccess::class, 'student_id');
    }

    public function calendarurls()
    {
        return $this->hasOne(UserCalendarURL::class);
    }
    
    public function trainingAccess()
    {
        return $this->hasOne(TrainingAccess::class);
    }

    public function memberGroupLesson()
    {
        return $this->hasMany(MemberGroupLesson::class);
    }

    public function integration()
    {
        return $this->hasOne(Integration::class);
    }

    public function aweberIntegration()
    {
        return $this->hasOne(AweberIntegration::class);
    }

    public function getresponseIntegration()
    {
        return $this->hasOne(GetResponseIntegration::class);
    }

    public function lessonRecordings()
    {
        return $this->hasMany(LessonRecording::class);
    }

    public function getadvisor()
    {
        return $this->belongsTo(User::class, 'advisor');
    }

    public function getbusinessadvisor()
    {
        return $this->belongsTo(User::class, 'business_advisor');
    }

    public function getmanager()
    {
        return $this->belongsTo(User::class, 'manager');
    }

    public function getlicenseeadvisor()
    {
        return $this->belongsTo(User::class, 'licensee_onboarding_advisor');
    }

    public function lastonboardingactivitypas()
    {
        return $this->hasOne(OnboardingLastActivity::class, 'user_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('category', 'pas');
        });
    }

    public function lastonboardingactivityweb()
    {
        return $this->hasOne(OnboardingLastActivity::class, 'user_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('category', 'business');
        });
    }

    public function lastonboardingactivitylicensee()
    {
        return $this->hasOne(OnboardingLastActivity::class, 'user_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('category', 'licensee_onboarding');
        });
    }

    public function onboardingactivity()
    {
        return $this->hasMany(OnboardingOngoingActivity::class, 'user_id');
    }

    public function lead_gen_activity()
    {
        return $this->hasMany(LeadGenOngoingActivity::class, 'user_id');
    }

    public function last_lead_gen_activity()
    {
        return $this->hasOne(LeadGenLastActivity::class, 'user_id');
    }



    public function onboarding_email_log()
    {
        return $this->hasMany(OnboardingLog::class, 'user_id');
    }

    public function loginSecurity()
    {
        return $this->hasOne(LoginSecurity::class, 'user_id');
    }
    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'user_status_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }


    public function getLeadGenAdvisor()
    {
        return $this->belongsTo(User::class, 'lead_gen_advisor');
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @param  \DateTimeInterface|null  $expiresAt
     * @return \App\Helpers\NewAccessToken
     */
    public function createToken(string $name, array $abilities = ['*'], DateTimeInterface $expiresAt = null)
    {
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    /**
     * Revoke Tokens when a user is set as `Inactive`
     * @return void
     */
    public function invalidateTokens()
    {
        $this->tokens()->delete();
    }

    public function simulators()
    {
        return $this->hasMany(UserSimulator::class);
    }

    /**
     * Check if the given token has expired.
     *
     * @param  $token  The token model instance
     * @return bool  True if the token has expired, false otherwise.
     */
    public function tokenExpired($token): bool
    {
        // Check if the token's expires_at is in the past
        return $token->expires_at && Carbon::parse($token->expires_at)->isPast();
    }
}