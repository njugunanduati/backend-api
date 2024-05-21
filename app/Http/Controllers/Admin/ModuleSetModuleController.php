<?php

namespace App\Http\Controllers\Admin;

use Validator;

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
use App\Models\ModuleQuestionOption;

use App\Models\ModuleQuestionResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\ModuleSetModule as ModuleSetModuleResource;




class ModuleSetModuleController extends Controller
{
    use ApiResponses;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $module_set_modules = ModuleSetModule::all();

            $transform = ModuleSetModuleResource::collection($module_set_modules);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Module do not exist', 404);
        }
    }

/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getByOrder($module_set_id)
    {
        try {

            $module_set_modules = ModuleSetModule::where('module_set_id',$module_set_id)->order()->get();

            $transform = ModuleSetModuleResource::collection($module_set_modules);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Module do not exist', 404);
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

            $validator = Validator::make($request->all(), [
                'module_name' => 'required|max:50',
                'module_set_id' => 'required|exists:module_sets,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $module_set_module = new ModuleSetModule;
            $module_set_module->module_name = $request->input('module_name');
            $module_set_module->module_set_id = $request->input('module_set_id');
            $module_set_module->save();


            $transform = new ModuleSetModuleResource($module_set_module);

            return $this->successResponse($transform, 200);
        } catch (Exception $ex) {
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
            $module_set_module = ModuleSetModule::findOrfail($id); //Get all modulesets

            return new ModuleSetModuleResource($module_set_module);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Module does not exist', 404);
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
                'module_name' => 'required|max:50',
                'module_set_id' => 'required|exists:module_sets,id',
            ]);


            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $module_set_module = ModuleSetModule::findOrfail($id);

            $module_set_module->module_name = $request->input('module_name');
            $module_set_module->module_set_id = $request->input('module_set_id');
            $module_set_module->save();

            $transform = new ModuleSetModuleResource($module_set_module);

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
            $module_set_module = ModuleSetModule::findOrFail($id);

            $module_set_module->delete(); //Delete the moduleset
            return $this->singleMessage('Moduleset Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Module not found', 400);
        }
    }
}
