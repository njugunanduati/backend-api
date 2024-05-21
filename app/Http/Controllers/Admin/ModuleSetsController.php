<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Module;
use App\Helpers\Helper;
use App\Models\ModuleSet;
use App\Models\ModuleMeta;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\ModuleQuestion;
use App\Models\ModuleSetModule;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

use App\Models\ModuleQuestionResponse;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Storage;

use App\Models\ModuleQuestionOption;
use App\Http\Resources\ModuleSet as ModuleSetResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\ModuleSetOne as ModuleSetOneResource;




class ModuleSetsController extends Controller
{
    use ApiResponses;


    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function userModuleSets($id)
    {
        try {

            $user = User::findOrFail($id);

            $transform = ModuleSetResource::collection($user->module_sets()->get()->sortBy('order'));

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Modulesets do not exist', 404);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $modulesets = ModuleSet::all()->sortBy('order'); // Get all modulesets

            $transform = ModuleSetResource::collection($modulesets);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Modulesets do not exist', 404);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCoreModuleSets()
    {
        try {

            $modulesets = ModuleSet::where('name', 'like', '%Core%')->get()->sortBy('order'); //Get all modulesets that have 'Core' in them

            $transform = ModuleSetResource::collection($modulesets);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Modulesets do not exist', 404);
        }
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
            Cache::tags('module_data')->flush();
            $validator = Validator::make($request->all(), [
                'module_name' => 'required|max:30|regex:/(^[A-Za-z0-9 ]+$)+/',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $module_name = $request->input('module_name');
            $current_module_names = Module::module_names();
            $collection = collect($current_module_names);


            if (!$collection->search($module_name)) {

                $real_class_name = str_replace(' ', '', ucwords(strtolower($module_name)));
                $table_base_name = 'm_' . str_replace(' ', '_', strtolower($module_name));
                $migration_time = Carbon::now()->format('Y_m_d_U');

                $tables = ["questions", "question_splits", "question_options", "question_responses", "question_notes", "question_comments", "meta"];
                foreach ($tables as $table) {
                    // Create Migrations

                    $current_migration = File::get(public_path() . '/storage/stubs/' . $table . '_migration.stub');
                    $current_migration = str_replace('{{migration_class}}', 'M' . $real_class_name, $current_migration);
                    $current_migration = str_replace('{{table_name}}', $table_base_name, $current_migration);

                    File::put(database_path() . '/migrations/' . $migration_time . '_create_' . $table_base_name . '_' . $table . '_table.php', $current_migration);

                    Artisan::call('migrate', array('--force' => true));

                    return $this->successResponse($module_name . ' module has been created successfully.', 200);
                }
            } else {

                return $this->errorResponse('This module name already exists.', 404);
            }

        }
        catch (Exception $ex) {
            return $this->errorResponse('Error occured while saving', 404);
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
            $module_set = ModuleSet::findOrfail($id); //Get all modulesets

            $transform = new ModuleSetOneResource($module_set);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Moduleset does not exist', 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    { }

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
                'name' => 'required|max:50',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $moduleset = ModuleSet::findOrfail($id);

            $moduleset->name = $request->input('name');
            $moduleset->save();

            $transform = new ModuleSetResource($moduleset);

            return $this->successResponse($transform, 200);
        } catch (Exception $ex) {
            return $this->errorResponse('Error occured while saving', 404);
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
            $moduleset = ModuleSet::findOrFail($id);

            $moduleset->delete(); //Delete the moduleset
            return $this->singleMessage('Moduleset Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Moduleset not found', 400);
        }
    }
}
