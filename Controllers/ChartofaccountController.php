<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Chartofaccount;
use App\Logchartofaccount;
use App\Ledger;
use Auth;
use DB;
use App\Supplier;
use App\Journalentery;
use App\Subjournalenteries;
use App\Fundstransfer;
use App\Expense;
use App\Cashrecieved;
use App\Cashpaid;
use App\Customer;
use App\Bill;
use App\Sale;
use App\Stichinv;
use App\Fixedacount;
class ChartofaccountController extends Controller
{   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    Public function __construct(){
        $this->middleware('auth');
    }
    public function index()
    {
        $query=0;
        $query=DB::raw('SELECT chartofaccounts.*,ledgers.balance FROM chartofaccounts left join ledgers ON chartofaccounts.coa_id=ledgers.account_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id)');
        // $chartofaccounts=Chartofaccount::select('chartofaccounts.*','ledgers.balance')->leftjoin('ledgers','ledgers.account_id','=','chartofaccounts.coa_id')->where('coa_id','NOT LIKE','%supplier_%')->where('coa_id','NOT LIKE','%customer_%')->groupBy('coa_id')->paginate(100);
        $chartofaccounts=DB::select($query);
     //   $chartofaccounts=DB::table('chartofaccounts AS t1')
     // ->leftJoin(DB::raw('(SELECT MAX(balance) FROM ledgers AS t2 Group by account_id)'),'t1.coa_id','=','t2.account_id')->where('coa_id','NOT LIKE','%customer_%')->where('coa_id','NOT LIKE','%supplier_%')->paginate(100);
      return view('chartofaccount/view',compact('chartofaccounts'));
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $suppliers=Supplier::all();
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        return view('chartofaccount/add',compact('suppliers','chartofaccounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check_duplicate=DB::table('chartofaccounts')->where('coa_title',$request->txt_acc_title)
           ->orWhere('coa_id', $request->txt_acc_id)
         ->first();

        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Account Already Available Please Try Changed Title OR Account Id');
            return redirect('coa/add');
        }
        else{
        
        $chartofaccount=new Chartofaccount();
        $chartofaccount->coa_id=$request->txt_acc_id;
        $chartofaccount->coa_title=$request->txt_acc_title;
        $chartofaccount->account_type=$request->txt_account_type;
        if($request->account_description != ""){
        $chartofaccount->coa_description=$request->account_description;
        }
         else{
          $chartofaccount->coa_description=$request->txt_acc_title;
         }
        $chartofaccount->user_id=Auth::id();
        if($chartofaccount->save()){
            if($request->account_description != ""){
            $this->log_chartofaccount($request->txt_acc_id,$request->txt_acc_title,$request->account_description,$request->txt_account_type);
          }else{$this->log_chartofaccount($request->txt_acc_id,$request->txt_acc_title,$request->txt_acc_title,$request->txt_account_type);}

            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Chart Of Account was successfully added!');
        }
        return redirect('coa/add');
        }

    }

    public function log_chartofaccount($coa_id,$coa_title,$coa_description,$account_type){
        $logcoa=new Logchartofaccount;
        $logcoa->coa_id=$coa_id;
        $logcoa->coa_title=$coa_title;
        $logcoa->coa_description=$coa_description;
        $logcoa->account_type=$account_type;
        $logcoa->user_id=Auth::id();;
        $logcoa->save();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Chartofaccount  $chartofaccount
     * @return \Illuminate\Http\Response
     */
    public function show($chartofaccount)
    {
        $chartofaccounts =DB::table('chartofaccounts')->where('id',$chartofaccount)->get();
        return view('chartofaccount/edit',compact('chartofaccounts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Chartofaccount  $chartofaccount
     * @return \Illuminate\Http\Response
     */
    public function edit(Chartofaccount $chartofaccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Chartofaccount  $chartofaccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if($request->account_description != ""){
        $chartofaccount=DB::table('chartofaccounts')
        ->where('id',$request->coa_id)
        ->update(['coa_id'=> $request->txt_acc_id,'coa_title'=>$request->txt_acc_title,'coa_description'=>$request->account_description,'account_type'=> $request->txt_account_type]);
        }
        else{
           $chartofaccount=DB::table('chartofaccounts')
        ->where('id',$request->coa_id)
        ->update(['coa_id'=> $request->txt_acc_id,'coa_title'=>$request->txt_acc_title,'account_type'=> $request->txt_account_type]);
        }
        if($chartofaccount){
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Chart Of Account was successfully Updated!');
        }
        return redirect('coa/show/'.$request->coa_id);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Chartofaccount  $chartofaccount
     * @return \Illuminate\Http\Response
     */
    public function destroy($chartofaccount)
    {
        DB::table('chartofaccounts')->where('id',$chartofaccount)->delete();
        return redirect('coa/show');
    }

    public function account_details($account_id){
        $account_details=Ledger::where('ledgers.account_id',$account_id)->get();
        return view('chartofaccount.accountdetails',compact('account_details'));
    }

    public function ledgerentries($ledger_id){
        $Ledger=Ledger::where('id',$ledger_id)->first();
        if($Ledger->transection_type == 1){
            $bill_id=$Ledger->ref_id;
            $bills=DB::table('bills')->leftjoin('suppliers','bills.supplier','suppliers.supplier_id')->Where('bll_id',$bill_id)->get();
            $bill_products=DB::table('bill_products')->select('bill_products.*','products.product_id','products.product_description')->Where('bill_products.bill_id',$bill_id)->leftjoin('products','bill_products.product_id','products.product_id')->get();

            return view('purchase/show',compact('bills','bill_products'));
        }
        if($Ledger->transection_type == 2){
            $inv_id=$Ledger->ref_id;
            $sales=DB::table('sales')->leftjoin('customers','sales.customer','customers.customer_id')->Where('sales.id',$inv_id)->get();
            $saleproducts=DB::table('saleproducts')->select('saleproducts.*','products.product_id','products.product_description')->Where('saleproducts.inv_id',$inv_id)->leftjoin('products','saleproducts.product','products.product_id')->get();

            return view('sale/show',compact('sales','saleproducts'));
        }
        if($Ledger->transection_type == 3){
            $bill_id=$Ledger->ref_id;
            $debitnotes=DB::table('debitnotes')->leftjoin('suppliers','debitnotes.supplier_id','suppliers.supplier_id')->Where('debitnotes.id',$bill_id)->get();
            $debitnoteproducts=DB::table('debitnoteproducts')->select('debitnoteproducts.*','products.product_id','products.product_description')->Where('debitnoteproducts.debitnote_id',$bill_id)->leftjoin('products','debitnoteproducts.product_id','products.product_id')->get();

            return view('debitnotes/show',compact('debitnotes','debitnoteproducts'));
        }
        if($Ledger->transection_type == 4){
            $inv_id=$Ledger->ref_id;
            $creditnotes=DB::table('creditnotes')->leftjoin('customers','creditnotes.customer_id','customers.customer_id')->Where('creditnotes.id',$inv_id)->get();
            $creditnoteproducts=DB::table('creditnoteproducts')->select('creditnoteproducts.*','products.product_id','products.product_description')->Where('creditnoteproducts.id',$inv_id)->leftjoin('products','creditnoteproducts.product_id','products.product_id')->get();

            return view('creditnotes/show',compact('creditnotes','creditnoteproducts'));
        }
        if($Ledger->transection_type == 5){
            $journal_id=$Ledger->ref_id;
            $journalentries=Journalentery::where('id',$journal_id)->first();
            $subjournalentries=DB::table('subjournalenteries')->select('subjournalenteries.*','chartofaccounts.coa_title','chartofaccounts.account_type')->join('chartofaccounts', 'chartofaccounts.coa_id', '=', 'subjournalenteries.account_id')->where('subjournalenteries.journalno',$journal_id)->get();

            return view('journal/show',compact('journalentries','subjournalentries'));
        }
        if($Ledger->transection_type == 6){
            $expense=$Ledger->ref_id;
            $expenses=Expense::where('id',$expense)->get();
            //return $funds;
            return view('expense/show',compact('expenses'));
        }
        if($Ledger->transection_type == 7){
            $fund_id=$Ledger->ref_id;
            $funds=Fundstransfer::where('id',$fund_id)->get();
            //return $funds;
            return view('fundtransfer/show',compact('funds'));
        }

        
    }


    public function tradepayable(){
     $payablequery= DB::raw('SELECT ledgers.account_id,ledgers.balance,suppliers.supplier_name FROM ledgers LEFT JOIN suppliers on CONCAT("supplier_",suppliers.supplier_id) =ledgers.account_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=2 && account_id LIKE "%supplier_%"');
       $payables=DB::select($payablequery);
       return view('chartofaccount/tradepayable',compact('payables'));
    }

    public function traderecieveable(){
      $recieveablequery=DB::raw('SELECT ledgers.account_id,ledgers.balance,customers.customer_name FROM ledgers LEFT JOIN customers on CONCAT("customer_",customers.customer_id) =ledgers.account_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=1 && account_id LIKE "%customer_%"');
      $recieveables=DB::select($recieveablequery);
       //$recievables=Customer::paginate(100);
       return view('chartofaccount/traderecievable',compact('recieveables'));
    }

    public function cashrecieved($customer_id){
        $cashrecieved=Stichinv::where('customer_id',$customer_id)->get();
        return view('sale/recipts',compact('cashrecieved'));
    }
    public function cashrecievedhistory($recipt_no){
        $cashrecieveds=Cashrecieved::where('invoice_id',$recipt_no)->paginate(100);
        return view('sale/cashrecievedhistory',compact('cashrecieveds'));
    }
    public function cashpaid($supplier_id){
        $cashpaids=Bill::where('supplier',$supplier_id)->paginate(100);
        return view('purchase/bills',compact('cashpaids'));
    }

    public function cashpaidhistory($bill_id){
        $cashpaids=cashpaid::where('cp_bill_no',$bill_id)->paginate(100);
        return view('purchase/cashpaidhistory',compact('cashpaids'));
    }

    public function addfixedaccount(Request $request){
        $message=0;
        $check_duplicate=Fixedacount::where('bill_id',$request->bill)->orWhere('ass_name',$request->ass_name)->count();
        if($check_duplicate != null){
          $message=1;
        }
        else{
          $facnts=new Fixedacount;
        $facnts->bill_id = $request->bill;
        $facnts->coa = $request->coa;
        $facnts->dated = $request->datepicker;
        $facnts->sup = $request->sup;
        $facnts->ass_name = $request->ass_name;
        $facnts->dp_rate =$request->dpr;
        $facnts->amo = $request->amo;
        $facnts->cp = $request->cp;
        $facnts->rm =  $request->rm;
        if($facnts->save()){
          
           $this->storledger($request->sup,$request->coa,$request->cp,$request->rm,$request->bill,9,$request->amo,$request->datepicker);
          $chartofaccount=new Chartofaccount();
          $chartofaccount->coa_id='fix_'.$request->bill;
          $chartofaccount->coa_title=$request->ass_name;
          $chartofaccount->account_type=6;
          $chartofaccount->coa_description=$request->ass_name;
          $chartofaccount->user_id=Auth::id();
          if($chartofaccount->save()){
              $this->log_chartofaccount('fix_'.$request->bill,$request->ass_name,$request->ass_name,6);
              $this->chartofaccountforexpenses($request->bill,$request->ass_name);
          }
          $message=2;
        }
        
        }
        echo $message;
    }

    public function chartofaccountforexpenses($bill,$asset_name){
        $chartofaccount=new Chartofaccount();
          $chartofaccount->coa_id='de_'.$bill;
          $chartofaccount->coa_title=$asset_name;
          $chartofaccount->account_type=5;
          $chartofaccount->coa_description=$asset_name;
          $chartofaccount->user_id=Auth::id();
          if($chartofaccount->save()){
              $this->log_chartofaccount('de_'.$bill,$asset_name,$asset_name,5);
          }
          
          $chartofaccount=new Chartofaccount();
          $chartofaccount->coa_id='ade_'.$bill;
          $chartofaccount->coa_title=$asset_name;
          $chartofaccount->account_type=5;
          $chartofaccount->coa_description=$asset_name;
          $chartofaccount->user_id=Auth::id();
          if($chartofaccount->save()){
              $this->log_chartofaccount('ade_'.$bill,$asset_name,$asset_name,5);
          }
    }
  public function storledger($supplier_id,$acc_id,$cashpaid,$dueammount,$refrenceid,$transectiontype,$amo,$date){
        //initialize $balance variable
        $balance=0;
        //checking if dueamount not = 0 to store supplier ledger
      if($dueammount != 0){
        //assing supplier prefix to account_id to keep track of it in ledger
        $account_id="supplier_".$supplier_id;
            //check if balnce is available already agaist this account id
             $last_record=Ledger::where('account_id',$account_id)->orderby('id', 'desc')->first();
             //if record is available
             if($last_record != null){
                //add dueammount to that record 
                $balance =$last_record->balance+$dueammount;
            }
            //if not record available
            else{
                //store new dueammount as supplier ledger amount
                $balance=$balance+$dueammount;
            }
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=2;
            $ledger->debit_ammount=Null;
            $ledger->credit_ammount=$dueammount;
            $ledger->balance=$balance;
            $ledger->transection_type=$transectiontype;
            $ledger->ref_id=$refrenceid;
            $ledger->date=$date." ".date('h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }
         //check if cashpaid agains chart of account is not empty
         if($cashpaid != null){
            //assigning account id
            $account_id=$acc_id;
            //check if balnce is available already agaist this account id
             $last_record=Ledger::where('account_id',$account_id)->orderby('id', 'desc')->first();
             //if record is available
             if($last_record != null){
                //add dueammount to that record 
                $balance =$last_record->balance-$cashpaid;
            }
            //if not record available
            else{
                //store new dueammount as supplier ledger amount
                $balance=$balance-$cashpaid;
            }
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=1;
            $ledger->debit_ammount=Null;
            $ledger->credit_ammount=$cashpaid;
            $ledger->balance=$balance;
            $ledger->transection_type=$transectiontype;
            $ledger->ref_id=$refrenceid;
            $ledger->date=$date." ".date('h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }
        $balance = 0;
        if($amo != null){
            //assigning account id
            $account_id='fix_'.$refrenceid;
            //check if balnce is available already agaist this account id
             $last_record=Ledger::where('account_id',$account_id)->orderby('id', 'desc')->first();
             //if record is available
             if($last_record != null){
                //add dueammount to that record 
                $balance =$last_record->balance+$amo;
            }
            //if not record available
            else{
                //store new dueammount as supplier ledger amount
                $balance=$balance+$amo;
            }
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=6;
            $ledger->debit_ammount=$amo;
            $ledger->credit_ammount=Null;
            $ledger->balance=$balance;
            $ledger->transection_type=$transectiontype;
            $ledger->ref_id=$refrenceid;
            $ledger->date=$date." ".date('h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }

        $balance = 0;
        if($amo != null){
            //assigning account id
            $account_id='ade_'.$refrenceid;
            //check if balnce is available already agaist this account id
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=5;
            $ledger->debit_ammount=0;
            $ledger->credit_ammount=Null;
            $ledger->balance=0;
            $ledger->transection_type=$transectiontype;
            $ledger->ref_id=$refrenceid;
            $ledger->date=$date." ".date('h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            //assigning account id
            $account_id='de_'.$refrenceid;
            //check if balnce is available already agaist this account id
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=5;
            $ledger->debit_ammount=0;
            $ledger->credit_ammount=Null;
            $ledger->balance=0;
            $ledger->transection_type=$transectiontype;
            $ledger->ref_id=$refrenceid;
            $ledger->date=$date." ".date('h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }

    }

    public function showdepriciatefa(){
       $fixedassets=Fixedacount::all();
       return view('fixedaccounts/show',compact('fixedassets'));

    }

    public function depriciatefa($faass_id){
      $depriciation=0;
      $dep=Fixedacount::where('bill_id',$faass_id)->first();
        $depriciation=$dep->amo*$dep->dp_rate/100*1/12;
        echo $depriciation;
        $balance = 0;
        if($depriciation != null){
            //assigning account id
            $account_id='de_'.$dep->bill_id;
            //check if balnce is available already agaist this account id
             $last_record=Ledger::where('account_id',$account_id)->orderby('id', 'desc')->first();
             //if record is available
             if($last_record != null){
                //add dueammount to that record 
                $balance =$last_record->balance+$depriciation;
            }
            //if not record available
            else{
                //store new dueammount as supplier ledger amount
                $balance=$balance+$depriciation;
            }
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=5;
            $ledger->debit_ammount=$depriciation;
            $ledger->credit_ammount=Null;
            $ledger->balance=$balance;
            $ledger->transection_type=9;
            $ledger->ref_id=$dep->bill_id;
            $ledger->date=date('y-m-d h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }

        $balance = 0;
        if($depriciation != null){
            //assigning account id
            $account_id='ade_'.$dep->bill_id;
            //check if balnce is available already agaist this account id
             $last_record=Ledger::where('account_id',$account_id)->orderby('id', 'desc')->first();
             //if record is available
             if($last_record != null){
                //add dueammount to that record 
                $balance =$last_record->balance+$depriciation;
            }
            //if not record available
            else{
                //store new dueammount as supplier ledger amount
                $balance=$balance+$depriciation;
            }
            //storing ledger
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=5;
            $ledger->debit_ammount=$depriciation;
            $ledger->credit_ammount=Null;
            $ledger->balance=$balance;
            $ledger->transection_type=9;
            $ledger->ref_id=$dep->bill_id;
            $ledger->date=date('y-m-d h:m:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
            
        }
      return redirect('depriciate');
    }
}