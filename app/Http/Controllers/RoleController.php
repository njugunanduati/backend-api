<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use App\Traits\ApiResponses;
use App\Http\Resources\Role as RoleResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller {

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
        $roles = Role::where('name','<>','Inactive')->get();//Get all roles

        $transform = RoleResource::collection($roles);

        return $this->successResponse($transform,200);

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function setUserRoles() {
        $users = User::all(); //Get all users

        foreach ($users as $key => $user) {
            if(count($user->roles) == 0){
                $role = Role::findOrFail($user->role_id);
                $user->assignRole($role);
            }
        }

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

        try{

            //Validate name and permissions field
            $this->validate($request, [
                'name'=>'required|unique:roles|max:10',
                'permissions' =>'required',
                ]
            );

            $name = trim($request['name']);

            $slug = preg_replace('/\s+/', '-', strtolower($name));
            $role = new Role();
            $role->name = $name;
            $role->slug = $slug;

            $permissions = $request['permissions'];

            $role->save();

            if (!empty($permissions)) { //If one or more permissions is selected
                //Looping thru selected permissions
                foreach ($permissions as $permission) {
                    $p = Permission::where('id', '=', $permission)->firstOrFail();
                    //Fetch the newly created role and assign permission
                    $role = Role::where('name', '=', $name)->first();
                    $role->givePermissionTo($p);
                }
            }
            
            $transform = new RoleResource($role);

            return $this->showMessage($transform, 201);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Permission record does not exist', 404);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        try {
            $response = Role::findOrFail($id);

            $transform = new RoleResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Role does not exist', 200);
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
            $role = Role::findOrFail($id);//Get role with the given id
            //Validate name and permission fields
            $this->validate($request, [
                'name'=>'required|max:10|unique:roles,name,'.$id,
                'permissions' =>'required',
            ]);

            $input = $request->except(['permissions']);
            $permissions = $request['permissions'];
            $role->fill($input)->save();

            if (!empty($permissions)) { //If one or more permissions is selected

                $p_all = Permission::all();//Get all permissions

                foreach ($p_all as $p) {
                    $role->revokePermissionTo($p); //Remove all permissions associated with role
                }

                foreach ($permissions as $permission) {
                    $p = Permission::where('id', '=', $permission)->firstOrFail(); //Get corresponding form //permission in db
                    $role->givePermissionTo($p);  //Assign permission to role
                }
            }

            $transform = new RoleResource($role);

            return $this->showMessage($transform, 200);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Record does not exist '.$ex->message, 404);
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

        try{
            $role = Role::findOrFail($id);
            $role->delete();

            return $this->singleMessage('Role Deleted' ,201);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Role record does not exist', 404);
        }

    }
}
