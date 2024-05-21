<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\ImpSettingsMeeting;

class OneHourMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hourly:meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a meeting reminder to client an hour before';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function processMeetings($meeting, $company, $user, $settings){
        
        $time_zone = trim(str_replace(" ","",$meeting->time_zone));
        $date = Carbon::now($time_zone)->addHour(); // Add an hour since we are sending a reminder 1hr B4 
        $time = $date->format('H:i'); // 24hrs time
        $day = $date->isoFormat('dddd');
        
        if(($meeting->meeting_day == $day) && ($meeting->meeting_time == $time)){
            sendOneHourMeetingReminder($meeting, $company, $user, $settings); 
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
            $timezone = trim(str_replace(" ","",$msetting->time_zone));
            
            $imp_start_date = $msetting->settings->assessment->implementation_start_date;
            $startdate = ($imp_start_date)? formatDate($imp_start_date): getImplementationStartDate($msetting->settings->assessment->created_at);
            $meeting_date = Carbon::createFromFormat('Y-m-d', $startdate);
            $meeting_date->setTimezone($timezone);
            
            // Check if implementation start date is in the past from today.
            // If implementation start date is in the future, we cannot send any notifications
            // So that we can loop through the set meetings and check on when to send notifications
            $isDatePast = $meeting_date->isPast();
            $company = $msetting->settings->assessment->company;
            $user = $msetting->settings->assessment->user();

            // And also check if this client has 3 days before reminders enabled
            if($user->isActive() && $isDatePast && boolval($msetting->settings->one_hour)){
                $this->processMeetings($msetting, $company, $user, $msetting->settings);
            }

        }
        
    }
}
