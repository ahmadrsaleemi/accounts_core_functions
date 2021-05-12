<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Session;
use App\Port;

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
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected function authenticated(Request $request, $user)
    {
         $port_number = 8811;
         $bill_check = 1;
         $ports = Port::where('user_id', Auth::user()->id)->first();
        if($ports != ''){
            $port_number = $ports->port;
        }
        Session::put('port_number', $port_number);
        // Session::put('bill_check', $bill_check);
    if( Auth::user()->role == 3 ) {
        return redirect('sale/add');
    }else{

     return redirect('/home');
     }
    }
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }
}

