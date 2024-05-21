<?php

use Carbon\Carbon;


use App\Models\User;
use App\Models\Integration;
use App\Models\AweberIntegration;
use App\Models\IntegrationGroupList;
use App\Models\ActiveCampaignIntegration;
use App\Models\GetResponseIntegration;
use App\Models\MeetingNoteSetting;
use App\Http\Resources\StripeIntegration as StripeIntegrationResource;
use App\Http\Resources\AweberIntegration as AweberIntegrationResource;
use App\Http\Resources\ActiveCampaignIntegrationResource;
use App\Http\Resources\Integration as IntegrationResource;
use App\Http\Resources\GetResponseIntegration as GetResponseIntegrationResource;

use Illuminate\Support\Facades\Notification;
use App\Notifications\CoachProspectsRequest;
use App\Notifications\AdminProspectsRequest;
use App\Notifications\CoachProspectsNotification;
use App\Jobs\ProcessEmail;
use Aws\Credentials\Credentials;
use GuzzleHttp\Psr7\Request;

function createMeetingNotesSettings($company_id)
{

    $list = [
        (object)[
            'company_id' => $company_id,
            'type' => 'top',
            'name' => 'wins',
            'label' => 'Biggest win since last session:',
            'placeholder' => 'Enter biggest win',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'top',
            'name' => 'challenges',
            'label' => 'Biggest challenge since last session:',
            'placeholder' => 'Enter biggest challenge',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'top',
            'name' => 'corrective',
            'label' => 'Corrective action to overcome biggest challenge:',
            'placeholder' => 'Enter corrective actions',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'bottom',
            'name' => 'revenue',
            'label' => 'Weekly revenue:',
            'placeholder' => 'Enter revenue',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'bottom',
            'name' => 'leads',
            'label' => 'Weekly leads:',
            'placeholder' => 'Enter leads',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'bottom',
            'name' => 'conversions',
            'label' => 'Weekly conversions:',
            'placeholder' => 'Enter conversions',
        ],
        (object)[
            'company_id' => $company_id,
            'type' => 'bottom',
            'name' => 'profits',
            'label' => 'Weekly profits:',
            'placeholder' => 'Enter profits',
        ],

    ];

    foreach ($list as $each) {
        MeetingNoteSetting::firstOrCreate(array(
            'company_id' => $each->company_id,
            'type' => $each->type,
            'name' => $each->name,
            'label' => $each->label,
            'placeholder' => $each->placeholder,
        ));
    }
}

function getImplementationStartDate($date)
{
    $time = strtotime($date);
    $newformat = date('Y-m-d', $time);
    return date("Y-m-d", strtotime("$newformat +1 month"));
}

function formatDate($date)
{
    $time = strtotime($date);
    return date('Y-m-d', $time);
}

function formatDateTime($date)
{
    $time = strtotime($date);
    return date('Y-m-d H:i:s', $time);
}

function formatHumanDate($date)
{
    $time = strtotime($date);
    return date('dS M Y', $time);
}

function formatAnalyticsType($type)
{
    switch ($type) {
        case '100k':
            return '100K Training';
        case 'pas-training':
            return 'Software Training';
        case 'group-coaching':
            return 'Group Coaching';
        case 'lead-gen-training':
            return 'Lead Gen Training';
        case 'pas-roleplay-prep':
            return 'Roleplay Preparation';
        case 'jumpstart-12-training':
            return 'Jumpstart 12 Training';
        default:
            return $type;
    }
}

function cleanUpAnnualModules($list)
{
    $array = [];
    $paths = [];
    foreach ($list as $key => $m) {
        $module = clone ($m);
        if (!in_array($m->path, $paths) && ($m->path != 'planning-meeting') && ($m->path != 'quarterly-review')) {
            $paths[] = $m->path;
            $array[] = $module;
        }
    }

    return $array;
}

function getAnnualPriorities($array)
{
    $totalWeeks = array_reduce($array, function ($sum, $value) {
        return $sum += (int)$value->time;
    }, 0);

    // 52 weeks = 1 year
    if ($totalWeeks <= 52) {
        return $array;
    } else {
        $picked = [];
        $sum = 0;
        foreach ($array as $index => $module) {
            $sum += (int)$module->time;
            if ($sum <= 52) {
                $picked[] = $module;
            } else {
                $picked[] = $module;
                break;
            }
        }

        return $picked;
    }
}

