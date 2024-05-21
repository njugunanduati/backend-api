<?php

namespace App\Jobs;

use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\CoachMailable;
use App\Mail\OnboardingMailable;
use App\Mail\OnboardingEmail;
use App\Mail\CoachNewStudent;
use App\Mail\CoachingPortalReport;
use App\Mail\CoachAttachmentMailable;
use App\Mail\GCCoachMailable;
use App\Mail\TeamMemberAttachmentMailable;
use App\Mail\GeneralMailable;
use Illuminate\Support\Str;
use App\Mail\TicketMailable;

class ProcessEmail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;
    protected $type;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3000;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details, $t = null)
    {
        $this->details = $details;
        $this->type = $t;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return (string) Str::uuid();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = null;

        switch ($this->type) {
            case 'attachment':
                $email = new CoachAttachmentMailable($this->details);
                break;
            case 'groupcoaching':
                $email = new GCCoachMailable($this->details);
                break;
            case 'newstudent':
                $email = new CoachNewStudent($this->details);
                break;
            case 'coachingportalreport':
                $email = new CoachingPortalReport($this->details);
                break;
            case 'onboarding':
                $email = new OnboardingMailable($this->details);
                break;
            case 'onboarding-email':
                $email = new OnboardingEmail($this->details);
                break;
            case 'assignment':
                $email = new TeamMemberAttachmentMailable($this->details);
                break;
            case 'ticket':
                $email = new TicketMailable($this->details);
                break;
            case 'general':
                $email = new GeneralMailable($this->details);
                break;
            default:
                $email = new CoachMailable($this->details);
                break;
        }

        Mail::to($this->details['to'])->send($email);
    }
}
