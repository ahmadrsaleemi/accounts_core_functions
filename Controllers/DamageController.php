<?php

namespace App\Http\Controllers;

use App\Damage;
use App\Inventory;
use App\Product;

use DB;
use Illuminate\Http\Request;

class DamageController extends Ledgerfunctions
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $damages = Damage::select('damages.*', 'products.product_description')->leftjoin('products', 'products.product_id', 'damages.product_id')->get();
        return view('damageitems.view', compact('damages'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        $test = env('DB_DATABASE');
         $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='damages'");
        $products = Inventory::select('inventories.inv_product_id', 'products.product_description')->leftjoin('products', 'products.barcode', 'inventories.inv_product_id')->groupBy('inventories.inv_product_id')->get();
        // $products = Product::select('products.*', 'inventories.inv_product_id')->leftjoin('inventories', 'inventories.inv_product_id', 'products.product_id')->groupBy('inventories.inv_product_id')->get();
        // return $products;
        return view('damageitems.add', compact('auto_increment','products'));
    }

    public function data(Request $request){
        $name = Product::select('products.*', 'bill_products.cost_price')->leftjoin('bill_products', 'bill_products.product_id', 'products.barcode')->where('products.barcode', $request->product_id)->first();
        $qty = Inventory::selectRaw('SUM(inv_qty) as sum')->where('inv_product_id', $request->product_id)->get();
        
        return array($name->product_description,$name->cost_price, $qty);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $tempamount = 0;
        $arr_productid = $request->txt_product_id;
        $arr_productid[]=$request->txt_product_id1;
        $arr_damage = $request->damage;
        $arr_damage[] =$request->damage1;
        $arr_costprice = $request->costprice;
        $arr_costprice[] = $request->costprice1;

        for ($i=0; $i < count($arr_productid); $i++) { 
            
            $damage = new Damage;
            $damage->product_id = $arr_productid[$i];
            $damage->damage = $arr_damage[$i];
            $damage->date = date('d-m-Y');
            $damage->save();

            $this->update_inventory($arr_productid[$i],$arr_damage[$i] ,date('d-m-Y'));
            

            $this->assetledger('product_'.$arr_productid[$i],1,"assets",null,$arr_costprice[$i] * $arr_damage[$i] ,1,$request->inv,date('Y-m-d'));

            $tempamount += $arr_costprice[$i] * $arr_damage[$i];

        }

        $this->expensledger(10104,5,"expense",$tempamount ,null,4,$request->inv,date('d-m-Y'));

        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'New Damage quantity Has Been Added');

        return redirect('damage/add');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Damage  $damage
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $damages = Damage::select('damages.*', 'products.product_description')->leftjoin('products', 'products.product_id', 'damages.product_id')->where('damages.id',$id)->get();

        return view('damageitems.edit', compact('damages'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Damage  $damage
     * @return \Illuminate\Http\Response
     */
    public function edit(Damage $damage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Damage  $damage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Damage $damage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Damage  $damage
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $delete = Damage::where('id', $id)->delete();
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'Damage quantity Has Been Deleted successfully');

        return redirect('damage/view');
    }

     public function update_inventory($product_id,$qty,$date){

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
        // $fin =$res_price+($input_qty-$originalqty)*$dbcp;
        //     $_costofsale +=$fin;
        // //   //echo $fin;
        // // //storing inventory ledger by calling inventoryledger function
        // $this->inventoryledger("product_".$product_id,$fin,$refrence_id,$date,$datebit);
        
    }
}
