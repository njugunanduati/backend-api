<?php

namespace App\Http\Controllers;

use App\Helpers\CSVReader;
use App\Helpers\GPTClient;
use App\Http\Resources\AIInternResource;
use App\Http\Resources\AISuggestion;
use App\Http\Resources\Response;
use App\Models\AIHowToHistory;
use App\Models\AIInterns;
use App\Models\AIResponseHistory;
use App\Models\Business;
use App\Models\Question;
use App\Models\LiveSearch;
use App\Models\Path;
use App\Models\Suggestion;
use App\Traits\ApiResponses;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Validator;
use Log;

class SuggestionController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $suggestions = Suggestion::all();
        return $this->successResponse($suggestions, 200);
    }

    /**
     * Seatch for suggestions
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/searchSuggestions?topic=costs&business=Advertising%20agency
     *
     * @param Request $request
     * @return void
     */

    public function searchSuggestions(Request $request)
    {
        $rules = [
            'topic' => 'required',
            'business' => 'required',
        ];

        $messages = [
            'topic.required' => 'Topic is required',
            'business.required' => 'Business is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            if ($request->topic == 'costs' && !empty($request->alias)) {
                $q = Suggestion::where('path' ,$request->topic)->where('alias',$request->alias)->first();
            } else {
               $q = Suggestion::where('path' ,$request->topic)->first();
            }

            if($q){
                $responses = $q->responses->where('business',$request->business)->all();
                
                $transform = new AISuggestion((object)[
                    '_id' => $q->_id, 
                    'question' => $q->question, 
                    'path' => $q->path, 
                    'alias' => $q->alias,
                    'responses' => $responses
                ]);
                return $this->successResponse($transform, 200);
            }else{
                return $this->successResponse(null, 200);
            }

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find a suggestion with the : $request->topic or $request->business " . $ex->getMessage(), 404);
        }
    }


    /**
     * Get Paths
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/ai/paths?module=ProfitJumpstart
     *
     * @param Request $request
     * @return void
     */

    public function getPaths(Request $request)
    {
        $rules = [
            'module' => 'required',
        ];

        $messages = [
            'module.required' => 'Module is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $paths = Path::where(['module' => $request->module])->get();
            return $this->successResponse($paths, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find paths" . $ex->getMessage(), 404);
        }
    }

    /**
     * Get Questions
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/ai/questions?path=costs
     *
     * @param Request $request
     * @return void
     */

    public function getQuestions(Request $request)
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
            $questions = Suggestion::where(['path' => $request->path])->get(['question']);
            return $this->successResponse($questions, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find questions" . $ex->getMessage(), 404);
        }
    }


    /**
     * Get Responses
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/ai/responses?path=costs&business=Advertising agency&question=What are 21 ways to cut COGS or variable costs
     *
     * @param Request $request
     * @return void
     */

    public function getResponses(Request $request)
    {
        $rules = [
            'path' => 'required',
            'question' => 'required',
            'business' => 'required',
        ];

        $messages = [
            'path.required' => 'Path is required',
            'business.required' => 'Business is required',
            'question.required' => 'Question is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $responses = Suggestion::where([
                'path' => $request->path,
                'question' => $request->question,
                'responses.business' => $request->business
            ])->first(['question', 'responses.description']);
            return $this->successResponse($responses, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find the responses" . $ex->getMessage(), 404);
        }
    }



    /**
     * Get Businesses
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/ai/businesses
     *
     * @param Request $request
     * @return void
     */

    public function getBusinesses(Request $request)
    {
        try {
            $businesses = Business::all();
            return $this->successResponse($businesses, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find businesses" . $ex->getMessage(), 404);
        }
    }



    /**
     * Get Businesses
     * 
     * 
     * Example URL: http://localhost:9999/api/internal/ai/businesses
     *
     * @param Request $request
     * @return void
     */

    public function listquestions(Request $request)
    {
        try {
            $questions = Question::all();
            return $this->successResponse($questions, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find questions" . $ex->getMessage(), 404);
        }
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $suggestion = Suggestion::create($request->all());
            return $this->successResponse($suggestion, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('There is an issue saving a new suggestion: ' . $ex->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Suggestion  $suggestion
     * @return \Illuminate\Http\Response
     */
    public function show(Suggestion $suggestion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Suggestion  $suggestion
     * @return \Illuminate\Http\Response
     */
    public function edit(Suggestion $suggestion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Suggestion  $suggestion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Suggestion $suggestion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Suggestion  $suggestion
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            Suggestion::find($id)->delete();
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('There is an issue deleting the suggestion: ' . $ex->getMessage(), 500);
        }
    }

    public function uploadSuggestions(Request $request)
    {
        try {
            $rules = [
                'file' => 'required',
                'target' => 'required',
            ];

            $messages = [
                'file.required' => 'File is required',
                'target.required' => 'Target is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $file = $request->file('file'); // The CSV File



            $csv = CSVReader::read($file);

            switch ($request->target) {

                case 'responses':
                    return $this->upsertResponses($request, $csv);
                    break;
                case 'how-tos':
                    return $this->upsertHowTos($request, $csv);
                    break;

                case 'questions':
                default:
                    return $this->upsertQuestions($csv);
                    break;
            }


            return $this->successResponse("CSV File Uploaded", 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function liveSearch(Request $request)
    {

        $rules = [
            'question' => 'required',
            'userid' => 'required|exists:users,id',
        ];

        $messages = [
            'question.required' => 'Question is required',
            'userid.required' => 'User ID is required',
            'userid.exists' => 'That user does not exist',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {


            // Search ChatGPT
            $question = trim($request->question);
            $search = new GPTClient($question);

            LiveSearch::create([
                'user_id' => $request->userid,
                'question' => $question,
            ]);

            return $this->successResponse($search->response, 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    protected function upsertQuestions($data)
    {
        $uploadedQuestions = [];
        try {
            foreach ($data as $row) {
                $question = Suggestion::updateOrCreate([
                    'question' => $row['question']
                ], $row);
                array_push($uploadedQuestions, $question);
            }
            return $this->successResponse(['message' => 'Questions have been added', 'payload' => $uploadedQuestions], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function upsertResponses(Request $request, $data)
    {
        try {
            $suggestion = Suggestion::where([
                'question' => $request->question,
                'path' => $request->path ?: 'mdp',
                'module' => $request->module ?: 'ProfitJumpstart'
            ])->first();

            if (!$suggestion) {
                $suggestion = new Suggestion;
                $suggestion->module = $request->module ?: 'ProfitJumpstart';
                $suggestion->question = $request->question;
                $suggestion->path = $request->path ?: 'mdp';
            }

            foreach ($data as $row) {
                $description = $row['description'];

                $existingResponses = $suggestion->responses->where('description', $description)->all();

                if (sizeOf($existingResponses) == 0) {
                    $suggestion->responses()->create(['description' => $description, 'business' => $request->business]);
                }
            }
            $suggestion->save();

            return $this->successResponse(['message' => 'Responses have been added', 'payload' => $suggestion], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function upsertHowTos(Request $request, $data)
    {
        try {
            $suggestion = Suggestion::where([
                'question' => $request->question,
                'path' => $request->path ?: 'mdp',
                'module' => $request->module ?: 'ProfitJumpstart'
            ])->first();

            $suggestionResponse = $suggestion->responses->where('description', $request->response)->first();

            foreach ($data as $row) {
                $description = array_values($row)[0];

                $existingHowTos = $suggestionResponse->howtos()->where('description', $description)->all();

                if (!$existingHowTos || sizeof($existingHowTos) == 0) {
                    $suggestionResponse->howtos()->create(['description' => $description]);
                }
            }
            $suggestion->save();

            return $this->successResponse(['message' => 'How-To statements have been added', 'payload' => $suggestion], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function authenticateInterns(Request $request)
    {
        try {
            $intern = AIInterns::where([
                'email' => $request->email,
                'password' => $request->password
            ])->first();
            if ($intern) {
                return $this->successResponse(['message' => 'User has been authenticated', 'payload' => new AIInternResource($intern)], 200);
            } else {
                return $this->errorResponse("Cannot find a user or the credentials are incorrect", 404);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse("Cannot find a user or the credentials are incorrect" . $ex->getMessage(), 404);
        }
    }

    protected function addResponseHistory(Request $request)
    {

        $rules = [
            'path' => 'required',
            'response' => 'required',
            'user_id' => 'required',
            'assessment_id' => 'required',
        ];

        $messages = [
            'path.required' => 'Path is required',
            'response.required' => 'Response is required',
            'user_id.required' => 'User ID is required',
            'assessment_id.required' => 'Assessment ID is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $suggestions = Suggestion::where([
                'path' => $request->path
            ])->get();
            $response = null;
            $questionId = null;
            $suggestions->each(function ($suggestion) use ($request, &$response, &$questionId) {
                $found = $suggestion->responses->where('description', $request->response)->first();
                if ($found) {
                    $response =  $found;
                    $questionId = $suggestion->_id;
                }
            });

            if ($response) {
                $payload = AIResponseHistory::firstOrCreate([
                    'user_id' => (int)$request->user_id,
                    'response_id' => $response->_id,
                    'question_id' => $questionId,
                    'assessment_id' => (int)$request->assessment_id,
                    'path' => $request->path
                ]);
            }

            return $this->successResponse(['message' => 'Response History has been saved', 'payload' => $payload], 200);
        } catch (Exception $ex) {
            return $this->errorResponse('There is an issue saving the response to your history', 500);
        }
    }

    protected function getResponseHistory(Request $request)
    {
        $rules = [
            'user_id' => 'required',
        ];

        $messages = [
            'user_id.required' => 'User ID is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $records = AIResponseHistory::where('user_id', (int)$request->user_id)->get();

            $savedResponses = [];
            $records->each(function ($record) use (&$savedResponses) {
                $suggestion = Suggestion::find($record->question_id);

                if($suggestion && $suggestion->responses){
                    $response = $suggestion->responses->where('_id', $record->response_id)->first();

                    if ($response) {
                        $result = [
                            'id' => $response->_id,
                            'path' => $record->path,
                            'description' => $response->description
                        ];
                        array_push($savedResponses, $result);
                    }
                }
            });
            return $this->successResponse(['message' => 'Response History has been retrieved', 'payload' => $savedResponses], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function addHowToHistory(Request $request)
    {
        $rules = [
            'question_id' => 'required',
            'response_id' => 'required',
            'howto' => 'required',
            'user_id' => 'required',
            'assessment_id' => 'required',
            'path' => 'required'
        ];

        $messages = [
            'question_id.required' => 'Question id is required',
            'response_id.required' => 'Response id is required',
            'howto.required' => 'How To is required',
            'user_id.required' => 'User ID is required',
            'assessment_id.required' => 'Assessmennt ID is required',
            'path.required' => 'Path is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $suggestion = Suggestion::where([
                '_id' => $request->question_id
            ])->first();

            $found = $suggestion->responses->where('_id', $request->response_id)->first();

            if ($found) {
                $howto =  $found->howtos->where('description', $request->howto)->first();
                $payload = AIHowToHistory::firstOrCreate([
                    'user_id' => (int)$request->user_id,
                    'howto_id' => $howto->_id,
                    'question_id' => $suggestion->_id,
                    'response_id' => $howto->a_i_responses->_id,
                    'assessment_id' => (int)$request->assessment_id,
                    'path' => $request->path
                ]);
            }

            return $this->successResponse(['message' => 'How To History has been saved', 'payload' => $payload], 200);
        } catch (Exception $ex) {
            return $this->errorResponse('There is an issue saving the how-to to your history', 500);
        }
    }

    protected function getHowToHistory(Request $request)
    {

        $rules = [
            'user_id' => 'required',
        ];

        $messages = [
            'user_id.required' => 'User ID is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $records = AIHowToHistory::where('user_id', (int)$request->user_id)->get();

            $savedHowTos = [];

            $records->each(function ($record) use (&$savedHowTos) {

                $suggestion = Suggestion::find($record->question_id);

                if($suggestion && $suggestion->responses){
                    $response = $suggestion->responses->where('_id', $record->response_id)->first();

                    $howto = $response->howtos->where('_id', $record->howto_id)->first();

                    if ($howto) {
                        $result = [
                            'id' => $howto->_id,
                            'path' => $record->path,
                            'description' => $howto->description,
                        ];
                        array_push($savedHowTos, $result);
                    }
                }
                
            });
            return $this->successResponse(['message' => 'How To History has been retrieved', 'payload' => $savedHowTos], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
