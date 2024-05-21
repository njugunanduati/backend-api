<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\Models\LoginOtp;

use Illuminate\Console\Command;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class PruneTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes tokens expired within 14/30 days. Needed to trigger OTP requests';

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
        $cut_of = Carbon::now()->subDays(31);

        $otp_data = LoginOtp::where('otp_date', '>', $cut_of)->get();
        
        foreach ($otp_data as $key => $value) {
            $this->checkDate($value);
        }

        Log::info('Operation Ended');      
    }

    public function checkDate(Object $data){
        $date_created = $this->convertTime($data->otp_date);
        // print_r($date_created->diffInDays().'----------');

        if ($date_created->diffInDays() >= $data->remember_me) {
            $token_data = PersonalAccessToken::where('tokenable_id', $data->user_id);

            if($token_data){
                $token_data->delete();
                $data->delete();
            }
            
        }

    }

    public function convertTime($data_obj)
    {

        $date_created_obj = Carbon::createFromDate($data_obj);

        $user_date = Carbon::createMidnightDate($date_created_obj->year, $date_created_obj->month, $date_created_obj->day, 'UTC'); //created at date

        $user_date->setTime($date_created_obj->hour, $date_created_obj->minute);

        return $user_date;
    }


   
}
