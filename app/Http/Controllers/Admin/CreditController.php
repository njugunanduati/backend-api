<?php

    namespace App\Http\Controllers\Admin;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Tenant;
    use App\Models\Credit;
    use App\Models\ModuleSet;
    use App\Models\Module;
    use App\Models\Role;
    use App\Helpers\Helper;
    use Carbon\Carbon;
    use Config;

    class CreditController extends Controller
    {
        public function index()
        {
            if(\Auth::user()->tenant) {
                $tenant_data = null;
            } else {
                
                $tenants = Tenant::all();
                $tenant_data = [];
                foreach ($tenants as $tenant) {
                    
                    $user_email = $tenant->email;
                    $db = $tenant->id . '-' . $tenant->name;
                    
                    Config::set('database.connections.' . $db, array(
                        'driver'    => 'mysql',
                        'host'      => env('DB_HOST', 'localhost'),
                        'database'  => $db,
                        'username'  => env('DB_USERNAME', 'myprofit_hyn'),
                        'password'  => env('DB_PASSWORD', 'ccZYViluhLjbs'),
                        'charset'   => 'utf8',
                        'collation' => 'utf8_general_ci',
                        'prefix'    => '',
                    ));
                    Config::set('database.default', $db);
                    $user = User::where('email', $user_email)->first();
                    $modules_sets = [];
                    foreach ($user->permittedModuleSets() as $module_set) {
                        $module_sets[] = array(
                            'name' => $module_set->name,
                            'id' => $module_set->id,
                            'credits_available' => $user->creditsAvailable($module_set->id)
                        );
                    }
                    $tenant_data[] = array(
                        'name' => $tenant->name,
                        'email' => $tenant->email,
                        'user_id' => $user->id,
                        'module_sets' => $module_sets,
                    );
                    $db = 'myprofit_mpb';
                    Config::set('database.connections.' . $db, array(
                        'driver'    => 'mysql',
                        'host'      => env('DB_HOST', 'localhost'),
                        'database'  => $db,
                        'username'  => env('DB_USERNAME', 'myprofit_hyn'),
                        'password'  => env('DB_PASSWORD', 'ccZYViluhLjbs'),
                        'charset'   => 'utf8',
                        'collation' => 'utf8_general_ci',
                        'prefix'    => '',
                    ));
                    Config::set('database.default', $db);
                }
            }
            $role_id = Role::where('slug', 'single')->first()->id;
            $single_users = User::where('role_id', $role_id)->get();
            return view('admin.credits', array(
                'tenants' => $tenant_data,
                'single_users' => $single_users,
            ));
        }

        public function add(Request $request) {
            $this->validate($request, [
                'module_set_id' => 'required|integer|exists:module_sets,id',
                'user_id' => 'required|integer|exists:users,id',
                'credit_amount' => 'required|integer',
                'transaction_id' => 'required',
                'tenant' => 'exists:tenants,name'
            ]);

            if ($request->input('tenant')) {
                $tenant = Tenant::where('name', $request->input('tenant'))->first();
                if(isset($tenant)) {

                    $db = $tenant->id . '-' . $tenant->name;

                    Config::set('database.connections.' . $db, array(
                        'driver'    => 'mysql',
                        'host'      => env('DB_HOST', 'localhost'),
                        'database'  => $db,
                        'username'  => env('DB_USERNAME', 'myprofit_hyn'),
                        'password'  => env('DB_PASSWORD', 'ccZYViluhLjbs'),
                        'charset'   => 'utf8',
                        'collation' => 'utf8_general_ci',
                        'prefix'    => '',
                    ));
                    Config::set('database.default', $db);
                }
            }

            $credit = new Credit;
            $credit->user_id = $request->input('user_id');
            $credit->module_set_id = $request->input('module_set_id');
            $credit->credit_amount = $request->input('credit_amount');
            $credit->transaction_id = $request->input('transaction_id');
            $credit->save();

            return redirect('/admin/credits');       
        }


    }
?>