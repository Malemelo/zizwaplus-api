<?php

namespace App\Http\Controllers;

use App\Models\MtnPaymentIntent;
use App\Models\Payments;
use App\Models\StrictSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentsController extends Controller
{
    //
    public function mtnPay(Request $request)
    {
        $request->validate([
            'phoneNumber' => ['required'],
            'plan' => ['required']
        ]);

        $amount = 0;

        if($request->plan == "monthly"){
            $amount = 90.07;
        }

        if($request->plan == "yearly"){
            $amount = 812.10;
        }

        $client_user = User::where('id', Auth::user()->id)->first();

        $payer_number = $request->phoneNumber;
        $receipt_number = Str::uuid()->toString();
        $reference = Str::uuid()->toString();

        $token_response = Http::withBasicAuth('5f098bf7-051d-411e-9da5-17d7bf9e6ae5', 'df31dbf6faf04fe4b003be6859448ea0')->withHeaders([
            'X-Target-Environment' => 'mtnzambia',
            'Ocp-Apim-Subscription-Key' => '2abcde1eed76408389592e1b181ba0be'
        ])->post('https://proxy.momoapi.mtn.com/collection/token/');

        $response = (string)$token_response->getBody();
        $json = json_decode($response);
        //save the token into a variable
        $token = $json->access_token;

        $body = [];
        $body['amount'] = $amount;
        $body['currency'] = "ZMW";
        $body['externalId'] = $receipt_number;
        $body['payer']['partyIdType'] = "MSISDN";
        $body['payer']['partyId'] = $payer_number;
        $body['payerMessage'] = $request->planName . ' Subscription for ' . $amount;
        $body['payeeNote'] = $request->planName . ' Subscription for ' . $amount;

        $headers = [];
        $headers[] = "X-Reference-Id: " . $reference;
        $headers[] = "X-Target-Environment: mtnzambia";
        $headers[] = "Ocp-Apim-Subscription-Key: 2abcde1eed76408389592e1b181ba0be";
        $headers[] = "Authorization: Bearer " . $token;
        $headers[] = "Content-Type: application/json";
        $headers[] = "X-Callback-Url: https://zizwaplus.com/payment-complete";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $curl_info = curl_getinfo($ch);

        $result = curl_exec($ch);

        if ($result) {
            $initaialised_transaction = MtnPaymentIntent::create([
                'user_id' => $client_user->id,
                'reference_id' => $reference,
                'receipt_number' => $receipt_number,
                'plan_name' => $request->plan,
                'plan_amount' => $amount,
                'plan_currency' => 'ZMW'
            ]);

            $initaialised_transaction->save();

            $intent_response = [
                "success" => "true",
                "message" => "Confirm payment by putting pin on your MTN MoMo phone"
            ];

            return response()->json($intent_response, 200);
        }


    }

    public function ConfirmPayment()
    {
        $reference = MtnPaymentIntent::where('id', Auth::user()->id)->where('status', 0)->orderBy('created_at', 'desc')->first();
        //$reference = "efa46c0a-8053-475c-ac9c-e8b2dd70635c";

        if($reference){
            $request_url = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/".$reference->reference_id;

            //generate auth token
            $token_response = Http::withBasicAuth('5f098bf7-051d-411e-9da5-17d7bf9e6ae5', 'df31dbf6faf04fe4b003be6859448ea0')->withHeaders([
                'X-Target-Environment' => 'mtnzambia',
                'Ocp-Apim-Subscription-Key' => '2abcde1eed76408389592e1b181ba0be'
            ])->post('https://proxy.momoapi.mtn.com/collection/token/');

            $response = (string)$token_response->getBody();
            $json = json_decode($response);
            //save the token into a variable
            $token = $json->access_token;

            //get status code
            $payment_response = Http::withToken($token)->withHeaders([
                'X-Target-Environment' => 'mtnzambia',
                'Ocp-Apim-Subscription-Key' => '2abcde1eed76408389592e1b181ba0be'
            ])->get($request_url);

            $status_state = $payment_response->status();
            $status_json = $payment_response->json();

            if($status_state == 200){
                $client_user = User::where('id', Auth::user()->id)->first();
                if($status_json['status'] == "SUCCESSFUL"){
                    $month_end = Date('y:m:d', strtotime('+30 days'));
                    $year_end = Date('y:m:d', strtotime('+365 days'));
                    $check_intent = MtnPaymentIntent::where('user_id', session('LoggedUser'))->where('status', 0)->orderBy('created_at', 'desc')->first();

                    if($check_intent){
                        if ($check_intent->plan_name == "monthly") {
                            $transaction = Payments::create([
                                'user_id' => $client_user->id,
                                'subtotal' => $check_intent->plan_amount,
                                'total' => $check_intent->plan_amount,
                                'plan_type' => "monthly",
                                'start_date' => Date('y:m:d'),
                                'end_date' => $month_end,
                                'payment_method' => 'mtn',
                                'mtn_reference_id' => $check_intent->reference_id
                            ]);
                            $transaction->save();

                            $update_intent = MtnPaymentIntent::find($check_intent->id);
                            $update_intent->status = 1;
                            $update_intent->update();

                            $success_response = [
                                "success" => true,
                                "message" => "Payment succeeded, enjoy"
                            ];

                            return response()->json($success_response, 200);
                        }
                        if ($check_intent->plan_name == "yearly") {
                            $transaction = Payments::create([
                                'user_id' => $client_user->id,
                                'subtotal' => $check_intent->plan_amount,
                                'total' => $check_intent->plan_amount,
                                'plan_type' => "yearly",
                                'start_date' => Date('y:m:d'),
                                'end_date' => $year_end,
                                'payment_method' => 'mtn',
                                'mtn_reference_id' => $check_intent->reference_id
                            ]);
                            $transaction->save();

                            $update_intent = MtnPaymentIntent::find($check_intent->id);
                            $update_intent->status = 1;
                            $update_intent->update();

                            $success_response = [
                                "success" => true,
                                "message" => "Payment succeeded, enjoy"
                            ];

                            return response()->json($success_response, 200);
                        }
                    }

                }else{
                    $error_response = [
                        "success" => false,
                        "message" => "Payment did not go through, try again"
                    ];

                    return response()->json($error_response, 400);
                }
            }else{
                $error_response = [
                    "success" => false,
                    "message" => "Payment did not go through, try again"
                ];

                return response()->json($error_response, 400);
            }
        }else{
            $error_response = [
                "success" => false,
                "message" => "Payment did not go through, try again"
            ];

            return response()->json($error_response, 400);
        }
    }

    public function zamtelPay(Request $request)
    {
        $today = today()->format('Y-m-d');
        $subscription = Payments::where('user_id', Auth::user()->id)->whereDate('end_date', '>=' , $today)->orderBy('created_at', 'desc')->first();

        if(!$subscription){
            $request->validate([
                'phoneNumber' => ['required'],
                'plan' => ['required']
            ]);

            $amount = 0;

            if($request->plan == "monthly"){
                $amount = 90.07;
                //$amount = 82;
            }

            if($request->plan == "yearly"){
                $amount = 812.10;
                // $amount = 822;
            }

            $client_user = User::where('id', Auth::user()->id)->first();

            $payer_number = $request->phoneNumber;
            $receipt_number = Str::uuid()->toString();

            //get status code
            $payment_response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => '*/*'
            ])->get('https://apps.zamtel.co.zm/ZampayRestProd/Req?Password=yUrzeBHvWD0/Y2jUQgQlrET4y8HbKU9b&ThirdPartyID=Kazadi_Caller&Shortcode=98495&Msisdn='.$payer_number.'&ConversationId='.$receipt_number.'&Amount='.$amount.'&Reference='.$request->plan.' plan');

            $status_state = $payment_response->status();
            $status_json = $payment_response->json();

            if($status_state == 200){
                if($status_json['message'] == "Success"){
                    $month_end = Date('y:m:d', strtotime('+30 days'));
                    $year_end = Date('y:m:d', strtotime('+365 days'));
                    if($request->plan == "monthly"){
                        $transaction = Payments::create([
                            'user_id' => $client_user->id,
                            'subtotal' => $amount,
                            'total' => $amount,
                            'plan_type' => "monthly",
                            'start_date' => Date('y:m:d'),
                            'end_date' => $month_end,
                            'payment_method' => 'zamtel',
                            'zamtel_reference_id' => $receipt_number
                        ]);
                        $transaction->save();

                        //return response($status_json, 200);
                        $sendSMS = Http::withoutVerifying()
                            ->post('https://bulksms.zamtel.co.zm/api/v2.1/action/send/api_key/1a06def69ef52e93d69337af7187becf/contacts/' .$request->phoneNumber. '/senderId/Zizwaplus/message/Thank+you+for+choosing+Zizwa%2B.+Your+'.$request->plan.'+viewing+plan+transaction+ref+no.+is+'.$receipt_number);

                        $confirm_response = [
                            "success" => true,
                            "message" => "Zamtel Mobile Money Payment succeeded"
                        ];
                        return response()->json($confirm_response, 200);
                    }

                    if ($request->plan == "yearly") {
                        $transaction = Payments::create([
                            'user_id' => $client_user->id,
                            'subtotal' => $amount,
                            'total' => $amount,
                            'plan_type' => "yearly",
                            'start_date' => Date('y:m:d'),
                            'end_date' => $year_end,
                            'payment_method' => 'zamtel',
                            'zamtel_reference_id' => $receipt_number
                        ]);
                        $transaction->save();

                        $sendSMS = Http::withoutVerifying()
                            ->post('https://bulksms.zamtel.co.zm/api/v2.1/action/send/api_key/1a06def69ef52e93d69337af7187becf/contacts/' .$request->phoneNumber. '/senderId/Zizwaplus/message/Thank+you+for+choosing+Zizwa%2B.+Your+'.$request->plan.'+viewing+plan+transaction+ref+no.+is+'.$receipt_number);

                        $yearly_response = [
                            "success" => true,
                            "message" => "Zamtel Mobile Money Payment succeeded"
                        ];
                        return response()->json($yearly_response, 200);
                    }
                }else{
                    $pay_response = [
                        "success" => false,
                        "message" => "Oops, payment has failed, try again later",
                        "error" => $status_json
                    ];
                    return response()->json($pay_response, 400);
                }

            }else{
                $pay_response = [
                    "success" => false,
                    "message" => "Oops, payment has failed, try again later",
                    "error" => $status_json
                ];
                return response()->json($pay_response, 400);
            }
        }else{
            $pay_response = [
                "success" => false,
                "message" => "Oops, payment has failed, try again later",
                "error" => "server error"
            ];
            return response()->json($pay_response, 200);
        }
    }

    public function userStatus()
    {
        $today = today()->format('Y-m-d');
        $subscription = Payments::where('user_id', Auth::user()->id)->whereDate('end_date', '>=' , $today)->orderBy('updated_at', 'desc')->first();

        if($subscription){
                $end_date = Payments::where('user_id', Auth::user()->id)->orderBy('updated_at', 'desc')->first()->end_date;
                $sub_response = [
                    "success" => true,
                    "message" => "Account active",
                ];
                return response()->json($sub_response, 200);
        }

        if(!$subscription){

            $reference = MtnPaymentIntent::where('id', Auth::user()->id)->where('status', 0)->orderBy('created_at', 'desc')->first();
            //$reference = "efa46c0a-8053-475c-ac9c-e8b2dd70635c";

            if($reference){
                $request_url = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/".$reference->reference_id;

                //generate auth token
                $token_response = Http::withBasicAuth('5f098bf7-051d-411e-9da5-17d7bf9e6ae5', 'df31dbf6faf04fe4b003be6859448ea0')->withHeaders([
                    'X-Target-Environment' => 'mtnzambia',
                    'Ocp-Apim-Subscription-Key' => '2abcde1eed76408389592e1b181ba0be'
                ])->post('https://proxy.momoapi.mtn.com/collection/token/');

                $response = (string)$token_response->getBody();
                $json = json_decode($response);
                //save the token into a variable
                $token = $json->access_token;

                //get status code
                $payment_response = Http::withToken($token)->withHeaders([
                    'X-Target-Environment' => 'mtnzambia',
                    'Ocp-Apim-Subscription-Key' => '2abcde1eed76408389592e1b181ba0be'
                ])->get($request_url);

                $status_state = $payment_response->status();
                $status_json = $payment_response->json();

                if($status_state == 200){
                    $client_user = User::where('id', Auth::user()->id)->first();
                    if($status_json['status'] == "SUCCESSFUL"){
                        $month_end = Date('y:m:d', strtotime('+30 days'));
                        $year_end = Date('y:m:d', strtotime('+365 days'));
                        $check_intent = MtnPaymentIntent::where('user_id', session('LoggedUser'))->where('status', 0)->orderBy('created_at', 'desc')->first();

                        if($check_intent){
                            if ($check_intent->plan_name == "monthly") {
                                $transaction = Payments::create([
                                    'user_id' => $client_user->id,
                                    'subtotal' => $check_intent->plan_amount,
                                    'total' => $check_intent->plan_amount,
                                    'plan_type' => "monthly",
                                    'start_date' => Date('y:m:d'),
                                    'end_date' => $month_end,
                                    'payment_method' => 'mtn',
                                    'mtn_reference_id' => $check_intent->reference_id
                                ]);
                                $transaction->save();

                                $update_intent = MtnPaymentIntent::find($check_intent->id);
                                $update_intent->status = 1;
                                $update_intent->update();

                                $success_response = [
                                    "success" => true,
                                    "message" => "Payment succeeded, enjoy"
                                ];

                                return response()->json($success_response, 200);
                            }
                            if ($check_intent->plan_name == "yearly") {
                                $transaction = Payments::create([
                                    'user_id' => $client_user->id,
                                    'subtotal' => $check_intent->plan_amount,
                                    'total' => $check_intent->plan_amount,
                                    'plan_type' => "yearly",
                                    'start_date' => Date('y:m:d'),
                                    'end_date' => $year_end,
                                    'payment_method' => 'mtn',
                                    'mtn_reference_id' => $check_intent->reference_id
                                ]);
                                $transaction->save();

                                $update_intent = MtnPaymentIntent::find($check_intent->id);
                                $update_intent->status = 1;
                                $update_intent->update();

                                $success_response = [
                                    "success" => true,
                                    "message" => "Payment succeeded, enjoy"
                                ];

                                return response()->json($success_response, 200);
                            }
                        }

                    }else{
                        $error_response = [
                            "success" => false,
                            "message" => "Account not paid for"
                        ];

                        return response()->json($error_response, 400);
                    }
                }else{
                    $sub_response = [
                        "success" => false,
                        "message" => "Account not paid for",
                    ];

                    return response()->json($sub_response, 400);
                }
            }else{
                $error_response = [
                    "success" => false,
                    "message" => "Account not paid for"
                ];

                return response()->json($error_response, 400);
            }

            $error_response = [
                "success" => false,
                "message" => "Account not paid for"
            ];

            return response()->json($error_response, 400);

        }
    }
}
