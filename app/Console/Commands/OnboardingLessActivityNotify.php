<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\Models\User;

use App\Models\OnboardingLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use App\Models\OnboardingLastActivity;
use Illuminate\Database\Eloquent\Builder;



class OnboardingLessActivityNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onboarding:less_activity_notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out onboarding notifications for less active coaches based on time inactive - 10 day thresh hold';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public $oba_template_one = ["subject" => " Let’s keep going ~coachfirstname~ ! ", "template" => "<p> Hi ~coachfirstname~,</p>
    <p>I hope that this message finds you well. When you started this program with Focused.com, you made the BEST decision, and we're sure glad that you did!</p>
    <p>We have designed this program and optimized it to help you be extremely successful in your coaching business BUT we need you to take consistent action, as often as you can! Is there anything we can help you with?</p>
    <p>Let's get you back on track and working on your onboarding portal checklist.</p>
    <p>If you have any questions or need assistance, don’t hesitate to contact your Onboarding Advisor, or any of our Onboarding team.</p>
    <p>Keep going and keep growing!</p>"];
    public $oba_template_two = ["subject" => "It has been 20 days!", "template" =>  "<p> Hi ~advisorname~,</p>
    <p>This is the Onboarding Portal, notifying you that  ~coachfirstname~ started in the portal but hasn’t taken any action for 20 days. Please call, using phone script 2.</p>
    <p>If you have any questions or need assistance, don't hesitate to contact us at support@focused.com</p>"];
    public $oba_template_three = ["subject" => "It has been 30 days", "template" => "<p> Hi Joy,</p>
    <p>This is the Onboarding Portal, notifying you that  ~coachfirstname~ has been inactive for 30 days in the portal. Please move their Asana profile to Adrian’s column. 
    Make a note in Asana that they started but are still inactive despite two attempts to activate them and that Adrian is to call them and note the result in Asana.</p>
    <p>If you have any questions or need assistance, don't hesitate to contact us at support@focused.com</p>
    <p>PS. Let’s go Joy! Let’s go! Let’s go!!! ;-) </p>"];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //get all onboarding notification data for users who have completed onboarding
        $data = OnboardingLastActivity::whereHas('user', function (Builder $query) {
            $query->where('role_id', '!=', 5)->where('onboarding_status', 0)->where('business_advisor', NULL)->where('onboarding', 1)->where('deleted_at', NULL)->where('email', 'not like', "%@focused.com%");
        })->with('user')->get();

        $list = $this->buildUserList($data);
        $batch_ten = $list['batch_ten'];
        $batch_twenty = $list['batch_twenty'];
        $batch_thirty = $list['batch_thirty'];


        if ($batch_ten) {

            $this->sendMail($this->oba_template_one, $batch_ten);
        }

        if ($batch_twenty) {

            //send to Advisor & licensee only
            $this->sendMail($this->oba_template_two, $batch_twenty);
        }

        if ($batch_thirty) {

            //send to Advisor & licensee only
            $this->sendMail($this->oba_template_three, $batch_thirty);
        }
    }

    public function buildUserList($list)
    {
        //escalate time based on inactivity - 7 days, 14 days , 19 days

        $batch_ten = [];
        $batch_twenty = [];
        $batch_thirty = [];

        foreach ($list as $key => $value) {
            # code...
            $today = Carbon::now();

            $user_created = $this->convertTime($value->updated_at);
            if ($user_created->diffInDays() >= 10 && $user_created->diffInDays() <= 11) {

                $batch_ten[] = array('type' => 'less_activity_10_days', 'user_id' => $value->user->id, 'name' => $value->user->first_name.' '.$value->user->last_name, 
                'email' => $value->user->email, 'manager' => $value->user->getmanager ? $value->user->getmanager : [], 'advisor' => $value->user->getadvisor ? $value->user->getadvisor : [], 'coach_name' => $value->user ? $value->user->first_name : '');
            }

            if ($user_created->diffInDays() >= 20 && $user_created->diffInDays() <= 21) {

                $batch_twenty[] = array('type' => 'less_activity_20_days', 'user_id' => $value->user->id, 'name' => $value->user->getadvisor ? $value->user->getadvisor->first_name.' '.$value->user->getadvisor->last_name : '', 
                'email' => $value->user->getadvisor ? $value->user->getadvisor->email : '', 'manager' => [], 'advisor' => $value->user->getadvisor ? $value->user->getadvisor : [], 'coach_name' => $value->user ? $value->user->first_name.' '.$value->user->last_name : '');
            }

            if ($user_created->diffInDays() >= 30 && $user_created->diffInDays() <= 31) {

                $batch_thirty[] = array('type' => 'less_activity_30_days', 'user_id' => $value->user->id, 'name' => $value->user->first_name.' '.$value->user->last_name, 
                'email' => 'joy@focused.com', 'manager' => [], 'advisor' => $value->user->getadvisor ? $value->user->getadvisor : [], 'coach_name' => $value->user ? $value->user->first_name.' '.$value->user->last_name : '');
            }

            // print_r($user_created->diffInDays().'----Days   ');
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

        $clean_template = preg_replace('/~advisorname~/', $replace_obj['advisor']? $replace_obj['advisor']->first_name : '', $clean_template);

        $template = [
            'subject' => $clean_subject,
            'template' => $clean_template
        ];

        return $template;
    }
}

