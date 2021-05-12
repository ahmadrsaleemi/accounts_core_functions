<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Inventory;
use App\Inventoryissuance;
use DB;
use App\Employee;
class InventoryissuanceController extends Ledgerfunctions
{   
    public function index(){
        $emps = Employee::where('type',5)->orwhere('type', 2)->get();
        $inventories =  Inventory::selectRaw('SUM(inventories.inv_qty) as qty')->addSelect('inventories.inv_product_id','products.product_description')->leftjoin('products','products.product_id','=','inventories.inv_product_id')->groupBy('inventories.inv_product_id')->get();
        return view('inventoryissuance.view',compact('inventories','emps'));
    }

    public function store(Request $request){
    $_costofsale = 0;
    for($i=0;$i < count($request->issue);$i++){
        if($request->issue[$i] != ""){
            $Inventoryissuance = new Inventoryissuance;
            $Inventoryissuance->product = $request->inv_product_id[$i];
            $Inventoryissuance->av_qty = $request->qty[$i];
            $Inventoryissuance->issueqty = $request->issue[$i];
            $Inventoryissuance->date =$request->txt_date;
            $Inventoryissuance->issuedto = $request->employee;
            $Inventoryissuance->save();
            $refrence_id = Inventoryissuance::orderby('id','desc')->first()->id;
           
            
            $this->update_inventory($request->inv_product_id[$i],$request->issue[$i],$refrence_id,$_costofsale,$request->txt_date);
        }

        
    }
    $this->cost_sale_ledger($_costofsale,12121212,$request->txt_date);
        return redirect('inventoryissuance');
    }

    public function update_inventory($product_id,$qty,$refrence_id,&$_costofsale,$date){

        // echo 'product_id&nbsp'.$product_id."&nbsp  Quantity&nbsp".$qty."<br>";
       //updated at date formate
        $updated_at=date('Y-m-d H:i:s');
        $fin=0;
        $costofsale=0;
        $fin2=0;
        $input_qty=$qty;
        $res_price=0;
        $originalqty=0;
        $wholeqty =Inventory::where('inv_product_id',$product_id)->sum('inv_qty');
        $dbcp=0;
        //getting inventories
        $inventories=Inventory::where('inv_product_id',$product_id)->get();
        foreach ($inventories as $inventory) {
           //storing inventory id in $inventory_id
            $inventory_id=$inventory->id;
           //checking if sold item quantot is less then qty available in inventory agaainst that product
         if($inventory->inv_qty > $qty && $qty != 0 || $inventory->inv_qty == $qty && $qty != 0){
               //updated qty
               $final_qty=$inventory->inv_qty-$qty;
               //updated inventory of particular product with final_qty in below query
               $dbcp=$inventory->inv_cost_price;
               DB::table('inventories')->where('id',$inventory->id)->update(['inv_qty'=>$final_qty,'updated_at'=>$updated_at]);

               //empting sold product qty
               $qty=$qty-$qty;
              
           }
            //checking if sold item quantot is greater then qty available in inventory agaainst that product
           elseif($inventory->inv_qty < $qty) {
               //updated qty
                 $final_qty=$inventory->inv_qty-$inventory->inv_qty;
                 
                 //updated inventory of particular product with $final_qty in below query
                DB::table('inventories')->where('id',$inventory->id)->update(['inv_qty'=>$final_qty,'updated_at'=>$updated_at]);

                //summing up qty
               $originalqty +=$inventory->inv_qty;
               //sutracting sold qty from available qty
               $qty=$qty-$inventory->inv_qty;
               //getting last product ledger
               
              
                   $dbcp=$inventory->inv_cost_price;
                 //calculating resulting price
                 $res_price += $inventory->inv_qty*$inventory->inv_cost_price;
           }
           
           
         //echo 'price ='.$fin."<br>";
               
             }
             //echo $wholeqty."<br>";
         if($input_qty > $wholeqty){
           $minus_quantity=$wholeqty-$input_qty;
           $last_record=DB::table('inventories')->where('inv_product_id',$product_id)->orderby('id', 'desc')->first();
               //updated inventory of particular product with $minus_quantity in below query
             DB::table('inventories')->where('id',$last_record->id)->update(['inv_qty'=>$minus_quantity,'updated_at'=>$updated_at]);
           }
           
       // //calculating final price
       $fin =$res_price+($input_qty-$originalqty)*$dbcp;
           $_costofsale +=$fin;
       //   //echo $fin;
       // //storing inventory ledger by calling inventoryledger function
       $this->inventoryledger("product_".$product_id,$fin,$refrence_id,$date);
       
   }

    public function inventoryledger($account_id,$credit_ammount,$refrenceid,$date){
        $this->assetledger($account_id,1,"assets",null,$credit_ammount,2,$refrenceid,$date);
    }

    public function cost_sale_ledger($total,$refrenceid,$date){

        $this->expensledger(10102,5,"expense",$total,null,2,$refrenceid,$date);
        
    }

    public function show(){
        $issuedInventories = Inventoryissuance::select('inventoryissuances.*','products.product_description','employees.name')->leftjoin('products','products.product_id','=','inventoryissuances.product')->leftjoin('employees','employees.id','=','inventoryissuances.issuedto')->get();
        return view('inventoryissuance.show',compact('issuedInventories'));
    }
}
