<?php

namespace App\Http\Controllers\Auth;

use App\Bruteforce;
use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    const MAX_ATTEMPTS = 3;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {

        $res = $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );

        //if user data false we start counting attempts
        if(!$res){
            //clear string
            $username = trim($request->get($this->username()));
            if(!empty($username)) {
                //find user
                $user = User::where($this->username(), '=', $username)->first();
                if($user){
                    //get ip
                    $ip = $request->ip();
                    //create or find model Bruteforce
                    $data = Bruteforce::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'attempts'=>1,
                            'ip_address'=>ip2long($ip),
                        ]
                    );

                    //check minutes ban
                    //use the last update, because when you banned, model can't updating

                    if($data->attempts>= self::MAX_ATTEMPTS){
                    $timeNow = Carbon::now();
                    $timeBan = $data->updated_at;
                    $minutes = $timeBan->diffInMinutes($timeNow);
                        if($minutes>10){
                            //if minutes is end delete model
                            $data->delete();
                        }else{
                            //else send mail to user about bruteforce atack
                            $mailData = [
                                'Username' => $user->name,
                                'Ip' => long2ip($data->ip_address),
                            ];


                            Mail::send('emails.bruteforce', $mailData, function (\Illuminate\Mail\Message $mail) use($user) {
                                $mail->to($user->email);
                            });

                            //error ban
                            //catch this error in Exceptions/Handler.php and show view errors/bruteforce

                            throw_if(
                                $data->attempts >= self::MAX_ATTEMPTS,
                                AccessDeniedHttpException::class,
                                "You are banned for 10 minutes. Time to end ban: ".(10-$minutes)." minutes"
                            );

                        }
                    }

                    //bruteforce attempts +1

                    $data->increment('attempts');
                }
            }
        }
        return $res;
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //if user is login delete bruteforce model
        //check bruteforce model
        if($user->bruteforce){
            $user->bruteforce->delete();
        }
    }
}
