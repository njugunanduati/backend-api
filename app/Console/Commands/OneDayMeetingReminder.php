<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\ImpSettings;
use App\Models\ImpSettingsMeeting;

class OneDayMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oneday:meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a meeting reminder to client daily';

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
            sendOneDayMeetingReminder($meeting, $company, $user, $date, $settings); 
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
            if ($imp_settings->one_day != 0 ) {
                $timezone = trim(str_replace(" ","",$msetting->time_zone));
                
                $imp_start_date = $msetting->settings->assessment->implementation_start_date;
                $startdate = ($imp_start_date)? formatDate($imp_start_date): getImplementationStartDate($msetting->settings->assessment->created_at);
                $meeting_date = Carbon::createFromFormat('Y-m-d', $startdate);
                $meeting_date->setTimezone($timezone);
                
                // Check if implementation start date is in the past from today.
                // If implementation start date is in the future, we cannot send any notifications
                // So that we can loop through the set meetings and check on when to send notifications
                $isDatePast = $meeting_date->isPast();

                $tomorrow = Carbon::tomorrow($timezone);
                $weekday = $tomorrow->isoFormat('dddd');
                $date = $tomorrow->isoFormat('dddd Do Y');

                $company = $msetting->settings->assessment->company;
                $user = $msetting->settings->assessment->user();

                // And also check if this client has 1 days before reminders enabled
                if($user->isActive() && $isDatePast && boolval($msetting->settings->one_day)){
                    $this->processMeetings($msetting, $date, $weekday, $company, $user, $msetting->settings);
                }
            }
        

        }
        
    }
}
