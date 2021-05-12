<?php

namespace App\Http\Controllers;

use App\StockTaking;
use App\Product;
use App\Inventory;
use Illuminate\Http\Request;
use DB;

class StockTakingController extends Ledgerfunctions
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();
        return view('stock/add', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $product_name = Product::where('barcode', $request->id)->first();
        $inventory = Inventory::where('inv_product_id', $request->id)->sum('inv_qty');

        return array($product_name->product_description, $inventory); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $diff = 0;
        $last_supplier = 0;
        $cost_price = 0;
        $test = env('DB_DATABASE');
        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='stock_takings'");
        
        foreach ($bill_id as $id) {
            $refrence_id = $id->AUTO_INCREMENT;
        }

        $stock = New StockTaking;
        $stock->product_id = $request->txt_product_id;
        $stock->previous_qty = $request->stock;
        $stock->new_qty = $request->new_stock;
        $stock->save();
        $diff = $request->stock - $request->new_stock;
        $last_inv = Inventory::where('inv_product_id', $request->txt_product_id)->orderBy('created_at', 'DESC')->first();
        $last_supplier = $last_inv->inv_supplier_id;
        $cost_price = $last_inv->inv_cost_price;
        $update = Inventory::where('inv_product_id', $request->txt_product_id)->update(['inv_qty'=> 0]);
            $this->Addinventory($request->txt_product_id,$request->txt_product_id,$last_inv->inv_supplier_id,$cost_price,$request->new_stock,date('d/m/Y'),'null');

        if($diff < 0){

             $this->expensledger(10102,5,"expense",null,-($cost_price * $diff),2,$refrence_id,$request->date_bit);
            $this->assetledger('product_'.$request->txt_product_id,1,"assets", -($cost_price * $diff),null,2,$refrence_id,$request->txt_date);
        }else{
            $this->expensledger(10102,5,"expense",($cost_price * $diff),null,2,$refrence_id,$request->date_bit);
            
            $this->assetledger('product_'.$request->txt_product_id,1,"assets",null, ($cost_price * $diff),2,$refrence_id,$request->txt_date);
        }
            
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'New Stock Has Been Added');
            return redirect()->back();
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StockTaking  $stockTaking
     * @return \Illuminate\Http\Response
     */
    public function show(StockTaking $stockTaking)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StockTaking  $stockTaking
     * @return \Illuminate\Http\Response
     */
    public function edit(StockTaking $stockTaking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StockTaking  $stockTaking
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StockTaking $stockTaking)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StockTaking  $stockTaking
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockTaking $stockTaking)
    {
        //
    }
    public function Addinventory($pid,$biil_id,$sup_id,$cost_price,$qty,$date,$tempexpire){
        $inventory=new Inventory;
        $inventory->inv_product_id=$pid;
        $inventory->inv_bill_id=$biil_id;
        $inventory->inv_supplier_id=$sup_id;
        $inventory->inv_cost_price=$cost_price;
        $inventory->inv_qty=$qty;
        $inventory->inv_purchased_qty=$qty;
        $inventory->expiry_date = $tempexpire;
        $inventory->inv_bill_date=$date;
        $inventory->save();
    }
    public function cost_sale_ledger($total,$refrenceid,$date,$datebit){

       
        
    }

}
