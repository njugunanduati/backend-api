<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\Models\User;


use App\Models\OnboardingLog;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;



use Illuminate\Support\Facades\Log;
use App\Models\OnboardingLastActivity;



class OnboardingNoActivityNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onboarding:no_activity_notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out onboarding notifications for none active coaches based on time inactive';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public $ten_day_template = ["subject" => "~coachfirstname~, you want to be a successful coach?...", "template" => "<p> Hi ~coachfirstname~,</p>
    <p>We are excited that you have taken up the challenge to become a business coach with Focused.com. We want to make sure that you stay laser-focused on your goals. Keep moving in the right direction and we'll be here to support you every step of the way.</p>
    <p>The Law of Inertia dictates that an object in motion tends to stay in motion. So let's get you taking action and gaining momentum!<p>
    <p>If you have any questions or need assistance, don't hesitate to contact us at support@focused.com</p>
    <p>See you soon!</p>"];
    public $twenty_day_template = ["subject" => "20 days with no Activity!", "template" => "<p> Hi Joy,</p>
    <p>This is the Onboarding Portal, notifying you that ~coachfirstname~ has yet to take any action after 20 days on the portal. Please call, using phone script 1.</p>
    <p>If you have any questions or need assistance, don't hesitate to contact us at support@focused.com</p>
    "];
    public $thirty_day_template = ["subject" => "30 days with no Activity!", "template" => "<p> Hi Joy,</p>
    <p>This is the Onboarding Portal, notifying you that ~coachfirstname~ has yet to take any action after 30 days on the portal.  Please move their Asana profile to Adrian’s column.</p>
    <p>If you have any questions or need assistance, don't hesitate to contact us at support@focused.com</p>
    "];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //get all onboarding notification data

        $last_activity_ids = OnboardingLastActivity::where('category', 'pas')->pluck('user_id');


        $data = User::where('onboarding', 1)
            ->where('onboarding_status', 0)
            ->where('created_at', '>=', '2022-05-25')
            ->where('deleted_at', NULL)
            ->where('business_advisor', NULL)
            ->where('email', 'not like', "%@focused.com%")
            ->where('role_id','!=', 5)
            ->whereNotIn('id', $last_activity_ids)
            ->get();


        $list = $this->buildUserList($data);
        $batch_ten = $list['batch_ten'];
        $batch_twenty = $list['batch_twenty'];
        $batch_thirty = $list['batch_thirty'];

        if ($batch_ten) {

            $this->sendMail($this->ten_day_template, $batch_ten);
        }

        if ($batch_twenty) {

            //send to /Joy & licensee only
            $this->sendMail($this->twenty_day_template, $batch_twenty);
        }

        if ($batch_thirty) {

            //send to /Joy & licensee only
            $this->sendMail($this->thirty_day_template, $batch_thirty);
        }
    }

    public function buildUserList($list)
    {
        //escalate time based on inactivity -10 days, 20 days , 30 days

        $batch_ten = [];
        $batch_twenty = [];
        $batch_thirty = [];

        foreach ($list as $key => $value) {

            $user_created = $this->convertTime($value->created_at);
            if ($user_created->diffInDays() >= 10 && $user_created->diffInDays() <= 11) {

                $batch_ten[] = array('type' => 'no_activity_10_days', 'user_id' => $value->id, 'name' =>  $value->first_name.' '.$value->last_name, 'email' => $value->email, 'manager' => $value->getmanager ? $value->getmanager : [], 'advisor' => $value->getadvisor ? $value->getadvisor : [], 'coach_name' => $value->first_name);
            }

            if ($user_created->diffInDays() >= 20 && $user_created->diffInDays() <= 21) {

                $batch_twenty[] = array('type' => 'no_activity_20_days', 'user_id' => $value->id, 'name' =>  $value->first_name.' '.$value->last_name, 'email' => 'joy@focused.com', 'manager' => [], 'advisor' => $value->getadvisor ? $value->getadvisor : [], 'coach_name' =>  $value->first_name.' '.$value->last_name);
            }

            if ($user_created->diffInDays() >= 30 && $user_created->diffInDays() <= 31) {

                $batch_thirty[] = array('type' => 'no_activity_30_days', 'user_id' => $value->id, 'name' =>  $value->first_name.' '.$value->last_name, 'email' => 'joy@focused.com', 'manager' => [], 'advisor' => $value->getadvisor ? $value->getadvisor : [], 'coach_name' =>  $value->first_name.' '.$value->last_name);
            }

            // print_r($user_created->diffInDays() . '----Days   ');
        }


        return array('batch_ten' => $batch_ten, 'batch_twenty' => $batch_twenty, 'batch_thirty' => $batch_thirty);
    }

    public function convertTime($data_obj)
    {

        $date_created_obj = Carbon::createFromDate($data_obj);

        $user_date = Carbon::createMidnightDate($date_created_obj->year, $date_created_obj->month, $date_created_obj->day, 'UTC'); //created at date

        $user_date->setTime($date_created_obj->hour, $date_created_obj->minute);

        return $user_date;
    }

    public function sendMail($template, $recepient_list)
    {

        foreach ($recepient_list as $key => $value) {
            $mailable = $this->cleanMailable($template, $value);
            onboardingReminder($mailable, (object)$value);
            //add sent details to log
            $log = new OnboardingLog;
            $log->type = $value['type'];
            $log->status = 1;
            $log->sent_to = $value['user_id'];
            $log->manager = $value['manager'] ? (int)$value['manager']->id : NULL;
            $log->advisor = $value['advisor'] ? (int)$value['advisor']->id : NULL;
            $log->save();
            Log::info('Notification sent'.$log);
        }
        return;
    }

    public function cleanMailable($template, $replace_obj)
    {
        //replace placeholders

        //replace for subject (recepient)
        $clean_subject = trim(preg_replace('/~coachfirstname~/', $replace_obj['coach_name'], $template['subject']));

        //replace for subject (recepient & coach)
        $clean_template = preg_replace('/~coachfirstname~/', $replace_obj['coach_name'], $template['template']);

        $template = [
            'subject' => $clean_subject,
            'template' => $clean_template
        ];

        return $template;
    }
}
