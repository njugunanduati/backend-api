<?php


namespace App\Helpers;
use Illuminate\Support\Str;

if (!ini_get("auto_detect_line_endings")) {
    ini_set("auto_detect_line_endings", '1');
}

use Log;
use League\Csv\Reader;


class CSVReader
{

    protected $file;
    public function __construct($file)
    {

        
    }


    public static function read($file)
    {
        $path = $file->path();
        $reader = Reader::createFromPath("$path", 'r');

        $headers = [];
        $content = [];
        foreach ($reader as $index => $row) {
            if ($index == 0) {
                $headers = $row;
            } else {
                $cleanRow = [];
                foreach($row as $cellIndex=>$cell){
                    if($cell!==""){
                        // Clean Up the CSV Header titles
                        $key = implode("_",explode(" ",preg_replace('/[^\x00-\x7F]+/', '', Str::lower($headers[$cellIndex]))));
                        $cleanRow[$key] = $cell;
                    }
                }
                if(sizeOf($cleanRow)>0){
                    array_push($content,$cleanRow);
                } 
                else{
                    unset($content[$index]);
                } 
            }
        }

        return($content);
    }
}
