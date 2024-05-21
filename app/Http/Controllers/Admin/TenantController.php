<?php

    namespace App\Http\Controllers\Admin;

    use App\Http\Controllers\Controller;
    use App\Models\ModuleSetModule;
    use App\Models\User;
    use Illuminate\Http\Request;
    use App\Models\Tenant;
    use App\Models\ModuleSet;
    use Carbon\Carbon;
    use Event;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Config;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Mail;
    use Mockery\CountValidator\Exception;

    class TenantController extends Controller
    {
        public function index()
        {
            $tenants = Tenant::all();
            $module_sets = ModuleSet::all();
            return view('admin.tenant.list', array('tenants' => $tenants, 'module_sets' => $module_sets));
        }

        public function add(Request $request)
        {
            $this->validate($request, [
                'service_level' => 'required|in:white-label,team,standard',
                'tenant_name' => 'required|alpha_dash|max:10|unique:tenants,name',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email|unique:tenants,email',
                'module_set' => 'required|exists:module_sets,id'
            ]);

            $tenant = app('Hyn\MultiTenant\Contracts\TenantRepositoryContract')->create([
                'name' => $request->input('tenant_name'),
                'email' => $request->input('email'),
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'service_level' => $request->input('service_level'),
                'module_sets' => $request->input('module_set'),
            ]);

            $website = app('Hyn\MultiTenant\Contracts\WebsiteRepositoryContract')->create([
                'identifier' => $request->input('tenant_name'),
                'tenant_id' => $tenant->id,
            ]);

            $hostname = app('Hyn\MultiTenant\Contracts\HostnameRepositoryContract')->create([
                'hostname' => $request->input('tenant_name') . '.' . env('APP_URL'),
                'website_id' => $website->id,
                'tenant_id' => $tenant->id,
            ]);

            $password = (string) rand(10000000, 99999999);
            $this->createUser($request, $password);
            $this->createTenantDB($request, $tenant, $password);
            $this->sendTenantEmail($request, $password);

            $request->session()->flash('msg', 'The new tenant has been added.');
            $request->session()->flash('msg_class', 'success');

            return redirect('/admin/tenant/list');
        }

        public function createTenantDB(Request $request, $tenant, $password)
        {
            // setting a proper name for new DB
            $db = $tenant->id.'-'.$tenant->name;

            // creating new DB
            DB::statement("CREATE DATABASE `$db` CHARACTER SET utf8 COLLATE utf8_general_ci;");

            // getting all tables from original DB
            $tables = DB::select('SHOW TABLES');

            // getting all modules which should be copied according to their presence in module_set
            $modules = ModuleSetModule::where('module_set_id', $request->module_set)->get();

            // Adding some extra required modules
            $extra_modules = ['SalesForce', 'CutCosts'];
            foreach ($extra_modules as $module_name) {
                $modules->push((object)['module_name' => $module_name]);
            }
            

            // establishing a DB connection using simple PHP MySQLi method (for Laravel-unsupported commands)
            $link = mysqli_connect(env('DB_HOST', 'localhost'), env('DB_USERNAME', 'myprofit_hyn'), env('DB_PASSWORD', 'ccZYViluhLjbs'), $db);

            // copying needed tables from original DB to a newly created
            foreach($tables as $table_object) {
                foreach ($table_object as $key => $table) {
                    // checking if current table is a module table and requires some additional operations
                    if(substr($table, 0, 2) == 'm_') {
                        // check if this module table should be copied to a new DB
                        foreach($modules as $module) {
                            $module_name_start = 'm' . preg_replace('/([A-Z])/', '_$1', $module->module_name);

                            if(strpos($table, strtolower($module_name_start)) !== false) {
                                // get a "create table" SQL code with 100% support of keys and indexes
                                $creates = DB::select("SHOW CREATE TABLE `myprofit_mpb`.`$table`");
                                foreach ($creates[0] as $ckey => $cvalue) {
                                    if($ckey == 'Create Table') {
                                        $create = $cvalue;
                                    }
                                }

                                // copy this table to a new DB
                                $link->query($create);

                                // populate newly created table with original data
                                $sql = "INSERT `$db`.`$table` SELECT * FROM `myprofit_mpb`.`$table`;";
                                mysqli_query($link, $sql, MYSQLI_USE_RESULT);
                            }
                        }
                    }
                    else {
                        // check if current table should be copied or skipped
                        $tables_to_skip = array('credits', 'activations', 'hostnames', 'migrations', 'password_resets',
                            'permissions', 'persistences', 'reminders', 'roles', 'role_users', 'ssl_certificates',
                            'ssl_hostnames', 'tenants', 'tenant_information', 'throttle', 'transactions',
                            'users', 'websites', 'assessments', 'assessment_user', 'company_user', 'module_set_user', 'module_sets',
                            'module_set_modules');

                        if(!in_array($table, $tables_to_skip)) {
                            // get a "create table" SQL code with 100% support of keys and indexes
                            $creates = DB::select("SHOW CREATE TABLE `myprofit_mpb`.`$table`");
                            foreach ($creates[0] as $ckey => $cvalue) {
                                if ($ckey == 'Create Table') {
                                    $create = $cvalue;
                                }
                            }

                            // disable foreign key checking so all tables are properly created and populated
                            $link->query('SET FOREIGN_KEY_CHECKS = 0;');

                            // copy this table to a new DB
                            $link->query($create);

                            // populate newly created table with original data
                            $sql = "INSERT `$db`.`$table` SELECT * FROM `myprofit_mpb`.`$table`;";
                            mysqli_query($link, $sql, MYSQLI_USE_RESULT);
                        }
                    }
                }
            }

            // create required tables in tenant DB to handle users
            // AND create other tables which were skipped before because they should come empty
            $tables = array('credits', 'users', 'roles', 'role_users', 'activations', 'assessments', 'assessment_user', 'company_user',
            'module_set_user', 'module_sets', 'module_set_modules', 'password_resets');
            foreach($tables as $table) {
                $creates = DB::select("SHOW CREATE TABLE `myprofit_mpb`.`$table`");
                foreach ($creates[0] as $ckey => $cvalue) {
                    if ($ckey == 'Create Table') {
                        $create = $cvalue;
                    }
                }

                // disable foreign key checking so all tables are properly created and populated
                $link->query('SET FOREIGN_KEY_CHECKS = 0;');

                // copy this table to a new DB
                $link->query($create);
            }

            // populate newly created tables with proper data
            $sql = "INSERT `$db`.`roles` SELECT * FROM `myprofit_mpb`.`roles`;";
            mysqli_query($link, $sql, MYSQLI_USE_RESULT);

            // create main user in new tenant DB
            $db = $tenant->id . '-' . $tenant->name;
            $this->createUser($request, $password, $db);

            // drop AUTO_INCREMENT value to 2 in 'users' table
            $sql = "ALTER TABLE `$db`.`users` AUTO_INCREMENT = 2;";
            mysqli_query($link, $sql, MYSQLI_USE_RESULT);

            // add an assigned module set to module_sets table in tenant DB
            $module_set = ModuleSet::find($request->module_set);
            $sql = "INSERT INTO `$db`.`module_sets` (`id`, `name`) VALUES ($module_set->id ,'$module_set->name');";
            mysqli_query($link, $sql, MYSQLI_USE_RESULT);

            // add all modules belonging to an assigned module set into module_set_modules table in tenant DB
            foreach($modules as $module) {
                // Skip the required modules that aren't part of the module set
                if (in_array($module->module_name, $extra_modules)) {
                    continue;
                }
                $sql = "INSERT INTO `$db`.`module_set_modules` (`module_set_id`, `module_name`) ".
                    "VALUES ($module->module_set_id ,'$module->module_name');";
                mysqli_query($link, $sql, MYSQLI_USE_RESULT);
            }

            // closing simple PHP MySQLi connection after all commands executed
            mysqli_close($link);
        }

        public function createUser(Request $request, $password, $db = null)
        {
            if(isset($db)) {
                $current_config = Config::get('database.default');

                Config::set('database.connections.' . $db, array(
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', 'localhost'),
                    'database' => $db,
                    'username' => env('DB_USERNAME', 'myprofit_hyn'),
                    'password' => env('DB_PASSWORD', 'ccZYViluhLjbs'),
                    'charset' => 'utf8',
                    'collation' => 'utf8_general_ci',
                    'prefix' => '',
                ));
                Config::set('database.default', $db);
            }

            $user = new User;
            if(isset($db)) {
                $user->id = 1;
            }
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make($password);
            $user->token = '';

            switch ($request->service_level) {
                case 'white-label':
                    $user->role_id = 2;
                    break;

                case 'team':
                    $user->role_id = 3;
                    break;

                case 'standard':
                    $user->role_id = 4;
                    break;

                default:
                    $user->role_id = 2;
            }

            $user->tenant = $request->tenant_name;
            $user->save();

            DB::statement("INSERT INTO `activations`(`user_id`, `code`, `completed`, `completed_at`, `created_at`, `updated_at`)
                VALUES ($user->id, 'DEFAULT CODE', 1, now(), now(), now())");

            DB::statement("INSERT INTO `role_users`(`user_id`, `role_id`, `created_at`, `updated_at`)
                VALUES ($user->id, $user->role_id, now(), now())");

            if(isset($db)) {
                Config::set('database.default', $current_config);
            }
        }

        public function sendTenantEmail($request, $password)
        {
            Mail::send('emails.newtenantaccount', array('password' => $password,
                'request' => $request), function ($message) use($request) {
                $message->from('support@myprofitsoftware.com', 'Profit Acceleration Software');

                $message->to($request->email)->subject('Welcome to MPB');
            });
        }

        public function view($id, Request $request)
        {
            $tenant = Tenant::find($id);
            $tenant_module_sets = explode(',', $tenant->module_sets);
            $module_sets = ModuleSet::all();
            return view('admin.tenant.edit', array(
                'tenant' => $tenant,
                'module_sets' => $module_sets,
                'tenant_module_sets' => $tenant_module_sets
            ));
        }

        public function edit($id, Request $request)
        {
            $this->validate($request, [
                'service_level' => 'required|in:white-label,team,standard',
                'first_name' => 'required',
                'last_name' => 'required'
            ]);

            $tenant = Tenant::find($id);

            $tenant->first_name = $request->input('first_name');
            $tenant->last_name = $request->input('last_name');
            $tenant->service_level = $request->input('service_level');

            if ($tenant->save()) {
                $user = User::where('email',$tenant->email)->first();

                switch ($request->service_level) {
                    case 'white-label':
                        $user->role_id = 2;
                        break;

                    case 'team':
                        $user->role_id = 3;
                        break;

                    case 'standard':
                        $user->role_id = 4;
                        break;

                    default:
                        $user->role_id = 2;
                }

                $user->save();

                DB::statement("UPDATE `role_users` SET `role_id` = $user->role_id WHERE `user_id` = $user->id");

                //
                // START EDITING USER ROLES IN TENANT DB
                //
                $current_config = Config::get('database.default');
                $db = $tenant->id . '-' . $tenant->name;

                Config::set('database.connections.' . $db, array(
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', 'localhost'),
                    'database' => $db,
                    'username' => env('DB_USERNAME', 'myprofit_hyn'),
                    'password' => env('DB_PASSWORD', 'ccZYViluhLjbs'),
                    'charset' => 'utf8',
                    'collation' => 'utf8_general_ci',
                    'prefix' => '',
                ));
                Config::set('database.default', $db);

                $sql = "UPDATE `users` SET `role_id` = $user->role_id WHERE `role_id` < $user->role_id;";
                DB::statement($sql);
                $sql = "UPDATE `users` SET `role_id` = $user->role_id WHERE `id` = 1;";
                DB::statement($sql);

                Config::set('database.default', $current_config);
                //
                // END EDITING USER ROLES IN TENANT DB. DEFAULT DB CONNECTION SET
                //

                $request->session()->flash('msg', 'The tenant has been edited.');
                $request->session()->flash('msg_class', 'success');
                return redirect('/admin/tenant/list');
            }
            else {
                $request->session()->flash('msg', 'Something went wrong. Please try editing selected tenant again.');
                $request->session()->flash('msg_class', 'danger');
                return redirect('/admin/tenant/list');
            }

        }

        public function delete($id, Request $request)
        {
            $tenant = Tenant::find($id);
            $tenant->delete();
            
            $request->session()->flash('msg', 'The tenant has been deleted.');
            $request->session()->flash('msg_class', 'success');
            return redirect('/admin/tenant/list');
        }

        public function addModuleSet($id, Request $request)
        {
            $this->validate($request, [
                'module_set_id' => 'required|exists:module_sets,id'
            ]);

            $tenant = Tenant::find($id);

            $module_sets = explode(',', $tenant->module_sets);
            if(!in_array($request->module_set_id, $module_sets)) {
                array_push($module_sets, $request->module_set_id);


                //
                // handle module tables in tenant DB
                //

                // setting tenant DB name
                $db = $tenant->id.'-'.$tenant->name;

                // getting all tables from original DB
                $tables = DB::select('SHOW TABLES');

                // getting all modules which should be copied according to their presence in module_set
                $modules = ModuleSetModule::where('module_set_id', $request->module_set_id)->get();

                // establishing a DB connection using simple PHP MySQLi method (for Laravel-unsupported commands)
                $link = mysqli_connect(env('DB_HOST', 'localhost'), env('DB_USERNAME', 'myprofit_hyn'), env('DB_PASSWORD', 'ccZYViluhLjbs'), $db);

                // copying needed tables from original DB to a newly created
                foreach($tables as $table_object) {
                    foreach ($table_object as $key => $table) {
                        // checking if current table is a module table and requires some additional operations
                        if (substr($table, 0, 2) == 'm_') {
                            // check if this module table should be copied to a new DB
                            foreach ($modules as $module) {
                                $module_name_start = 'm' . preg_replace('/([A-Z])/', '_$1', $module->module_name);

                                if (strpos($table, strtolower($module_name_start)) !== false) {
                                    // get a "create table" SQL code with 100% support of keys and indexes
                                    $creates = DB::select("SHOW CREATE TABLE `myprofit_mpb`.`$table`");
                                    foreach ($creates[0] as $ckey => $cvalue) {
                                        if ($ckey == 'Create Table') {
                                            $create = $cvalue;
                                        }
                                    }

                                    // copy this table to a new DB
                                    $link->query($create);

                                    // populate newly created table with original data
                                    $sql = "INSERT `$db`.`$table` SELECT * FROM `myprofit_mpb`.`$table`;";
                                    mysqli_query($link, $sql, MYSQLI_USE_RESULT);
                                }
                            }
                        }
                    }
                }

                // add an added module set to module_sets table in tenant DB
                $module_set = ModuleSet::find($request->module_set_id);
                $sql = "INSERT INTO `$db`.`module_sets` (`id`, `name`) VALUES ($module_set->id ,'$module_set->name');";
                mysqli_query($link, $sql, MYSQLI_USE_RESULT);

                // add all modules belonging to an assigned module set into module_set_modules table in tenant DB
                foreach($modules as $module) {
                    $sql = "INSERT INTO `$db`.`module_set_modules` (`module_set_id`, `module_name`) ".
                        "VALUES ($module->module_set_id ,'$module->module_name');";
                    mysqli_query($link, $sql, MYSQLI_USE_RESULT);
                }

                // closing simple PHP MySQLi connection after all commands executed
                mysqli_close($link);
            }


            $tenant->module_sets = implode(',', $module_sets);
            $tenant->save();

            $request->session()->flash('msg', 'Module set added.');
            $request->session()->flash('msg_class', 'success');

            return redirect('admin/tenant/edit/'.$id);
        }

        public function deleteModuleSet($tenant_id, $module_set_id, Request $request)
        {
            $tenant = Tenant::find($tenant_id);

            $module_sets = explode(',', $tenant->module_sets);
            $module_sets = array_diff($module_sets, array($module_set_id));


            //
            // handle module tables in tenant DB
            //

            // setting tenant DB name
            $db = $tenant->id.'-'.$tenant->name;

            // getting all tables from original DB
            $tables = DB::select('SHOW TABLES');

            // getting all modules which should be removed from tenant DB according to their presence in module_set
            $modules = ModuleSetModule::where('module_set_id', $request->module_set_id)->get();

            // establishing a DB connection using simple PHP MySQLi method (for Laravel-unsupported commands)
            $link = mysqli_connect(env('DB_HOST', 'localhost'), env('DB_USERNAME', 'myprofit_hyn'), env('DB_PASSWORD', 'ccZYViluhLjbs'), $db);

            // removing needed tables from a tenant DB
            foreach($tables as $table_object) {
                foreach ($table_object as $key => $table) {
                    // checking if current table is a module table and requires some additional operations
                    if (substr($table, 0, 2) == 'm_') {
                        // check if this module table should be removed from a tenant DB
                        foreach ($modules as $module) {
                            $module_name_start = 'm' . preg_replace('/([A-Z])/', '_$1', $module->module_name);

                            if (strpos($table, strtolower($module_name_start)) !== false) {
                                // remove this table from a tenant DB
                                $link->query("DROP TABLE IF EXISTS `$table`");
                            }
                        }
                    }
                }
            }

            // remove an added module set from module_sets table in tenant DB
            $module_set = ModuleSet::find($request->module_set_id);
            $sql = "DELETE FROM `$db`.`module_sets` WHERE `id` = $module_set->id;";
            mysqli_query($link, $sql, MYSQLI_USE_RESULT);

            // remove all modules belonging to a removed module set from module_set_modules table in tenant DB
            foreach($modules as $module) {
                $sql = "DELETE FROM `$db`.`module_set_modules` WHERE `module_set_id` = $module->module_set_id;";
                mysqli_query($link, $sql, MYSQLI_USE_RESULT);
            }

            // closing simple PHP MySQLi connection after all commands executed
            mysqli_close($link);


            $tenant->module_sets = implode(',', $module_sets);
            $tenant->save();

            $request->session()->flash('msg', 'Module set removed from tenant.');
            $request->session()->flash('msg_class', 'success');

            return redirect('admin/tenant/edit/'.$tenant_id);
        }
    }
?>