function getStartEndDate($start_date, $modules, $index)
{

    $start = Carbon::createFromFormat('Y-m-d', $start_date);

    if ($index > 0) {

        $array = array_slice($modules, 0, $index);

        $weeks = array_reduce($array, function ($sum, $value) {
            return $sum += (int)$value->time;
        }, 0);

        $temp = $start->copy();
        $startdate = $temp->addDays($weeks * 6);
        $endtemp = $startdate->copy();
        $days = ((int) $modules[$index]->time) * 6;
        $end = $endtemp->addDays($days);

        return (object)[
            'startdate' => $startdate->format('Y-m-d'),
            'enddate' => $end->format('Y-m-d'),
        ];
    }

    $days = ((int)$modules[$index]->time) * 6;
    $end = $start->copy()->addDays($days);
    return (object)[
        'startdate' => $start->format('Y-m-d'),
        'enddate' => $end->format('Y-m-d'),
    ];
}


function addMeetingDetails($settings, $messages)
{
    switch (intval($settings->meeting_type)) {
        case 1: {
                if (strlen($settings->zoom_url) > 0) {
                    $link = '<em><a href="' . $settings->zoom_url . '" target="_blank">Meeting URL</a></em>';
                    $messages[] =  'As a reminder, our meeting will be online at this link: ' . $link;
                }
                break;
            }
        case 2: {
                if (strlen($settings->meeting_address) > 0) {
                    $messages[] =  'As a reminder, our meeting will be in person at this address: ' . $settings->meeting_address;
                }
                break;
            }
        case 3: {
                if (strlen($settings->phone_number) > 0) {
                    $messages[] =  'As a reminder, our meeting will be on phone at this phone number: ' . $settings->phone_number;
                }
                break;
            }
        default:
            break;
    }
    return $messages;
}


function getIntegration($user)
{

    $integration = Integration::where('user_id', $user->id)->first();

    if ($integration) {
        if ($integration->stripe == 1) {
            $integration->stripe_details = new StripeIntegrationResource($integration->stripeDetails());
        }
        if ($integration->aweber == 1) {
            $integration->aweber_details = new AweberIntegrationResource($integration->aweberDetails());
        }
        if ($integration->active_campaign == 1) {
            $integration->active_campaign_details = new ActiveCampaignIntegrationResource($integration->activecampaignDetails());
        }
        if ($integration->getresponse == 1) {
            $integration->getresponse_details = new GetResponseIntegrationResource($integration->getresponseDetails());
        }
    }

    return ($integration) ? new IntegrationResource($integration) : null;
}


function sendOneHourMeetingReminder($meeting, $company, $user, $settings)
{

    $messages = [];
    $messages[] = 'Just a friendly reminder that our meeting together is starting in <b>1 hour</b>. See you soon.';

    $results = addMeetingDetails($settings, $messages);

    $notice = [
        'client_name' => $company->contact_name,
        'messages' => $results,
        'user' => $user,
        'to' => $company->contact_email,
        'copy' => [$user->email],
        'bcopy' => [],
        'subject' => 'Reminder of our meeting in 1 hour',
    ];

    ProcessEmail::dispatch($notice);
}


function sendOneDayCommitmentReminder($task)
{

    $user_id = $task->meetingnote->user->id;

    $user = User::findOrFail($user_id);

    $date = Carbon::createFromDate($task->reminder_date);
    $mtime = $date->format('h:i A');
    $mdate = $date->isoFormat('dddd Do MMMM Y');

    $messages = [];

    $messages[] = 'Just a friendly reminder that you have a commitment task due tomorrow, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b>.';
    $messages[] = 'Your commitment task was: <br/>';
    $messages[] = '<em>' . $task->note . '</em>';

    $recipient = null;

    if ($task->type == 'coach') {
        $recipient = $task->meetingnote->user;
    } else {
        $recipient = User::where('company_id', $task->meetingnote->company_id)->first();
    }

    if ($recipient) {

        $task->send_reminder = 0;
        $task->save();

        $notice = [
            'client_name' => $recipient->first_name,
            'messages' => $messages,
            'user' => $user,
            'to' => $recipient->email,
            'copy' => [],
            'bcopy' => [],
            'subject' => 'Commitment task due (' . $mdate . ')',
        ];

        ProcessEmail::dispatch($notice);
    }
}

