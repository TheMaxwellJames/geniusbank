<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaystackController extends Controller
{

    public function store(Request $request){
        if($request->currency_code != "NGN")
        {
            return redirect()->back()->with('unsuccess','Please Select NGN Currency For Paystack.');
        }

        $currency = Currency::where('id',$request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $deposit = new Deposit;
        $deposit['deposit_number'] = Str::random(12);
        $deposit['user_id'] = auth()->id();
        $deposit['currency_id'] = $request->currency_id;
        $deposit['amount'] = $amountToAdd;
        $deposit['method'] = $request->method;
        $deposit['txnid'] = $request->ref_id;
        $deposit['status'] = "complete";
        $deposit->save();

        $user = auth()->user();
        $user->balance += $amountToAdd;
        $user->save();

        $gs =  Generalsetting::findOrFail(1);

        if($gs->is_smtp == 1)
        {
            $data = [
                'to' => $user->email,
                'type' => "Deposit",
                'cname' => $user->name,
                'oamount' => $amountToAdd,
                'aname' => "",
                'aemail' => "",
                'wtitle' => "",
                'accountno' => $user->account_number,
                'transid' => $request->txn_id4,
            ];

            $mailer = new GeniusMailer();
            $mailer->sendAutoMail($data);
        }
        else
        {
           $to = $user->email;
           $subject = " Update On Deposit.";
           $msg = "Hello ".$user->name."!\nYou have invested successfully.\nThank you.";
           $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
           mail($to,$subject,$msg,$headers);
        }

        return redirect()->route('user.deposit.create')->with('success','Deposit amount ('.$request->amount.') successfully!');
    }
}
