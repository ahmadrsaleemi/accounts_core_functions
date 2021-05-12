<?php

namespace App\Http\Controllers;

use App\Creditnote;
use App\Creditnoteproduct;
use Illuminate\Http\Request;
use App\Customer;
use App\Sale;
use App\Saleproduct;
use App\Product;
use App\Ledger;
use App\Inventory;
use DB;
use Auth;
use App\Fiscalyear;

class CreditnoteController extends Ledgerfunctions
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $profit = '';
    Public function __construct(){
        $this->middleware('auth');
    }
    
    public function index()
    {
         $query=DB::raw('SELECT * From creditnotes LEFT JOIN customers ON creditnotes.customer_id=customers.customer_id LEFT JOIN users ON creditnotes.user_id = users.id where void=false');
        $creditnotes=DB::select($query);
         return view('creditnotes/view',compact('creditnotes'));
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
        $customers=Customer::all();
        $products=Product::all();
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        return view('creditnotes/add',compact('customers','products','chartofaccounts','fy'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->cash_paid != ""){
            $cashpaid=$request->cash_paid;
        }
        else{
            $cashpaid=0;
        }
         $date=date('Y-m-d H:i:s');
        $creditnote=new Creditnote();
        $creditnote->customer_id=$request->txt_customer;
        $creditnote->date=$request->txt_date;
        $creditnote->total=$request->total;
        $creditnote->cashpaid=$cashpaid;
        $creditnote->duebalance=$request->total_amount;
        $creditnote->coa=$request->coa;
        $creditnote->void=false;
        $creditnote->user_id=Auth::id();
         if($creditnote->save()){
            $last_record=Creditnote::orderby('id', 'desc')->first();
            $refrenceid=$last_record->id;
            $this->cashpaid_returnledger($request->coa,$cashpaid,$refrenceid,$request->txt_date);
            $this->duebalance_returnledger('customer_'.$request->txt_customer,$request->total_amount,$refrenceid,$request->txt_date);
            
            $cren_products=new Creditnoteproduct;
            $cren_products->creditnote_id=$last_record->id;
            $cren_products->product_id=$request->txt_product_id1;
            $cren_products->price=$request->price1;
            $cren_products->qty=$request->qty1;
            $cren_products->inlinetotal=$request->linetotal1;
            $cren_products->save();
            $this->product_returnledger('product_'.$request->txt_product_id1,$request->linetotal1,$refrenceid,$request->txt_date);
              $finalarray = array();
               $tempprice=0;
               $tempqty=0;
               $tempresult=0;
               $arr_productid = $request->txt_product;
               $arr_productid[]=$request->txt_product_id1;
               $arr_productprice = $request->price;
               $arr_productprice[] =$request->price1;
               $arr_productqty = $request->qty;
               $arr_productqty[] = $request->qty1;
                $_costofsale = 0;
               $arr_flag = array();
               for ($i = 0; $i < count($arr_productid); $i++){
                    $arr_flag[$i] = 0;
               }
               for ($i = 0; $i < count($arr_productid); $i++){
                        echo $arr_productid[$i] . "&nbsp;" . $arr_productprice[$i] . "&nbsp;" . $arr_productqty[$i]  . "&nbsp;" . $arr_flag[$i];
                            echo "<br>";
               }
                            echo "Data Begins";
                            echo "<br>";
               for ($i = 0; $i < count($arr_productid); $i++){
                    $tempid = $arr_productid[$i];
                    for($j = 0; $j < count($arr_productid); $j++){
                        if($arr_productid[$j] !=""){
                        if($arr_productid[$j] == $tempid && $arr_flag[$j] != 1){
                            $tempqty += $arr_productqty[$j];
                            $tempprice = $arr_productprice[$j];
                            $arr_flag[$j] = 1;
                        }
                        }
                    }
                    if ($tempqty != 0){
                        $fin = $tempqty * $tempprice;
                        $this->Updateinventory($arr_productid[$i],$tempprice,$tempqty,$date,$request->total,$_costofsale);
                        $tempqty = 0;
                        $tempprice = 0;
                    }
               }
        if(isset($_POST['counter'])){
            for($i=0;$i < count($request->counter);$i++){
                    if($request->txt_product[$i] !="" && $request->price[$i] !="" && $request->qty[$i] !=""){
                    $cren_products=new Creditnoteproduct;
                    $cren_products->Creditnote_id=$last_record->id;
                    $cren_products->product_id=$request->txt_product[$i];
                    $cren_products->price=$request->price[$i];
                    $cren_products->qty=$request->qty[$i];
                    $cren_products->inlinetotal=$request->linetotal[$i];
                    $cren_products->save();
                    $this->product_returnledger('product_'.$request->txt_product[$i],$request->linetotal[$i],$refrenceid);
                }
            }
        }    
            $this->sale_ledger($request->total,$refrenceid,$request->txt_date);
            $this->cost_sale_ledger($_costofsale,$refrenceid,$request->txt_date);
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Creditnotes Have Been Added');
         }
        return redirect('creditnotes/add');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Creditnote  $creditnote
     * @return \Illuminate\Http\Response
     */
    public function show(Creditnote $creditnote)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Creditnote  $creditnote
     * @return \Illuminate\Http\Response
     */
    public function edit(Creditnote $creditnote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Creditnote  $creditnote
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Creditnote $creditnote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Creditnote  $creditnote
     * @return \Illuminate\Http\Response
     */
    public function destroy(Creditnote $creditnote)
    {
        //
    }

    public function voidcreditnotes($cren_id){
         $date=date('Y-m-d H:i:s');
         Creditnote::where('id',$cren_id)->update(['void'=>True,'updated_at'=>date('y-m-d h:m:s')]);
        return redirect('creditnotes/view');
    }

    public function getcusbills(request $request){
        $bills=Sale::WHERE('customer',$request->customer)->get();
        if($bills != null){
            foreach ($bills as $bill) {
               echo"<option value=".$bill->id."></option>";
            }
        }
        
    }

    public function getbillproducts(request $request){
        $bill_products=Saleproduct::leftjoin('products','products.product_id','saleproducts.product')->Groupby('products.product_id')->WHERE('inv_id',$request->bill)->get();
        if($bill_products != null){
            foreach ($bill_products as $bill_product) {
               echo"<option value=".$bill_product->product.">".$bill_product->product_description."</option>";
            }
        }
        
    }

    public function product_returnledger($account_id,$amount,$refrenceid,$date){
        if($amount > 0){
            $this->assetledger($account_id,1,"assets",$amount,null,4,$refrenceid,$date);
        }
    }
    public function cashpaid_returnledger($account_id,$amount,$refrenceid,$date){
        if($amount > 0){
            $this->assetledger($account_id,1,"assets",null,$amount,$transectiontype,$refrenceid,$date);
        }
    }

    public function duebalance_returnledger($account_id,$amount,$refrenceid,$date){
        $this->assetledger($account_id,1,"assets",null,$amount,4,$refrenceid,$date);
        
    }
    

    public function Updateinventory($pid,$cost_price,$qty,$date,$total,&$_costofsale){
        // echo 'product_id =  &nbsp'.$pid."&nbsp cost_price = &nbsp".$cost_price.'&nbsp $qty = &nbsp'.$qty.'&nbsp  date = &nbsp'.$date."<br>";
        $last_record=Inventory::where('inv_product_id',$pid)
        ->orderby('id', 'desc')->first();        
        
        Inventory::where('inv_product_id',$last_record->inv_product_id)
        ->where('id',$last_record->id)
        ->update(['inv_qty'=>($last_record->inv_qty+$qty),'updated_at'=>date('y-m-d h:m:s')]);

        $_costofsale +=($qty*$last_record->inv_cost_price);

    }

    public function sale_ledger($total,$refrenceid,$date){
       $this->incomeledger(10101,4,"income",null,$total,4,$refrenceid,$date);
        }

    public function cost_sale_ledger($total,$refrenceid,$date){
    
        $this->incomeledger(10102,5,"expense",$total,null,4,$refrenceid,$date);
    }
}
