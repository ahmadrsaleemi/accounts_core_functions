<?php

namespace App\Http\Controllers;

use App\Debitnote;
use App\Debitnoteproduct;
use Illuminate\Http\Request;
use App\Supplier;
use App\Bill;
use App\Bill_product;
use DB;
use App\Ledger;
use App\Inventory;
use Auth;
use App\Fiscalyear;

class DebitnoteController extends Ledgerfunctions
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
         $query=DB::raw('SELECT * From debitnotes LEFT JOIN suppliers ON debitnotes.supplier_id=suppliers.supplier_id LEFT JOIN users ON debitnotes.user_id = users.id where void=false');
        $debitnotes=DB::select($query);
        // $debitnotes=Debitnote::leftjoin('suppliers','debitnotes.supplier_id','=','suppliers.supplier_id')->leftjoin('users','debitnotes.user_id','=','users.id')->where('void',False)->paginate(100);
       return view('debitnotes/view',compact('debitnotes'));
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
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name = 'debitnotes'");
        $suppliers=Supplier::all();
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        return view('debitnotes/add',compact('auto_increment','suppliers','chartofaccounts','fy'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $date=date('Y-m-d H:i:s');
        $debitnote=new Debitnote();
        $debitnote->supplier_id=$request->txt_supplier;
        $debitnote->bill_id=$request->bill;
        $debitnote->date=$request->txt_date;
        $debitnote->total=$request->total;
        $debitnote->cashpaid=$request->cash_paid;
        $debitnote->duebalance=$request->total_amount;
        $debitnote->coa=$request->coa;
        $debitnote->user_id=Auth::id();
        $debitnote->void=false;
        if($debitnote->save()){
            $last_record=Debitnote::orderby('id', 'desc')->first();
            $refrenseid=$last_record->id;
            $this->cashpaid_returnledger($request->coa,$request->cash_paid,$refrenseid,$request->txt_date);
            $this->duebalance_returnledger('supplier_'.$request->txt_supplier,$request->total_amount,$refrenseid,$request->txt_date);
            
            $debn_products=new Debitnoteproduct;
            $debn_products->debitnote_id=$last_record->id;
            $debn_products->product_id=$request->txt_product_id1;
            $debn_products->price=$request->price1;
            $debn_products->qty=$request->qty1;
            $debn_products->inlinetotal=$request->linetotal1;
            $this->product_returnledger('product_'.$request->txt_product_id1,$request->linetotal1,$refrenseid,$request->txt_date);
            $this->Updateinventory($request->txt_product_id1,$request->bill,$request->txt_supplier,$request->price1,$request->qty1,$request->txt_date);
            $debn_products->save();
            if(isset($_POST['counter'])){
                 for($i=0;$i < count($request->counter);$i++){
                        if($request->txt_product[$i] !="" && $request->price[$i] !="" && $request->qty[$i] !=""){
                        $debn_products=new Debitnoteproduct;
                        $debn_products->debitnote_id=$last_record->id;
                        $debn_products->product_id=$request->txt_product[$i];
                        $debn_products->price=$request->price[$i];
                        $debn_products->qty=$request->qty[$i];
                        $debn_products->inlinetotal=$request->linetotal[$i];
                        $debn_products->save();
                        $this->product_returnledger('product_'.$request->txt_product[$i],$request->linetotal[$i],$refrenseid,$request->txt_date);
                        $this->Updateinventory($request->txt_product[$i],$request->bill,$request->txt_supplier,$request->price[$i],$request->qty[$i],$request->txt_date);
                    }
                }
            }

            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Debitnotes Have Been Added');
        }
        return redirect('debitnotes/add');
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Debitnote  $debitnote
     * @return \Illuminate\Http\Response
     */
    public function show(Debitnote $debitnote)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Debitnote  $debitnote
     * @return \Illuminate\Http\Response
     */
    public function edit(Debitnote $debitnote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Debitnote  $debitnote
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Debitnote $debitnote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Debitnote  $debitnote
     * @return \Illuminate\Http\Response
     */
    public function destroy(Debitnote $debitnote)
    {
        //
    }

     public function voiddebitnotes($debn_id){
         $date=date('Y-m-d H:i:s');
         Debitnote::where('id',$debn_id)->update(['void'=>True,'updated_at'=>date('y-m-d h:m:s')]);
        return redirect('debitnotes/view');
    }

    public function getbillproducts(request $request){
        $bill_products=Bill_product::leftjoin('products','products.product_id','bill_products.product_id')->WHERE('bill_products.bill_id',$request->bill)->groupby('products.product_id')->get();
        if($bill_products != null){
            foreach ($bill_products as $bill_product) {
               echo"<option value=".$bill_product->product_id.">".$bill_product->product_description."</option>";
            }
        }
        
    }

public function getproductcostprice(request $request){
       // echo $request->product." ".$request->bill;
         $product_prices=Bill_product::where('product_id',$request->product)->where('bill_id',$request->bill)->get();
        if($product_prices != null){
            foreach ($product_prices as $product_price) {
               echo $product_price->cost_price;
                //echo "wroking";
            }
        }
        
    }
    public function getsupbills(request $request){
        $bills=Bill::WHERE('bills.supplier',$request->supplier)->get();
        if($bills != null){
            foreach ($bills as $bill) {
               echo"<option value=".$bill->bll_id."></option>";
            }
        }
        
    }

    public function product_returnledger($account_id,$amount,$refrenceid,$date){
        if($amount > 0){
         $this->assetledger($account_id,1,"assets",null,$amount,3,$refrenceid,$date);
        }
    }
    public function cashpaid_returnledger($account_id,$amount,$refrenceid,$date){
        if($amount > 0){
            $this->assetledger($account_id,1,"assets",$amount,null,3,$refrenceid,$date);
        }
    }

    public function duebalance_returnledger($account_id,$amount,$refrenceid,$date){
        if($amount > 0){
         $this->assetledger($account_id,2,"liabilities",$amount,null,3,$refrenceid,$date);
        }
    }

    public function Updateinventory($pid,$biil_id,$sup_id,$cost_price,$qty,$date){
        
        $old_qty="";
        // echo $biil_id." ".$pid." ".$cost_price."<br>";
         $inventories=Inventory::
         where('inv_bill_id',$biil_id)
        ->where('inv_product_id',$pid)
        ->where('inv_cost_price',$cost_price)->get();        
        if($inventories !== null){
            foreach($inventories as $inv){
               $old_qty=$inv->inv_qty;
            }
            
        $old_qty=$old_qty-$qty;
        if($old_qty > 0){
            Inventory::where('inv_supplier_id',$sup_id)
        ->where('inv_bill_id',$biil_id)
        ->where('inv_product_id',$pid)
        ->where('inv_cost_price',$cost_price)
        ->update([ 'inv_product_id'=>$pid, 'inv_supplier_id'=>$sup_id, 'inv_bill_id'=>$biil_id, 'inv_cost_price'=>$cost_price, 'inv_qty'=>$old_qty,'inv_bill_date'=>$date,'updated_at'=>date('y-m-d h:m:s')]);
        }
         }
       
        
    }
}
