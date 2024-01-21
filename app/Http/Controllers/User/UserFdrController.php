<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\FdrPlan;
use App\Models\Transaction;
use App\Models\UserFdr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;


class UserFdrController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        $data['fdr'] = UserFdr::whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.fdr.index',$data);
    }

    public function running(){
        $data['fdr'] = UserFdr::whereStatus(1)->whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.fdr.running',$data);
    }

    public function closed(){
        $data['fdr'] = UserFdr::whereStatus(2)->whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.fdr.closed',$data);
    }

    public function fdrPlan(){
        $data['plans'] = FdrPlan::orderBy('id','desc')->whereStatus(1)->orderby('id','desc')->paginate(12);
        return view('user.fdr.plan',$data);
    }

    public function fdrAmount(Request $request){
        $plan = FdrPlan::whereId($request->planId)->first();
        $amount = $request->amount;

        $min_amount = convertedAmount($plan->min_amount);
        $max_amount = convertedAmount($plan->max_amount);

        if($amount >= $min_amount && $amount <= $max_amount ){
            $data['data'] = $plan;
            $data['fdrAmount'] = $amount;
            $data['currency'] = globalCurrency();
            $data['profit_amount'] = ($amount * $plan->interest_rate)/100;

            return view('user.fdr.apply',$data);
        }else{
            return redirect()->back()->with('warning','Request Money should be between minium and maximum amount!');
        }
    }

    public function fdrRequest(Request $request){
        $user = auth()->user();
        if($user->balance >= baseCurrencyAmount($request->fdr_amount)){

            $data = new UserFdr();
            $plan = FdrPlan::findOrFail($request->plan_id);

            $data->transaction_no = Str::random(4).time();
            $data->user_id = auth()->id();
            $data->fdr_plan_id = $plan->id;
            $data->amount = baseCurrencyAmount($request->fdr_amount);
            $data->profit_type = $plan->interval_type;
            $data->profit_amount = baseCurrencyAmount($request->profit_amount);
            $data->interest_rate = $plan->interest_rate;

            if($plan->interval_type == 'partial'){
                $data->next_profit_time = Carbon::now()->addDays($plan->interest_interval);
            }
            $data->matured_time = Carbon::now()->addDays($plan->matured_days);
            $data->status = 1;
            $data->save();

            $user->decrement('balance',baseCurrencyAmount($request->fdr_amount));

            $trans = new Transaction();
            $trans->email = auth()->user()->email;
            $trans->amount = baseCurrencyAmount($request->fdr_amount);
            $trans->type = "Fdr";
            $trans->profit = "minus";
            $trans->txnid = $data->transaction_no;
            $trans->user_id = auth()->id();
            $trans->save();

            $gs = Generalsetting::first();

            $namePicker = DB::table('users')->where('email', $trans->email)->first();
            $name = $namePicker->name;
            $msg = "Dear " . $name . ", your FDR application was sucessful";
            $msg2 = "An FDR application has been made by customer with name: " . $name . " and email: " . $trans->email;
            $admine = DB::table('admins')->where('name', 'Admin')->first();


            if ($gs->is_smtp) {
                // FDR Request Successful Email to User
                $loanUserData = [
                    'to' => $trans->email,
                    'subject' => 'FDR Request was successful',
                    'body' => $msg,
                ];

                // FDR Request Notification Email to Admin
                $adminData = [
                    'to' => $admine->email,
                    'subject' => 'FDR Request Notification',
                    'body' => $msg2,
                ];

                $mailer = new GeniusMailer();

                // Send FDR Request Successful Email to User
                $mailer->sendCustomMail($loanUserData);

                // Send FDR Request Notification Email to Admin
                $mailer->sendCustomMail($adminData);
            }





            return redirect()->route('user.fdr.index')->with('success','Loan Requesting Successfully');
        }else{
            return redirect()->back()->with('warning','You Don,t have sufficient balance');
        }
    }
}
