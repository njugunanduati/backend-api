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

class GroupCoachingThreeMinMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group-coaching:three-min-before';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a meeting reminder to a member just 3 min before a lesson.';

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
        // Get the lesson settings for three minutes after reminder
        $lesson_meeting_settings = GroupCoachingLessonMeetingSetting::where('three_min_before_reminder', 1)->whereNull('deleted_at')->with(['lessonmeeting' => function ($query) {
            $query->where('lesson_paused', 0);
        }])->get();
        $data = [];
        $lesson_data = [];

        //loop through the lesson setting to get the lesson meeting
        foreach ($lesson_meeting_settings as $key => $each) {

            //get meeting
            $lesson_meeting = $each->lessonmeeting;

            // Check time threashhold i.e three min before the meeting
            $check = $this->checkIfThreeMinBefore($lesson_meeting, $each);

            if ($check) {

                //retrieve email recipients
                $data = $this->createRecipientList($lesson_meeting);
                $lesson_data[] = (object) array(
                    'lesson_id' => $lesson_meeting->lesson_id,
                    'invited_by' => $lesson_meeting->invited_by,
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
                        $this->sendMail($value->recipients, $this->instructor, $this->email_template);
                    }
                }
                
            }
        } else {
            // Log here
            print_r('No recipients found');
            return;
        }
    }

    public function checkIfThreeMinBefore($meeting, $msettings)
    {

        if ($meeting) {
            $today = Carbon::now($meeting->time_zone);

            $meeting_day = Carbon::createFromDate($meeting->meeting_time);
            $meeting_day->setTimezone($meeting->time_zone);

            if ($today->diffInMinutes($meeting_day) === 3 && $today->lessThanOrEqualTo($meeting_day)) {
                $msettings->three_min_before_reminder = 0;
                $msettings->save();
                return true;
            }
        }

        return false;
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
            $subject = $notification_template->three_min_after_sub;
            $template = $notification_template->three_min_after;


            return [
                'subject' => $subject,
                'template' => $template
            ];
        }else{
            return null;
        }
    }

    public function sendMail($recipients, $instructor, $email_template)
    {
        // 
        foreach ($recipients as $key => $value) {

            $template = $this->cleanMailable($value->first_name, $instructor->first_name, $email_template);
            sendGCMeetingReminder($template, $value, $instructor);
            Log::info('GCS Notification sent to'.json_encode($value).'message'.json_encode($template));
        }
    }

    public function cleanMailable($recipient, $instructor, $email_template)
    {
        //replace for subject (recepient)
        $clean_subject = trim(preg_replace('/~recipientfirstname~/', $recipient, $email_template['subject']));

        //replace for subject (recepient & coach)
        $replace_recepient = preg_replace('/~recipientfirstname~/', $recipient, $email_template['template']);
        $clean_template = trim(preg_replace('/~coachfirstname~/', $instructor, $replace_recepient));
        $url = env('STUDENT_URL', 'https://student.profitaccelerationsoftware.com');
        $clean_template = trim(preg_replace('/~student_url~/', $url, $clean_template));
        $clean_template = trim(preg_replace('/~link~/', $url, $clean_template));

        $template = [
            'subject' => $clean_subject,
            'template' => $clean_template
        ];

        return $template;
    }
}