function sendOneDayMeetingReminder($meeting, $company, $user, $date, $settings)
{

    $messages = [];
    $messages[] = 'Just a friendly reminder that our meeting together is tomorrow on, <b>' . $date . '</b>, at <b>' . $meeting->meeting_time . '</b> (<em>' . $meeting->time_zone . '</em>). I’m looking forward to our time as we explore further ways to grow your business.';

    $results = addMeetingDetails($settings, $messages);

    $notice = [
        'client_name' => $company->contact_name,
        'messages' => $results,
        'user' => $user,
        'to' => $company->contact_email,
        'copy' => [$user->email],
        'bcopy' => [],
        'subject' => 'Reminder of our meeting in 24 hours',
    ];

    ProcessEmail::dispatch($notice);
}

function sendThreeDaysMeetingReminder($meeting, $company, $user, $date, $settings)
{

    $messages = [];
    $messages[] = 'Just a friendly reminder that we’re scheduled to meet in 3 days on, <b>' . $date . '</b>, at <b>' . $meeting->meeting_time . '</b> (<em>' . $meeting->time_zone . '</em>). Please notify me at least 24 hours in advance if you are unable to make this meeting.';

    $results = addMeetingDetails($settings, $messages);

    $notice = [
        'client_name' => $company->contact_name,
        'messages' => $results,
        'user' => $user,
        'to' => $company->contact_email,
        'copy' => [$user->email],
        'bcopy' => [],
        'subject' => 'Reminder of our meeting in 3 days',
    ];

    ProcessEmail::dispatch($notice);
}

function sendProspectsRequestNotificationToAdmin($user, $name)
{
    $notify_one = env('PROSPECTS_NOTIFY_ONE');
    $notify_two = env('PROSPECTS_NOTIFY_TWO');
    $notify_three = env('PROSPECTS_NOTIFY_THREE');

    $notice = (object)[
        'coach_first_name' => $user->first_name,
        'coach_last_name' => $user->last_name,
        'coach_email' => $user->email,
        'notify_two' => $notify_two,
        'notify_three' => $notify_three,
        'name' => $name,
    ];

    Notification::route('mail', $notify_one)->notify(new AdminProspectsRequest($notice));
}

function sendProspectsRequestNotificationToCoach($user, $name)
{

    $notice = (object)[
        'coach_first_name' => $user->first_name,
        'name' => $name,
    ];

    Notification::route('mail', $user->email)->notify(new CoachProspectsRequest($notice));
}

function sendProspectsNotificationToCoach($user)
{

    $notice = (object)[
        'coach_first_name' => $user->first_name,
    ];

    Notification::route('mail', $user->email)->notify(new CoachProspectsNotification($notice));
}

function sendGCMeetingReminder($template, $user, $instructor)
{

    $messages = [];
    $messages[] = $template['template'];
    $type = 'groupcoaching';

    $notice = [
        'messages' => $messages,
        'user' => $instructor,
        'to' => $user->email,
        'copy' => [],
        'bcopy' => [],
        'subject' => $template['subject'],
    ];

    ProcessEmail::dispatch($notice, $type);
}

function postAweberCurl($api, $data, $token, $verb, $type = null)
{
    $request = curl_init($api);
    curl_setopt($request, CURLOPT_HEADER, 10);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_CUSTOMREQUEST, $verb);
    curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($request, CURLOPT_MAXREDIRS, 10);
    curl_setopt($request, CURLOPT_TIMEOUT, 10);
    curl_setopt($request, CURLOPT_POSTFIELDS, $data);

    curl_setopt(
        $request,
        CURLOPT_HTTPHEADER,
        array(
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json",
            "Authorization: Bearer " . $token
        ),
    );
    curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
    $response = curl_exec($request);
    curl_close($request);

    return json_decode($response);
}

function postACampaignCurl($api, $data)
{
    $request = curl_init($api);
    curl_setopt($request, CURLOPT_HEADER, 0);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($request, CURLOPT_MAXREDIRS, 10);
    curl_setopt($request, CURLOPT_TIMEOUT, 0);
    curl_setopt($request, CURLOPT_POSTFIELDS, $data);
    //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
    curl_setopt(
        $request,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: PHPSESSID=478ec09ab8d3d25f0a521b8a7f5f54ef; em_acp_globalauth_cookie=06444ea1-f7f1-4ecf-9de0-85dc8894aa88'
        ),
    );
    curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
    $response = curl_exec($request);
    curl_close($request);
    return $response;
}


function aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type, $access_token, $data)
{
    $client = new GuzzleHttp\Client();

    try {
        $url = $base_url . '/' . $end_point;
        $auth = base64_encode(env('AWEBER_CLIENT_ID') . ':' . env('AWEBER_CLIENT_SECRET'));
        switch ($authentication_type) {

            case 'basic':
                $options = array(
                    'http' => array(
                        'header' => ["Authorization: Basic $auth", "Content-type: application/x-www-form-urlencoded"],
                        'method'  => 'POST',
                        'ignore_errors' => true,
                        'content' => http_build_query($data)
                    )
                );
                $context  = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                return json_decode($result);

            case 'curl':
                return postAweberCurl($url, $data, $access_token, $verb);
            case 'urlencoded':
                $response = $client->post($url, ['form_params' => $data, 'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer " . $access_token,
                ]]);
                return json_decode($response->getbody(), true);
            case 'json':
                $response = $client->post($url, ['json' => $data, 'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer " . $access_token,
                ]]);
                return json_decode($response->getbody(), true);

            default:
                $response = $client->$verb(
                    $url,
                    [
                        'json' => json_encode($data),
                        'headers' => [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                            'Accept' => 'application/json',
                            'Authorization' => "Bearer " . $access_token,
                        ]
                    ],
                );

                return json_decode($response->getBody()->getContents(), true);
        }
    } catch (Exception $e) {
        dd($e);
    }
}


function scheduleAWeberBroadcastRequest($self_link, $access_token)
{
    $timestamp = new \DateTime('now', new \DateTimeZone('UTC'));
    $scheduled_for = $timestamp->format(DateTime::ATOM);  // must be iso8601 compliant

    $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'];
    $verb = 'post';
    $base_url = $self_link;
    $data = ['scheduled_for' => $scheduled_for];
    $end_point = 'schedule';

    // https://api.aweber.com/1.0/accounts/1793299/lists/6090248/broadcasts/50282760/schedule

    $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type = 'urlencoded', $access_token, $data);

    if ($response && $response['self_link']) {
        return 'Broadcast scheduled successfully';
    }

    // 'Broadcast failed to scheduled';
    return $response['error']['message']; // check the errors in the $response array

}

function validateAweberToken($details)
{
    $now = Carbon::now('UTC');
    $date = new Carbon($details->expires_in, 'UTC');
    return $now->greaterThanOrEqualTo($date);
}

function refreshAweberToken($details)
{
    $end_point = 'oauth2/token';
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'];
    $verb = 'POST';
    $base_url = 'https://auth.aweber.com';
    $data = ['grant_type' => 'refresh_token', 'refresh_token' => $details->refresh_token];
    $now = Carbon::now('UTC');
    $authentication_type = 'basic';

    $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type, $access_token = null, $data);

    if ($response && isset($response->refresh_token)) {
        //save new data
        $details->refresh_token = $response->refresh_token;
        $details->access_token = $response->access_token;
        $details->expires_in = $now->addHours($response->expires_in / 3600);
        $details->save();
    }
    return $details;
}


function validateGetResponseToken($details)
{
    $now = Carbon::now('UTC');
    $date = new Carbon($details->expires_in, 'UTC');
    return $now->greaterThanOrEqualTo($date);
}

function refreshGetResponseToken($details)
{
    $verb = 'POST';
    $base_url = env('GET_RESPONSE_ENDPOINT');
    $end_point = 'token';
    $data = ['grant_type' => 'refresh_token', 'refresh_token' => $details->refresh_token];
    $now = Carbon::now('UTC');
    $authentication_type = 'basic';
    $access_token = $details->access_token;
    $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data);

    if ($response && isset($response->refresh_token)) {
        //save new data
        $details->refresh_token = $response->refresh_token;
        $details->access_token = $response->access_token;
        $details->expires_in = $now->addHours($response->expires_in / 3600);
        $details->save();
    }
    return $details;
}

