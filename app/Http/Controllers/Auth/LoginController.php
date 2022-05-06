<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\AuthUserAPI;
use App\Http\Controllers\Controller;
use App\Models\activityLog;
use App\Models\permission;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Faker\Factory;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::REG;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

   public function authenticate(Request $request, AuthUserAPI $api)
   {      
      /* Authen siriraj user */
      $sirirajUser = $api->authenticate($request->username, $request->password);
      if($sirirajUser['reply_code'] != 0)
      {
         #ถ้าไม่เท่ากับ 0 => ชื่อผู้ใช้หรือรหัสผ่านผิด
         $errors = ['message' => $sirirajUser['reply_text']];
         return Redirect::back()->withErrors($errors)->withInput($request->all());
      }

      #authen office name from siriraj user
      #ไม่ใช่ "สนง.ภาควิชาอายุรศาสตร์" return to login page with error message
      if($sirirajUser['office_name'] != "สนง.ภาควิชาอายุรศาสตร์")
      {
         abort(403);
         // $errors = ['message' => 'ไม่มีสิทธิ์เข้าถึง กรุณาติดต่อผู้ดูแลระบบ'];
         // return Redirect::back()->withErrors($errors)->withInput($request->all());
      }
      #เป็นเจ้าหน้าที่ "สนง.ภาควิชาอายุรศาสตร์" check condition 
      else
      {
         #authen username from table user = login from sirirajuser
         $user = User::where('username', $sirirajUser['login'])->where('status','Active')->first();
         #ไม่ตรงกัน return to login page with error message
         if (!$user) 
         {
            abort(403);
            // $errors = ['message' => 'ไม่มีสิทธิ์เข้าถึง กรุณาติดต่อผู้ดูแลระบบ'];
            // return Redirect::back()->withErrors($errors)->withInput($request->all());
         }
         #ตรงกัน save avtivity log to activitylog table
         else
         {
            #ให้ login
            Auth::login($user); 
            // Log::info("test");
            #update fullname to user table
            if(Auth::user()->full_name != $sirirajUser['full_name'])
            {
               User::where('username', Auth::user()->username)->update([
                  'full_name' => $sirirajUser['full_name']
               ]);
            }

            $log_activity = new activityLog;
            $log_activity->username = Auth::user()->username;
            $log_activity->program_name = 'med_edu';
            $log_activity->url = URL::current();
            $log_activity->method = $request->method();
            $log_activity->user_agent = $request->header('user-agent');
            if (Auth::user()->is_admin == "1") {
               $log_activity->action = 'Admin login';
            } else {
               $log_activity->action = 'User login';
            }
            
            $dt = Carbon::now();
            $log_activity->date_time = $dt->toDayDateTimeString();
            $log_activity->save();

            if($user->is_admin == "1")
            {
               // Log::info("admin");
               return redirect('activitylog')->with('success', "Account successfully registered.");
            }
            else
            {
               return redirect('regDoc')->with('success', "Account successfully registered.");
            }

         }
      }
      // dd($sirirajUser);
   }
   public function logout(Request $request)
   {
      $log_activity = new activityLog;
      $log_activity->username = Auth::user()->username;
      $log_activity->program_name = 'med_edu';
      $log_activity->url = URL::current();
      $log_activity->method = $request->method();
      $log_activity->user_agent = $request->header('user-agent');
      if (Auth::user()->is_admin == "1") {
         $log_activity->action = 'Admin logout';
      } else {
         $log_activity->action = 'User logout';
      }

      $dt = Carbon::now();
      $log_activity->date_time = $dt->toDayDateTimeString();
      $log_activity->save();

      Auth::logout();
      Session::forget('user');

      return Redirect::route('login');
   }

   public function update(){
      return ['active' => true];
   }
}