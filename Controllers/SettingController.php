<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Fiscalyear;
use App\Ledger;
use Auth;
use Carbon\Carbon;
use App\Endyear;
use DB;
use App\Bill;
use App\Bill_product;
use App\Sale;
use App\Saleproduct;
use App\Stichinv;
use App\Stichinvoice;
use App\Port;
class SettingController extends Controller
{
    public function index(){
    	$fy="";
        $port = 8811;
    	$fy=Fiscalyear::orderby('id','desc')->first();
    	if($fy != null){
    	$fy=$fy->fy;
    	}
		$time1=date("g:i:s a");
    	$time2= date("H:i:s");
        $port_number = Port::where('user_id', Auth::id())->first();
        if($port_number != Null){
            $port = $port_number->port;

        }else{
            $port = 8811;
        }
    	return view('settings.view',compact('time1','fy','time2', 'port'));
    }

    public function setfisaclyear(Request $request){
    	$count=Fiscalyear::count('id');
    	if($count > 0){
    		Fiscalyear::where('id',1)->update(['fy'=>$request->date]);
    		echo'Fiscalyear saved';
    	}
    	else{
	    	$fiy=new Fiscalyear;
	    	$fiy->user_id=Auth::id();
	    	$fiy->fy= $request->date;
	    	if($fiy->save()){
				echo'Fiscalyear saved';
	    	}
    	}
    }

    public function endyear(){
    	$mytime = date('Y-m-d', strtotime(Carbon::now()));
    	$fy=Fiscalyear::orderby('id','desc')->first();
    	if($fy->fy == $mytime){
    		$endyears=Endyear::where('date',$fy->fy)->count();
    		if($endyears > 0){
    			echo "year already ended";
    		}
    		else{
                $this->incomeexpenseledger();
    			$endyear=new Endyear;
    			$endyear->user_id=Auth::id();
    			$endyear->date=$fy->fy;
    			$endyear->save();
    			$ledgers=Ledger::where('account_type',4)->orwhere('account_type',5)->groupby('account_id')->get();
		    	if($ledgers != null){
		    		foreach ($ledgers as $ledger) {
			    		$endyear=new Ledger;
				        $endyear->account_id=$ledger->account_id;
				        if($ledger->account_type == 4){
				        $endyear->debit_ammount=null;
				        $endyear->credit_ammount=0;
				       }
				       if($ledger->account_type == 5){
				        $endyear->debit_ammount=0;
				        $endyear->credit_ammount=null;
				       }
				        $endyear->account_type=$ledger->account_type;
				        $endyear->balance=0;
                        $endyear->date=date('20y-m-d H:i:s');
				        $endyear->transection_type=100;
				        $endyear->ref_id=0;
				        $endyear->user_id=Auth::id();
				        $endyear->save();
			    	}
                    echo"Done";
		    	}
                $this->destroy_all_sale_purchase_incoices($fy->fy);
    		}
	    	
		    	
    	}
    	else{
    		echo 'date to come';
    	}
        
    }


    public function incomeexpenseledger(){
        $cramount=0;
        $total_income=0;
        $total_expense=0;
        $incquery=DB::raw('SELECT ledgers.balance FROM  ledgers WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=4');
        $incomes =DB::select($incquery);
        foreach($incomes as $income){
            $total_income += $income->balance;
        }

        $expquery=DB::raw('SELECT ledgers.balance FROM  ledgers WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=5');
        $expenses =DB::select($expquery);
        foreach($expenses as $expense){
            $total_expense += $expense->balance;
        }
       $cramount = $total_income-$total_expense;
        if($cramount != null){
            $balance=0;
            //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',10103)->orderby('id', 'desc')->first();
            ///if  record available
             if($last_record != null){
                 //add dueammount to that record 
                $balance =$last_record->balance+$cramount;
            }
            //if not record available
            else{
                 //store new dueammount as customer ledger amount
                $balance=$balance+$cramount;
            }
            //storing ledger

            $ledger=new Ledger;
            $ledger->account_id=10103;
            $ledger->debit_ammount=null;
            $ledger->account_type=4;
            $ledger->credit_ammount=$cramount;
            $ledger->balance=$balance;
            $ledger->date=date('20y-m-d H:i:s');
            $ledger->transection_type=2;
            $ledger->ref_id=0;
            $ledger->user_id=Auth::id();
            $ledger->save();
        }
        
    }

    public function destroy_all_sale_purchase_incoices($date){
        
        foreach(Sale::where('date','<=',$date)->get() AS $sales){
            Saleproduct::where('inv_id',$sales->id)->delete();

        }
        Sale::where('date','<=',$date)->delete();

        
        foreach(Bill::where('date','<=',$date)->get() AS $Bills){
            Bill_product::where('bill_id',$Bills->bll_id)->delete();
        }
        Bill::where('date','<=',$date)->delete();
        foreach(Stichinv::where('date','<=',$date)->get() AS $stinvs){
            Stichinvoice::where('inv_id',$stinvs->id)->delete();
        }
        Stichinv::where('date','<=',$date)->delete();
        // Stichinvoice
    }

    public function port(Request $request)
    {
        $port_number = Port::where('user_id', Auth::id())->first();
        if($port_number != Null){
            $update = Port::where('user_id', Auth::user()->id)->update([ 'port' => $request->port ]);
            if($update){

                $request->session()->flash('message.level', 'danger');
                $request->session()->flash('message.content', 'Printing Port Changed Successfully');
                return redirect()->back();
            }

        }else{
            
            $port = New Port;
            $port->user_id = Auth::user()->id;
            $port->port = $request->port;
            
            if($port->save()){

                $request->session()->flash('message.level', 'danger');
                $request->session()->flash('message.content', 'Printing Port Changed Successfully');
                return redirect()->back();
            }
        }
    }
}
