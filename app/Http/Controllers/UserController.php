<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Payments;
use App\Models\StrictSession;
use App\Models\Title;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    public function generate_code()
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $res = "";
        for ($i = 0; $i < 8; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars)-1)];
        }
        return $res;
    }

    public function uniqueCode()
    {
        $user_count = User::where('unique_code', null)->count();

        for($i = 0; $i < $user_count; $i++){
            $unique_code = $this->generate_code();
            $update_each_record = User::where('unique_code',null)->first()->update(['unique_code' => $unique_code]);
        }


        $json = [
            'total_users' => $user_count,
            'unique_code' => $unique_code
        ];
        return response()->json($json);
    }

    public function emailRegisterData(Request $request)
    {
        //validate the user unput
        $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required'],
            'password' => ['required'],
            'device_id' => ['required']
        ]);

        $unique_code = $this->generate_code();

        $registered_but_suspended_user = User::where('email', $request->email)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
        $registered_user = User::where('email', $request->email)->whereNotNull('email')->exists();

        if($registered_user) {
            $response = [
                "success" => "false",
                "message" => "Sorry, user already registered"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);

        } elseif ($registered_but_suspended_user) {
            $response = [
                "success" => "false",
                "message" => "Sorry, your account is currently suspended. Please call us for assistance"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }

        $stripe = new \Stripe\StripeClient(env('TEST_STRIPE_SECRET_KEY'));
        $new_customer = $stripe->customers->create([
            'email' => $request->email,
            'name' => $request->name,
            'phone' => null,
            'description' => $request->name.'of contact '.$request->email,
        ]);

        $customer_id = $new_customer->id;


        if($customer_id){
            $email_user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phoneNumber' => $request->phoneNumber,
                'stripe_customer_id' => $customer_id,
                'unique_code' => $unique_code
            ]);

            $email_user->save();
            if($email_user)
            {
                if(Auth::attempt(['email' => $request->email, 'password' => $request->password]))
                {
                    $auth = Auth::user();
                    $token = $auth->createToken('LaravelSanctumAuth')->plainTextToken;

                    $session_count = StrictSession::where('user_id', $auth->id)->get()->count();

                    if($session_count > 3)
                    {
                        //deny entry

                        $response = [
                            "success" => false,
                            "message" => "Your account is already in use on two other devices. Kindly logout on one of the devices and try again."
                        ];
                        $status_code = 201;
                        return response()->json($response, $status_code);
                    }

                    if($session_count < 2)
                    {
                        $strict_session = StrictSession::create([
                            'user_id' => $auth->id,
                            'device_id' => $request->device_id
                        ]);

                        $response = [
                            "success" => "true",
                            "message" => "Enjoy video streaming re-imagined",
                            "id" => $auth->id,
                            "unique_code" => $auth->unique_code,
                            "name" => $auth->name,
                            "email" => $auth->email,
                            "phoneNumber" => $auth->phoneNumber,
                            "profilePic" => $auth->profile_pic,
                            "subscriptionPlan" => null,
                            'stripe_customer_id' => $auth->stripe_customer_id,
                            "token" => $token
                        ];
                        $status_code = 201;
                        return response()->json($response, $status_code);
                    }

                }
            }else{
                $response = [
                    "success" => "false",
                    "message" => "Oops something went wrong. try again"
                ];
                $status_code = 400;
                return response()->json($response, $status_code);
            }
        }else{
            $response = [
                "success" => "false",
                "message" => "Sorry, user already registered"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }

    }

    public function phoneRegisterData(Request $request)
    {
        //validate the user input
        $request->validate([
            'name' => ['required', 'max:255'],
            'phoneNumber' => ['required', 'min:9', 'max:9'],
            'password' => ['required'],
            'device_id' => ['required']
        ]);

        $registering_phoneNumber = '+260' .$request->phoneNumber;
        $registered_but_suspended_user = User::where('phoneNumber', $registering_phoneNumber)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
        $registered_user = User::where('phoneNumber', $registering_phoneNumber)->whereNotNull('phoneNumber')->exists();

        if ($registered_user) {
            $response = [
                "success" => "false",
                "message" => "Sorry, user already registered"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);

        } elseif ($registered_but_suspended_user) {
            $response = [
                "success" => "false",
                "message" => "Sorry, your account is currently suspended. Please call us for assistance"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }

        $stripe = new \Stripe\StripeClient(env('TEST_STRIPE_SECRET_KEY'));
        $new_customer = $stripe->customers->create([
            'email' => $registering_phoneNumber.'@zizwaplus.app',
            'name' => $request->name,
            'phone' => $registering_phoneNumber,
            'description' => $request->name.'of contact '.$registering_phoneNumber,
        ]);

        $customer_id = $new_customer->id;
        $unique_code = $this->generate_code();

        $client_user = User::create([
            'name' => $request->name,
            'phoneNumber' => $registering_phoneNumber,
            'password' => Hash::make($request->password),
            'stripe_customer_id' => $customer_id,
            'unique_code' => $unique_code
        ]);

        $client_user->save();

        if($client_user)
        {
            if(Auth::attempt(['phoneNumber' => $request->phoneNumber, 'password' => $request->password]))
            {

                $auth = Auth::user();
                $token = $auth->createToken('LaravelSanctumAuth')->plainTextToken;

                $session_count = StrictSession::where('user_id', $auth->id)->get()->count();

                if($session_count > 3)
                {
                    //deny entry

                    $response = [
                        "success" => false,
                        "message" => "Your account is already in use on two other devices. Kindly logout on one of the devices and try again."
                    ];
                    $status_code = 201;
                    return response()->json($response, $status_code);
                }

                if($session_count < 2)
                {
                    $strict_session = StrictSession::create([
                        'user_id' => $auth->id,
                        'device_id' => $request->device_id
                    ]);

                    $response = [
                        "success" => "true",
                        "message" => "Enjoy video streaming re-imagined",
                        "id" => $auth->id,
                        "unique_code" => $auth->unique_code,
                        "name" => $auth->name,
                        "email" => $auth->email,
                        "phoneNumber" => $auth->phoneNumber,
                        "profilePic" => $auth->profile_pic,
                        "subscriptionPlan" => null,
                        'stripe_customer_id' => $auth->stripe_customer_id,
                        "token" => $token
                    ];
                    $status_code = 201;
                    $sendSMS = Http::withoutVerifying()
                        ->post('https://bulksms.zamtel.co.zm/api/v2.1/action/send/api_key/1a06def69ef52e93d69337af7187becf/contacts/' .$registering_phoneNumber. '/senderId/Zizwaplus/message/Hi+' . $request->name . '%2C+welcome+to+Zizwaplus+your+amazing+video+on+demand+platform.+Subscribe+monthly+or+yearly+to+enjoy+our+premium+content.');

                    return response()->json($response, $status_code);
                }


            }
        }else{
            $response = [
                "success" => "false",
                "message" => "Oops something went wrong. try again"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }



    }

    public function phoneLoginData(Request $request)
    {
        $request->validate([
            'phoneNumber' => ['required', 'min:9', 'max:9'],
            'password' => ['required'],
            'device_id' => ['required']
        ]);

        $logging_phoneNumber = '+260' .$request->phoneNumber;
        $registered_user = User::where('phoneNumber', $logging_phoneNumber)->whereNotNull('phoneNumber')->exists();
        $subscription_end = "null";

        if($registered_user) {
            $user = DB::table('users')->where('phoneNumber', $logging_phoneNumber)->first();
            if (Hash::check($request->password, $user->password)) {
                $registered_but_suspended_user = User::where('phoneNumber', $logging_phoneNumber)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();

                $activated_user = User::where('phoneNumber', $logging_phoneNumber)->where('isActive', 1)->where('accountDeleted', 0)->first();
                $fetched_subscription = Payments::where('user_id', $user->id)->whereNotNull('plan_name')->exists();
                if ($activated_user) {
                    if ($fetched_subscription == true) {
                        $subscription_end = Payments::where('user_id', $user->id)->orderBy('updated_at','DESC')->first()->end_date;
                    }
                    $token = $user->createToken('LaravelSanctumAuth')->plainTextToken;

                    $session_count = StrictSession::where('user_id', $user->id)->get()->count();

                    if($session_count > 3)
                    {
                        //deny entry

                        $response = [
                            "success" => false,
                            "message" => "Your account is already in use on two other devices. Kindly logout on one of the devices and try again."
                        ];
                        $status_code = 200;
                        return response()->json($response, $status_code);
                    }

                    if($session_count < 2)
                    {
                        $strict_session = StrictSession::create([
                            'user_id' => $user->id,
                            'device_id' => $request->device_id
                        ]);

                        $response = [
                            "success" => "true",
                            "message" => "Enjoy video streaming re-imagined",
                            "id" => $user->id,
                            "unique_code" => $user->unique_code,
                            "name" => $user->name,
                            "email" => $user->email,
                            "phoneNumber" => $user->phoneNumber,
                            "profilePic" => $user->profile_pic,
                            "subscriptionPlan" => null,
                            'stripe_customer_id' => $user->stripe_customer_id,
                            "token" => $token
                        ];
                        $status_code = 200;
                        return response()->json($response, $status_code);
                    }

                } elseif ($registered_but_suspended_user) {
                    $response = [
                        "success" => "false",
                        "message" => "Sorry, your account is currently suspended. Please call us for assistance"
                    ];
                    $status_code = 400;
                    return response()->json($response, $status_code);
                }
            }else {
                $response = [
                    "success" => "false",
                    "message" => "Oops, wrong credentials"
                ];
                $status_code = 400;
                return response()->json($response, $status_code);
            }
        }else{
            $response = [
                "success" => "false",
                "message" => "No related account, please register"
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }
    }

    public function emailLoginData(Request $request)
    {
        $request->validate([
            'email' => ['required'],
            'password' => ['required'],
            'device_id' => ['required']
        ]);

        $registered_user = User::where('email', $request->email)->whereNotNull('email')->exists();
        if($registered_user == true) {
            $user = User::where('email', $request->email)->first();
            $subscription_plan = "null";
            if(Hash::check($request->password, $user->password)) {
                $feature_movie = Movie::inRandomOrder()->where('feature',1)->where('published',1)->first();
                $registered_but_suspended_user = User::where('email', $request->email)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
                $activated_user = User::where('email', $request->email)->where('isActive', 1)->where('accountDeleted', 0)->first();
                if ($activated_user) {
                    $session_count = StrictSession::where('user_id', $user->id)->get()->count();

                    if($session_count >= 3)
                    {
                        //deny entry
                        $deny_response = [
                            "success" => false,
                            "message" => "Your account is already in use on two other devices. Kindly logout on one of the devices and try again."
                        ];
                        $status_code = 400;
                        return response()->json($deny_response, $status_code);
                    }

                    if($session_count <= 2)
                    {
                        $token = $user->createToken('LaravelSanctumAuth')->plainTextToken;

                        $strict_session = StrictSession::create([
                            'user_id' => $user->id,
                            'device_id' => $request->device_id
                        ]);

                        $accept_response = [
                            "success" => "true",
                            "message" => "Enjoy video streaming re-imagined",
                            "id" => $user->id,
                            "unique_code" => $user->unique_code,
                            "name" => $user->name,
                            "email" => $user->email,
                            "phoneNumber" => $user->phoneNumber,
                            "profilePic" => $user->profile_pic,
                            "subscriptionPlan" => null,
                            'stripe_customer_id' => $user->stripe_customer_id,
                            "token" => $token
                        ];
                        $status_code = 200;
                        return response()->json($accept_response, $status_code);
                    }

                } elseif ($registered_but_suspended_user) {
                    $response = [
                        'success' => false,
                        'message' => 'Sorry, your account is currently suspended. Please call us for assistance'
                    ];
                    $status_code = 400;
                    return response()->json($response, $status_code);
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Ooops wrong credentials entered'
                ];
                $status_code = 400;
                return response()->json($response, $status_code);
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No related account, please register'
            ];
            $status_code = 400;
            return response()->json($response, $status_code);
        }
    }
}
