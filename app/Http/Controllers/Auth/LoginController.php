<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\AuthUserAPI;
use App\Http\Controllers\Controller;
use App\Models\activityLog;
use App\Models\Member;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class LoginController extends Controller
{
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

         //  Authen siriraj user
        $sirirajUser = $api->authenticate($request->username, $request->password);
        if ($sirirajUser['reply_code'] != 0) {
            //ถ้าไม่เท่ากับ 0 => ชื่อผู้ใช้หรือรหัสผ่านผิด
            $errors = ['message' => $sirirajUser['reply_text']];
            Log::critical($sirirajUser['reply_text']);

            return Redirect::back()->withErrors($errors)->withInput($request->all());
        }

        $member = Member::where('org_id', $sirirajUser['org_id'])->where('status', 'Active')->first();

        //   no permis
        if (! $member) {
            Log::critical('ไม่มีสิทธิ์เข้าถึง');
            abort(403);
        } else {
            $userAll = User::all();
            if ($userAll == null) {
                $user = new User;
                $user->org_id = $sirirajUser['org_id'];
                $user->username = $sirirajUser['login'];
                $user->full_name = $sirirajUser['full_name'];
                $user->office_name = $sirirajUser['office_name'];
                $user->is_admin = $member->is_admin;
                $user->status = $member->status;
                $user->save();
            } else {
                $users = User::where('org_id', $sirirajUser['org_id'])->first();
                // ไม่มีสิทธิ์เข้าถึงระบบ
                if ($users == null) {
                    $user = new User;
                    $user->org_id = $sirirajUser['org_id'];
                    $user->username = $sirirajUser['login'];
                    $user->full_name = $sirirajUser['full_name'];
                    $user->office_name = $sirirajUser['office_name'];
                    $user->is_admin = $member->is_admin;
                    $user->status = $member->status;
                    $user->save();
                }
            }
            $users = User::where('org_id', $sirirajUser['org_id'])->first();
            // Auth::login($users);

            // dd($users);
        }

        Auth::login($users);
        $log_activity = new activityLog;
        $log_activity->username = Auth::user()->username;
        $log_activity->full_name = Auth::user()->full_name;
        $log_activity->office_name = Auth::user()->office_name;
        $log_activity->action = 'เข้าสู่ระบบ';
        $log_activity->type = 'login';
        $log_activity->url = URL::current();
        $log_activity->method = $request->method();
        $log_activity->user_agent = $request->header('user-agent');
        $log_activity->date_time = date('d-m-Y H:i:s');
        $log_activity->save();

        $full_name = Auth::user()->full_name;
        Log::info($full_name.' เข้าสู่ระบบ');

        return Redirect::route('docShow');
    }

    public function logout(Request $request)
    {
        $log_activity = new activityLog;
        $log_activity->username = Auth::user()->username;
        $log_activity->full_name = Auth::user()->full_name;
        $log_activity->office_name = Auth::user()->office_name;
        $log_activity->action = 'ออกจากระบบ';
        $log_activity->type = 'logout';
        $log_activity->url = URL::current();
        $log_activity->method = $request->method();
        $log_activity->user_agent = $request->header('user-agent');
        $log_activity->date_time = date('d-m-Y H:i:s');
        $log_activity->save();

        Auth::logout();
        Session::forget('user');
        Toastr::success('ออกจากระบบสำเร็จ', 'แจ้งเตือน', ['positionClass' => 'toast-top-right']);

        return Redirect::route('login');
    }
}
