<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\ImpSettings;
use App\Models\ImpSettingsMeeting;

class ThreeDaysMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'threedays:meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a meeting reminder to client 3 days before';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function processMeetings($meeting, $date, $weekday, $company, $user, $settings){
        if($meeting->meeting_day == $weekday){
            sendThreeDaysMeetingReminder($meeting, $company, $user, $date, $settings); 
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
        $meeting_settings = ImpSettingsMeeting::orderBy('id')->get();

        foreach ($meeting_settings as $key => $msetting) {
            $imp_settings = ImpSettings::find($msetting->settings_id);
            if ($imp_settings->three_days != 0 ){
                $timezone = trim(str_replace(" ","",$msetting->time_zone));
            
                $imp_start_date = $msetting->settings->assessment->implementation_start_date;
                $startdate = ($imp_start_date)? formatDate($imp_start_date): getImplementationStartDate($msetting->settings->assessment->created_at);
                $meeting_date = Carbon::createFromFormat('Y-m-d', $startdate);
                $meeting_date->setTimezone($timezone);
                
                // Check if implementation start date is in the past from today.
                // If implementation start date is in the future, we cannot send any notifications
                // So that we can loop through the set meetings and check on when to send notifications
                $isDatePast = $meeting_date->isPast();
                $future = Carbon::now($timezone)->addDays(3);
                $weekday = $future->isoFormat('dddd');
                $date = $future->isoFormat('dddd Do Y');
                $company = $msetting->settings->assessment->company;
                $user = $msetting->settings->assessment->user();

                // And also check if this client has 3 days before reminders enabled
                if($user->isActive() && $isDatePast && boolval($msetting->settings->three_days)){
                    $this->processMeetings($msetting, $date, $weekday, $company, $user, $msetting->settings);
                }
            }

        }

    }
}
