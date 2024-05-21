<?php

namespace App\Console;

use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\DeletePdfs;
use App\Console\Commands\PruneTokens;
use App\Console\Commands\DeleteUploads;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\OneDayMeetingReminder;
use App\Console\Commands\OneHourMeetingReminder;
use App\Console\Commands\OneDayCommitmentReminder;
use App\Console\Commands\ThreeDaysMeetingReminder;
use App\Console\Commands\OnboardingNoActivityNotify;
use App\Console\Commands\OnboardingLessActivityNotify;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GroupCoachingOneDayMeetingReminder;
use App\Console\Commands\GroupCoachingOneHourMeetingReminder;
use App\Console\Commands\GroupCoachingDayAfterMeetingReminder;
use App\Console\Commands\GroupCoachingThreeDayMeetingReminder;
use App\Console\Commands\GroupCoachingThreeDayMeetingCoachReminder;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        DeletePdfs::class,
        DeleteUploads::class,
        OneHourMeetingReminder::class,
        OneDayMeetingReminder::class,
        ThreeDaysMeetingReminder::class,
        GroupCoachingDayAfterMeetingReminder::class,
        GroupCoachingOneDayMeetingReminder::class,
        GroupCoachingOneHourMeetingReminder::class,
        GroupCoachingThreeDayMeetingReminder::class,
        GroupCoachingThreeDayMeetingCoachReminder::class,
        OnboardingLessActivityNotify::class,
        OnboardingNoActivityNotify::class,
        OneDayCommitmentReminder::class,
        PruneTokens::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('delete:pdfs')->daily()->onOneServer();
        $schedule->command('delete:uploads')->daily()->onOneServer();
        $schedule->command('tokens:prune')->daily()->onOneServer();//Prune all expired tokens older than 14 or 30 days based on user preference

        if(env('APP_ENV') == 'production'){
            $schedule->command('hourly:meetings')->everyMinute()->onOneServer(); // send coach meeting reminder an hour before
            $schedule->command('oneday:meetings')->daily()->onOneServer(); // send coach meeting reminder 24hrs before
            $schedule->command('threedays:meetings')->daily()->onOneServer(); // send coach meeting reminder 3 days before
            $schedule->command('onedayreminder:commitment')->daily()->onOneServer(); // send client/coach commitment reminder 1 days before

            // Group coaching Notifications & emails
            $schedule->command('group-coaching:three-days-before')->daily()->onOneServer(); // send meeting reminder to student 3 days before
            $schedule->command('group-coaching:three-days-before-coach')->daily()->onOneServer(); // send meeting reminder to coach 3 days before
            $schedule->command('group-coaching:day-before')->twiceDaily(); // send meeting reminder to student 1 day before
            $schedule->command('group-coaching:hour-before')->hourly()->onOneServer(); // send meeting reminder to student 1 hour before
            $schedule->command('group-coaching:day-after')->twiceDaily()->onOneServer(); // send meeting reminder to student 1 day after

            $schedule->command('onboarding:no_activity_notify')->daily()->onOneServer(); // send onboarding reminder to stakeholder 
            $schedule->command('onboarding:less_activity_notify')->daily()->onOneServer(); // send onboarding reminder to stakeholder 

        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