function sendAweberBroadcast($user, $group_id, $details)
{

    // Get user API details
    $integration = AweberIntegration::where('user_id', $user->id)->first();

    if ($integration) {

        // Check current token validity
        if (validateAweberToken($integration)) {
            // refresh token
            $integration = refreshAweberToken($integration);
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'];
        $verb = 'post';
        $base_url = env('AWEBER_BASE_URL', 'https://api.aweber.com/1.0');
        $data = ['body_html' => $details->message, 'body_text' => '', 'subject' => $details->subject];

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            // https://api.aweber.com/1.0/accounts/1793299/lists/6090248/broadcasts

            $end_point = 'accounts/' . $integration->account_id . '/lists/' . $list_id . '/broadcasts';

            $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type = 'urlencoded', $integration->access_token, $data);

            if ($response && $response['self_link']) {
                //trigger schedule broadcast
                return scheduleAWeberBroadcastRequest($response['self_link'], $integration->access_token);
            }
        }
    }
}

function createACampaignMessage($user, $integration, $details, $list_id)
{
    $url = $integration->url;
    $api_key = $integration->api_key;

    $params = array(
        'api_key'      => $api_key,
        'api_action'   => 'message_add',
        'api_output'   => 'json'
    );

    // here we define the data we are posting in order to perform an update
    $post = array(
        'format'    => 'html',
        'subject'      => $details->subject,
        'fromemail'      => $user->email,
        'fromname'      => $user->first_name . ' ' . $user->last_name,
        'reply2'            => $user->email,
        'priority'        => '1',
        'charset'          => 'utf-8',
        'encoding'        => 'quoted-printable',
        'htmlconstructor'    => 'editor',
        'html'            => $details->message,
        'text'            => strip_tags($details->message),
        'textfetch'            => $user->website,
        'textfetchwhen'      => 'send',
        'p[' . $list_id . ']'    => intval($list_id), // example list ID
    );


    // This section takes the input fields and converts them to the proper format
    $query = "";
    foreach ($params as $key => $value) $query .= urlencode($key) . '=' . urlencode($value) . '&';
    $query = rtrim($query, '& ');
    $data = "";
    foreach ($post as $key => $value) $data .= urlencode($key) . '=' . urlencode($value) . '&';
    $data = rtrim($data, '& ');
    $url = rtrim($url, '/ ');
    // This sample code uses the CURL library for php to establish a connection,
    // submit your request, and show (print out) the response.
    if (!function_exists('curl_init')) die('CURL not supported. (introduced in PHP 4.0.2)');
    // If JSON is used, check if json_decode is present (PHP 5.2.0+)
    if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
        die('JSON not supported. (introduced in PHP 5.2.0)');
    }
    // define a final API request - GET
    $api = $url . '/admin/api.php?' . $query;
    $response = postACampaignCurl($api, $data);

    if (!$response) {
        die('Nothing was returned. Do you have a connection to Email Marketing server?');
    }
    $result = json_decode($response, TRUE);

    // Result info that is always returned

    if ($result['result_code'] == 1) {
        return array(
            'status_code' => $result['result_code'],
            'message_id' => $result['id'],
            'subject' => $details->subject,
            'message' => $result['result_message'],
        );
    } else {
        return array(
            'status_code' => $result['result_code'],
            'message' => $result['result_message'],
        );
    }
}

function createACampaignCampaign($user, $integration, $details, $list_id, $message_id)
{
    $url = $integration->url;
    $api_key = $integration->api_key;

    $params = array(
        'api_key'      => $api_key,
        'api_action'   => 'campaign_create',
        'api_output'   => 'json'
    );
    $post = array(
        'type' => 'single',
        'segmentid'        => 0,
        'name'      => $details->subject . ':' . date("m/d/Y H:i", strtotime("now")),
        'sdate'      => date("Y-m-d H:i", strtotime("now")),
        'status'        => 1,
        // if campaign should be visible via public side
        'public'        => 0,
        'tracklinks'    => 'all',
        'trackreads'    => 1,
        'trackreplies'    => 0,
        // append unsubscribe link to the bottom of HTML body
        'htmlunsub'        => 1,
        // append unsubscribe link to the bottom of TEXT body
        'textunsub'        => 1,
        'p[' . $list_id . ']'  => intval($list_id),
        'm[' . $message_id . ']'  => $message_id,
    );

    // This section takes the input fields and converts them to the proper format
    $query = "";
    foreach ($params as $key => $value) $query .= urlencode($key) . '=' . urlencode($value) . '&';
    $query = rtrim($query, '& ');
    $data = "";
    foreach ($post as $key => $value) $data .= urlencode($key) . '=' . urlencode($value) . '&';
    $data = rtrim($data, '& ');
    $url = rtrim($url, '/ ');
    // This sample code uses the CURL library for php to establish a connection,
    // submit your request, and show (print out) the response.
    if (!function_exists('curl_init')) die('CURL not supported. (introduced in PHP 4.0.2)');
    // If JSON is used, check if json_decode is present (PHP 5.2.0+)
    if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
        die('JSON not supported. (introduced in PHP 5.2.0)');
    }
    // define a final API request - GET
    $api = $url . '/admin/api.php?' . $query;
    $response = postACampaignCurl($api, $data);

    if (!$response) {
        die('Nothing was returned. Do you have a connection to Email Marketing server?');
    }
    $result = json_decode($response, TRUE);

    if ($result['result_code'] == 1) {
        return array(
            'status_code' => $result['result_code'],
            'campaign_id' => $result['id'],
            'campaign_name' => $details->subject,
            'message' => $result['result_message'],
        );
    } else {
        return array(
            'status_code' => $result['result_code'],
            'message' => $result['result_message'],
        );
    }
}

