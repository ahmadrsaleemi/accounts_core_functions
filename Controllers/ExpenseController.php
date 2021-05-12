<?php

namespace App\Http\Controllers;

use App\Expense;
use Illuminate\Http\Request;
use App\Bill;
use App\Chartofaccount;
use App\Logexpense;
use App\Ledger;
use DB;
use Auth;
use App\Fiscalyear;
class ExpenseController extends Ledgerfunctions
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    Public function __construct(){
        //check if user lgged in
        $this->middleware('auth');
    }
    //index function to show all expenses
    public function index()
    {
        $query=DB::raw('SELECT expenses.*, expenses.id as e_id, chartofaccounts.*, users.* From expenses LEFT JOIN chartofaccounts ON expenses.coa_id = chartofaccounts.coa_id LEFT JOIN users ON expenses.user_id = users.id where void=false');
        $expenses=DB::select($query);
        // return $expenses;
         return view('expense/view',compact('expenses'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fy=Fiscalyear::orderby('id','desc')->first();
        if($fy != null){
        $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
        }
        else{
            $fy='2017,12,5';
        }
        //passing purchase bills to add new expense view
        $bills=Bill::all();
        $test = env('DB_DATABASE');
        //passing only expense chart of accounts
        $chartofaccounts=Chartofaccount::Where('account_type',5)->get();
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='expenses'");
        
        //passing chart of account to make expense payment against assets chartof account
        $assets=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get(); 
        return view("expense/add",compact('bills','chartofaccounts','assets','fy','auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validating empty fields
       $this->validate($request,[
        'txt_bill_no'=>'required',
        'txt_date'=>'required',
        'txt_coa_id'=>'required',
        'txt_expense_amount'=>'required'
       ]);
       $refrence_id=expense::orderby('id', 'desc')->first();
        $tempamount = 0;
        $arr_coaid = $request->txt_coa_id;
        $arr_coaid[]=$request->txt_coa_id1;
        $arr_cash_paid = $request->cash_paid;
        $arr_cash_paid[] = $request->cash_paid1;
       //storing Expense 
       for ($i=0; $i < count($arr_coaid); $i++) { 
           $data=new Expense;
           $data->bill_id=$request->txt_bill_no;
           $data->date=$request->txt_date;
           $data->coa_id=$arr_coaid[$i];
           $data->void=false;
           $data->expense_amount=$arr_cash_paid[$i];
           $data->user_id=Auth::id();
           if($data->save()){
            //ledger refrence id
            
            // //ledger storing function 
            // $this->storledger($arr_coaid[$i],$request->coa,$arr_cash_paid[$i],$request->txt_expense_amount,$refrence_id->id,$request->txt_date);

            $this->expensledger($arr_coaid[$i],1,"expense",null,$arr_cash_paid[$i],6,$refrence_id->id,$request->txt_date);
            //getting latest expense id
            $expense_id=Expense::orderby('id','desc')->first();
            //storing expense log

            $this->expense_log($request->txt_bill_no,$expense_id->id,$request->txt_date,$arr_coaid[$i],$arr_cash_paid[$i]);

            
       }
       }
       $this->expensledger($request->coa,5,"expense",$request->txt_expense_amount,null,6,$refrence_id->id,$request->txt_date);
       $request->session()->flash('message.level', 'success');
       $request->session()->flash('message.content', 'New Expense was successfully added!');
       return redirect('expense/add');

    }
    //expense_log function to save expense log
    public function expense_log($bill_id,$expense_id,$date,$coa_id,$ammount){
        
        $expense_log=new Logexpense;
        $expense_log->bill_id=$bill_id;
        $expense_log->expense_id=$expense_id;
        $expense_log->date=$date;
        $expense_log->coa_id=$coa_id;
        $expense_log->void=false;
        $expense_log->expense_amount=$ammount;
        $expense_log->user_id=Auth::id();
        $expense_log->save();

    }

    //saving ledger entries
    public function storledger($expense_account_id,$acc_id,$cashpaid,$expenseammount,$refrence_id,$date){
        //initializing balance variable
        $balance=0;
      //checking if expense amount is not 0
      if($expenseammount != 0){
        //assing supplier prefix to account_id to keep track of it in ledger
            
            $account_id=$acc_id;
            $this->expensledger($account_id,5,"expense",$expenseammount,null,6,$refrence_id,$date);
        }
        //checking if cashpaid amount is not 0
         if($cashpaid != null){
            //assing supplier prefix to account_id to keep track of it in ledger
            $account_id=$expense_account_id;
            //check if balnce is available already agaist this account id
             $this->expensledger($account_id,1,"expense",null,$cashpaid,6,$refrence_id,$date);
        }


    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show($expense)
    {
        //all bills from bills table
        $bills=Bill::all();
        //all expense accounts 
        $chartofaccounts=Chartofaccount::all();
        //getting expenses  against $expense
        $expenses=DB::table('expenses')->select('expenses.*','chartofaccounts.coa_description')->where('expenses.id',$expense)->leftjoin('chartofaccounts', 'chartofaccounts.coa_id', '=', 'expenses.coa_id')->get();

        //passing data to the view
        return view('expense/edit',compact('bills','chartofaccounts','expenses'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //validating empty fields
        $this->validate($request,[
        'txt_bill_no'=>'required',
        'txt_date'=>'required',
        'txt_coa_id'=>'required',
        'txt_expense_amount'=>'required'
       ]);
        //query to update expense
        $update_expense=DB::table('expenses')->where('id',$request->rec_id)->update(['bill_id'=>$request->txt_bill_no,'date'=>$request->txt_date,'coa_id'=>$request->txt_coa_id,'expense_amount'=>$request->txt_expense_amount]);

       if($update_expense){
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Expense was successfully Updated!');
       }
       return redirect('expense/show/'.$request->rec_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy($expense_id)
    {
        //deleting expenses
        DB::table('expenses')->where('id',$expense_id)->delete();
        return redirect('expense/show');
    }

     //making expenses invisible
    public function voidexpense($expense_id){

     DB::table('logexpenses')->where('expense_id',$expense_id)->update(['void'=>true,'updated_at'=>date('y-m-d')]);

        $logexpenses=Expense::where('id',$expense_id)->get();
        foreach ($logexpenses as  $logexpense) {
           $this->expense_log($logexpense->bill_id,$expense_id,$logexpense->date,$logexpense->coa_id,$logexpense->expense_amount);
        }
        DB::table('expenses')->where('id',$expense_id)->update(['void'=>true,'updated_at'=>date('y-m-d')]);

        return redirect('expense/show');
    }
    //ajax call to this function to populate description agains expense coa
    public function populate_coa_description(Request $request){

        $chartofaccount=DB::table('chartofaccounts')->where('coa_id',$request->coa_id)->first();
        echo $chartofaccount->coa_description;
    }
}
