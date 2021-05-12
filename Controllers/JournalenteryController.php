<?php

namespace App\Http\Controllers;

use App\Journalentery;
use Illuminate\Http\Request;
use DB;
use App\Chartofaccount;
use App\Subjournalenteries;
use App\Ledger;
use Auth;
use App\Fiscalyear;
class JournalenteryController extends Ledgerfunctions
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
    //index function to show all journals
    public function index()
    {
        $query=DB::raw('Select * from journalenteries where void=false');
        $journals=DB::select($query);
        return view('journal/view',compact('journals'));
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
            $test = env('DB_DATABASE');
        //Auto increment to add new journal with new id
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='journalenteries'");
        //chart of accounts from chartofaccounts table
        $chartofaccounts=Chartofaccount::all();
        return view('journal/add',compact('auto_increment','chartofaccounts','fy'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //initializing $ref id 
        $ref_id=0;
        //if remaining balanse field is not equals to 0
        if($request->balance != 0){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'Balance Should be Zero');
            return redirect('journal/add');
        }
        //if remaining balanse field is 0
        else{
        //storing journal
        $date=date('Y-m-d H:i:s');
        $journal=new Journalentery();
        $journal->date=$request->txt_date;
        $journal->void=false;
        $journal->user_id=Auth::id();
        //if journal added successfully proceed further
        if($journal->save()){

            $acc_type=0;
            $acc_type2=0;
            //getting lates journal id
            $last_record=Journalentery::orderby('id', 'desc')->first();
            //assigning $ref_id thats latest id
            $ref_id=$last_record->id;
            //saving journal entries
            $subjournal=new Subjournalenteries;
            $subjournal->journalno=$last_record->id;
            $subjournal->account_id=$request->coa_id1;
            $subjournal->description=$request->description1;
            $subjournal->debit=$request->debit1;
            $subjournal->credit=$request->credit1;
            //checking account type asset,expense,liability,capital,income etc
            if($request->coa_type1 == "assets"){
                $acc_type=1;
            }
            else if($request->coa_type1 == "liabilities"){
                $acc_type=2;
            }
            else if($request->coa_type1 == "capital"){
                $acc_type=3;
            }
            else if($request->coa_type1 == "income"){
                $acc_type=4;
            }
            else if($request->coa_type1 == 'expense'){
                $acc_type=5;
            }
            //save joutrnal for first timr
            $subjournal->save();
            //storing ledger
            $this->storledger($request->coa_id1,$acc_type,$request->coa_type1,$request->debit1,$request->credit1,5,$ref_id,$request->txt_date);
            //checking count of fileds dynamically genrated
            if(isset($_POST['counter'])){
                //looping till count of fileds genrated
                for($i=0;$i < count($request->counter);$i++){
                    //checking if any field is not empty
                if($request->coa_id[$i] != "" && $request->description[$i] && $request->debit[$i] !="" || $request->credit[$i] !=""){
                    //saving journal entries
                        $subjournal=new Subjournalenteries;
                        $subjournal->journalno=$last_record->id;
                        $subjournal->account_id=$request->coa_id[$i];
                        $subjournal->description=$request->description[$i];
                        $subjournal->debit=$request->debit[$i];
                        $subjournal->credit=$request->credit[$i];
                        $subjournal->save();
                        //cheking account types of entries
                        if($request->coa_type[$i] == "assets"){
                                $acc_type2=1;
                            }
                            else if($request->coa_type[$i] == "liabilities"){
                                $acc_type2=2;
                            }
                            else if($request->coa_type[$i] == "capital"){
                                $acc_type2=3;
                            }
                            else if($request->coa_type[$i] == "income"){
                                $acc_type2=4;
                            }
                            else if($request->coa_type[$i] == 'expense'){
                                $acc_type2=5;
                            }
                        //saving journal entries ledger
                        $this->storledger($request->coa_id[$i],$acc_type2,$request->coa_type[$i],$request->debit[$i],$request->credit[$i],5,$ref_id,$request->txt_date);
                    }
                }
            }
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Journal Has Been Added');
        }
       return redirect('journal/add');
        }
    }

     //saving journal entries ledger
    public function storledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        //initialize $balance variable
        
        //checking if dueamount not = 0 to store supplier ledger
        $this->assetledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date);
        $this->liabilityledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date);
        $this->capitalledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date);
        $this->incomeledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date);
        $this->expensledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date);
        
    }

   
    /**
     * Display the specified resource.
     *
     * @param  \App\Journalentery  $journalentery
     * @return \Illuminate\Http\Response
     */
    public function show($journalentery)
    {
        //chart of accounts from chartofaccounts table
        $chartofaccounts=Chartofaccount::all();
        //journalentries of accounts from journalentries table
        $journalentries=Journalentery::where('id',$journalentery)->get();
        //subjournalenteries of accounts from subjournalenteries table
        $subjournalentries=DB::table('subjournalenteries')->select('subjournalenteries.*','chartofaccounts.coa_title','chartofaccounts.account_type')->join('chartofaccounts', 'chartofaccounts.coa_id', '=', 'subjournalenteries.account_id')->where('subjournalenteries.journalno',$journalentery)->get();
        return view('journal/edit',compact('auto_increment','chartofaccounts','journalentries','subjournalentries'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Journalentery  $journalentery
     * @return \Illuminate\Http\Response
     */
    public function edit(Journalentery $journalentery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Journalentery  $journalentery
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Journalentery $journalentery)
    {
        //updating journalentries
        for($i=0;$i < count($request->coa_id);$i++){
            Subjournalenteries::where('journalno',$request->txt_inv_no)->where('id',$request->rec_id[$i])->update(['account_id'=>$request->coa_id[$i],'description'=>$request->description[$i],'debit'=>$request->debit[$i],'credit'=>$request->credit[$i]]);
            }
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Journal Has Been Added');           
            return redirect('journal/show/'.$request->txt_inv_no);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Journalentery  $journalentery
     * @return \Illuminate\Http\Response
     */
    public function destroy($journalentery)
    {
        //deleting journals and journal entries
       DB::table('journalenteries')->where('id',$journalentery)->delete();
        DB::table('subjournalenteries')->where('journalno',$journalentery)->delete();
        return redirect('journal/show');
    }

    //ajax call to populate accout title/name and account type
    public function populatecoanameandtype(Request $request){
        //return $request->supplier;
        $coas =DB::table('chartofaccounts')->where('coa_id',$request->coa)->get();
        foreach ($coas as $coa) {
            if($coa->account_type == 1){
                $coa_type="assets";
            }
            elseif($coa->account_type == 2){
                 $coa_type="liabilities";
            }
            elseif($coa->account_type == 3){
                 $coa_type="capital";
            }
            elseif($coa->account_type == 4){
                 $coa_type="income";
            }
            elseif($coa->account_type == 5){
                $coa_type="expense"; 
            }
           echo json_encode(array($coa->coa_title,$coa_type));
        }
    }

    //making journal invisible
    public function voidjournal($journalentery){
        DB::table('journalenteries')->where('id',$journalentery)->update(['void'=>True]);
        return redirect('journal/show');
    }

    // public function view($journalentery){
    //     $journalentries=Journalentery::where('id',$journalentery)->get();
    //     $subjournalentries=DB::table('subjournalenteries')->select('subjournalenteries.*','chartofaccounts.coa_title','chartofaccounts.account_type')->join('chartofaccounts', 'chartofaccounts.coa_id', '=', 'subjournalenteries.account_id')->where('subjournalenteries.journalno',$journalentery)->get();
    //     return view('journal/viewprint',compact('journalentries','subjournalentries'));
    // }

    //showing ledger entries in demoledger view
    public function ledgerdemo(){
        $query=DB::raw('Select ledgers.id as lid,ledgers.account_id,ledgers.debit_ammount,ledgers.credit_ammount,ledgers.balance,ledgers.date,ledgers.created_at,chartofaccounts.coa_title from ledgers left join chartofaccounts on chartofaccounts.coa_id = ledgers.account_id ORDER BY ledgers.id ASC');

        $ledgers=DB::select($query);
        // $=Ledger::select('ledgers.id as lid','ledgers.debit_ammount','ledgers.credit_ammount','ledgers.balance','ledgers.created_at','chartofaccounts.coa_title')->leftjoin('chartofaccounts','chartofaccounts.coa_id','=','ledgers.account_id')->orderby('ledgers.id','ASC')->paginate(100);
        return view('ledger/view',compact('ledgers'));

    }
}