function sendACampaignBroadcast($user, $group_id, $details)
{

    // Get user API details
    $integration = ActiveCampaignIntegration::where('user_id', $user->id)->first();

    if ($integration) {

        $data = ['body_html' => $details->message, 'body_text' => strip_tags($details->message), 'subject' => $details->subject];

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            // create a message first
            $msg = createACampaignMessage($user, $integration, $details, $list_id);

            // then create a campaign with the message_id
            if ($msg['status_code'] == 1) {
                $message_id = $msg['message_id'];
                return createACampaignCampaign($user, $integration, $details, $list_id, $message_id);
            }
        }
    }
}


function addSingleAweberSubscriber($user, $group_id, $email, $name)
{
    // Get user API details
    $integration = AweberIntegration::where('user_id', $user->id)->first();

    if ($integration) {
        // Check current token validity

        if (validateAweberToken($integration)) {
            // refresh token
            $integration = refreshAweberToken($integration);
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json', 'Access-Control-Allow-Origin' => '*'];
        $verb = 'POST';
        $base_url = env('AWEBER_BASE_URL', 'https://api.aweber.com/1.0');
        $data = 'update_existing=true&email=' . $email . '&name=' . $name;

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            // https://api.aweber.com/1.0/accounts/{accountId}/lists/{listId}/subscribers

            $end_point = 'accounts/' . $integration->account_id . '/lists/' . $list_id . '/subscribers';

            return aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type = 'curl', $integration->access_token, $data);
        }
    }
}


function addSingleGetResponseSubscriber($user, $group_id, $email, $name)
{
    // Get user API details
    $integration = GetResponseIntegration::where('user_id', $user->id)->first();

    if ($integration) {

        if (validateGetResponseToken($integration)) {
            // refresh token
            $integration = refreshGetResponseToken($integration);
        }

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            $form_data = [
                "name" => $name,
                "email" => $email,
                "dayOfCycle" => "5",
                "campaign" => [
                    "campaignId" => $list_id
                ],
            ];

            $verb = 'POST';
            $base_url = env('GET_RESPONSE_ENDPOINT');
            $end_point = 'contacts';
            $access_token = $integration->access_token;
            $authentication_type = 'token';
            return getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $form_data);
        }
    }
}

function getResponseFromFieldId($data)
{
    $verb = 'GET';
    $base_url = env('GET_RESPONSE_ENDPOINT');
    $end_point = 'from-fields';
    $authentication_type = 'token';
    $access_token = $data->access_token;
    return getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data);
}


function sendGetResponseBroadcast($user, $group_id, $details)
{

    // Get user API details
    $integration = GetResponseIntegration::where('user_id', $user->id)->first();

    if ($integration) {

        if (validateGetResponseToken($integration)) {
            // refresh token
            $integration = refreshGetResponseToken($integration);
        }

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            $now = Carbon::now('UTC');
            $now->addMinutes(3);
            $now = date("Y-m-d\\TH:i:sO", strtotime($now));

            $getFromFieldId = getResponseFromFieldId($integration);
            $getFromFieldId = json_decode(json_encode($getFromFieldId), True);
            $fromFieldId = $getFromFieldId[0]['fromFieldId'];

            $form_data = array(
                "content" => [
                    "html" => $details->message,
                    "plain" => strip_tags($details->message)
                ],
                "flags" => [
                    "openrate"
                ],
                "name" => $details->subject . ' : ' . date("m/d/Y H:i", strtotime("now")),
                "type" => "broadcast",
                "editor" => "custom",
                "subject" => $details->subject,
                "fromField" => (object)[
                    "fromFieldId" => $fromFieldId
                ],
                "replyTo" => (object)[
                    "fromFieldId" => $fromFieldId
                ],
                "campaign" => (object)[
                    "campaignId" => $list_id
                ],
                "sendOn" => $now,
                "sendSettings" => [
                    "selectedCampaigns" => [
                        $list_id
                    ],
                    "timeTravel" => "true",
                    "perfectTiming" => "false",
                    "selectedSegments" => [],
                    "selectedSuppressions" => [],
                    "excludedCampaigns" => [],
                    "excludedSegments" => [],
                    "selectedContacts" => []
                ]
            );

            $verb = 'POST';
            $base_url = env('GET_RESPONSE_ENDPOINT');
            $end_point = 'newsletters';
            $access_token = $integration->access_token;
            $authentication_type = 'token';
            return getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $form_data);
        }
    }
}


