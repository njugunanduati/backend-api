<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\StudentPasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Models\User;
use App\Models\PasswordReset;

use App\Traits\ApiResponses;

class ResetPasswordController extends Controller
{
    use ApiResponses;
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    |
    |
    */


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user)
            return $this->errorResponse("We can't find a user with that e-mail address", 400);


        $passwordReset = PasswordReset::updateOrCreate(['email' => $user->email], ['email' => $user->email, 'token' => Str::random(30)]);

        if ($user && $passwordReset)
            if($user->role_id == 10){ // students
                $user->notify(
                    new StudentPasswordResetRequest($passwordReset->token)
                );

                return $this->singleMessage('We have e-mailed your password reset link! '.$user->email ,201);
            }else{
                $user->notify(
                    new PasswordResetRequest($passwordReset->token)
                );

                return $this->singleMessage('We have e-mailed your password reset link! '.$user->email ,201);
            }
            

    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset)

            return $this->errorResponse("This password reset token is invalid.", 400);

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();

            return $this->errorResponse("This password reset token has expired.", 400);

        }

        return response()->json([
            'data' => $passwordReset
        ], 200);
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'token' => 'required|string'
        ]);

        $passwordReset = PasswordReset::where([['token', $request->token]])->first();

        if (!$passwordReset)
            return $this->errorResponse("This password reset token is invalid.", 400);

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();

            return $this->errorResponse("This password reset token has expired.", 400);

        }

        $user = User::where('email', $passwordReset->email)->first();

        if (!$user)
            return $this->errorResponse("We can't find a user with that e-mail address", 400);

        $user->password = $request->password;
        $user->save();
        $passwordReset->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        return $this->singleMessage('Password was successfully reset for  '.$user->email ,200);


    }
}
