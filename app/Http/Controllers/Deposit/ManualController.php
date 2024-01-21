<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    public function store(Request $request){

        $currency = Currency::where('id',$request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $deposit = new Deposit();
        $deposit['deposit_number'] = Str::random(12);
        $deposit['user_id'] = auth()->id();
        $deposit['currency_id'] = $request->currency_id;
        $deposit['amount'] = $amountToAdd;
        $deposit['method'] = $request->method;
        $deposit['txnid'] = $request->txn_id4;
        $deposit['status'] = "pending";
        $deposit->save();


        $gs =  Generalsetting::findOrFail(1);
        $user = auth()->user();
        $namePicker = DB::table('users')->where('email', $user->email)->first();
        $uid = $namePicker->id;
        $name = $namePicker->name;
        $msg2 = "User with name: " . $name . " and id: " . $uid . " made a deposit of " . $amountToAdd;
        $admine = DB::table('admins')->where('name', 'Admin')->first();
        if ($gs->is_smtp == 1) {
            // deposit Email to User
            $userData = [
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

            // Notification Email to Admin
            $adminData = [
                'to' => $admine->email,
                'subject' => 'Deposit made by user',
                'body' => $msg2,
            ];

            $mailer = new GeniusMailer();

            // Send Welcome Email to User
            $mailer->sendAutoMail($userData);

            // Send Notification Email to Admin
            $mailer->sendCustomMail($adminData);
        } else
        {
           $to = $user->email;
           $subject = " Update On Deposit.";
           $msg = "Hello ".$user->name."!\nYou have invested successfully.\nThank you.";
           $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
           mail($to,$subject,$msg,$headers);
        }

        return redirect()->route('user.deposit.create')->with('success','Deposit amount '.$request->amount.' ('.$request->currency_code.') successfully!');
    }
}
