<?php

namespace App\Helpers;

use Cache;
use App\Models\Module;
use App\Models\ModuleQuestion;
use App\Models\ModuleQuestionResponse;
use App\Models\Assessment;

  class Helper {

	/**
	 * Return the environment specific list of memcached servers
	 */
	function memcachedServers(){

		if (env('APP_ENV') == 'local') {
			return [
				[
					'host' => memcached, 'port' => 11211, 'weight' => 100, //memcached is the name of the docker container running memcached
				],
			];
		}

		if (env('APP_ENV') == 'staging') {
			return [
				[
					'host' => '127.0.0.1', 'port' => 11211, 'weight' => 100,
				],
			];
		}

		if (env('APP_ENV') == 'production') {
			return [
				[
					'host' => '127.0.0.1', 'port' => 11211, 'weight' => 100,
				],
			];
		}

        if (env('APP_ENV') == 'development') {
			return [
				[
					'host' => '127.0.0.1', 'port' => 11211, 'weight' => 100,
				],
			];
		}

        if (env('APP_ENV') == 'uat') {
			return [
				[
					'host' => '127.0.0.1', 'port' => 11211, 'weight' => 100,
				],
			];
		}

	}

	public static function module_name_to_base_table_name($module_name) {
		return strtolower('m_' . preg_replace('/(?<!^)([A-Z])/', '_\\1', $module_name) . '_questions');
	}

	public static function module_name_to_full_table_name($module_name, $section) {
		return 'm_' . strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $module_name) . '_' . preg_replace('/(?<!^)([A-Z])/', '_\\1', $section) . 's');
	}

	public static function module_name_to_meta_table_name($module_name) {
		return 'm_' . strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $module_name) . '_meta');
	}

	public static function getModuleExpense($path){
		switch($path){
			case 'dgintro':
			return 0;
			case 'dgcontent':
			return 19.90;
			case 'dgwebsite':
			return 21.57;
			case 'dgemail':
			return 5.17;
			case 'dgseo':
			return 31.81;
			case 'dgadvertising':
			return 29.97;
			case 'dgsocial':
			return 19.23;
			case 'dgvideo':
			return 23.11;
			case 'dgmetrics':
			return 2.11;
			case 'dgmdp':
			return 0;
			case 'dgoffer':
			return 15.53;
			case 'dgupsell':
			return 10.01;
			case 'dgdownsell':
			return 11.11;
			case 'dgcampaign':
			return 9.93;
			case 'leads':
			return 12.22;
			case 'mdp':
			return 0;
			case 'products':
			return 7.77;
			case 'alliances':
			return 18.89;
			case 'bundling':
			return 9.87;
			case 'costs':
			return 0;
			case 'downsell':
			return 11.11;
			case 'campaign':
			return 9.93;
			case 'prices':
			return 0;
			case 'internet':
			return 21.13;
			case 'introduction':
			return 0;
			case 'upsell':
			return 10.01;
			case 'financials':
			return 0;
			case 'valuation':
			return 0;
			case 'foundational':
			return 0;
			case 'sales':
			return 0;
            case 'salesteam':
			return 29.77;
			case 'salesgeneral':
			return 9.03;
			case 'salesmanager':
			return 11.33;
			case 'salescompensation':
			return 14.65;
			case 'salessuperstars':
			return 28.37;
			case 'salestraining':
			return 15.21;
			case 'salesprospecting':
			return 12.49;
			case 'salesclients':
			return 17.77;
			case 'salestrade':
			return 26.99;
			case 'salesdm':
			return 13.47;
			case 'salesclosing':
			return 15.22;
			case 'salesorder':
			return 11.16;
			case 'salesremorse':
			return 12.34;
			case 'salesreps':
			return 0;
			case 'strategy':
			return 0;
			case 'advertising':
			return 35.33;
			case 'appointments':
			return 5.11;
			case 'followupclose':
			return 7.22;
			case 'initialclose':
			return 0;
			case 'formercustomers':
			return 4.94;
			case 'longevity':
			return 5.13;
			case 'mail':
			return 22.23;
			case 'offer':
			return 15.53;
			case 'policies':
			return 7.21;
			case 'publicity':
			return 6.09;
			case 'purchase':
			return 6.22;
			case 'referral':
			return 19.91;
			case 'scripts':
			return 7.94;
			case 'trust':
			return 5.12;
			default:
			return 0;
		}
	}

	public static function getModuleIncrease($module_path,$assessment_id){
        return Cache::tags('assessment_'.$assessment_id)->get('module_increase_'.$module_path.'_assessment_'.$assessment_id);
	}
	
	public static function getModuleImpact($module_path,$assessment_id){
        return Cache::tags('assessment_'.$assessment_id)->get('module_impact_'.$module_path.'_assessment_'.$assessment_id);
    }

	public static function getMaxIncrease($assessment_id){
    	$increase = array(
			floatval(Helper::getModuleIncrease('upsell', $assessment_id)),
			floatval(Helper::getModuleIncrease('internet', $assessment_id)),
			floatval(Helper::getModuleIncrease('prices', $assessment_id)),
			floatval(Helper::getModuleIncrease('campaign', $assessment_id)),
			floatval(Helper::getModuleIncrease('downsell', $assessment_id)),
			floatval(Helper::getModuleIncrease('bundling', $assessment_id)),
			floatval(Helper::getModuleIncrease('alliances', $assessment_id)),
			floatval(Helper::getModuleIncrease('products', $assessment_id)),
			floatval(Helper::getModuleIncrease('leads', $assessment_id)),
			floatval(Helper::getModuleIncrease('mdp', $assessment_id)),
			floatval(Helper::getModuleIncrease('strategy', $assessment_id)),
			floatval(Helper::getModuleIncrease('advertising', $assessment_id)),
			floatval(Helper::getModuleIncrease('appointments', $assessment_id)),
			floatval(Helper::getModuleIncrease('followupclose', $assessment_id)),
			floatval(Helper::getModuleIncrease('initialclose', $assessment_id)),
			floatval(Helper::getModuleIncrease('formercustomers', $assessment_id)),
			floatval(Helper::getModuleIncrease('longevity', $assessment_id)),
			floatval(Helper::getModuleIncrease('mail', $assessment_id)),
			floatval(Helper::getModuleIncrease('offer', $assessment_id)),
			floatval(Helper::getModuleIncrease('policies', $assessment_id)),
			floatval(Helper::getModuleIncrease('publicity', $assessment_id)),
			floatval(Helper::getModuleIncrease('purchase', $assessment_id)),
			floatval(Helper::getModuleIncrease('referral', $assessment_id)),
			floatval(Helper::getModuleIncrease('scripts', $assessment_id)),
			floatval(Helper::getModuleIncrease('strategy', $assessment_id)),
			floatval(Helper::getModuleIncrease('trust', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesteam', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesgeneral', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesmanager', $assessment_id)),
			floatval(Helper::getModuleIncrease('salescompensation', $assessment_id)),
			floatval(Helper::getModuleIncrease('salessuperstars', $assessment_id)),
			floatval(Helper::getModuleIncrease('salestraining', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesprospecting', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesclients', $assessment_id)),
			floatval(Helper::getModuleIncrease('salestrade', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesdm', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesclosing', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesorder', $assessment_id)),
			floatval(Helper::getModuleIncrease('salesremorse', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgcontent', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgwebsite', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgemail', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgseo', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgadvertising', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgsocial', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgvideo', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgmetrics', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgmdp', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgoffer', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgupsell', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgdownsell', $assessment_id)),
			floatval(Helper::getModuleIncrease('dgcampaign', $assessment_id)),
		);
		$maxIncrease = max($increase);
    	return $maxIncrease;
	}
	
	public static function addExpense($path, $impact){
        $expense = Helper::getModuleExpense($path);
        if(floatval($expense) > 0 && floatval($impact) > 0){
            // Add expense to impact percentage
            return (1 - (floatval($expense) / 100)) * intval($impact);
        }
        return floatval($impact);
    }
    
    public static function calculateExpectedIncreaseRevenue($assessment_id) {

        $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
        $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
        $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
        $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
        $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
        $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
        $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
        $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
        $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
        $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));

        $a = (floatval($mdp) / 100);
        $b = (floatval($offer) / 100);
        $d = (floatval($upsell) / 100);
        $e = (floatval($bundling) / 100);
        $f = (floatval($downsell) / 100);
        $g = (floatval($products) / 100);
        $h = (floatval($alliances) / 100);
        $i = (floatval($campaign) / 100);
        $j = (floatval($leads) / 100);
        $k = (floatval($internet) / 100);
        $newimpact = (
            (100
            * (1 + $a)
            * (1 + $b)
            * (1 + $d)
            * (1 + $e)
            * (1 + $f)
            * (1 + $g)
            * (1 + $h)
            * (1 + $i)
            * (1 + $j)
            * ((1 + $k) / 100)) - 1);

        return $newimpact;

    }


    public static function processDigitalIncrease($assessment_id, $module_path) {

        $result = Cache::tags('assessment_'.$assessment_id)->remember('module_increase_'.$module_path.'_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module_path) {
            
            $impact = Helper::getModuleImpact($module_path,$assessment_id);

            switch ($module_path) {
                case 'dgcontent':
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    return Helper::addExpense('dgcontent', $impact);  
                case 'dgwebsite':{
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $newimpact = ((100 * (1 + $a) * ((1 + $b) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    
                    return round($imp,2);
                }
                case 'dgemail':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * ((1 + $c) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgseo':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * ((1 + $d) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgadvertising':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * ((1 + $e) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgsocial':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * ((1 + $f) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgvideo':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * ((1 + $g) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgmetrics':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * ((1 + $h) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'prices':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * (1 + $h) * ((1 + $i) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgmdp':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $dgmdp = Helper::addExpense('dgmdp', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $j = (floatval($dgmdp) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * ((1 + $j) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgoffer':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $dgmdp = Helper::addExpense('dgmdp', Helper::getModuleImpact('dgmdp',$assessment_id));
                    $dgoffer = Helper::addExpense('dgoffer', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $j = (floatval($dgmdp) / 100);
                    $k = (floatval($dgoffer) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * ((1 + $k) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgupsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $dgmdp = Helper::addExpense('dgmdp', Helper::getModuleImpact('dgmdp',$assessment_id));
                    $dgoffer = Helper::addExpense('dgoffer', Helper::getModuleImpact('dgoffer',$assessment_id));
                    $dgupsell = Helper::addExpense('dgupsell', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $j = (floatval($dgmdp) / 100);
                    $k = (floatval($dgoffer) / 100);
                    $l = (floatval($dgupsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * ((1 + $l) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgdownsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $dgmdp = Helper::addExpense('dgmdp', Helper::getModuleImpact('dgmdp',$assessment_id));
                    $dgoffer = Helper::addExpense('dgoffer', Helper::getModuleImpact('dgoffer',$assessment_id));
                    $dgupsell = Helper::addExpense('dgupsell', Helper::getModuleImpact('dgupsell',$assessment_id));
                    $dgdownsell = Helper::addExpense('dgdownsell', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $j = (floatval($dgmdp) / 100);
                    $k = (floatval($dgoffer) / 100);
                    $l = (floatval($dgupsell) / 100);
                    $m = (floatval($dgdownsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * ((1 + $m) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'dgcampaign':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $dgcontent = Helper::addExpense('dgcontent', Helper::getModuleImpact('dgcontent',$assessment_id));
                    $dgwebsite = Helper::addExpense('dgwebsite', Helper::getModuleImpact('dgwebsite',$assessment_id));
                    $dgemail = Helper::addExpense('dgemail', Helper::getModuleImpact('dgemail',$assessment_id));
                    $dgseo = Helper::addExpense('dgseo', Helper::getModuleImpact('dgseo',$assessment_id));
                    $dgadvertising = Helper::addExpense('dgadvertising', Helper::getModuleImpact('dgadvertising',$assessment_id));
                    $dgsocial = Helper::addExpense('dgsocial', Helper::getModuleImpact('dgsocial',$assessment_id));
                    $dgvideo = Helper::addExpense('dgvideo', Helper::getModuleImpact('dgvideo',$assessment_id));
                    $dgmetrics = Helper::addExpense('dgmetrics', Helper::getModuleImpact('dgmetrics',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $dgmdp = Helper::addExpense('dgmdp', Helper::getModuleImpact('dgmdp',$assessment_id));
                    $dgoffer = Helper::addExpense('dgoffer', Helper::getModuleImpact('dgoffer',$assessment_id));
                    $dgupsell = Helper::addExpense('dgupsell', Helper::getModuleImpact('dgupsell',$assessment_id));
                    $dgdownsell = Helper::addExpense('dgdownsell', Helper::getModuleImpact('dgdownsell',$assessment_id));
                    $dgcampaign = Helper::addExpense('dgcampaign', $impact);

                    $a = (floatval($dgcontent) / 100);
                    $b = (floatval($dgwebsite) / 100);
                    $c = (floatval($dgemail) / 100);
                    $d = (floatval($dgseo) / 100);
                    $e = (floatval($dgadvertising) / 100);
                    $f = (floatval($dgsocial) / 100);
                    $g = (floatval($dgvideo) / 100);
                    $h = (floatval($dgmetrics) / 100);
                    $i = (floatval($prices) / 100);
                    $j = (floatval($dgmdp) / 100);
                    $k = (floatval($dgoffer) / 100);
                    $l = (floatval($dgupsell) / 100);
                    $m = (floatval($dgdownsell) / 100);
                    $n = (floatval($dgcampaign) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * ((1 + $n) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
                default:
                    break;
            }
        });
        return $result;
    }

    public static function processJS12Increase($assessment_id, $module_path) {

        $result = Cache::tags('assessment_'.$assessment_id)->remember('module_increase_'.$module_path.'_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module_path) {
            
            $impact = Helper::getModuleImpact($module_path,$assessment_id);

            switch ($module_path) {
                case 'mdp':
                    return $impact;
                case 'offer':{
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $newimpact = ((100 * (1 + $a) * ((1 + $b) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    
                    return round($imp,2);
                }
                case 'prices':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * ((1 + $c) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'upsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * ((1 + $d) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'bundling':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * ((1 + $e) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'downsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * ((1 + $f) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'products':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $g = (floatval($products) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * ((1 + $g) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'alliances':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $alliances = Helper::addExpense('alliances', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $g = (floatval($products) / 100);
                    $h = (floatval($alliances) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * ((1 + $h) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'campaign':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $campaign = Helper::addExpense('campaign', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $g = (floatval($products) / 100);
                    $h = (floatval($alliances) / 100);
                    $i = (floatval($campaign) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * (1 + $h) * ((1 + $i) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'leads':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $leads = Helper::addExpense('leads', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $g = (floatval($products) / 100);
                    $h = (floatval($alliances) / 100);
                    $i = (floatval($campaign) / 100);
                    $j = (floatval($leads) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * ((1 + $j) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'internet':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $internet = Helper::addExpense('internet', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($offer) / 100);
                    $c = (floatval($prices) / 100);
                    $d = (floatval($upsell) / 100);
                    $e = (floatval($bundling) / 100);
                    $f = (floatval($downsell) / 100);
                    $g = (floatval($products) / 100);
                    $h = (floatval($alliances) / 100);
                    $i = (floatval($campaign) / 100);
                    $j = (floatval($leads) / 100);
                    $k = (floatval($internet) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * ((1 + $k) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            default:
                break;
            }
        });
        return $result;
	}

    public static function processJS40Increase($assessment_id, $module_path) {

        $result = Cache::tags('assessment_'.$assessment_id)->remember('module_increase_'.$module_path.'_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module_path) {
            
            $impact = Helper::getModuleImpact($module_path,$assessment_id);

            switch ($module_path) {
                case 'mdp':
                    return $impact;
                case 'strategy':{
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $newimpact = ((100 * (1 + $a) * ((1 + $b) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    
                    return round($imp,2);
                }
                case 'trust':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * ((1 + $c) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'policies':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * ((1 + $d) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'leads':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * ((1 + $e) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'alliances':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * ((1 + $f) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'referral':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * ((1 + $g) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'internet':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * ((1 + $h) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'publicity':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * (1 + $h) * ((1 + $i) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'mail':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * ((1 + $j) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'advertising':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * ((1 + $k) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'offer':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * ((1 + $l) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'campaign':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * ((1 + $m) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'scripts':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * ((1 + $n) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'initialclose':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * ((1 + $o) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'followupclose':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * ((1 + $p) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'formercustomers':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * ((1 + $q) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'salesteam':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * ((1 + $r) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'appointments':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * ((1 + $s) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'downsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * ((1 + $t) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'products':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $u = (floatval($products) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * ((1 + $u) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'purchase':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $u = (floatval($products) / 100);
                    $v = (floatval($purchase) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * ((1 + $v) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'bundling':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $bundling = Helper::addExpense('bundling', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $u = (floatval($products) / 100);
                    $v = (floatval($purchase) / 100);
                    $w = (floatval($bundling) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * ((1 + $w) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'upsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $upsell = Helper::addExpense('upsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $u = (floatval($products) / 100);
                    $v = (floatval($purchase) / 100);
                    $w = (floatval($bundling) / 100);
                    $x = (floatval($upsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * ((1 + $x) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'longevity':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', Helper::getModuleImpact('salesteam',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($campaign) / 100);
                    $n = (floatval($scripts) / 100);
                    $o = (floatval($initialclose) / 100);
                    $p = (floatval($followupclose) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($salesteam) / 100);
                    $s = (floatval($appointments) / 100);
                    $t = (floatval($downsell) / 100);
                    $u = (floatval($products) / 100);
                    $v = (floatval($purchase) / 100);
                    $w = (floatval($bundling) / 100);
                    $x = (floatval($upsell) / 100);
                    $y = (floatval($longevity) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * ((1 + $y) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'prices':
                {
                    return 0;
                }
            default:
                break;
            }
        });
        return $result;
	}

	public static function processIncrease($assessment_id, $module_path) {

        $result = Cache::tags('assessment_'.$assessment_id)->remember('module_increase_'.$module_path.'_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module_path) {
            
            $impact = Helper::getModuleImpact($module_path,$assessment_id);

            switch ($module_path) {
                case 'mdp':
                    return $impact;
                case 'strategy':{
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $newimpact = ((100 * (1 + $a) * ((1 + $b) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    
                    return round($imp,2);
                }
                case 'trust':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * ((1 + $c) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'policies':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * ((1 + $d) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'leads':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * ((1 + $e) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'alliances':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * ((1 + $f) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'referral':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * ((1 + $g) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'internet':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * ((1 + $h) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'publicity':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $newimpact = ((100 * (1 + $a) * (1 + $b) * (1 + $c) * (1 + $d) * (1 + $e) * (1 + $f) * (1 + $g) * (1 + $h) * ((1 + $i) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'mail':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * ((1 + $j) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'advertising':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * ((1 + $k) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'offer':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * ((1 + $l) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'scripts':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * ((1 + $m) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'initialclose':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * ((1 + $n) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'followupclose':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * ((1 + $o) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'campaign':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * ((1 + $p) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'formercustomers':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * ((1 + $q) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'appointments':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * ((1 + $r) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'downsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * ((1 + $s) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'products':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * ((1 + $t) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'purchase':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * ((1 + $u) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'prices':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * ((1 + $v) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'upsell':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * ((1 + $w) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'longevity':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * ((1 + $x) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'bundling':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * ((1 + $y) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    return round($imp,2);
                }
            case 'salesteam':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesteam = Helper::addExpense('salesteam', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesteam) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * ((1 + $z) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesgeneral':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * ((1 + $z) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesmanager':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * ((1 + $zz) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salescompensation':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * ((1 + $aa) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salessuperstars':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * ((1 + $ab) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salestraining':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * ((1 + $ac) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesprospecting':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * ((1 + $ad) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesclients':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * ((1 + $ae) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salestrade':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', Helper::getModuleImpact('salesclients',$assessment_id));
                    $salestrade = Helper::addExpense('salestrade', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $af = (floatval($salestrade) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * (1 + $ae)
                        * ((1 + $af) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesdm':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', Helper::getModuleImpact('salesclients',$assessment_id));
                    $salestrade = Helper::addExpense('salestrade', Helper::getModuleImpact('salestrade',$assessment_id));
                    $salesdm = Helper::addExpense('salesdm', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $af = (floatval($salestrade) / 100);
                    $ag = (floatval($salesdm) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * (1 + $ae)
                        * (1 + $af)
                        * ((1 + $ag) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesclosing':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', Helper::getModuleImpact('salesclients',$assessment_id));
                    $salestrade = Helper::addExpense('salestrade', Helper::getModuleImpact('salestrade',$assessment_id));
                    $salesdm = Helper::addExpense('salesdm', Helper::getModuleImpact('salesdm',$assessment_id));
                    $salesclosing = Helper::addExpense('salesclosing', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $af = (floatval($salestrade) / 100);
                    $ag = (floatval($salesdm) / 100);
                    $ah = (floatval($salesclosing) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * (1 + $ae)
                        * (1 + $af)
                        * (1 + $ag)
                        * ((1 + $ah) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesorder':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', Helper::getModuleImpact('salesclients',$assessment_id));
                    $salestrade = Helper::addExpense('salestrade', Helper::getModuleImpact('salestrade',$assessment_id));
                    $salesdm = Helper::addExpense('salesdm', Helper::getModuleImpact('salesdm',$assessment_id));
                    $salesclosing = Helper::addExpense('salesclosing', Helper::getModuleImpact('salesclosing',$assessment_id));
                    $salesorder = Helper::addExpense('salesorder', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $af = (floatval($salestrade) / 100);
                    $ag = (floatval($salesdm) / 100);
                    $ah = (floatval($salesclosing) / 100);
                    $ai = (floatval($salesorder) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * (1 + $ae)
                        * (1 + $af)
                        * (1 + $ag)
                        * (1 + $ah)
                        * ((1 + $ai) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
            case 'salesremorse':
                {
                    if(floatval($impact) == 0){
                        return 0;
                    }
                    $mdp = Helper::addExpense('mdp', Helper::getModuleImpact('mdp',$assessment_id));
                    $strategy = Helper::addExpense('strategy', Helper::getModuleImpact('strategy',$assessment_id));
                    $trust = Helper::addExpense('trust', Helper::getModuleImpact('trust',$assessment_id));
                    $policies = Helper::addExpense('policies', Helper::getModuleImpact('policies',$assessment_id));
                    $leads = Helper::addExpense('leads', Helper::getModuleImpact('leads',$assessment_id));
                    $alliances = Helper::addExpense('alliances', Helper::getModuleImpact('alliances',$assessment_id));
                    $referral = Helper::addExpense('referral', Helper::getModuleImpact('referral',$assessment_id));
                    $internet = Helper::addExpense('internet', Helper::getModuleImpact('internet',$assessment_id));
                    $publicity = Helper::addExpense('publicity', Helper::getModuleImpact('publicity',$assessment_id));
                    $mail = Helper::addExpense('mail', Helper::getModuleImpact('mail',$assessment_id));
                    $advertising = Helper::addExpense('advertising', Helper::getModuleImpact('advertising',$assessment_id));
                    $offer = Helper::addExpense('offer', Helper::getModuleImpact('offer',$assessment_id));
                    $scripts = Helper::addExpense('scripts', Helper::getModuleImpact('scripts',$assessment_id));
                    $initialclose = Helper::addExpense('initialclose', Helper::getModuleImpact('initialclose',$assessment_id));
                    $followupclose = Helper::addExpense('followupclose', Helper::getModuleImpact('followupclose',$assessment_id));
                    $campaign = Helper::addExpense('campaign', Helper::getModuleImpact('campaign',$assessment_id));
                    $formercustomers = Helper::addExpense('formercustomers', Helper::getModuleImpact('formercustomers',$assessment_id));
                    $appointments = Helper::addExpense('appointments', Helper::getModuleImpact('appointments',$assessment_id));
                    $downsell = Helper::addExpense('downsell', Helper::getModuleImpact('downsell',$assessment_id));
                    $products = Helper::addExpense('products', Helper::getModuleImpact('products',$assessment_id));
                    $purchase = Helper::addExpense('purchase', Helper::getModuleImpact('purchase',$assessment_id));
                    $prices = Helper::addExpense('prices', Helper::getModuleImpact('prices',$assessment_id));
                    $upsell = Helper::addExpense('upsell', Helper::getModuleImpact('upsell',$assessment_id));
                    $longevity = Helper::addExpense('longevity', Helper::getModuleImpact('longevity',$assessment_id));
                    $bundling = Helper::addExpense('bundling', Helper::getModuleImpact('bundling',$assessment_id));
                    $salesgeneral = Helper::addExpense('salesgeneral', Helper::getModuleImpact('salesgeneral',$assessment_id));
                    $salesmanager = Helper::addExpense('salesmanager', Helper::getModuleImpact('salesmanager',$assessment_id));
                    $salescompensation = Helper::addExpense('salescompensation', Helper::getModuleImpact('salescompensation',$assessment_id));
                    $salessuperstars = Helper::addExpense('salessuperstars', Helper::getModuleImpact('salessuperstars',$assessment_id));
                    $salestraining = Helper::addExpense('salestraining', Helper::getModuleImpact('salestraining',$assessment_id));
                    $salesprospecting = Helper::addExpense('salesprospecting', Helper::getModuleImpact('salesprospecting',$assessment_id));
                    $salesclients = Helper::addExpense('salesclients', Helper::getModuleImpact('salesclients',$assessment_id));
                    $salestrade = Helper::addExpense('salestrade', Helper::getModuleImpact('salestrade',$assessment_id));
                    $salesdm = Helper::addExpense('salesdm', Helper::getModuleImpact('salesdm',$assessment_id));
                    $salesclosing = Helper::addExpense('salesclosing', Helper::getModuleImpact('salesclosing',$assessment_id));
                    $salesorder = Helper::addExpense('salesorder', Helper::getModuleImpact('salesorder',$assessment_id));
                    $salesremorse = Helper::addExpense('salesremorse', $impact);

                    $a = (floatval($mdp) / 100);
                    $b = (floatval($strategy) / 100);
                    $c = (floatval($trust) / 100);
                    $d = (floatval($policies) / 100);
                    $e = (floatval($leads) / 100);
                    $f = (floatval($alliances) / 100);
                    $g = (floatval($referral) / 100);
                    $h = (floatval($internet) / 100);
                    $i = (floatval($publicity) / 100);
                    $j = (floatval($mail) / 100);
                    $k = (floatval($advertising) / 100);
                    $l = (floatval($offer) / 100);
                    $m = (floatval($scripts) / 100);
                    $n = (floatval($initialclose) / 100);
                    $o = (floatval($followupclose) / 100);
                    $p = (floatval($campaign) / 100);
                    $q = (floatval($formercustomers) / 100);
                    $r = (floatval($appointments) / 100);
                    $s = (floatval($downsell) / 100);
                    $t = (floatval($products) / 100);
                    $u = (floatval($purchase) / 100);
                    $v = (floatval($prices) / 100);
                    $w = (floatval($upsell) / 100);
                    $x = (floatval($longevity) / 100);
                    $y = (floatval($bundling) / 100);
                    $z = (floatval($salesgeneral) / 100);
                    $zz = (floatval($salesmanager) / 100);
                    $aa = (floatval($salescompensation) / 100);
                    $ab = (floatval($salessuperstars) / 100);
                    $ac = (floatval($salestraining) / 100);
                    $ad = (floatval($salesprospecting) / 100);
                    $ae = (floatval($salesclients) / 100);
                    $af = (floatval($salestrade) / 100);
                    $ag = (floatval($salesdm) / 100);
                    $ah = (floatval($salesclosing) / 100);
                    $ai = (floatval($salesorder) / 100);
                    $aj = (floatval($salesremorse) / 100);
                    $newimpact = ((100
                        * (1 + $a)
                        * (1 + $b)
                        * (1 + $c)
                        * (1 + $d)
                        * (1 + $e)
                        * (1 + $f)
                        * (1 + $g)
                        * (1 + $h)
                        * (1 + $i)
                        * (1 + $j)
                        * (1 + $k)
                        * (1 + $l)
                        * (1 + $m)
                        * (1 + $n)
                        * (1 + $o)
                        * (1 + $p)
                        * (1 + $q)
                        * (1 + $r)
                        * (1 + $s)
                        * (1 + $t)
                        * (1 + $u)
                        * (1 + $v)
                        * (1 + $w)
                        * (1 + $x)
                        * (1 + $y)
                        * (1 + $z)
                        * (1 + $zz)
                        * (1 + $aa)
                        * (1 + $ab)
                        * (1 + $ac)
                        * (1 + $ad)
                        * (1 + $ae)
                        * (1 + $af)
                        * (1 + $ag)
                        * (1 + $ah)
                        * (1 + $ai)
                        * ((1 + $aj) / 100)) - 1);
                    $imp = ($newimpact * 100);
                    // Helper::saveSalesIncrease();
                    return round($imp,2);
                    
                }
                default:
                    break;
            }
        });
        return $result;
	}

	public static function switch_table_section($table_name, $section) {
		if(strpos($table_name, 'question')) {
			$base_table_name = preg_replace("/\_question.+/", "", $table_name);
		} else if (strpos($table_name, 'action')) {
			$base_table_name = preg_replace("/\_action.+/", "", $table_name);
		}
		switch ($section) {
			case 'note':
				return $base_table_name . '_question_notes';
				break;
			case 'option':
				return $base_table_name . '_question_options';
				break;
			case 'response':
				return $base_table_name . '_question_responses';
				break;
			case 'split':
				return $base_table_name . '_question_splits';
				break;
			case 'question':
				return $base_table_name . '_questions';
				break;
			case 'comment':
				return $base_table_name . '_question_comment';
				break;
			case 'action':
				return $base_table_name . '_actions';
				break;
			case 'action_response':
				return $base_table_name . '_action_responses';
				break;
		}
	}

	public static function sort_date_usort($a, $b) {
		$a_date = strtotime($a['date']);
		$b_date = strtotime($b['date']);
		return ($b_date - $a_date);
	}

	public static function sort_priorities($b, $a) {
	    return $a["priority"] - $b["priority"];
	}

	public static function replace_report_variables($text, $assessment_id) {
		while (strpos($text, '{$') !== FALSE) {
			$variable = substr($text, strpos($text, '{$'), (strpos($text, '}', strpos($text, '{$')) - strpos($text, '{$'))+1);
			$replacement = Helper::handle_variable($variable, $assessment_id);
			$text = str_replace($variable, $replacement, $text);
		}
		while (strpos($text, '{%') !== FALSE) {
			$variable = substr($text, strpos($text, '{%'), (strpos($text, '}', strpos($text, '{%')) - strpos($text, '{%'))+1);
			$variable_fix = str_replace('%', '$', $variable);
			$replacement = Helper::handle_variable($variable_fix, $assessment_id);
			$text = str_replace($variable, $replacement, $text);
		}
		$text = str_replace('	', ' ', $text);
		return $text;
	}

	public static function handle_variable($variable, $assessment_id) {
		if(strpos($variable, '_impact') !== FALSE) {
			$module_name = substr($variable, strpos($variable, '{$')+2, (strpos($variable, '_impact', strpos($variable, '{$')) - strpos($variable, '{$'))-2);
			$module = new Module(Helper::module_name_to_full_table_name($module_name, 'Question'));
			return $module->impactPercentage($assessment_id);
		} else if (strpos($variable, '_one_year_improvement') !== FALSE) {
			$module_name = substr($variable, strpos($variable, '{$')+2, (strpos($variable, '_one_year_improvement', strpos($variable, '{$')) - strpos($variable, '{$'))-2);
			$module = new Module(Helper::module_name_to_full_table_name($module_name, 'Question'));
			return number_format($module->impactProfit($assessment_id));
		} else if (strpos($variable, '_five_year_improvement') !== FALSE) {
			$module_name = substr($variable, strpos($variable, '{$')+2, (strpos($variable, '_five_year_improvement', strpos($variable, '{$')) - strpos($variable, '{$'))-2);
			$module = new Module(Helper::module_name_to_full_table_name($module_name, 'Question'));
			return number_format($module->impactProfit($assessment_id)*5);
		} else if (strpos($variable, 'company_name}') !== FALSE) {
			return Assessment::find($assessment_id)->company->company_name;
		} else if (strpos($variable, 'client_ltv_revenue}') !== FALSE) {
			$question = new ModuleQuestion();
	        $question->setTable(Helper::module_name_to_full_table_name('Foundational', 'Question'));
	        $responses = new ModuleQuestionResponse;
	        $responses->setTable(Helper::module_name_to_full_table_name('Foundational', 'QuestionResponse'));
	        $client_ltv_revenue_id = $question->where('question_text', 'How much revenue will you make from your average client over the lifetime of their buying relationship with you? (LTV or Lifetime Value)')->first()->id;
	        if ($responses->where('question_id', $client_ltv_revenue_id)->where('assessment_id', $assessment_id)->count()) {
	        	$client_ltv_revenue = $responses->where('question_id', $client_ltv_revenue_id)->where('assessment_id', $assessment_id)->first()->response;
	        	return number_format($client_ltv_revenue);
	        } else {
	        	return '$XXX';
	        }
	        
		} else if (strpos($variable, 'client_ltv_profit}') !== FALSE) {
			$question = new ModuleQuestion();
	        $question->setTable(Helper::module_name_to_full_table_name('Foundational', 'Question'));
	        $responses = new ModuleQuestionResponse;
	        $responses->setTable(Helper::module_name_to_full_table_name('Foundational', 'QuestionResponse'));
	        $client_ltv_revenue_id = $question->where('question_text', 'How much revenue will you make from your average client over the lifetime of their buying relationship with you? (LTV or Lifetime Value)')->first()->id;
	        if ($responses->where('question_id', $client_ltv_revenue_id)->where('assessment_id', $assessment_id)->count()) {
		        $client_ltv_revenue = $responses->where('question_id', $client_ltv_revenue_id)->where('assessment_id', $assessment_id)->first()->response;
		        $gross_margin = Assessment::find($assessment_id)->grossProfitMargin();
		        return number_format(round($client_ltv_revenue*($gross_margin/100)));
		    } else {
		    	return '$XXX';
		    }
	    } else if (strpos($variable, 'profit_percentage}') !== FALSE) {
	    	return number_format(Assessment::find($assessment_id)->totalImpactProfit()/Assessment::find($assessment_id)->netProfit()*100);
	   	} else if (strpos($variable, 'currency_symbol}') !== FALSE) {
	    	return Assessment::find($assessment_id)->currency_symbol;
	   	} else if (strpos($variable, 'profit_amount}') !== FALSE) {
	   		return number_format(Assessment::find($assessment_id)->totalImpactProfit());
	   	} else if (strpos($variable, 'profit_five_year_amount}') !== FALSE) {
	   		return number_format(Assessment::find($assessment_id)->totalImpactProfit()*5);
	   	} else if (strpos($variable, 'business_value_increase}') !== FALSE) {
	   		return number_format(Assessment::find($assessment_id)->totalImpactValuation());
	   	} else if (strpos($variable, 'full_one_year}') !== FALSE) {
	   		return number_format(Assessment::find($assessment_id)->totalImpactValuation()+Assessment::find($assessment_id)->totalImpactProfit());
		} else {
			return 'XXXXX' . substr($variable,2,50);
		}
	}

    public static function getReactAppBaseOrigin()
    {
        $env = env('APP_ENV');

        switch ($env) {
            case 'local':
                return env('REACT_APP_LOCAL');
            case 'development':
                return env('REACT_APP_DEV');
            case 'staging':
                return env('REACT_APP_STAGING');
            case 'uat':
                return env('REACT_APP_UAT');
            case 'production':
                return env('REACT_APP_PROD');
            default:
                return env('REACT_APP_LOCAL');  // local environments
        }
    }
}