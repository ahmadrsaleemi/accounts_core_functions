<?php

namespace App\Http\Controllers;

use App\Fundstransfer;
use Illuminate\Http\Request;
use App\Chartofaccount;
use App\Ledger;
use DB;
use Auth;
class FundstransferController extends Ledgerfunctions
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
        $funds=Fundstransfer::paginate(100);
        return view('fundtransfer/view',compact('funds'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        return view('fundtransfer/add',compact('chartofaccounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'from'=>'required',
            'to'=>'required',
            'amount'=>'required'
        ]);
        $balance=0;
        $balance2=0;
        if($request->from != $request->to){
         $last_record=Ledger::where('account_id',$request->from)->orderby('id', 'desc')->first();
             if($last_record != null && $last_record->balance > 0 && $last_record->balance > $request->amount){ 
                $fund_t=new Fundstransfer;
                $fund_t->from_coa_id=$request->from;
                $fund_t->to_coa_id=$request->to;
                $fund_t->ammount=$request->amount;
                $fund_t->user_id=Auth::id();
                if($fund_t->save()){
                 
                    $refrence_id=Fundstransfer::orderby('id', 'desc')->first();
                    $this->assetledger($request->from,1,"assets",Null,$request->amount,7,$refrence_id->id,date('20y-m-d'));

                    $this->assetledger($request->to,1,"assets",$request->amount,null,7,$refrence_id->id,date('20y-m-d'));
                    $request->session()->flash('message.level', 'success');
                    $request->session()->flash('message.content', 'Amount Transsfered');
                }
            return redirect('fundstranser/add');
            }
             else{
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'Insufficient Amount');
            return redirect('fundstranser/add');
       }
        
       }
       else{
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'From Chart Of Account And To Chart Of account Must Be Different');
            return redirect('fundstranser/add');
       }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fundstransfer  $fundstransfer
     * @return \Illuminate\Http\Response
     */
    public function show(Fundstransfer $fundstransfer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fundstransfer  $fundstransfer
     * @return \Illuminate\Http\Response
     */
    public function edit(Fundstransfer $fundstransfer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fundstransfer  $fundstransfer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fundstransfer $fundstransfer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fundstransfer  $fundstransfer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fundstransfer $fundstransfer)
    {
        //
    }
}
