<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\DpsPlan;
use App\Models\InstallmentLog;
use App\Models\Transaction;
use App\Models\UserDps;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;

class UserDpsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        $data['dps'] = UserDps::whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.dps.index',$data);
    }

    public function running(){
        $data['dps'] = UserDps::whereStatus(1)->whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.dps.running',$data);
    }

    public function matured(){
        $data['dps'] = UserDps::whereStatus(2)->whereUserId(auth()->id())->orderby('id','desc')->paginate(10);
        return view('user.dps.matured',$data);
    }

    public function dpsPlan(){
        $data['plans'] = DpsPlan::orderBy('id','desc')->whereStatus(1)->orderby('id','desc')->paginate(12);
        return view('user.dps.plan',$data);
    }

    public function planDetails(Request $request, $id){
        $data['data'] = DpsPlan::findOrFail($id);
        return view('user.dps.apply',$data);
    }

    public function dpsSubmit(Request $request){
        $user = auth()->user();
        if($user->balance >= $request->per_installment){
            $data = new UserDps();

            $plan = DpsPlan::findOrFail($request->dps_plan_id);
            $data->transaction_no = Str::random(4).time();
            $data->user_id = auth()->id();
            $data->dps_plan_id = $plan->id;
            $data->per_installment = $plan->per_installment;
            $data->installment_interval = $plan->installment_interval;
            $data->total_installment = $plan->total_installment;
            $data->interest_rate = $plan->interest_rate;
            $data->given_installment = 1;
            $data->deposit_amount = $request->deposit_amount;
            $data->matured_amount = $request->matured_amount;
            $data->paid_amount = $request->per_installment;
            $data->status = 1;
            $data->next_installment = Carbon::now()->addDays($plan->installment_interval);
            $data->save();

            $user->decrement('balance',$request->per_installment);

            $log = new InstallmentLog();
            $log->user_id = auth()->id();
            $log->transaction_no = $data->transaction_no;
            $log->type = 'dps';
            $log->amount = $request->per_installment;
            $log->save();

            $trans = new Transaction();
            $trans->email = auth()->user()->email;
            $trans->amount = $request->per_installment;
            $trans->type = "Dps";
            $trans->profit = "minus";
            $trans->txnid = $data->transaction_no;
            $trans->user_id = auth()->id();
            $trans->save();

            $gs = Generalsetting::first();

            $namePicker = DB::table('users')->where('email', $trans->email)->first();
            $name = $namePicker->name;
            $msg = "Dear " . $name . ", your DPS application was sucessful";
            $msg2 = "A DPS application has been made by customer with name: " . $name . " and email: " . $trans->email;
            $admine = DB::table('admins')->where('name', 'Admin')->first();


            if ($gs->is_smtp) {
                // DPS Request Successful Email to User
                $loanUserData = [
                    'to' => $trans->email,
                    'subject' => 'DPS Request was successful',
                    'body' => $msg,
                ];

                // DPS Request Notification Email to Admin
                $adminData = [
                    'to' => $admine->email,
                    'subject' => 'DPS Request Notification',
                    'body' => $msg2,
                ];

                $mailer = new GeniusMailer();

                // Send DPS Request Successful Email to User
                $mailer->sendCustomMail($loanUserData);

                // Send DPS Request Notification Email to Admin
                $mailer->sendCustomMail($adminData);
            }








            return redirect()->route('user.dps.index')->with('success','DPS application submitted');
        }else{
            return redirect()->back()->with('warning','You Don,t have sufficient balance');
        }
    }

    public function log($id){
        $loan = UserDps::findOrfail($id);
        $logs = InstallmentLog::whereTransactionNo($loan->transaction_no)->whereUserId(auth()->id())->orderby('id','desc')->orderby('id','desc')->paginate(20);
        $currency = Currency::whereIsDefault(1)->first();

        return view('user.dps.log',compact('logs','currency'));
    }
}
