<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class CustomMailMessage extends MailMessage
{
    protected function formatLine($line) {
        if (is_array($line)) {
            return implode(' ', array_map('trim', $line));
        }
        // Just return without removing new lines.
        return trim($line);
    }

    public function splitInLines($steps){
        $arrSteps = explode("\n", $steps);
        if(!empty($arrSteps)){
            foreach ($arrSteps as $line) {
               $this->with($line);
            }
          }
         return $this; 
    }
}