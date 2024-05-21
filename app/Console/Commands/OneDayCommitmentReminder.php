<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\MeetingNoteReminder;

class OneDayCommitmentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onedayreminder:commitment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a reminder to client/coach a day before their commitment deadline expires';

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

        $today = new Carbon();
        $dt = $today->format('Y-m-d H:i:s');
        $fdt = $today->format('Y-m-d');
        $commitments = MeetingNoteReminder::where('reminder_date', '>', $dt)->where('send_reminder', 1)->orderBy('id')->get();
        
        foreach ($commitments as $key => $each) {
            $reminder_date = Carbon::createFromFormat('Y-m-d H:i:s', $each->reminder_date)->subDay()->format('Y-m-d');
            
            //If today happens to be the day to send the reminder
            if(($fdt == $reminder_date) && ($each->send_reminder == 1)){
                sendOneDayCommitmentReminder($each);
            }
        }
        
    }
}