function addSingleACampaignSubscriber($user, $group_id, $email, $first_name, $last_name)
{
    // Get user API details
    $integration = ActiveCampaignIntegration::where('user_id', $user->id)->first();

    if ($integration) {

        $url = $integration->url;
        $api_key = $integration->api_key;

        // Get integration group list attachment
        $attachment = IntegrationGroupList::where('group_id', $group_id)->first();

        if ($attachment) {

            $list_id = $attachment->list_id;

            $params = array(
                'api_key'      => $api_key,
                'api_action'   => 'contact_add',
                'api_output'   => 'json',
            );

            // here we define the data we are posting in order to perform an update
            $post = array(
                'email'                    => $email,
                'first_name'               => $first_name,
                'last_name'                => $last_name,
                'p[' . $list_id . ']'                   => intval($list_id), // example list ID (REPLACE '123' WITH ACTUAL LIST ID, IE: p[5] = 5)
                'instantresponders[0]' => 1, // set to 0 to if you don't want to sent instant autoresponders
            );

            // This section takes the input fields and converts them to the proper format
            $query = "";
            foreach ($params as $key => $value) $query .= urlencode($key) . '=' . urlencode($value) . '&';
            $query = rtrim($query, '& ');

            // This section takes the input data and converts it to the proper format
            $data = "";
            foreach ($post as $key => $value) $data .= urlencode($key) . '=' . urlencode($value) . '&';
            $data = rtrim($data, '& ');

            // clean up the url
            $url = rtrim($url, '/ ');

            // This sample code uses the CURL library for php to establish a connection,
            // submit your request, and show (print out) the response.
            if (!function_exists('curl_init')) die('CURL not supported. (introduced in PHP 4.0.2)');

            // If JSON is used, check if json_decode is present (PHP 5.2.0+)
            if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
                die('JSON not supported. (introduced in PHP 5.2.0)');
            }

            // define a final API request - GET
            $api = $url . '/admin/api.php?' . $query;
            $response = postACampaignCurl($api, $data);

            if (!$response) {
                die('Nothing was returned. Do you have a connection to Email Marketing server?');
            }
            // Result info that is always returned
            $result = json_decode($response, TRUE);

            if ($result['result_code'] == 1) {
                return array(
                    'status_code' => $result['result_code'],
                    'subscriber_id' => $result['subscriber_id'],
                    'message' => $result['result_message'],
                );
            } else {
                return array(
                    'status_code' => $result['result_code'],
                    'message' => $result['result_message'],
                );
            }
        }
    }
}

function getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data = null)
{
    try {
        $url = $base_url . '/' . $end_point;
        $auth = base64_encode(env('GET_RESPONSE_CLIENT_ID') . ':' . env('GET_RESPONSE_CLIENT_SECRET'));

        switch ($authentication_type) {
            case 'basic':
                $options = array(
                    'http' => array(
                        'header' => ["Authorization: Basic $auth", "Content-type: application/x-www-form-urlencoded"],
                        'method'  => 'POST',
                        'ignore_errors' => true,
                        'content' => http_build_query($data)
                    )
                );
                $context  = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                return json_decode($result);
            default:
                $request = curl_init($url);
                curl_setopt($request, CURLOPT_HEADER, 0);
                curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($request, CURLOPT_CUSTOMREQUEST, $verb);
                curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($request, CURLOPT_MAXREDIRS, 10);
                curl_setopt($request, CURLOPT_TIMEOUT, 15);
                if ($verb === 'POST') {
                    curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($data));
                }
                curl_setopt(
                    $request,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Authorization: Bearer ' . $access_token
                    ),
                );
                curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($request, CURLOPT_VERBOSE, TRUE);
                curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
                $response = curl_exec($request);
                curl_close($request);
                return json_decode($response);
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function onboardingReminder($template, $user)
{

    $messages = [];
    $messages[] = $template['template'];
    $type = 'onboarding';

    $notice = [
        'messages' => $messages,
        'user' => [],
        'to' => $user->email,
        'copy' => $user->manager ? [$user->manager->email] : [],
        'bcopy' => [],
        'subject' => $template['subject'],
    ];

    ProcessEmail::dispatch($notice, $type);
}

function trimSpecial($value)
{
    $firststr = substr($value, 0, 1);
    $regex = preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $firststr);

    if ($regex) {
        $str1 = substr($value, 1);
        return $str1;
    }
    return $value;
}

function trimApostrophe($value)
{
    $newstr = str_replace('’', "'", $value);
    return $newstr;
}



function getAlias($path){
    switch ($path) {
        case 'planning-meeting':
            return 'Planning Meeting';
        case 'quarterly-review':
            return 'Quarterly Review';
        case 'dgcontent':
            return 'Content Marketing';
        case 'dgwebsite':
            return 'Website Optimization';
        case 'dgemail':
            return 'Email Marketing';
        case 'dgseo':
            return 'Search Engine Optimization';
        case 'dgsocial':
            return 'Social Media';
        case 'dgvideo':
            return 'Video Marketing';
        case 'dgmetrics':
            return 'Metrics & KPIs';
        case 'dgupsell':
            return 'One Time Offers & Cross-sell';
        case 'dgdownsell':
            return 'Downsell & Pop-Up Windows';
        case 'dgcampaign':
            return 'List Building & Drip Campaign';
        case 'products':
            return 'Additional Products & Services';
        case 'alliances':
            return 'Alliances & Joint Ventures';
        case 'bundling':
            return 'Bundling';
        case 'costs':
            return 'Cut Costs';
        case 'downsell':
            return 'Downsell';
        case 'campaign':
            return 'Drip Campaign';
        case 'prices':
            return 'Increase Prices';
        case 'internet':
            return 'Digital Marketing';
        case 'introduction':
        case 'dgintro':
            return 'Introduction';
        case 'leads':
            return 'Leads';
        case 'mdp':
        case 'dgmdp':
            return 'Market Dominating Position';
        case 'upsell':
            return 'Upsell & Cross-sell';
        case 'foundational':
            return 'Foundational';
        case 'financials':
            return 'Financials';
        case 'valuation':
            return 'Valuation';
        case 'salesgeneral':
            return 'General Questions';
        case 'salesmanager':
            return 'Sales Manager';
        case 'salescompensation':
            return 'Compensation';
        case 'salessuperstars':
            return 'Superstars';
        case 'salestraining':
            return 'Training';
        case 'salesprospecting':
            return 'Prospecting and Lists';
        case 'salesclients':
            return 'Dream Clients';
        case 'salestrade':
            return 'Trade Shows';
        case 'salesdm':
            return 'Dealing With Decision Makers';
        case 'salesclosing':
            return 'Closing the Sale';
        case 'salesorder':
            return 'Order Fulfillment';
        case 'salesremorse':
            return 'Buyers Remorse';
        case 'salesteam':
            return 'Sales Team';
        case 'strategy':
            return 'Strategy';
        case 'trust':
            return 'Trust, Expertise, Education';
        case 'policies':
            return 'Policies & Procedures';
        case 'referral':
            return 'Referral Systems';
        case 'publicity':
            return 'Publicity & PR';
        case 'mail':
            return 'Direct Mail';
        case 'advertising':
        case 'dgadvertising':
            return 'Advertising';
        case 'dgoffer':
        case 'offer':
            return 'Compelling Offer';
        case 'scripts':
            return 'Scripts';
        case 'initialclose':
            return 'Initial Close Rate';
        case 'followupclose':
            return 'Follow-up Close Rate';
        case 'formercustomers':
            return 'Reactivate Former Customers';
        case 'appointments':
            return 'More Appointments';
        case 'purchase':
            return 'Increase Frequency of Purchase';
        case 'longevity':
            return 'Increase Longevity';
        default:
            return '...';
    }
};
