<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Helpers\Cypher;
use App\Models\MailBox;
use App\Models\ExpertiseJS12;
use App\Models\ExpertiseJS40;
use App\Models\ExpertiseSales;
use App\Models\ExpertiseDigital;
use App\Models\QuotumCommitment;
use App\Models\MeetingNoteReminder;
use App\Models\PriorityQuestionnaire;
use App\Models\User;
use App\Models\QuotumLevelOne;
use App\Models\Assessment;
use App\Jobs\ProcessEmail;
use App\Models\ImpSimplifiedStep;
use App\Models\MeetingNoteImplementation;
use App\Models\ImpStep;
use App\Http\Resources\QuotumCommitmentResource;
use App\Http\Resources\QuotumLevelOneResource;
use App\Http\Resources\QuotumMiniLevelOneResource;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\PriorityQuestionnaire as QuestionnaireResource;
use App\Http\Resources\ExpertiseResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QuotumController extends Controller
{
    use ApiResponses;

    protected $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = $request->user();

            return $next($request);
        });
    }


    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCommitments(Request $request)
    {
        try {

            $rules = [
                'note_id' => 'required',
                'assessment_id' => 'required',
                'content_id' => 'required',
            ];

            $messages = [
                'note_id.required' => 'Meeting note ID is required',
                'assessment_id.required' => 'Assessment ID is required',
                'content_id.required' => 'Content ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->assessment_id));
            $meeting_note_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->note_id));
            $content_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->content_id));
            $coach_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->coach_id));

            $commitments = QuotumCommitment::where('note_id', $meeting_note_id)->where('assessment_id', $assessment_id)->where('content_id', $content_id)->get();

            $transform = QuotumCommitmentResource::collection($commitments);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Quotum Commitment not found', 400);
        }
    }


    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function manageCommitments(Request $request)
    {
        try {

            $rules = [
                'note_id' => 'required',
                'assessment_id' => 'required',
                'coach_id' => 'required',
                'company_id' => 'required',
                'content_id' => 'required',
                'type' => 'required',
            ];

            $messages = [
                'note_id.required' => 'Meeting note ID is required',
                'assessment_id.required' => 'Assessment ID is required',
                'coach_id.required' => 'Coach ID is required',
                'company_id.required' => 'Company ID is required',
                'content_id.required' => 'Content ID is required',
                'type.required' => 'Type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->assessment_id));
            $meeting_note_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->note_id));
            $content_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->content_id));
            $company_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->company_id));
            $coach_id = $cypher->decryptID(env('HASHING_SALT'), trim($request->coach_id));

            $assessment = Assessment::findOrFail($assessment_id);

            $name = trim($request->name);
            $type = trim($request->type);
            $checked = (bool)$request->checked;
            
            $quotum = QuotumCommitment::where('note_id', $meeting_note_id)->where('assessment_id', $assessment_id)->where('type', $name)->where('content_id', $content_id)->first();
            
            if($quotum && ($checked == false)){ // Commitment already exist so remove it
                $commitment = MeetingNoteReminder::findOrFail($quotum->commitment_id);
                $commitment->delete();
                $quotum->delete();

                // Return the list of the current commitments
                $commitments = QuotumCommitment::where('note_id', $meeting_note_id)->where('assessment_id', $assessment_id)->where('content_id', $content_id)->get();
            
                $transform = QuotumCommitmentResource::collection($commitments);
                return $this->showMessage($transform, 200);

            }else{

                if($type == 'quotum'){

                    $content = QuotumLevelOne::where(['_id' => $content_id])->first();

                    if($content){

                        $today = new Carbon();
                        $today->addWeeks(2); // Add 2 weeks from today
                        
                        $date = $today->format('Y-m-d H:i:s');
                        $time = $today->format('H:i:s');

                        $commitment = new MeetingNoteReminder;
                        $commitment->meeting_note_id = $meeting_note_id;
                        $commitment->note = $content->description;
                        $commitment->reminder_date = $date;
                        $commitment->reminder_time = $time;
                        $commitment->type = ($name == 'coach_commitment')? 'coach' : 'client';

                        $commitment->save();
                        $commitment = $commitment->refresh();

                        $new_quotum = new QuotumCommitment;
                        $new_quotum->commitment_id = $commitment->id;
                        $new_quotum->note_id = $meeting_note_id;
                        $new_quotum->assessment_id = $assessment_id;
                        $new_quotum->coach_id = $coach_id;
                        $new_quotum->company_id = $company_id;
                        $new_quotum->content_id = $content_id;
                        $new_quotum->type = $name;

                        $new_quotum->save();

                        // Return the list of the current commitments
                        $commitments = QuotumCommitment::where('note_id', $meeting_note_id)->where('assessment_id', $assessment_id)->where('content_id', $content_id)->get();

                        $transform = QuotumCommitmentResource::collection($commitments);
                        return $this->showMessage($transform, 200);
                    }

                }else if($type == 'simplified'){
                    $content = ImpSimplifiedStep::findOrFail($content_id);
                }else if($type == 'old'){
                    $content = ImpStep::findOrFail($content_id);
                }

                if(($type == 'simplified') || ($type == 'old')){
                    if($content){

                        $today = new Carbon();
                        $today->addWeeks(2); // Add 2 weeks from today
                        
                        $date = $today->format('Y-m-d H:i:s');
                        $time = $today->format('H:i:s');

                        $commitment = new MeetingNoteReminder;
                        $commitment->meeting_note_id = $meeting_note_id;
                        $commitment->note = $content->body;
                        $commitment->reminder_date = $date;
                        $commitment->reminder_time = $time;
                        $commitment->type = ($name == 'coach_commitment')? 'coach' : 'client';

                        $commitment->save();
                        $commitment = $commitment->refresh();

                        $new_quotum = new QuotumCommitment;
                        $new_quotum->commitment_id = $commitment->id;
                        $new_quotum->note_id = $meeting_note_id;
                        $new_quotum->assessment_id = $assessment_id;
                        $new_quotum->coach_id = $coach_id;
                        $new_quotum->company_id = $company_id;
                        $new_quotum->content_id = $content_id;
                        $new_quotum->type = $name;

                        $new_quotum->save();

                        // Return the list of the current commitments
                        $commitments = QuotumCommitment::where('note_id', $meeting_note_id)->where('assessment_id', $assessment_id)->where('content_id', $content_id)->get();

                        $transform = QuotumCommitmentResource::collection($commitments);
                        return $this->showMessage($transform, 200);
                    }
                }
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unable to save/delete commitment', 400);
        }
    }




    /**
     * Add responses to the priorities questionnaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveQuestionnaire(Request $request)
    {

        try {

            $cypher = new Cypher;
            $company_id = $cypher->decryptID(env('HASHING_SALT'), $request->company_id);
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);
            $user_id = $cypher->decryptID(env('HASHING_SALT'), $request->user_id);
            $any = MeetingNoteImplementation::where('assessment_id', $assessment_id)->first();
            $signal = null;

            foreach ($request->response as $key => $item) {

                if(($item['name'] == 'recommendation') && ($any != null)){
                    $signal = true;
                    break;
                }

                $array = [$item['name'] => $item['value']];
                $questionnaire = PriorityQuestionnaire::updateOrCreate(['company_id' => $company_id, 'user_id' => $user_id],$array);
                
                // Add planning meetings to the assessment model 
                if($item['name'] == 'q4'){
                    $value = (int)$item['value'];
                    $assessment = Assessment::find($assessment_id);
                    $assessment->add_planning_meetings = ($value > 0)? 1 : 0;
                    $assessment->planning_meetings = $value;
                    $assessment->save();
                }

                // Toggle review meetings to the assessment model 
                if($item['name'] == 'q5'){
                    $assessment = Assessment::find($assessment_id);
                    $assessment->add_review_meetings = ($item['value'] == 'Yes')? 1 : 0;
                    $assessment->save();
                }
            }

            if($signal){
                return $this->successResponse("SORRY: Your client is already working on this assessment impementation. You cannot change this recommendation", 201);
            }else{
                $questionnaire = PriorityQuestionnaire::where('company_id', $company_id)->first();

                $transform = new QuestionnaireResource($questionnaire);

                return $this->successResponse($transform, 200);
            }

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Priority questionnaire does not exist', 200);
        }
    }


    /**
     * Add responses to the priorities questionnaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getQuestionnaire(Request $request)
    {

        try {

            $cypher = new Cypher;
            $company_id = $cypher->decryptID(env('HASHING_SALT'), $request->company_id);

            $questionnaire = PriorityQuestionnaire::where('company_id', $company_id)->first();

            $transform = new QuestionnaireResource($questionnaire);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Priority questionnaire does not exist', 200);
        }
    }


    /**
     * Add responses to the expertise tables.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function expertise(Request $request)
    {

        try {

            $cypher = new Cypher;
            $user_id = $cypher->decryptID(env('HASHING_SALT'), $request->user_id);

            foreach ($request->expertise as $key => $item) {

                $array = [$item['name'] => $item['value']];

                if($item['type'] == 'js_12_expertise'){
                    ExpertiseJS12::updateOrCreate(['user_id' => $user_id ], $array);
                }

                if($item['type'] == 'js_40_expertise'){
                    ExpertiseJS40::updateOrCreate(['user_id' => $user_id ], $array);
                }

                if($item['type'] == 'sales_expertise'){
                    ExpertiseSales::updateOrCreate(['user_id' => $user_id ], $array);
                }

                if($item['type'] == 'digital_expertise'){
                    ExpertiseDigital::updateOrCreate(['user_id' => $user_id ], $array);
                }
            }

            $response_js12 = ExpertiseJS12::where('user_id', $user_id)->first();
            $response_js40 = ExpertiseJS40::where('user_id', $user_id)->first();
            $response_sales = ExpertiseSales::where('user_id', $user_id)->first();
            $response_digital = ExpertiseDigital::where('user_id', $user_id)->first();

            $response = (object)[
                'user_id' => $user_id, 
                'js_12_expertise' => $response_js12, 
                'js_40_expertise' => $response_js40, 
                'sales_expertise' => $response_sales, 
                'digital_expertise' => $response_digital, 
            ];

            $transform = new ExpertiseResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Expertise does not exist', 200);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
    }


     /*
     * Get LevelOne Content
     *
     *
     * @param Request $request
     * @return void
     */

    public function getLevelOne(Request $request)
    {
        $rules = [
            'path' => 'required',
        ];

        $messages = [
            'path.required' => 'Path is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {

            $responses = QuotumLevelOne::where(['path' => trim($request->path)])->orderBy('step')->get();
            
            if(count($responses) > 0){

                $array = [];
                
                foreach ($responses as $key => $response) {

                    $array[] = (object)[
                        'id' => (string)$response->_id, 
                        'description' => $response->description,
                        'step' => $response->step,
                    ];

                }// end of foreach
                
                return $this->successResponse(QuotumMiniLevelOneResource::collection($array), 200);

            } // End  of if(count($responses) > 0){

            return $this->successResponse([], 200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find level one content on that path" . $ex->getMessage(), 404);
        }
    }


    /**
     * Get Quotum Content as a nested JSON
     * 
     *
     * @param Request $request
     * @return void
     */

    public function getQuotum(Request $request)
    {
        $rules = [
            'path' => 'required',
        ];

        $messages = [
            'path.required' => 'Path is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {

            if($request->id){
                $response = QuotumLevelOne::where(['path' => $request->path])->where(['_id' => $request->id])->first();
            }else{
                $responses = QuotumLevelOne::where(['path' => $request->path])->get();
            }
            
            if(!isset($request->id) && count($responses) > 0){

                $array = [];
                
                foreach ($responses as $key => $response) {

                    $details = (object)['module' => $response->module, 'path' => $response->path];

                    $array[] = (object)[
                        'id' => (string)$response->_id,
                        'parent_id' => (string)$response->parent_id, 
                        'description' => $response->description,
                        'step' => $response->step,
                        'module' => $response->module,
                        'path' => $response->path,
                        'status' => $response->status,
                        'children' => $this->formatQuotumLevel($response->children, $details, true)
                    ];

                }// end of foreach
                
                return $this->successResponse(QuotumLevelOneResource::collection($array), 200);

            }else if(isset($request->id) && $response){
                
                $details = (object)['module' => $response->module, 'path' => $response->path];

                $resp = (object)[
                    'id' => (string)$response->_id,
                    'parent_id' => (string)$response->parent_id, 
                    'description' => $response->description,
                    'step' => $response->step,
                    'module' => $response->module,
                    'path' => $response->path,
                    'status' => $response->status,
                    'children' => $this->formatQuotumLevel($response->children, $details, true)
                ];
                
                return $this->successResponse(new QuotumLevelOneResource($resp), 200);
            }

            if(isset($request->id)){
                return $this->successResponse(null, 200);
            }
            
            return $this->successResponse([], 200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find quotum responses on that path" . $ex->getMessage(), 404);
        }
    }


    /**
     * Send an email 
     * Content of email is the implementation steps
     * 
     *
     * @param Request $request
     * @return void
     */

    public function sendQuotumEmail(Request $request)
    {
        $rules = [
            'to' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ];

        $messages = [
            'to.required' => 'Email recipient is required',
            'subject.required' => 'Email subject is required',
            'message.required' => 'Email message is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            
            $note = trimSpecial(strip_tags(trim($request->message)));
            $subject = strip_tags(trim($request->subject));
            $cc = strip_tags(trim($request->cc));
            
            $copy = [];

            if(strlen($cc) > 0){
                $clean_cc = str_replace(' ', '', $cc);
                $copy = explode(',', trim($clean_cc));
                $copy[] = $this->user->email;
            }else{
                $copy[] = $this->user->email;
            }

            $details = [
                'user' => $this->user,
                'to' => trim($request->to),
                'messages' => $note,
                'subject' => $subject,
                'copy' => $copy,
                'bcopy' => ['pasmailaudit@focused.com'],
            ];

            ProcessEmail::dispatch($details, 'onboarding-email');

            $cleaned = (object)[
                'to' => trim($request->to),
                'subject' => $subject,
                'message' => $note
            ];

            $this->saveToMailBox($cleaned);

            return $this->showMessage("Email sent successfully", 200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Unable to send the quotum email" . $ex->getMessage(), 404);
        }
    }


    private function saveEmailToS3($text)
    {
        $s3 = \AWS::createClient('s3');
        $key = date('mdYhia') . '_' . str_random(6);
        $url = $s3->putObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'mailbox/emails/' . $key,
            'ACL'          => 'public-read',
            'ContentType' => 'text/plain',
            'Body' => $text,
        ]);

        $url = $url->get('ObjectURL');
        return $url;
    }

    private function saveToMailBox($request){

        $touser = User::where('email', $request->to)->first();

        // Save the email records to DB
        $box = new MailBox;

        $box->from = $this->user->email;
        $box->to = $request->to;
        $box->from_id = $this->user->id;
        $box->to_id = ($touser)? $touser->id : null;
        
        $box->uuid = uniqid(); // Create a unique id for email
        $box->subject = $request->subject;
        $html = Str::markdown($request->message);
        $url = $this->saveEmailToS3(base64_encode($html));
        $box->body = $url;
        $box->read = 0;
        $box->save();
    }


    /**
     * Get Email Content
     * This is level one content and level 2 highlight
     * 
     *
     * @param Request $request
     * @return void
     */

    public function getEmailQuotum(Request $request)
    {
        $rules = [
            'path' => 'required',
            'id' => 'required',
        ];

        $messages = [
            'path.required' => 'Path is required',
            'id.required' => 'ID is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {

            $response = QuotumLevelOne::where(['path' => $request->path])->where(['_id' => $request->id])->first();
            
            if($response){
                
                $details = (object)['module' => $response->module, 'path' => $response->path];

                $resp = (object)[
                    'id' => (string)$response->_id,
                    'parent_id' => (string)$response->parent_id, 
                    'description' => $response->description,
                    'step' => $response->step,
                    'module' => $response->module,
                    'path' => $response->path,
                    'status' => $response->status,
                    'children' => $this->formatQuotumLevel($response->children, $details, false)
                ];

                $template = $this->formatEmailTemplate($resp);
                
                return $this->successResponse($template, 200);

            } // End  of if($response){

            return $this->successResponse(null, 200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find quotum responses on that path" . $ex->getMessage(), 404);
        }
    }


    private function formatQuotumLevel($items, $details, $status){
        
        $formated = [];
        
        if($items && count($items) > 0){
            foreach ($items as $key => $item) { 
                if($status){
                    $formated[] = (object)[
                        'id' => (string)$item->_id,
                        'parent_id' => (string)$item->parent_id, 
                        'description' => $item->description,
                        'module' => $details->module,
                        'path' => $details->path,
                        'status' => $item->status,
                        'children' => $this->formatQuotumLevel($item->children, $details, true)
                    ];
                }else{
                    $formated[] = (object)[
                        'id' => (string)$item->_id,
                        'parent_id' => (string)$item->parent_id, 
                        'description' => $item->description,
                        'module' => $details->module,
                        'path' => $details->path,
                        'status' => $item->status
                    ];
                } 
                 
            }
        }

        return $formated;
        
    }

    private function formatEmailTemplate($data){

        $module_name = getAlias($data->path);
        $list = '';
        $items = '';
        foreach ($data->children as $key => $each) {
            $list .= "<li>".$each->description."</li>";
            $items .= ($key+1).": ".$each->description."\n\n";
        }

        $body = "<p>Hi [Recipient name],</p>
        <p>Here's one of our implementation steps in the area of ".$module_name.":</p>
        <p><b><u>".$data->description."</u></b></p>
        <ol>".$list."</ol>
        <p>To your success</p>
        <p>".$this->user->first_name."</p>
        ";

        $text = $data->description."\n\n".$items;

        $subject = "One of our implementation steps in ".$module_name;

        return (object)['subject' => $subject, 'body' => $body, 'text' => $text];
    }

}
