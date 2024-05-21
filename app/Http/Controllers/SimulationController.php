<?php

namespace App\Http\Controllers;

use Http;
use Mail;
use DateTime;
use Validator;

use Carbon\Carbon;
use App\Models\User;
use PHPUnit\Util\Json;
use App\Jobs\ProcessEmail;
use App\Models\Simulation;
use Illuminate\Support\Str;
use App\Mail\SimulationUser;
use App\Mail\SimulationUserOld;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Mail\SimulationCoach;
use App\Mail\SimulationCoachOld;
use App\Models\UserSimulator;
use Illuminate\Support\Facades\DB;
use App\Mail\SimulationCoachMeetRequest;
use App\Mail\SimulationCoachMeetRequestOld;
use App\Http\Resources\SimulationResource;
use App\Http\Resources\UserSimulatorResource;

class SimulationController extends Controller
{
    use ApiResponses;


    /**
     * Get simulation data from db old
     *
     * @return \Illuminate\Http\Response
     */
    public function getOld(Request $request)
    {
        try {
            
            $rules = [
                'useremail' => 'required|exists:simulations,email',
            ];

            $messages = [
                'useremail.required' => 'The email is required',
                'useremail.exists' => 'That email does not exist',
            ];

            $email = base64_decode($request->get('useremail'));

            $validator = Validator::make(['useremail' => $email], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $findSimulationResults = Simulation::whereEmail($email)->first();
            $transform = new SimulationResource($findSimulationResults);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Get simulation data from db
     *
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        // Fetch and return the data to be used on the public report page
        try {
            $rules = [
                'report_token' => 'required',
            ];

            $messages = [
                'report_token.required' => 'The token is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            // Validate the unique token
            $binaryUuid = pack('H*', str_replace('-', '', $request->report_token));
            $sim_results = Simulation::where('uuid', $binaryUuid)->first();
            // dd($sim_results);

            if (!$sim_results) {
                return $this->errorResponse('Invalid Token or User.', 401);
            }

            $transform = new SimulationResource($sim_results);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Something went wrong.', 404);
        }
    }

        /**
     * Add a new simulation to the db.
     *
     * @return \Illuminate\Http\Response
     */
    public function postOld(Request $request)
    {
        try {
            $data = $request->all();
            $dt = new DateTime();
            $simulation_date = $dt->format('Y-m-d H:i:s');
            $findSimulationResults = Simulation::whereEmail($data['useremail'])->first();

            if (isset($findSimulationResults)) {
                // check the time interval between the requests
                $last_simulation_time = $findSimulationResults->updated_at;
                $check_time_interval = $this->verifyTimeInterval($last_simulation_time);

                if ($check_time_interval['status'] !== 200) {
                    return $this->errorResponse($check_time_interval['data'], 400);
                }
                $findSimulationResults->first_name = $data['firstname'];
                $findSimulationResults->last_name = $data['lastname'];
                $findSimulationResults->client_ip_address = $this->getIp($request);
                $findSimulationResults->user_id = null;
                $findSimulationResults->uuid = null;
                $findSimulationResults->annual_revenue = $data['annualrevenue'];
                $findSimulationResults->gross_profit_margin = $data['grossprofitmargin'];
                $findSimulationResults->net_profit_margin = $data['netprofitmargin'];
                $findSimulationResults->percentage_impact = $data['percentageimpact'];
                $findSimulationResults->currency = $data['currency'];
                $findSimulationResults->net_profit = $data['netprofit'];
                $findSimulationResults->new_annual_profit = $data['newannualprofit'];
                $findSimulationResults->coach_first_name = $data['coachfirstname'];
                $findSimulationResults->coach_last_name = $data['coachlastname'];
                $findSimulationResults->coach_email = $data['coachemail'];
                $findSimulationResults->coach_url = $data['coachurl'];
                $findSimulationResults->cut_costs = $data['cutcosts'];
                $findSimulationResults->revenueincrease_cut_costs = $data['revenueincrease_cut_costs'];
                $findSimulationResults->market_dominating_position = $data['marketdominatingposition'];
                $findSimulationResults->revenueincrease_market_dominating_position = $data['revenueincrease_market_dominating_position'];
                $findSimulationResults->compelling_offer = $data['compellingoffer'];
                $findSimulationResults->revenueincrease_compelling_offer = $data['revenueincrease_compelling_offer'];
                $findSimulationResults->increase_prices = $data['increaseprices'];
                $findSimulationResults->revenueincrease_increase_prices = $data['revenueincrease_increase_prices'];
                $findSimulationResults->upsell_cross_sell = $data['upsellcrosssell'];
                $findSimulationResults->revenueincrease_upsell_cross_sell = $data['revenueincrease_upsell_cross_sell'];
                $findSimulationResults->bundling = $data['thebundling'];
                $findSimulationResults->revenueincrease_bundling = $data['revenueincrease_bundling'];
                $findSimulationResults->downsell = $data['thedownsell'];
                $findSimulationResults->revenueincrease_downsell = $data['revenueincrease_downsell'];
                $findSimulationResults->additional_products_services = $data['additionalproductsservices'];
                $findSimulationResults->revenueincrease_additional_products_services_revenue = $data['revenueincrease_additional_products_services_revenue'];
                $findSimulationResults->drip_campaign = $data['dripcampaign'];
                $findSimulationResults->revenueincrease_drip_campaign = $data['revenueincrease_drip_campaign'];
                $findSimulationResults->alliances_joint_ventures = $data['alliancesjointventures'];
                $findSimulationResults->revenueincrease_alliances_joint_ventures = $data['revenueincrease_alliances_joint_ventures'];
                $findSimulationResults->more_leads = $data['moreleads'];
                $findSimulationResults->revenueincrease_more_leads = $data['revenueincrease_more_leads'];
                $findSimulationResults->digital_marketing = $data['digitalmarketing'];
                $findSimulationResults->revenueincrease_digital_marketing = $data['revenueincrease_digital_marketing'];
                $findSimulationResults->content_marketing = $data['contentmarketing'];
                $findSimulationResults->revenueincrease_content_marketing = $data['revenueincrease_content_marketing'];
                $findSimulationResults->website_optimization = $data['websiteoptimization'];
                $findSimulationResults->revenueincrease_website_optimization = $data['revenueincrease_website_optimization'];
                $findSimulationResults->email_marketing = $data['emailmarketing'];
                $findSimulationResults->revenueincrease_email_marketing = $data['revenueincrease_email_marketing'];
                $findSimulationResults->search_engine_optimization = $data['searchengineoptimization'];
                $findSimulationResults->revenueincrease_search_engine_optimization = $data['revenueincrease_search_engine_optimization'];
                $findSimulationResults->digital_advertising = $data['digitaladvertising'];
                $findSimulationResults->revenueincrease_digital_advertising = $data['revenueincrease_digital_advertising'];
                $findSimulationResults->social_media = $data['socialmedia'];
                $findSimulationResults->revenueincrease_social_media = $data['revenueincrease_social_media'];
                $findSimulationResults->video_marketing = $data['videomarketing'];
                $findSimulationResults->revenueincrease_video_marketing = $data['revenueincrease_video_marketing'];
                $findSimulationResults->metrics_marketing = $data['metricsmarketing'];
                $findSimulationResults->revenueincrease_metrics_marketing = $data['revenueincrease_metrics_marketing'];
                $findSimulationResults->strategy = $data['thestrategy'];
                $findSimulationResults->revenueincrease_strategy = $data['revenueincrease_strategy'];
                $findSimulationResults->trust_expertise_education = $data['trustexpertiseeducation'];
                $findSimulationResults->revenueincrease_trust_expertise_education = $data['revenueincrease_trust_expertise_education'];
                $findSimulationResults->policies_procedures = $data['policiesprocedures'];
                $findSimulationResults->revenueincrease_policies_procedures = $data['revenueincrease_policies_procedures'];
                $findSimulationResults->referral_systems = $data['referralsystems'];
                $findSimulationResults->revenueincrease_referral_systems = $data['revenueincrease_referral_systems'];
                $findSimulationResults->publicity_pr = $data['publicitypr'];
                $findSimulationResults->revenueincrease_publicity_pr = $data['revenueincrease_publicity_pr'];
                $findSimulationResults->direct_mail = $data['directmail'];
                $findSimulationResults->revenueincrease_direct_mail = $data['revenueincrease_direct_mail'];
                $findSimulationResults->advertising = $data['theadvertising'];
                $findSimulationResults->revenueincrease_advertising = $data['revenueincrease_advertising'];
                $findSimulationResults->scripts = $data['thescripts'];
                $findSimulationResults->revenueincrease_scripts = $data['revenueincrease_scripts'];
                $findSimulationResults->revenueincrease_initial_close_rate = $data['revenueincrease_initial_close_rate'];
                $findSimulationResults->initial_close_rate = $data['initialcloserate'];
                $findSimulationResults->follow_up_close_rate = $data['followupcloserate'];
                $findSimulationResults->revenueincrease_follow_up_close_rate = $data['revenueincrease_follow_up_close_rate'];
                $findSimulationResults->sales_team = $data['salesteam'];
                $findSimulationResults->revenueincrease_sales_team = $data['revenueincrease_sales_team'];
                $findSimulationResults->more_appointments = $data['moreappointments'];
                $findSimulationResults->revenueincrease_more_appointments = $data['revenueincrease_more_appointments'];
                $findSimulationResults->increase_frequency_of_purchase = $data['increasefrequencyofPurchase'];
                $findSimulationResults->revenueincrease_increase_frequency_of_purchase = $data['revenueincrease_increase_frequency_of_purchase'];
                $findSimulationResults->increase_longevity_of_buying_relationship = $data['increaselongevityofbuyingrelationship'];
                $findSimulationResults->revenueincrease_increase_longevity_buying_relationship = $data['revenueincrease_increase_longevity_buying_relationship'];
                $findSimulationResults->sales_training = $data['salestraining'];
                $findSimulationResults->revenueincrease_sales_training = $data['revenueincrease_sales_training'];
                $findSimulationResults->more_profitable_trade_shows = $data['moreprofitabletradeshows'];
                $findSimulationResults->revenueincrease_more_profitable_trade_shows = $data['revenueincrease_more_profitable_trade_shows'];
                $findSimulationResults->dealing_with_decision_makers = $data['dealingwithdecisionmakers'];
                $findSimulationResults->revenueincrease_dealing_with_decision_makers = $data['revenueincrease_dealing_with_decision_makers'];
                $findSimulationResults->attracting_dream_clients = $data['attractingdreamclients'];
                $findSimulationResults->revenueincrease_attracting_dream_clients = $data['revenueincrease_attracting_dream_clients'];
                $findSimulationResults->order_fullfillment = $data['orderfullfillment'];
                $findSimulationResults->revenueincrease_order_fullfillment = $data['revenueincrease_order_fullfillment'];
                $findSimulationResults->overcoming_buyers_remorse = $data['overcomingbuyersremorse'];
                $findSimulationResults->revenueincrease_overcoming_buyers_remorse = $data['revenueincrease_overcoming_buyers_remorse'];
                $findSimulationResults->simulation_date = $simulation_date;
                $findSimulationResults->save();
                Mail::to($findSimulationResults->email)->send(new SimulationUserOld($findSimulationResults));
                Mail::to($findSimulationResults->coach_email)->send(new SimulationCoachOld($findSimulationResults));
                return $this->successResponse('Simulation has been updated', 200);
            } else {
                $user_simulation = new Simulation;
                $user_simulation->first_name = $data['firstname'];
                $user_simulation->last_name = $data['lastname'];
                $user_simulation->email = $data['useremail'];
                $user_simulation->user_id = null;
                $user_simulation->uuid = (string) Str::uuid();
                $user_simulation->client_ip_address = $this->getIp($request);
                $user_simulation->annual_revenue = $data['annualrevenue'];
                $user_simulation->gross_profit_margin = $data['grossprofitmargin'];
                $user_simulation->net_profit_margin = $data['netprofitmargin'];
                $user_simulation->percentage_impact = $data['percentageimpact'];
                $user_simulation->currency = $data['currency'];
                $user_simulation->net_profit = $data['netprofit'];
                $user_simulation->new_annual_profit = $data['newannualprofit'];
                $user_simulation->coach_first_name = $data['coachfirstname'];
                $user_simulation->coach_last_name = $data['coachlastname'];
                $user_simulation->coach_email = $data['coachemail'];
                $user_simulation->coach_url = $data['coachurl'];
                $user_simulation->cut_costs = $data['cutcosts'];
                $user_simulation->revenueincrease_cut_costs = $data['revenueincrease_cut_costs'];
                $user_simulation->market_dominating_position = $data['marketdominatingposition'];
                $user_simulation->revenueincrease_market_dominating_position = $data['revenueincrease_market_dominating_position'];
                $user_simulation->compelling_offer = $data['compellingoffer'];
                $user_simulation->revenueincrease_compelling_offer = $data['revenueincrease_compelling_offer'];
                $user_simulation->increase_prices = $data['increaseprices'];
                $user_simulation->revenueincrease_increase_prices = $data['revenueincrease_increase_prices'];
                $user_simulation->upsell_cross_sell = $data['upsellcrosssell'];
                $user_simulation->revenueincrease_upsell_cross_sell = $data['revenueincrease_upsell_cross_sell'];
                $user_simulation->bundling = $data['thebundling'];
                $user_simulation->revenueincrease_bundling = $data['revenueincrease_bundling'];
                $user_simulation->downsell = $data['thedownsell'];
                $user_simulation->revenueincrease_downsell = $data['revenueincrease_downsell'];
                $user_simulation->additional_products_services = $data['additionalproductsservices'];
                $user_simulation->revenueincrease_additional_products_services_revenue = $data['revenueincrease_additional_products_services_revenue'];
                $user_simulation->drip_campaign = $data['dripcampaign'];
                $user_simulation->revenueincrease_drip_campaign = $data['revenueincrease_drip_campaign'];
                $user_simulation->alliances_joint_ventures = $data['alliancesjointventures'];
                $user_simulation->revenueincrease_alliances_joint_ventures = $data['revenueincrease_alliances_joint_ventures'];
                $user_simulation->more_leads = $data['moreleads'];
                $user_simulation->revenueincrease_more_leads = $data['revenueincrease_more_leads'];
                $user_simulation->digital_marketing = $data['digitalmarketing'];
                $user_simulation->revenueincrease_digital_marketing = $data['revenueincrease_digital_marketing'];
                $user_simulation->content_marketing = $data['contentmarketing'];
                $user_simulation->revenueincrease_content_marketing = $data['revenueincrease_content_marketing'];
                $user_simulation->website_optimization = $data['websiteoptimization'];
                $user_simulation->revenueincrease_website_optimization = $data['revenueincrease_website_optimization'];
                $user_simulation->email_marketing = $data['emailmarketing'];
                $user_simulation->revenueincrease_email_marketing = $data['revenueincrease_email_marketing'];
                $user_simulation->search_engine_optimization = $data['searchengineoptimization'];
                $user_simulation->revenueincrease_search_engine_optimization = $data['revenueincrease_search_engine_optimization'];
                $user_simulation->digital_advertising = $data['digitaladvertising'];
                $user_simulation->revenueincrease_digital_advertising = $data['revenueincrease_digital_advertising'];
                $user_simulation->social_media = $data['socialmedia'];
                $user_simulation->revenueincrease_social_media = $data['revenueincrease_social_media'];
                $user_simulation->video_marketing = $data['videomarketing'];
                $user_simulation->revenueincrease_video_marketing = $data['revenueincrease_video_marketing'];
                $user_simulation->metrics_marketing = $data['metricsmarketing'];
                $user_simulation->revenueincrease_metrics_marketing = $data['revenueincrease_metrics_marketing'];
                $user_simulation->strategy = $data['thestrategy'];
                $user_simulation->revenueincrease_strategy = $data['revenueincrease_strategy'];
                $user_simulation->trust_expertise_education = $data['trustexpertiseeducation'];
                $user_simulation->revenueincrease_trust_expertise_education = $data['revenueincrease_trust_expertise_education'];
                $user_simulation->policies_procedures = $data['policiesprocedures'];
                $user_simulation->revenueincrease_policies_procedures = $data['revenueincrease_policies_procedures'];
                $user_simulation->referral_systems = $data['referralsystems'];
                $user_simulation->revenueincrease_referral_systems = $data['revenueincrease_referral_systems'];
                $user_simulation->publicity_pr = $data['publicitypr'];
                $user_simulation->revenueincrease_publicity_pr = $data['revenueincrease_publicity_pr'];
                $user_simulation->direct_mail = $data['directmail'];
                $user_simulation->revenueincrease_direct_mail = $data['revenueincrease_direct_mail'];
                $user_simulation->advertising = $data['theadvertising'];
                $user_simulation->revenueincrease_advertising = $data['revenueincrease_advertising'];
                $user_simulation->scripts = $data['thescripts'];
                $user_simulation->revenueincrease_scripts = $data['revenueincrease_scripts'];
                $user_simulation->revenueincrease_initial_close_rate = $data['revenueincrease_initial_close_rate'];
                $user_simulation->initial_close_rate = $data['initialcloserate'];
                $user_simulation->follow_up_close_rate = $data['followupcloserate'];
                $user_simulation->revenueincrease_follow_up_close_rate = $data['revenueincrease_follow_up_close_rate'];
                $user_simulation->sales_team = $data['salesteam'];
                $user_simulation->revenueincrease_sales_team = $data['revenueincrease_sales_team'];
                $user_simulation->more_appointments = $data['moreappointments'];
                $user_simulation->revenueincrease_more_appointments = $data['revenueincrease_more_appointments'];
                $user_simulation->increase_frequency_of_purchase = $data['increasefrequencyofPurchase'];
                $user_simulation->revenueincrease_increase_frequency_of_purchase = $data['revenueincrease_increase_frequency_of_purchase'];
                $user_simulation->increase_longevity_of_buying_relationship = $data['increaselongevityofbuyingrelationship'];
                $user_simulation->revenueincrease_increase_longevity_buying_relationship = $data['revenueincrease_increase_longevity_buying_relationship'];
                $user_simulation->sales_training = $data['salestraining'];
                $user_simulation->revenueincrease_sales_training = $data['revenueincrease_sales_training'];
                $user_simulation->more_profitable_trade_shows = $data['moreprofitabletradeshows'];
                $user_simulation->revenueincrease_more_profitable_trade_shows = $data['revenueincrease_more_profitable_trade_shows'];
                $user_simulation->dealing_with_decision_makers = $data['dealingwithdecisionmakers'];
                $user_simulation->revenueincrease_dealing_with_decision_makers = $data['revenueincrease_dealing_with_decision_makers'];
                $user_simulation->attracting_dream_clients = $data['attractingdreamclients'];
                $user_simulation->revenueincrease_attracting_dream_clients = $data['revenueincrease_attracting_dream_clients'];
                $user_simulation->order_fullfillment = $data['orderfullfillment'];
                $user_simulation->revenueincrease_order_fullfillment = $data['revenueincrease_order_fullfillment'];
                $user_simulation->overcoming_buyers_remorse = $data['overcomingbuyersremorse'];
                $user_simulation->revenueincrease_overcoming_buyers_remorse = $data['revenueincrease_overcoming_buyers_remorse'];
                $user_simulation->simulation_date = $simulation_date;
                $user_simulation->save();
                Mail::to($user_simulation->email)->send(new SimulationUserOld($user_simulation));
                Mail::to($user_simulation->coach_email)->send(new SimulationCoachOld($user_simulation));
                return $this->successResponse('Simulation has been saved', 200);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }
    }

    /**
     * Add a new simulation to the db.
     *
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        try {
            $data = $request->all();
            $dt = new DateTime();
            $simulation_date = $dt->format('Y-m-d H:i:s');
            $findSimulationResults = Simulation::whereEmail($data['useremail'])->first();

            // Get user based on unique token
            $binaryUuid = pack('H*', str_replace('-', '', $data['id']));
            $sim_user = UserSimulator::where('uuid', $binaryUuid)->where('active', 1)->with('user')->first();
            //get alternative email if exists
            $alt_email = $sim_user && $sim_user->user->secondary_email ? $sim_user->user->secondary_email : null;

            if (isset($findSimulationResults) && $sim_user) {
                // check the time interval between the requests
                $last_simulation_time = $findSimulationResults->updated_at;
                $check_time_interval = $this->verifyTimeInterval($last_simulation_time);

                if ($check_time_interval['status'] !== 200) {
                    return $this->errorResponse($check_time_interval['data'], 400);
                }
                $findSimulationResults->first_name = $data['firstname'];
                $findSimulationResults->last_name = $data['lastname'];
                $findSimulationResults->client_ip_address = $this->getIp($request);
                $findSimulationResults->user_id = $sim_user ? $sim_user->user_id : null;
                $findSimulationResults->uuid = $findSimulationResults->uuid;
                $findSimulationResults->annual_revenue = $data['annualrevenue'];
                $findSimulationResults->gross_profit_margin = $data['grossprofitmargin'];
                $findSimulationResults->net_profit_margin = $data['netprofitmargin'];
                $findSimulationResults->percentage_impact = $data['percentageimpact'];
                $findSimulationResults->currency = $data['currency'];
                $findSimulationResults->net_profit = $data['netprofit'];
                $findSimulationResults->new_annual_profit = $data['newannualprofit'];
                $findSimulationResults->coach_first_name = $sim_user->user->first_name;
                $findSimulationResults->coach_last_name = $sim_user->user->last_name;
                $findSimulationResults->coach_email = $sim_user->user->email;
                $findSimulationResults->coach_url = $sim_user->user->website ? $sim_user->user->website : 'Not available';
                $findSimulationResults->cut_costs = $data['cutcosts'];
                $findSimulationResults->revenueincrease_cut_costs = $data['revenueincrease_cut_costs'];
                $findSimulationResults->market_dominating_position = $data['marketdominatingposition'];
                $findSimulationResults->revenueincrease_market_dominating_position = $data['revenueincrease_market_dominating_position'];
                $findSimulationResults->compelling_offer = $data['compellingoffer'];
                $findSimulationResults->revenueincrease_compelling_offer = $data['revenueincrease_compelling_offer'];
                $findSimulationResults->increase_prices = $data['increaseprices'];
                $findSimulationResults->revenueincrease_increase_prices = $data['revenueincrease_increase_prices'];
                $findSimulationResults->upsell_cross_sell = $data['upsellcrosssell'];
                $findSimulationResults->revenueincrease_upsell_cross_sell = $data['revenueincrease_upsell_cross_sell'];
                $findSimulationResults->bundling = $data['thebundling'];
                $findSimulationResults->revenueincrease_bundling = $data['revenueincrease_bundling'];
                $findSimulationResults->downsell = $data['thedownsell'];
                $findSimulationResults->revenueincrease_downsell = $data['revenueincrease_downsell'];
                $findSimulationResults->additional_products_services = $data['additionalproductsservices'];
                $findSimulationResults->revenueincrease_additional_products_services_revenue = $data['revenueincrease_additional_products_services_revenue'];
                $findSimulationResults->drip_campaign = $data['dripcampaign'];
                $findSimulationResults->revenueincrease_drip_campaign = $data['revenueincrease_drip_campaign'];
                $findSimulationResults->alliances_joint_ventures = $data['alliancesjointventures'];
                $findSimulationResults->revenueincrease_alliances_joint_ventures = $data['revenueincrease_alliances_joint_ventures'];
                $findSimulationResults->more_leads = $data['moreleads'];
                $findSimulationResults->revenueincrease_more_leads = $data['revenueincrease_more_leads'];
                $findSimulationResults->digital_marketing = $data['digitalmarketing'];
                $findSimulationResults->revenueincrease_digital_marketing = $data['revenueincrease_digital_marketing'];
                $findSimulationResults->content_marketing = $data['contentmarketing'];
                $findSimulationResults->revenueincrease_content_marketing = $data['revenueincrease_content_marketing'];
                $findSimulationResults->website_optimization = $data['websiteoptimization'];
                $findSimulationResults->revenueincrease_website_optimization = $data['revenueincrease_website_optimization'];
                $findSimulationResults->email_marketing = $data['emailmarketing'];
                $findSimulationResults->revenueincrease_email_marketing = $data['revenueincrease_email_marketing'];
                $findSimulationResults->search_engine_optimization = $data['searchengineoptimization'];
                $findSimulationResults->revenueincrease_search_engine_optimization = $data['revenueincrease_search_engine_optimization'];
                $findSimulationResults->digital_advertising = $data['digitaladvertising'];
                $findSimulationResults->revenueincrease_digital_advertising = $data['revenueincrease_digital_advertising'];
                $findSimulationResults->social_media = $data['socialmedia'];
                $findSimulationResults->revenueincrease_social_media = $data['revenueincrease_social_media'];
                $findSimulationResults->video_marketing = $data['videomarketing'];
                $findSimulationResults->revenueincrease_video_marketing = $data['revenueincrease_video_marketing'];
                $findSimulationResults->metrics_marketing = $data['metricsmarketing'];
                $findSimulationResults->revenueincrease_metrics_marketing = $data['revenueincrease_metrics_marketing'];
                $findSimulationResults->strategy = $data['thestrategy'];
                $findSimulationResults->revenueincrease_strategy = $data['revenueincrease_strategy'];
                $findSimulationResults->trust_expertise_education = $data['trustexpertiseeducation'];
                $findSimulationResults->revenueincrease_trust_expertise_education = $data['revenueincrease_trust_expertise_education'];
                $findSimulationResults->policies_procedures = $data['policiesprocedures'];
                $findSimulationResults->revenueincrease_policies_procedures = $data['revenueincrease_policies_procedures'];
                $findSimulationResults->referral_systems = $data['referralsystems'];
                $findSimulationResults->revenueincrease_referral_systems = $data['revenueincrease_referral_systems'];
                $findSimulationResults->publicity_pr = $data['publicitypr'];
                $findSimulationResults->revenueincrease_publicity_pr = $data['revenueincrease_publicity_pr'];
                $findSimulationResults->direct_mail = $data['directmail'];
                $findSimulationResults->revenueincrease_direct_mail = $data['revenueincrease_direct_mail'];
                $findSimulationResults->advertising = $data['theadvertising'];
                $findSimulationResults->revenueincrease_advertising = $data['revenueincrease_advertising'];
                $findSimulationResults->scripts = $data['thescripts'];
                $findSimulationResults->revenueincrease_scripts = $data['revenueincrease_scripts'];
                $findSimulationResults->revenueincrease_initial_close_rate = $data['revenueincrease_initial_close_rate'];
                $findSimulationResults->initial_close_rate = $data['initialcloserate'];
                $findSimulationResults->follow_up_close_rate = $data['followupcloserate'];
                $findSimulationResults->revenueincrease_follow_up_close_rate = $data['revenueincrease_follow_up_close_rate'];
                $findSimulationResults->sales_team = $data['salesteam'];
                $findSimulationResults->revenueincrease_sales_team = $data['revenueincrease_sales_team'];
                $findSimulationResults->more_appointments = $data['moreappointments'];
                $findSimulationResults->revenueincrease_more_appointments = $data['revenueincrease_more_appointments'];
                $findSimulationResults->increase_frequency_of_purchase = $data['increasefrequencyofPurchase'];
                $findSimulationResults->revenueincrease_increase_frequency_of_purchase = $data['revenueincrease_increase_frequency_of_purchase'];
                $findSimulationResults->increase_longevity_of_buying_relationship = $data['increaselongevityofbuyingrelationship'];
                $findSimulationResults->revenueincrease_increase_longevity_buying_relationship = $data['revenueincrease_increase_longevity_buying_relationship'];
                $findSimulationResults->sales_training = $data['salestraining'];
                $findSimulationResults->revenueincrease_sales_training = $data['revenueincrease_sales_training'];
                $findSimulationResults->more_profitable_trade_shows = $data['moreprofitabletradeshows'];
                $findSimulationResults->revenueincrease_more_profitable_trade_shows = $data['revenueincrease_more_profitable_trade_shows'];
                $findSimulationResults->dealing_with_decision_makers = $data['dealingwithdecisionmakers'];
                $findSimulationResults->revenueincrease_dealing_with_decision_makers = $data['revenueincrease_dealing_with_decision_makers'];
                $findSimulationResults->attracting_dream_clients = $data['attractingdreamclients'];
                $findSimulationResults->revenueincrease_attracting_dream_clients = $data['revenueincrease_attracting_dream_clients'];
                $findSimulationResults->order_fullfillment = $data['orderfullfillment'];
                $findSimulationResults->revenueincrease_order_fullfillment = $data['revenueincrease_order_fullfillment'];
                $findSimulationResults->overcoming_buyers_remorse = $data['overcomingbuyersremorse'];
                $findSimulationResults->revenueincrease_overcoming_buyers_remorse = $data['revenueincrease_overcoming_buyers_remorse'];
                $findSimulationResults->simulation_date = $simulation_date;
                $findSimulationResults->save();
                Mail::to($findSimulationResults->email)->send(new SimulationUser($findSimulationResults));
                Mail::to($findSimulationResults->coach_email)->send(new SimulationCoach($findSimulationResults, $alt_email));
                return $this->successResponse('Simulation has been updated', 200);
            } else {
                $user_simulation = new Simulation;
                $user_simulation->first_name = $data['firstname'];
                $user_simulation->last_name = $data['lastname'];
                $user_simulation->email = $data['useremail'];
                $user_simulation->user_id = $sim_user ? $sim_user->user_id : null;
                $user_simulation->uuid = (string) Str::uuid();
                $user_simulation->client_ip_address = $this->getIp($request);
                $user_simulation->annual_revenue = $data['annualrevenue'];
                $user_simulation->gross_profit_margin = $data['grossprofitmargin'];
                $user_simulation->net_profit_margin = $data['netprofitmargin'];
                $user_simulation->percentage_impact = $data['percentageimpact'];
                $user_simulation->currency = $data['currency'];
                $user_simulation->net_profit = $data['netprofit'];
                $user_simulation->new_annual_profit = $data['newannualprofit'];
                $user_simulation->coach_first_name = $sim_user->user->first_name;
                $user_simulation->coach_last_name = $sim_user->user->last_name;
                $user_simulation->coach_email = $sim_user->user->email;
                $user_simulation->coach_url = $sim_user->user->website ? $sim_user->user->website : 'Not available';
                $user_simulation->cut_costs = $data['cutcosts'];
                $user_simulation->revenueincrease_cut_costs = $data['revenueincrease_cut_costs'];
                $user_simulation->market_dominating_position = $data['marketdominatingposition'];
                $user_simulation->revenueincrease_market_dominating_position = $data['revenueincrease_market_dominating_position'];
                $user_simulation->compelling_offer = $data['compellingoffer'];
                $user_simulation->revenueincrease_compelling_offer = $data['revenueincrease_compelling_offer'];
                $user_simulation->increase_prices = $data['increaseprices'];
                $user_simulation->revenueincrease_increase_prices = $data['revenueincrease_increase_prices'];
                $user_simulation->upsell_cross_sell = $data['upsellcrosssell'];
                $user_simulation->revenueincrease_upsell_cross_sell = $data['revenueincrease_upsell_cross_sell'];
                $user_simulation->bundling = $data['thebundling'];
                $user_simulation->revenueincrease_bundling = $data['revenueincrease_bundling'];
                $user_simulation->downsell = $data['thedownsell'];
                $user_simulation->revenueincrease_downsell = $data['revenueincrease_downsell'];
                $user_simulation->additional_products_services = $data['additionalproductsservices'];
                $user_simulation->revenueincrease_additional_products_services_revenue = $data['revenueincrease_additional_products_services_revenue'];
                $user_simulation->drip_campaign = $data['dripcampaign'];
                $user_simulation->revenueincrease_drip_campaign = $data['revenueincrease_drip_campaign'];
                $user_simulation->alliances_joint_ventures = $data['alliancesjointventures'];
                $user_simulation->revenueincrease_alliances_joint_ventures = $data['revenueincrease_alliances_joint_ventures'];
                $user_simulation->more_leads = $data['moreleads'];
                $user_simulation->revenueincrease_more_leads = $data['revenueincrease_more_leads'];
                $user_simulation->digital_marketing = $data['digitalmarketing'];
                $user_simulation->revenueincrease_digital_marketing = $data['revenueincrease_digital_marketing'];
                $user_simulation->content_marketing = $data['contentmarketing'];
                $user_simulation->revenueincrease_content_marketing = $data['revenueincrease_content_marketing'];
                $user_simulation->website_optimization = $data['websiteoptimization'];
                $user_simulation->revenueincrease_website_optimization = $data['revenueincrease_website_optimization'];
                $user_simulation->email_marketing = $data['emailmarketing'];
                $user_simulation->revenueincrease_email_marketing = $data['revenueincrease_email_marketing'];
                $user_simulation->search_engine_optimization = $data['searchengineoptimization'];
                $user_simulation->revenueincrease_search_engine_optimization = $data['revenueincrease_search_engine_optimization'];
                $user_simulation->digital_advertising = $data['digitaladvertising'];
                $user_simulation->revenueincrease_digital_advertising = $data['revenueincrease_digital_advertising'];
                $user_simulation->social_media = $data['socialmedia'];
                $user_simulation->revenueincrease_social_media = $data['revenueincrease_social_media'];
                $user_simulation->video_marketing = $data['videomarketing'];
                $user_simulation->revenueincrease_video_marketing = $data['revenueincrease_video_marketing'];
                $user_simulation->metrics_marketing = $data['metricsmarketing'];
                $user_simulation->revenueincrease_metrics_marketing = $data['revenueincrease_metrics_marketing'];
                $user_simulation->strategy = $data['thestrategy'];
                $user_simulation->revenueincrease_strategy = $data['revenueincrease_strategy'];
                $user_simulation->trust_expertise_education = $data['trustexpertiseeducation'];
                $user_simulation->revenueincrease_trust_expertise_education = $data['revenueincrease_trust_expertise_education'];
                $user_simulation->policies_procedures = $data['policiesprocedures'];
                $user_simulation->revenueincrease_policies_procedures = $data['revenueincrease_policies_procedures'];
                $user_simulation->referral_systems = $data['referralsystems'];
                $user_simulation->revenueincrease_referral_systems = $data['revenueincrease_referral_systems'];
                $user_simulation->publicity_pr = $data['publicitypr'];
                $user_simulation->revenueincrease_publicity_pr = $data['revenueincrease_publicity_pr'];
                $user_simulation->direct_mail = $data['directmail'];
                $user_simulation->revenueincrease_direct_mail = $data['revenueincrease_direct_mail'];
                $user_simulation->advertising = $data['theadvertising'];
                $user_simulation->revenueincrease_advertising = $data['revenueincrease_advertising'];
                $user_simulation->scripts = $data['thescripts'];
                $user_simulation->revenueincrease_scripts = $data['revenueincrease_scripts'];
                $user_simulation->revenueincrease_initial_close_rate = $data['revenueincrease_initial_close_rate'];
                $user_simulation->initial_close_rate = $data['initialcloserate'];
                $user_simulation->follow_up_close_rate = $data['followupcloserate'];
                $user_simulation->revenueincrease_follow_up_close_rate = $data['revenueincrease_follow_up_close_rate'];
                $user_simulation->sales_team = $data['salesteam'];
                $user_simulation->revenueincrease_sales_team = $data['revenueincrease_sales_team'];
                $user_simulation->more_appointments = $data['moreappointments'];
                $user_simulation->revenueincrease_more_appointments = $data['revenueincrease_more_appointments'];
                $user_simulation->increase_frequency_of_purchase = $data['increasefrequencyofPurchase'];
                $user_simulation->revenueincrease_increase_frequency_of_purchase = $data['revenueincrease_increase_frequency_of_purchase'];
                $user_simulation->increase_longevity_of_buying_relationship = $data['increaselongevityofbuyingrelationship'];
                $user_simulation->revenueincrease_increase_longevity_buying_relationship = $data['revenueincrease_increase_longevity_buying_relationship'];
                $user_simulation->sales_training = $data['salestraining'];
                $user_simulation->revenueincrease_sales_training = $data['revenueincrease_sales_training'];
                $user_simulation->more_profitable_trade_shows = $data['moreprofitabletradeshows'];
                $user_simulation->revenueincrease_more_profitable_trade_shows = $data['revenueincrease_more_profitable_trade_shows'];
                $user_simulation->dealing_with_decision_makers = $data['dealingwithdecisionmakers'];
                $user_simulation->revenueincrease_dealing_with_decision_makers = $data['revenueincrease_dealing_with_decision_makers'];
                $user_simulation->attracting_dream_clients = $data['attractingdreamclients'];
                $user_simulation->revenueincrease_attracting_dream_clients = $data['revenueincrease_attracting_dream_clients'];
                $user_simulation->order_fullfillment = $data['orderfullfillment'];
                $user_simulation->revenueincrease_order_fullfillment = $data['revenueincrease_order_fullfillment'];
                $user_simulation->overcoming_buyers_remorse = $data['overcomingbuyersremorse'];
                $user_simulation->revenueincrease_overcoming_buyers_remorse = $data['revenueincrease_overcoming_buyers_remorse'];
                $user_simulation->simulation_date = $simulation_date;
                $user_simulation->save();
                Mail::to($user_simulation->email)->send(new SimulationUser($user_simulation));
                Mail::to($user_simulation->coach_email)->send(new SimulationCoach($user_simulation, $alt_email));
                return $this->successResponse('Simulation has been saved', 200);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }
    }

    /**
     * Add a new simulation old to the db.
     *
     * @return \Illuminate\Http\Response
     */
    public function request_meet_old(Request $request)
    {
        try {

            $data = $request->all();

            $rules = [
                'useremail' => 'required|exists:simulations,email',
            ];

            $messages = [
                'useremail.required' => 'The email is required',
                'useremail.exists' => 'That email does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $findSimulationResults = Simulation::whereEmail($request->useremail)->first();

            if ($findSimulationResults->coach_url) {
                $new_data = [
                    "meeting_details" => $data,
                    "simulation_details" => $findSimulationResults,
                ];
                // array_push($data, $findSimulationResults);
                // dd($new_data);
                Mail::to($findSimulationResults->coach_email)->send(new SimulationCoachMeetRequestOld((object)$new_data));
                return $this->successResponse('Email has been sent to the coach.', 200);
            } else {
                return $this->errorResponse('Sorry there was an issue sending your email', 400);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 400);
        }
    }


    /**
     * Add a new simulation to the db.
     *
     * @return \Illuminate\Http\Response
     */
    public function request_meet(Request $request)
    {
        try {

            $data = $request->all();

            $rules = [
                'useremail' => 'required|exists:simulations,email',
            ];

            $messages = [
                'useremail.required' => 'The email is required',
                'useremail.exists' => 'That email does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $findSimulationResults = Simulation::whereEmail($request->useremail)->first();

            if ($findSimulationResults->coach_url) {
                $new_data = [
                    "meeting_details" => $data,
                    "simulation_details" => $findSimulationResults,
                ];
                // array_push($data, $findSimulationResults);
                // dd($new_data);
                Mail::to($findSimulationResults->coach_email)->send(new SimulationCoachMeetRequest((object)$new_data));
                return $this->successResponse('Email has been sent to the coach.', 200);
            } else {
                return $this->errorResponse('Sorry there was an issue sending your email', 400);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 400);
        }
    }

    // verify recaptcha token
    public function verifyRecaptchaToken(Request $request)
    {
        try {
            $data = $request->all();
            // define a final API request - POST
            $query = 'secret=' . $data['secret'] . '&response=' . $data['response'];
            $api = env('VERIFY_RECAPTCHA_URL') . $query;
            $response = $this->postCurl($api);
            return $this->successResponse(json_decode($response), 200);
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), 400);
        }
    }

    // post curl 
    public function postCurl($api)
    {
        $request = curl_init($api);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($request, CURLOPT_MAXREDIRS, 10);
        curl_setopt($request, CURLOPT_TIMEOUT, 0);
        curl_setopt($request, CURLOPT_POSTFIELDS, '');
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt(
            $request,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Cookie: PHPSESSID=478ec09ab8d3d25f0a521b8a7f5f54ef; em_acp_globalauth_cookie=06444ea1-f7f1-4ecf-9de0-85dc8894aa88'
            ),
        );
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($request);
        curl_close($request);
        return $response;
    }


    /**
     * Get User location details via their IP address
     *
     * @return \Illuminate\Http\Response
     */
    public function loginLookpup(Request $request)
    {
        try {

            $rules = [
                'ip' => 'required'
            ];

            $messages = [
                'ip.required' => 'The IP is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $ip = $request->ip;

            // This key can be put on the .env file
            $link = 'https://ipapi.co/' . $ip . '/json/?key=YweGT602WdXKa1mRi4y8Y3MQDlwSqmQZw5cBgWe7EcF4Qc4Ria';

            $response = Http::acceptJson()->get($link);

            if ($response->successful()) {
                return $response;
            } else {
                return $this->successResponse('Error searching look up', 400);
            }

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }


    //  get clients ip address
    public function getIp($request)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }

    //  verify time interval between requests
    public function verifyTimeInterval($last_simulation_time)
    {
        try {
            $allowed_minutes = 1;
            $date = new DateTime($last_simulation_time);
            $now = new DateTime();
            $difference = date_diff($now, $date)->format("%i");
            if ((int)$difference > (int)$allowed_minutes) {
                return [
                    'data' => 'Request interval is Ok',
                    'status' => 200,
                ];
            } else {
                return [
                    'data' => 'Request interval Invalid!',
                    'status' => 400
                ];
            }
        } catch (Exception $e) {
            return [
                'data' => 'Invalid request. ' . $e->message(),
                'status' => 400
            ];
        }
    }

    public function accessPublicPage($uniqueToken)
    {
        try {
            // Validate the unique token
            $binaryUuid = pack('H*', str_replace('-', '', $uniqueToken));
            $sim_user = UserSimulator::where('uuid', $binaryUuid)->with('user')->first();

            if (!$sim_user || $sim_user->user->user_status_id == 2) {
                return $this->errorResponse('Invalid Token or User', 401);
            }

            // Fetch and return the data to be used on the public page

            $transform = new UserSimulatorResource($sim_user);
            return $this->successResponse($transform, 200);
        } catch (Exception $e) {
            return [
                'data' => 'Invalid request. ' . $e->message(),
                'status' => 400
            ];
        }
    }
    /**
    * Get simulation report data from db ol
    *
    * @return \Illuminate\Http\Response
    */
   public function simulatorReport(Request $request)
   {
       // Fetch and return the data to be used on the public report page
       try {
           $rules = [
               'report_token' => 'required',
           ];

           $messages = [
               'report_token.required' => 'The token is required',
           ];

           $validator = Validator::make($request->all(), $rules, $messages);
           if ($validator->fails()) {
               return $this->errorResponse($validator->errors(), 400);
           }
           // Validate the unique token
           $binaryUuid = pack('H*', str_replace('-', '', $request->report_token));
           $sim_results = Simulation::where('uuid', $binaryUuid)->first();
           // dd($sim_results);

           if (!$sim_results) {
               return $this->errorResponse('Invalid Token or User.', 401);
           }

           $transform = new SimulationResource($sim_results);
           return $this->successResponse($transform, 200);
       } catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Something went wrong.', 404);
       }
   }

    /**
     * Get simulation data from db
     *
     * @return \Illuminate\Http\Response
     */
    public function accessReportPage(Request $request)
    {
        // Fetch and return the data to be used on the public report page
        try {
            $rules = [
                'report_token' => 'required',
            ];

            $messages = [
                'report_token.required' => 'The token is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            // Validate the unique token
            $binaryUuid = pack('H*', str_replace('-', '', $request->report_token));
            $sim_results = Simulation::where('uuid', $binaryUuid)->first();
            // dd($sim_results);

            if (!$sim_results) {
                return $this->errorResponse('Invalid Token or User.', 401);
            }

            $transform = new SimulationResource($sim_results);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Something went wrong.', 404);
        }
    }

    public function saveUrl(Request $request)
    {
        $request->validate([
            'uuid' => 'required',
            'unique_url_token' => 'required',
        ]);

        $data = UserSimulator::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'uuid' => $request->uuid,
                'unique_url_token' => $request->unique_url_token,
                'active' => true
            ]
        );

        if ($data) {
            $transform = new UserSimulatorResource($data);
            return $this->successResponse($transform, 200);
        } else {
            return response()->json(['message' => 'Failed to save data'], 500);
        }
    }

    public function fetchByUserId(Request $request)
    {
        $data = $request->all();

        $rules = [
            'user_id' => 'required|exists:user_simulators,user_id',
        ];

        $messages = [
            'user_id.required' => 'id is required',
            'user_id.exists' => 'ID does not exist',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $data = UserSimulator::where('user_id', $request->user_id)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data found for this user_id'], 404);
        }

        $transform = UserSimulatorResource::collection($data);
        return $this->successResponse($transform, 200);
    }

    /**
     * Get simulation data from db
     *
     * @return \Illuminate\Http\Response
     */
    public function fetchSimulationById(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'The user is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $findSimulationResults = Simulation::where('user_id', $request->user_id)->get();
            // $transform = new SimulationResource($findSimulationResults);

            $transform = SimulationResource::collection($findSimulationResults);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }
    /**
     * update UUIDs in db
     *
     * @return \Illuminate\Http\Response
     */

    public function updateUUIDs()
    {
        Simulation::whereBetween('id', [0, 2000])->chunk(200, function ($simulations) {
            foreach ($simulations as $simulation) {
                $uuidString = Str::uuid(); // This will generate a v4 UUID
                $simulation->uuid = $uuidString; // The mutator will handle the conversion ti binary
                $simulation->save();
            }
        });
        return response()->json(['message' => 'updated'], 200);
    }
}
