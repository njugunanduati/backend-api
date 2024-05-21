<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\Models\Role;

use App\Models\User;
use App\Models\Lesson;
use Illuminate\Console\Command;
use App\Models\MemberGroupLesson;
use Illuminate\Support\Facades\Log;
use App\Models\GroupCoachingLessonMeeting;
use App\Models\GroupCoachingEmailNotification;
use App\Models\GroupCoachingLessonMeetingSetting;

class GroupCoachingOneHourMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group-coaching:hour-before';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a meeting reminder to a member an hour before a Lesson.';

    public $recipients = '';
    public $email_template = '';
    public $instructor = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get the lesson settings for one hour before reminder
        $lesson_meeting_settings = GroupCoachingLessonMeetingSetting::where('one_hour_reminder', 1)->whereNull('deleted_at')->with(['lessonmeeting' => function ($query) {
            $query->where('lesson_paused', 0);
        }])->get();
        $data = [];
        $lesson_data = [];

        //loop through the lesson setting to get the lesson meeting
        foreach ($lesson_meeting_settings as $key => $each) {

            //get meeting 

            $lesson_meeting = $each->lessonmeeting;

            // Check time threashhold i.e one hour before
            $check = $this->checkIfHourBefore($lesson_meeting, $each);

            if ($check) {

                //retrieve email recipients
                $data = $this->createRecipientList($lesson_meeting);
                $lesson_data[] = (object) array(
                    'lesson_id' => $lesson_meeting->lesson_id,
                    'invited_by' => $lesson_meeting->invited_by,
                    'meeting_url' => $lesson_meeting->meeting_url,
                    'meeting_time' => $lesson_meeting->meeting_time,
                    'time_zone' => $lesson_meeting->time_zone,
                    'recipients' => $data
                );
            }
        }
        if (count($data) != 0) {
            $lesson_data = array_map("unserialize", array_unique(array_map("serialize", $lesson_data)));
            foreach ($lesson_data as $key => $value) {

                //retrieve email templates
                $this->email_template = $this->getMailTemplate($value->lesson_id);

                if($this->email_template){
                    //retrieve instructor
                    $this->instructor = MemberGroupLesson::where('lesson_id', $value->lesson_id)->where('invited_by', $value->invited_by)->first()->user;

                    //Send Mail

                    if ($this->email_template) {
                        $date_time = new Carbon();                  // equivalent to Carbon::now()
                        $date_time = new Carbon($value->meeting_time, $value->time_zone);
                        
                        $date_str = Carbon::createFromFormat('Y-m-d H:i:s', $date_time)->format('D, d Y g:i A');
                        $date_str = $date_str.' '.$value->time_zone;
                        $this->email_template['template'] = preg_replace('/~date_gmt~/', $date_str, $this->email_template['template']);
                        $this->sendMail($value->recipients, $this->instructor, $this->email_template, $value->meeting_url);
                    }
                }
            }
        } else {
            // Log here
            print_r('No recipients found');
            return;
        }
    }

    public function checkIfHourBefore($meeting, $msettings)
    {
        if ($meeting) {
            $today = Carbon::now($meeting->time_zone);

            $meeting_date = $this->convertTime($meeting);

            if ($today->diffInHours($meeting_date) === 6 && $today->lessThanOrEqualTo($meeting_date)) {
                $msettings->one_hour_reminder = 0;
                $msettings->save();
                return true;
            }
        }

        return false;
    }

    public function convertTime($meeting_obj)
    {

        $meeting = Carbon::createFromDate($meeting_obj->meeting_time);

        $user_date = Carbon::createMidnightDate($meeting->year, $meeting->month, $meeting->day, $meeting_obj->time_zone); //meeting day

        $user_date->setTime($meeting->hour, $meeting->minute);

        return $user_date;
    }



    #TODO Logs for activity

    public function createRecipientList($lesson_meeting)
    {

        //will need to compare name of lesson with relevant template
        $lesson = Lesson::find($lesson_meeting->lesson_id);

        // Get members in group,lesson invited by user except the coach
        $members = $lesson->memberGroupLesson
            ->where('user_id', '!=', $lesson_meeting->invited_by)
            ->where('group_id', $lesson_meeting->group_id)
            ->where('invited_by', $lesson_meeting->invited_by);


        $member_ids = [];

        foreach ($members as $key => $each) {
            $member_ids[] = $each->user_id;
        }

        //Get users in lesson
        $users = User::whereIn('id', $member_ids)->get();

        return $users;
    }

    public function getMailTemplate($lesson_id)
    {
        $notification_template = GroupCoachingEmailNotification::where('lesson_id', $lesson_id)->first();

        if($notification_template){
            //clean up template & subject
            $subject = $notification_template->one_hour_before_sub;
            $template = $notification_template->one_hour_before;
            $template .= '<br/><p>In case you forgot your password, you can reset it by clicking on the <i>"Forgot Password"</i> link on the same URL above.</p>';

            return [
                'subject' => $subject,
                'template' => $template
            ];
        }else{
            return null;
        }
    }

    public function sendMail($recipients, $instructor, $email_template, $meeting_url)
    {
        // 
        foreach ($recipients as $key => $value) {

            $template = $this->cleanMailable($value->first_name, $instructor->first_name, $email_template, $meeting_url);
            sendGCMeetingReminder($template, $value, $instructor);
            Log::info('GCS Notification sent to'.json_encode($value).'message'.json_encode($template));
        }
    }

    public function cleanMailable($recipient, $instructor, $email_template, $meeting_url)
    {
        //replace for subject (recepient)
        $clean_subject = trim(preg_replace('/~recipientfirstname~/', $recipient, $email_template['subject']));

        //replace for subject (recepient & coach)
        $replace_recepient = preg_replace('/~recipientfirstname~/', $recipient, $email_template['template']);
        $clean_template = trim(preg_replace('/~coachfirstname~/', $instructor, $replace_recepient));
        $url = env('STUDENT_URL', 'https://student.profitaccelerationsoftware.com');
        $clean_template = trim(preg_replace('/~student_url~/', $url, $clean_template));
        $clean_template = trim(preg_replace('/~link~/', $url, $clean_template));
        $clean_template = trim(preg_replace('/~meetinglink~/', $meeting_url, $clean_template));

        $template = [
            'subject' => $clean_subject,
            'template' => $clean_template
        ];

        return $template;
    }
}
