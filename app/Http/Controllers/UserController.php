<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    public function emailRegisterData(Request $request)
    {
        //validate the user unput
        $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required'],
            'password' => ['required'],
        ]);

        session()->pull('LoggedUser');

        $registered_but_suspended_user = User::where('email', $request->email)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
        $registered_user = User::where('email', $request->email)->whereNotNull('email')->exists();

        if ($registered_user) {
            Toastr::error('Sorry, user already registered','Error');
            return Redirect::back();
        } elseif ($registered_but_suspended_user) {
            /*$token = Str::random(64);

            Mail::send('email.forgetPassword', ['token' => $token], function($message) use($request){
                $message->to($request->email);
                $message->subject('Reset Password');
            });*/
            Toastr::info('Sorry, your account is currently suspended. Please call us for assistance','Info');
            return Redirect::back();
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
                'stripe_customer_id' => $customer_id
            ]);

            $email_user->save();

            $saved_user = User::where('email', $request->email)->first();

            $request->session()->put('LoggedUser', $saved_user->id);
            /*$session_id = session()->getId();
            $update_session_id = DB::table('sessions')
                ->where('id', $session_id)
                ->update(['user_id' => $saved_user->id]);*/

            Toastr::success('Welcome ' .$saved_user->name.' Enjoy video streaming re-imagined','success');
            return Redirect::route('client.home');
        }else{
            Toastr::info('Please check the strength of your password and try again','Info');
            return Redirect::back();
        }

    }

    public function phoneRegisterData(Request $request)
    {
        //validate the user unput
        $request->validate([
            'name' => ['required', 'max:255'],
            'phoneNumber' => ['required', 'min:9', 'max:9'],
            'password' => ['required'],
        ]);

        session()->pull('LoggedUser');
        $registering_phoneNumber = '+260' .$request->phoneNumber;
        $registered_but_suspended_user = User::where('phoneNumber', $registering_phoneNumber)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
        $registered_user = User::where('phoneNumber', $registering_phoneNumber)->whereNotNull('phoneNumber')->exists();

        if ($registered_user) {
            Toastr::info('Sorry, user already registered','Info');
            return Redirect::back();
        } elseif ($registered_but_suspended_user) {
            $sendSMS = Http::withoutVerifying()
                ->post('https://bulksms.zamtel.co.zm/api/v2.1/action/send/api_key/1a06def69ef52e93d69337af7187becf/contacts/' .$registering_phoneNumber. '/senderId/Zizwaplus/message/Sorry%2C+your+Zizwaplus+account+you+are+trying+to+register+is+temporarily+suspended.+Please+call+us+for+assistance.');

            Toastr::info('Sorry, your account is currently suspended. Please call us for assistance','Info');
            return Redirect::back();
        }

        $stripe = new \Stripe\StripeClient(env('TEST_STRIPE_SECRET_KEY'));
        $new_customer = $stripe->customers->create([
            'email' => $registering_phoneNumber.'@zizwaplus.app',
            'name' => $request->name,
            'phone' => $registering_phoneNumber,
            'description' => $request->name.'of contact '.$registering_phoneNumber,
        ]);

        $customer_id = $new_customer->id;

        $client_user = User::create([
            'name' => $request->name,
            'phoneNumber' => $registering_phoneNumber,
            'password' => Hash::make($request->password),
            'stripe_customer_id' => $customer_id
        ]);

        $client_user->save();

        $saved_user = User::where('phoneNumber', $registering_phoneNumber)->first();

        $request->session()->put('LoggedUser', $saved_user->id);

        Toastr::success('Welcome ' .$saved_user->name.' Enjoy video streaming re-imagined','success');

        //send the otp sms to the user
        $sendSMS = Http::withoutVerifying()
            ->post('https://bulksms.zamtel.co.zm/api/v2.1/action/send/api_key/1a06def69ef52e93d69337af7187becf/contacts/' .$registering_phoneNumber. '/senderId/Zizwaplus/message/Hi+' . $request->name . '%2C+welcome+to+Zizwaplus+your+amazing+video+on+demand+platform.+Subscribe+monthly+or+yearly+to+enjoy+our+premium+content.');
        return Redirect::route('client.home');

    }

    public function phoneLoginData(Request $request)
    {
        $request->validate([
            'phoneNumber' => ['required', 'min:9', 'max:9'],
            'password' => ['required']
        ]);

        $logging_phoneNumber = '+260' .$request->phoneNumber;
        $registered_user = User::where('phoneNumber', $logging_phoneNumber)->whereNotNull('phoneNumber')->exists();
        $subscription_end = "null";

        if($registered_user) {
            $UserPassword = User::where('phoneNumber', $logging_phoneNumber)->first()->password;
            if (Hash::check($request->password, $UserPassword)) {
                $registered_but_suspended_user = User::where('phoneNumber', $logging_phoneNumber)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
                $user = DB::table('users')->where('phoneNumber', $logging_phoneNumber)->first();
                $activated_user = User::where('phoneNumber', $logging_phoneNumber)->where('isActive', 1)->where('accountDeleted', 0)->first();
                $fetched_subscription = Payments::where('user_id', $user->id)->whereNotNull('plan_name')->exists();
                if ($activated_user) {
                    if ($fetched_subscription == true) {
                        $subscription_end = Payments::where('user_id', $user->id)->orderBy('updated_at','DESC')->first();
                    }
                    $request->session()->put('LoggedUser', $user->id);
                    $token = $user->createToken('nzvenzvana')->plainTextToken;

                    $response = [
                        "success" => "true",
                        "message" => "Account created successfully",
                        "name" => $user->name,
                        "email" => $user->email,
                        "phoneNumber" => $user->phoneNumber,
                        "profilePic" => $user->profile_pic,
                        "subscriptionPlan" => $subscription_end->end_date,
                        "token" => $token
                    ];
                    $status_code = 201;
                    return response($response, $status_code);

                } elseif ($registered_but_suspended_user) {
                    $response = [
                        "success" => "false",
                        "message" => "Sorry, your account is currently suspended. Please call us for assistance"
                    ];
                    $status_code = 400;
                    return response($response, $status_code);
                }
            } else {
                $response = [
                    "success" => "false",
                    "message" => "Oops, wrong credentials"
                ];
                $status_code = 400;
                return response($response, $status_code);
            }
        }else{
            $response = [
                "success" => "false",
                "message" => "No related account, please register"
            ];
            $status_code = 400;
            return response($response, $status_code);
        }
    }

    public function emailLoginData(Request $request)
    {
        $request->validate([
            'email' => ['required'],
            'password' => ['required']
        ]);

        $registered_user = User::where('email', $request->email)->whereNotNull('email')->exists();
        if($registered_user == true) {
            $user = User::where('email', $request->email)->first();
            $Email = User::where('email', $request->email)->first();
            $subscription_end = "null";
            if (Hash::check($request->password, $user->password)) {
                $registered_but_suspended_user = User::where('email', $request->email)->where('isActive', 0)->where('accountDeleted', 0)->where('isSuspended', 1)->first();
                $activated_user = User::where('email', $request->email)->where('isActive', 1)->where('accountDeleted', 0)->first();
                $fetched_subscription = Payments::where('user_id', $user->id)->whereNotNull('plan_name')->exists();
                if ($activated_user) {
                    if ($fetched_subscription == true) {
                        $subscription_end = Payments::where('user_id', $user->id)->orderBy('updated_at','DESC');
                    }
                    $tokenResult = $user->createToken('nzvenzvana')->plainTextToken;

                    $response = [
                        'success' => true,
                        'message' => 'Welcome back',
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phoneNumber' => $user->phoneNumber,
                        'country' => $user->country,
                        'profilePic' => $user->profile_pic,
                        'subscription_end' => $subscription_end->end_date,
                        'token' => $tokenResult
                    ];
                    $status_code = 200;
                    return response($response, $status_code);

                } elseif ($registered_but_suspended_user) {
                    $response = [
                        'success' => false,
                        'message' => 'Sorry, your account is currently suspended. Please call us for assistance'
                    ];
                    $status_code = 400;
                    return response($response, $status_code);
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Ooops wrong credentials entered'
                ];
                $status_code = 400;
                return response($response, $status_code);
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No related account, please register'
            ];
            $status_code = 400;
            return response($response, $status_code);
        }
    }
}
