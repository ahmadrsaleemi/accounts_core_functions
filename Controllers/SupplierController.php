<?php

namespace App\Http\Controllers;

use App\Supplier;
use Illuminate\Http\Request;
use App\Chartofaccount;
use DB;
use Auth;
class SupplierController extends Controller
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
        $query=DB::raw('SELECT suppliers.*,users.name From suppliers LEFT JOIN users ON suppliers.user_id = users.id');
        $suppliers=DB::select($query);
         return view('supplier.view',['suppliers'=>$suppliers]);
    }

 
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $test = env('DB_DATABASE');
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='suppliers'");
        $supplier=DB::table('suppliers')->orderBy('supplier_id', 'desc')->first();
        return view('supplier.add',compact('supplier','auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request,
            [
            'txt_supplier_id'=>'required',
            'txt_supplier_name'=>'required',
        ]);
         $check_duplicate=Supplier::where('supplier_id',$request->txt_supplier_id)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Supplier Already Available Please Try Changed Supplier Id');
            return redirect('supplier/add');
        }
        else{
        $supplier=new Supplier;
        $supplier->supplier_id=$request->txt_supplier_id;
        $supplier->supplier_name=$request->txt_supplier_name;
        $supplier->addres=$request->supplier_address;
        $supplier->user_id=Auth::id();
        $supplier->contact=$request->cono;
        $supplier->contact_person=$request->co_pe;
        $supplier->bank_name=$request->bank_name;
        $supplier->email=$request->email;
        $supplier->bank_account=$request->account_number;
        if ($supplier->save()) {
            $this->supplier_chart_of_account('supplier_'.$request->txt_supplier_id,$request->txt_supplier_name,'supplier_'.$request->txt_supplier_name);
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Supplier was successfully added!');
        }
        return redirect('supplier/add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $supplier =DB::table('suppliers')->where('id',$id)->get();
        //return $supplier;
        return view('supplier/edit',['suppliers'=>$supplier]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
      $this->validate($request,
            ['txt_supplier_id'=>'required',
            'txt_supplier_name'=>'required',
            'supplier_address'=>'required'
        ]);
     
        $update=DB::table('suppliers')
            ->where('id', $request->supplier_id)
            ->update(['supplier_id'=>$request->txt_supplier_id,'supplier_name' => $request->txt_supplier_name,'addres'=>$request->supplier_address
            ,'contact'=>$request->cono,'contact_person'=>$request->co_pe,'bank_name' => $request->bank_name,
        'bank_account' => $request->account_number,
        'status'=>$request->status,
        'email'=>$request->email,
            ]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Supplier Details successfully Updated!');
        }
        return redirect('supplier/show/'.$request->supplier_id);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $supplier =DB::table('suppliers')->where('id',$id)->delete();
        return redirect('supplier/show');
    }

    public function allsuppliers(){
        $suppliers=Supplier::all();

        // echo"<option value=''>Select</option>";
            foreach ($suppliers as $supplier) {
                if($supplier->status == 0){
                echo "<option value=".$supplier->supplier_id.">".$supplier->supplier_name."</option>";
            }
        }
    }

    public function supplier_chart_of_account($account_id,$account_title,$account_description){
        $chartofaccount=new Chartofaccount();
        $chartofaccount->coa_id=$account_id;
        $chartofaccount->coa_title=$account_title;
        $chartofaccount->account_type=2;
        $chartofaccount->coa_description=$account_description;
        $chartofaccount->user_id=Auth::id();
        if($chartofaccount->save()){
            app('App\Http\Controllers\ChartofaccountController')->log_chartofaccount($account_id,$account_title,$account_description,2);
        }
    }
}
