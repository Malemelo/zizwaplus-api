<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            $amount = 82.21;
        }

        if($request->plan == "yearly"){
            $amount = 822.10;
        }

        $client_user = User::where('id', session('LoggedUser'))->first();

        $payer_number = '260' . $request->phoneNumber;
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
                'user_id' => session('LoggedUser'),
                'reference_id' => $reference,
                'receipt_number' => $receipt_number,
                'plan_name' => $request->plan,
                'plan_amount' => $amount,
                'plan_currency' => 'ZMW'
            ]);

            $initaialised_transaction->save();

            return Redirect::route('mtn.wait');
        }

    }
}
