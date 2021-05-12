<?php

namespace App\Http\Controllers;

use App\Cprelation;
use Illuminate\Http\Request;
use App\Chartofaccount;
use Auth;
class CprelationController extends Controller
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
        
        $chartofaccounts=Chartofaccount::where('account_type','1')->where('coa_id','Not Like','%product_%')->where('coa_id','Not Like','%supplier_%')->where('coa_id','Not Like','%customer_%')->where('coa_id','!=','padvance101')->get();
        $cprelations=Cprelation::all();
        return view('cprelation/show',compact('chartofaccounts','cprelations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $message="";
        if(isset($_POST['relate'])){
           foreach ($_POST['relate'] as $value) {

            $check_duplicate=Cprelation::where('acc_id',$request->id[$value])->first();
            if($check_duplicate != null){
                $message ="notok";
            }
            else{
               $cprelation=new Cprelation;
               $cprelation->acc_id=$request->id[$value];
               $cprelation->acc_title=$request->title[$value];
               $cprelation->user_id=Auth::id();
               if($request->id[$value] == $request->default){
                $cprelation->def='1';
               }
               else{
                $cprelation->def='0';
               }
               if($cprelation->save()){
                $message ="ok";
               }
             } 
           }
            
            
        }
        if($message == "ok"){
        $request->session()->flash('message.level','success');
        $request->session()->flash('message.content','Relation Has Been Done');
        }
        elseif($message == "notok"){
            $request->session()->flash('message.level','danger');
        $request->session()->flash('message.content','Relation already Been Done');
        }
        else{
            $request->session()->flash('message.level','danger');
        $request->session()->flash('message.content','Select Account To Be Related');
        }
        return redirect('cprelation/show');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cprelation  $cprelation
     * @return \Illuminate\Http\Response
     */
    public function show(Cprelation $cprelation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Cprelation  $cprelation
     * @return \Illuminate\Http\Response
     */
    public function edit(Cprelation $cprelation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cprelation  $cprelation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cprelation $cprelation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Cprelation  $cprelation
     * @return \Illuminate\Http\Response
     */
    public function destroy($cprelation)
    {
        Cprelation::where('id',$cprelation)->delete();
        return redirect('cprelation/show');
    }
}
