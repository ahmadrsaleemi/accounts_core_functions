<?php

namespace App\Http\Controllers;

use App\Construction;
use Illuminate\Http\Request;
use App\Employee;
use App\Construtiondetail;
use App\Expenseproject;
use App\Expenseprojectdetail;
use App\Projectitem;
use Auth;
use App\Alotitem;
class ConstructionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects=Construction::select('constructions.*','employees.name')->leftjoin('employees','constructions.employee_id','=','employees.id')->get();
        return view('construction/view',compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $employees=Employee::all();
        return view('construction/add',compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $con=new Construction;
        $con->projectname=$request->p_name;
        $con->date=$request->date;
        $con->employee_id=$request->emp;
        $con->total=$request->total;
        if($con->save()){
            $last_id=Construction::orderby('id','desc')->first();
            $condet=new Construtiondetail;
            $condet->project_id=$last_id->id;
            $condet->description=$request->description1;
            $condet->amount=$request->amount1;
            $condet->date=$request->rowdate1;
            $condet->save();
            if(isset($request->description) > 0){
                for($i=0;$i < count($request->description);$i++){
                    $condet=new Construtiondetail;
                    $condet->project_id=$last_id->id;
                    $condet->description=$request->description[$i];
                    $condet->amount=$request->amount[$i];
                    $condet->date=$request->rowdate[$i];
                    $condet->save();
                }
            }
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Project Created Successfully..!');
            return redirect('project/add');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Construction  $construction
     * @return \Illuminate\Http\Response
     */
    public function show($construction)
    {
        $employees=Employee::all();
        $projects=Construction::select('constructions.*','employees.name')->leftjoin('employees','constructions.employee_id','=','employees.id')->where('constructions.id',$construction)->get();
        $projectdetails=Construtiondetail::where('project_id',$construction)->get();
        return view('construction/edit',compact('employees','projects','projectdetails'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Construction  $construction
     * @return \Illuminate\Http\Response
     */
    public function edit(Construction $construction)
    {
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Construction  $construction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {   
        $update=Construction::where('id',$request->project_id)->update(['projectname'=>$request->p_name,'date'=>$request->date,'employee_id'=>$request->emp,'total'=>$request->total]);
        if($update){
            if(count($request->description) > 0){
                for($i=0;$i < count($request->description);$i++){
                    
                Construtiondetail::where('id',$request->pdid[$i])->where('project_id',$request->project_id)->update(['description'=>$request->description[$i],'amount'=>$request->amount[$i],'date'=>$request->rowdate[$i]]);
                }
            }
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Project updated Successfully..!');
            return redirect('project/show/'.$request->project_id.'');
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Construction  $construction
     * @return \Illuminate\Http\Response
     */
    public function destroy($construction)
    {
        Construction::where('id',$construction)->delete();
        Construtiondetail::where('project_id',$construction)->delete();
        return redirect('project/view');
    }

    public function pexpenseform(){

        $projects=Construction::get();
        return view('construction/add-p-expense',compact('projects'));
    }

    public function savepexpense(Request $request){
        $pexp=new Expenseproject;
        $pexp->project=$request->p_name;
        $pexp->date=$request->date;
        $pexp->total=$request->total;
        $pexp->approve=false;
        if($pexp->save()){
            $last_id=Expenseproject::orderby('id','desc')->first();
            $pexpdet=new Expenseprojectdetail;
            $pexpdet->expenseid=$last_id->id;
            $pexpdet->description=$request->description1;
            $pexpdet->amount=$request->amount1;
            $pexpdet->save();
            if(isset($request->description) > 0){
                for($i=0;$i < count($request->description);$i++){
                    $pexpdet=new Expenseprojectdetail;
                    $pexpdet->expenseid=$last_id->id;
                    $pexpdet->description=$request->description[$i];
                    $pexpdet->amount=$request->amount[$i];
                    $pexpdet->save();
                }
            }
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Project Expense Sent for approval  Successfully..!');
            return redirect('pexpense/add');
        }
    }

    public function viewpexpense(){
        $pexpense = Expenseproject::select('expenseprojects.*','constructions.projectname')->leftjoin('constructions','constructions.id','=','expenseprojects.project')->get();
        return view('construction/viewexpense',['pexpenses'=>$pexpense]);
    }

    public function destroyexpense($pexp_id){
         Expenseproject::where('id',$pexp_id)->delete();
        Expenseprojectdetail::where('expenseid',$pexp_id)->delete();
        return redirect('pexpense/view');
    }

    public function showapproveexpense($pexp_id){
        $projects=Construction::get();
        $pexpens=Expenseproject::select('expenseprojects.*','constructions.projectname')->leftjoin('constructions','constructions.id','=','expenseprojects.project')->where('expenseprojects.id',$pexp_id)->get();
        $expensedetails=Expenseprojectdetail::where('expenseid',$pexp_id)->get();
        return view('construction/editexpense',['pexpenses'=>$pexpens,'expensedetails'=>$expensedetails,'projects'=>$projects]);
    }

    public function updatepexpense(Request $request){

        $update=Expenseproject::where('id',$request->exp_id)->update(['project'=>$request->p_name,'date'=>$request->date,'total'=>$request->total,'approve'=>true]);
        if($update){
            if(isset($request->description) > 0){
                for($i=0;$i < count($request->description);$i++){
                    
                Expenseprojectdetail::where('id',$request->expdet_id[$i])->where('expenseid',$request->exp_id)->update(['description'=>$request->description[$i],'amount'=>$request->amount[$i]]);
                }
            }
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Project updated Successfully..!');
            return redirect('pexpense/show/'.$request->exp_id.'');
        }
    }

    public function createitem(){
       return view('construction/createitem');
    }

    public function saveitem(Request $request){
       $item=new Projectitem();
       $item->user_id=Auth::id();
       $item->itemdescription=$request->itemdescription;
       $item->itemprice=$request->price;
       if($item->save()){
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','New Item Saved Successfully!');
       }
       return redirect('project/createitem');
    }

    public function allitems(){
        $allitems=Projectitem::select('projectitems.*','users.name')->leftjoin('users','projectitems.user_id','=','users.id')->get();
        return view('construction/allitems',['items'=>$allitems]);
    }

    public function destroyitem($item){
        Projectitem::where('id',$item)->delete();
        return redirect('project/itemview');
    }

    public function showitem($item){
        $edititem=Projectitem::where('id',$item)->get();
        return view('construction/showitem',['items'=>$edititem]);
    }
    public function updateitem(Request $request){
        $update=Projectitem::where('id',$request->rec_id)->update(['itemdescription'=>$request->itemdescription,'itemprice'=>$request->price]);
        if($update){
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Item Updated Successfully!');
            return redirect('item/show/'.$request->rec_id);
        }
    }
    public function alotitem(){
        $items=Projectitem::all();
        $projects=Construction::all();
        return view('construction/alotitem',compact('items','projects'));
    }

    public function alotitems(Request $request){
        $alotitem=new Alotitem;
        $alotitem->item=$request->item1;
        $alotitem->project=$request->project1;
        $alotitem->user_id=Auth::id();
        if($alotitem->save()){
            if(count($request->project) > 0){
                for($i=0;$i < count($request->project);$i++){
                $alotitem=new Alotitem;
                $alotitem->item=$request->item[$i];
                $alotitem->project=$request->project[$i];
                $alotitem->user_id=Auth::id();
                $alotitem->save();
                
                }
            }

            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Items Aloted');
        }
        return redirect('project/alotitem');
        
    }

    public function viewaloteditems(){
        $aloteditems=Alotitem::select('alotitems.*','users.name','constructions.projectname','projectitems.itemdescription')->leftjoin('constructions','constructions.id','=','alotitems.project')->leftjoin('projectitems','projectitems.id','=','alotitems.item')->leftjoin('users','users.id','=','alotitems.user_id')->orderby('id','desc')->get();
       //return $aloteditems;
        return view('construction/alotitems',compact('aloteditems'));
    }
}
