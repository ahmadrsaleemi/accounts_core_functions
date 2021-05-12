<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Inventory;
use App\Saleproduct;
use App\Bill_product;
use App\Product;
class InventoryController extends Controller
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

    //index function to show all inventories
    public function index()
    {
           $inventories=Inventory::leftjoin('products','products.barcode','=','inventories.inv_product_id')->leftjoin('recipes', 'recipes.barcode', '=', 'inventories.inv_product_id')->groupBy('inv_product_id')
           ->selectRaw('sum(inv_qty) as sum, inv_product_id,inv_supplier_id,inv_bill_id, inv_cost_price,inv_bill_date,inventories.created_at as cat,inventories.updated_at,products.product_description, recipes.name as recipe_description, recipes.barcode')
           ->orderby('inventories.id','desc')->get();   
         return view('inventory/view',compact('inventories'));
    }

    //returning inventories against single product
    public function single_product_inventory($product_id){
        $productname=Product::where('barcode',$product_id)->first();
        //return $purchaseproductqty;
        $inventories=Inventory::leftjoin('products','products.barcode','=','inventories.inv_product_id')->leftjoin('suppliers','suppliers.supplier_id','=','inventories.inv_supplier_id')->where('inv_product_id',$product_id)->orderby('inventories.id','desc')->get();
        return view('inventory/singleview',compact('inventories','productname'));
        // foreach ($inventories as $Inventory) {



        // }
        
    }

    
}
