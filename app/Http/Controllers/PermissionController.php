<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\Permission as PermissionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Auth;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller {

    use ApiResponses;

    public function __construct() {
        $this->middleware(['isSuper']); //Only super admin can perform the following requests
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {
        $permissions = Permission::all(); //Get all permissions
        $transform = PermissionResource::collection($permissions);

        return $this->successResponse($transform,200);
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request) {
        $this->validate($request, [
            'name'=>'required|max:40',
        ]);

        $name = strtolower(trim($request['name']));

        $slug = preg_replace('/\s+/', '-', $name);

        $permission = new Permission();
        $permission->name = $name;
        $permission->slug = $slug;

        $roles = $request['roles'];

        $permission->save();

        if (!empty($roles)) { //If one or more role is selected
            foreach ($roles as $role) {
                $r = Role::where('id', '=', $role)->firstOrFail(); //Match input role to db record

                $permission = Permission::where('name', '=', $name)->first(); //Match input //permission to db record
                $r->givePermissionTo($permission);
            }
        }

        $transform = new PermissionResource($permission);

        return $this->showMessage($transform, 201);

    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {
        try {
            $response = Permission::findOrFail($id);

            $transform = new PermissionResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Permission does not exist', 200);
        }
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id) {
        
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request, $id) {

        try{

            $permission = Permission::findOrFail($id);
            $this->validate($request, [
                'name'=>'required|max:40',
            ]);

            $name = strtolower(trim($request['name']));
            $slug = preg_replace('/\s+/', '-', $name);

            $permission->update([
                'name' => $name,
                'slug' => $slug
            ]);

            $transform = new PermissionResource($priority);

            return $this->successResponse($transform, 200);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Permission record does not exist', 404);
        }

    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id) {

        try{

            $permission = Permission::findOrFail($id);

            //Make it impossible to delete this specific permission
            if ($permission->name == "administer roles") {
                return response()->json([ 'data' => $permission, 'Cannot delete this Permission!']);
            }

            $permission->delete();
  
            return $this->singleMessage('Permission Deleted', 201);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Permission record does not exist', 404);
        }

    }
}
