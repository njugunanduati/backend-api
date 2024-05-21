<?php

namespace App\Http\Controllers;

use Validator;
Use Image;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyFile;
use App\Models\TrainingAccess;
use App\Models\MemberGroupLesson;
use App\Models\CompanyUser;
use App\Traits\ApiResponses;
// use cypher
use App\Helpers\Cypher;
use Illuminate\Http\Request;
use App\Notifications\NewStudent;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CoachDetails as CoachDetailsResource;
use App\Http\Resources\Company as CompanyResource;
use App\Http\Resources\CompanyFile as CompanyFileResource;
use App\Http\Resources\CompanyAll as CompanyAllResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompanyController extends Controller
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
     * Update or create a new student account associated to this company
     *
     * @param  $company
     * 
     */
    private function clientAccount($company){

            $user = DB::table('users')->where('email', trim($company->contact_email))->first();
                
            if($user){
                $huser = User::hydrate([$user])->first();
                
                if($huser->company_id == null){
                    $huser->company_id = $company->id;
                    $huser->company = $company->company_name;

                    if($company->contact_first_name){
                        $huser->first_name = $company->contact_first_name;
                    }

                    if($company->contact_last_name){
                        $huser->last_name = $company->contact_last_name;
                    }

                    if($company->company_website){
                        $huser->website = $company->company_website;
                    }

                    if($company->contact_title){
                        $huser->title = $company->contact_title;
                    }

                    if($company->time_zone){
                        $huser->time_zone = $company->time_zone;
                    }

                    if($company->contact_phone){
                        $huser->phone_number = $company->contact_phone;
                    }
                    
                    $huser->save();
                    TrainingAccess::firstOrCreate(
                    [
                        'user_id' => $huser->id,
                        'training_software' => 0,	
                        'training_100k' => 0,
                        'training_lead_gen' => 0,	
                        'group_coaching' => 0
                    ]);
                }else{

                    if ($company->company_name){
                        $huser->company = $company->company_name;
                    }

                    if($company->contact_first_name){
                        $huser->first_name = $company->contact_first_name;
                    }

                    if($company->contact_last_name){
                        $huser->last_name = $company->contact_last_name;
                    }

                    if($company->company_website){
                        $huser->website = $company->company_website;
                    }

                    if($company->contact_title){
                        $huser->title = $company->contact_title;
                    }

                    if($company->time_zone){
                        $huser->time_zone = $company->time_zone;
                    }

                    if($company->contact_phone){
                        $huser->phone_number = $company->contact_phone;
                    }
                    
                    $huser->save();
                    TrainingAccess::firstOrCreate(
                    [
                        'user_id' => $huser->id,
                        'training_software' => 0,	
                        'training_100k' => 0,
                        'training_lead_gen' => 0,	
                        'group_coaching' => 0
                    ]);
                }
            }else{
                $password = (string)rand(1000000, 9999999);
                $nuser = new User;
                $nuser->email = $company->contact_email;
                $nuser->first_name = $company->contact_first_name;
                $nuser->last_name = $company->contact_last_name;
                $nuser->company = $company->company_name;
                $nuser->password = $password;
                $nuser->role_id = 10; // Student or Client Role	
                $nuser->company_id = $company->id;
                $nuser->created_by_id = ($this->user->id) ? $this->user->id : null;

                if($company->company_website){
                    $nuser->website = $company->company_website;
                }

                if($company->contact_title){
                    $nuser->title = $company->contact_title;
                }

                if($company->time_zone){
                    $nuser->time_zone = $company->time_zone;
                }

                if($company->contact_phone){
                    $nuser->phone_number = $company->contact_phone;
                }
                
                $nuser->save();
                $role = Role::findOrFail(10);
                $nuser->assignRole($role);

                // Dont send a notification to student/client at this time
                // $nuser->notify(new NewStudent($nuser, $password));

                TrainingAccess::firstOrCreate(
                [
                    'user_id' => $nuser->id,
                    'training_software' => 0,	
                    'training_100k' => 0,
                    'training_lead_gen' => 0,	
                    'group_coaching' => 0
                ]);
            }
    }

    
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'status' => 'required',
                'id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->input('id');
            $status = $request->input('status');

            $company = Company::findOrfail($id);
            $company->status = trim($status);
            
            if ($company->isDirty()) {
                $company->save();
            }

            $company = $company->refresh();

            $transform = new CompanyResource($company);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This company does not exist', 404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBusinessType(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'business_type' => 'required',
                'id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->input('id');
            $business_type = $request->input('business_type');

            $company = Company::findOrfail($id);
            $company->business_type = trim($business_type);

            if ($company->isDirty()) {
                $company->save();
            }

            $company = $company->refresh();

            $transform = new CompanyResource($company);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This company does not exist', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sendCredentials(Request $request)
    {
        try {

            $rules = [
                'client_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'client_id.required' => 'Company ID is required',
                'client_id.exists' => 'Company Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $client_id = $request->input('client_id');

            $company = Company::findOrfail($client_id);
            $role = Role::findOrfail(10);
            
            $user = User::where('email', trim($company->contact_email))->firstOr(function () use ($company, $role) {
                    $password = (string)rand(1000000, 9999999);
                    $nuser = new User;
                    $nuser->email = $company->contact_email;
                    $nuser->first_name = $company->contact_first_name;
                    $nuser->last_name = $company->company_name;
                    $nuser->company = $company->company_name;
                    $nuser->website = $company->company_website;
                    $nuser->password = $password;
                    $nuser->role_id = 10;	
                    $nuser->company_id = $company->id;
                    $nuser->created_by_id = ($this->user->id) ? $this->user->id : null;

                    $nuser->save();
                    $nuser = $nuser->refresh();
                    $nuser->assignRole($role);

                    $nuser->companies()->attach($company->id);

                    if ($request->has('flash_coaching') && !empty($request->flash_coaching)) {
                        TrainingAccess::where('user_id', $nuser->id)->firstOr(function () use ($nuser) { 
                            $access = new TrainingAccess;
                            $access->user_id = $nuser->id;
                            $access->training_software = 0;
                            $access->training_100k = 0;
                            $access->training_lead_gen = 0;
                            $access->group_coaching = 0;
                            $access->flash_coaching = 1;
                            $access->save();
                        });
                    }else{
                        TrainingAccess::where('user_id', $nuser->id)->firstOr(function () use ($nuser) { 
                            $access = new TrainingAccess;
                            $access->user_id = $nuser->id;
                            $access->training_software = 0;
                            $access->training_100k = 0;
                            $access->training_lead_gen = 0;
                            $access->group_coaching = 0;
                            $access->save();
                        });
                    }
                    return $nuser;
                });
                
                $password = (string)rand(1000000, 9999999);
                $user->password = $password;
                $user->save();

                $user->notify(new NewStudent($user, $password));

            return $this->showMessage("Credentials sent", 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This company does not exist', 404);
        }
    }


    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function companyFiles($id)
    {
        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);
            $files = CompanyFile::where('company_id', $my_id)->orderBy('id')->get();

            $transform = CompanyFileResource::collection($files);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That company file does not exist', 404);
        }
    }

    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function newCompanyFiles(Request $request)
    {

        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'user_type' => 'required',
            ];

            $messages = [
                'user_type.required' => 'User type is required',
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            if ($request->hasFile('files')) {

                $files = $request->file('files');

                $description = trim($request->description);

                $s3 = \AWS::createClient('s3');
                foreach ($files as $key => $f) {

                    $key = date('mdYhia') . '_' . str_random(6) . '.' . $f->getClientOriginalExtension();
                    $uploadfile = $s3->putObject([
                        'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                        'Key'        => 'clientfiles/' . $key,
                        'ACL'          => 'public-read',
                        'ContentType' => $f->getMimeType(),
                        'SourceFile' => $f,
                    ]);

                    $url = $uploadfile->get('ObjectURL');
                    
                    $file = new CompanyFile;
                    $file->company_id = $request->company_id;
                    $file->user_type = $request->user_type;
                    
                    if(($description) && strlen($description) > 0){
                        $file->description = $description;
                    }
                    
                    $file->name = $f->getClientOriginalName();
                    $file->size = $f->getSize();
                    $file->url = $url;
                    $file->key = $key;
                    $file->type = $f->getMimeType();
                    $file->save();
                }
                
             }

            $files = CompanyFile::where('company_id', $request->input('company_id'))->orderBy('id', 'DESC')->get();

            $transform = CompanyFileResource::collection($files);

            return $this->successResponse($transform, 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That prospect not found', 400);
        }
    }


    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function newCoachFiles(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            if ($request->hasFile('files')) {

                $files = $request->file('files');
                $s3 = \AWS::createClient('s3');
                $company_id = $request->company_id;
                $description = trim($request->description);

                foreach ($files as $key => $f) {
                    $key = date('mdYhia') . '_' . str_random(6) . '.' . $f->getClientOriginalExtension();
                    $uploadfile = $s3->putObject([
                        'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                        'Key'        => 'clientfiles/' . $key,
                        'ACL'          => 'public-read',
                        'ContentType' => $f->getMimeType(),
                        'SourceFile' => $f,
                    ]);

                    $url = $uploadfile->get('ObjectURL');
                    
                    $file = new CompanyFile;
                    $file->company_id = $company_id;
                    $file->user_type = 'coach';
                    $file->name = $f->getClientOriginalName();

                    if(($description) && strlen($description) > 0){
                        $file->description = $description;
                    }

                    $file->size = $f->getSize();
                    $file->url = $url;
                    $file->key = $key;
                    $file->type = $f->getMimeType();
                    $file->save();

                }
             }

            $files = CompanyFile::where('company_id', $request->input('company_id'))->orderBy('id', 'DESC')->get();

            $transform = CompanyFileResource::collection($files);

            return $this->successResponse($transform, 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That prospect not found', 400);
        }
    }


    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function companyImage(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $company = Company::findOrfail($company_id);

            if ($request->hasFile('companyfile')) {

                $file = $request->file('companyfile');

                $input['file'] = time().'.'.$file->getClientOriginalExtension();
        
                $destination = storage_path('app/uploads');
                $imgFile = Image::make($file->getRealPath());
                
                $imgFile->fit(350, 350, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($destination.'/'.$input['file']);

                $s3 = \AWS::createClient('s3');
                
                $key = $company_id . '.' . $file->getClientOriginalExtension();
                $uploadfile = $s3->putObject([
                    'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                    'Key'        => 'clientimages/' . $key,
                    'ACL'          => 'public-read',
                    'ContentType' => $file->getMimeType(),
                    'SourceFile' => $destination.'/'.$input['file'], // $input['file'],
                ]);

                $url = $uploadfile->get('ObjectURL');
                $company->image = $url;
                $company->save();
                
             }

            $transform = new CompanyResource($company);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That prospect not found', 400);
        }
    }


    /**
     * Delete File from AWS S3 bucket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function fileDelete($key)
    {

        $s3 = \AWS::createClient('s3');

        $result = $s3->deleteObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'clientfiles/' . $key,
        ]);

        if ($result['DeleteMarker']) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteCompanyFile(Request $request)
    {

        try {

            $rules = [
                'id' => 'required|exists:company_files,id',
                'company_id' => 'required|exists:companies,id',
                'name' => 'required',
                'size' => 'required',
                'type' => 'required',
                'key' => 'required',
            ];

            $messages = [
                'id.required' => 'File ID is required',
                'id.exists' => 'File Not Found',
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
                'name.required' => 'File name is required',
                'size.required' => 'File size is required',
                'type.required' => 'File type is required',
                'key.required' => 'File key is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->input('id');
            $key = $request->input('key');

            $file = CompanyFile::findOrFail($id);
            $file->delete(); //Delete the file

            if ($key) {
                $this->fileDelete($key);
            }

            $files = CompanyFile::where('company_id', $request->input('company_id'))->orderBy('id', 'DESC')->get();

            $transform = CompanyFileResource::collection($files);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That prospect not found', 400);
        }
    }


    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateCompanyFile(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:company_files,id',
                'name' => 'required',
            ];

            $messages = [
                'id.required' => 'File ID is required',
                'id.exists' => 'File Not Found',
                'name.required' => 'File name is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $name = trim($request->name);
            $description = trim($request->description);

            $file = CompanyFile::findOrFail($id);
            $file->name = $name;

            if(($description) && strlen($description) > 0){
                $file->description = $description;
            }

            if ($file->isDirty()) {
                $file->save();
            }

            $files = CompanyFile::where('company_id', $file->company_id)->orderBy('id', 'DESC')->get();

            $transform = CompanyFileResource::collection($files);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That prospect not found', 400);
        }
    }



    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function userCompanies($id)
    {

        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);
            $user = User::findOrFail(intval($my_id));
            $transform = CompanyResource::collection($user->companies()->orderBy('id', 'DESC')->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Search for companies by query and list 20 records
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        try {

            $query = $request->input('query');
            $last_id = $request->input('last');

            if (empty($query)) {
                if (empty($last_id)) {
                    $companies = Company::orderBy('id')->limit(20)->get();
                } else {
                    $companies = Company::where('id', '>', $last_id)->orderBy('id')->limit(20)->get();
                }
            } else {
                if (empty($last_id)) {
                    $companies = Company::where('company_name', 'LIKE', '%' . $query . '%')->orderBy('id')->limit(20)->get();
                } else {
                    $companies = Company::where('id', '>', $last_id)
                        ->where(function ($q) use ($query) {
                            $q->where('company_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('id')->limit(20)->get();
                }
            }

            $transform = CompanyResource::collection($companies);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Company not found', 400);
        }
    }

    /**
     * Search for user companies by query and list the records
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function userCompanySearch(Request $request)
    {
        try {

            $query = $request->input('query');

            $user = $this->user;

            if (empty($query)) {
                $results = $user->companies()->get();
            } else {
                $results = $user->companies()->where(function ($q) use ($query) {
                    $q->where('company_name', 'LIKE', '%' . $query . '%')->orWhere('contact_name', 'LIKE', '%' . $query . '%');
                })->orderBy('id')->get();
            }

            $transform = CompanyResource::collection($results);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Company not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::all(); //Get all companies

        $transform = CompanyAllResource::collection($companies);

        return $this->successResponse($transform, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|max:100',
                'contact_first_name' => 'required|max:100',
                'contact_last_name' => 'required|max:100',
                'contact_title' => 'max:100',
                'contact_phone' => 'max:20',
                'contact_email' => 'required|email',
                'time_to_call' => 'max:100',
                'address' => 'max:200',
                'company_website' => 'max:200',
                'whatsup_number' => 'max:50',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company = new Company;
            $company->contact_first_name = trim($request->input('contact_first_name'));
            $company->contact_last_name = trim($request->input('contact_last_name'));
            $company->contact_title = trim($request->input('contact_title'));
            $company->contact_phone = trim($request->input('contact_phone'));
            $company->contact_email = trim($request->input('contact_email'));
            $company->time_to_call = trim($request->input('time_to_call'));
            $company->company_name = trim($request->input('company_name'));
            $company->address = trim($request->input('address'));
            $company->company_website = trim($request->input('company_website'));

            if ($request->country) {
                $company->country = trim($request->input('country'));
            }

            if ($request->contact_secondary_phone) {
                $company->contact_secondary_phone = trim($request->input('contact_secondary_phone'));
            }

            if ($request->whatsup_number) {
                $company->whatsup_number = trim($request->input('whatsup_number'));
            }

            if ($request->time_zone) {
                $company->time_zone = trim($request->input('time_zone'));
            }

            $company->save();

            $this->clientAccount($company); 

            $this->user->companies()->attach($company->id);

            $transform = new CompanyResource($company);

            return $this->showMessage($transform, 201);
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {

            $company = Company::findOrfail($id); //Get by id

            $transform = new CompanyResource($company);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That company does not exist', 404);
        }
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
        try {

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|max:100',
                'contact_first_name' => 'required|max:100',
                'contact_last_name' => 'required|max:100',
                'contact_title' => 'max:100',
                'contact_phone' => 'max:20',
                'contact_email' => 'required|email',
                'time_to_call' => 'max:100',
                'address' => 'max:200',
                'company_website' => 'max:200',
                'whatsup_number' => 'max:50',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $company = Company::find($my_id);

            $company->contact_first_name = trim($request->input('contact_first_name'));
            $company->contact_last_name = trim($request->input('contact_last_name'));
            $company->contact_title = trim($request->input('contact_title'));
            $company->contact_phone = trim($request->input('contact_phone'));
            $company->contact_email = trim($request->input('contact_email'));
            $company->time_to_call = trim($request->input('time_to_call'));
            $company->company_name = trim($request->input('company_name'));
            $company->address = trim($request->input('address'));
            $company->company_website = trim($request->input('company_website'));

            if ($request->country) {
                $company->country = trim($request->input('country'));
            }

            if ($request->status) {
                $company->status = trim($request->input('status'));
            }

            if ($request->contact_secondary_phone) {
                $company->contact_secondary_phone = trim($request->input('contact_secondary_phone'));
            }

            if ($request->whatsup_number) {
                $company->whatsup_number = trim($request->input('whatsup_number'));
            }

            if ($request->business_type) {
                $company->business_type = trim($request->input('business_type'));
            }
            
            if ($request->time_zone) {
                $company->time_zone = trim($request->input('time_zone'));
            }

            if ($company->isDirty()) {
                $company->save();
            }

            $this->clientAccount($company);

            $transform = new CompanyResource($company);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This company does not exist', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);
            $company = Company::findOrFail(intval($my_id));
            // check whether the company belongs to this user
            $company_user = CompanyUser::where('company_id', $company->id)->first();
            if ($company_user->user_id === $this->user->id) {                
                $assessments_count = Company::findOrFail($my_id)->assessments()->count();

                DB::table('company_user')->where('company_id', $my_id)->delete(); //Delete company_user associated
                DB::table('assessments')->where('company_id', $my_id)->delete(); // Delete all assessments associated
                DB::table('company_files')->where('company_id', $my_id)->delete(); // Delete all files associated
                $company->delete(); //Delete the company

                return response()->json(['data' => $company, 'Company deleted!']);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This company does not exist', 404);
        }
    }


    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCoachProfile(Request $request)
    {

        try {

            if($request->company_id){

                $rules = [
                    'company_id' => 'required|exists:companies,id',
                ];

                $messages = [
                    'company_id.required' => 'Company ID is required',
                    'company_id.exists' => 'Company Not Found',
                ];

                $validator = Validator::make($request->all(), $rules, $messages);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }
                
                $company_id = $request->company_id;

                $company_user = CompanyUser::where('company_id', '=', $company_id)->first();
                
                $user = User::findOrFail($company_user->user_id);

                $transform = new CoachDetailsResource($user);

                return $this->successResponse($transform, 200);
            }else if($request->client_id){

                $rules = [
                    'client_id' => 'required|exists:users,id',
                ];

                $messages = [
                    'client_id.required' => 'User ID is required',
                    'client_id.exists' => 'User Not Found',
                ];

                $validator = Validator::make($request->all(), $rules, $messages);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }

                $client_id = $request->client_id;

                $mgl = MemberGroupLesson::where('user_id', $client_id)->groupBy(['invited_by'])->first();
                
                if($mgl){
                    $user = User::findOrFail($mgl->invited_by);

                    $transform = new CoachDetailsResource($user);
                    return $this->successResponse($transform, 200);
                }
            }

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }
}
