<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 11.08.2017
 * Time: 17:01
 */

namespace App\Helpers;


class ModulePriority
{
    public static function calculate($impactProfit, $cost, $time, $moduleName = '')
    {
        $multiplier = 1;

        switch($moduleName) {
            case 'Usp':
                $multiplier = 5;
                break;
            case 'CompellingOffer':
                $multiplier = 3;
                break;
            case 'Strategy':
                $multiplier = 4;
                break;
            case 'Trust':
                $multiplier = 3;
                break;
            default:
                $multiplier = 1;
                break;
        }


        return round($impactProfit / $cost / $time / 100) * $multiplier;
    }
}
