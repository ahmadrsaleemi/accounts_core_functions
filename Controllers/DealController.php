<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Recipe;
use App\Product;
use App\Deal;
use App\Dealdetail;
use App\Dealsellables;
use Auth;
class DealController extends Controller
{
    public function index()
    {
        $deals = Deal::select('deals.*','users.name as user')->leftjoin('users','deals.user_id','=','users.id')->get();
        return view('deals/view' , compact('deals'));
    }

    public function create()
    {
        $products2 = Recipe::select('barcode','name as product_description','expiry_date', 'status');
        
        $products = Product::select('barcode', 'product_description', 'expiry_date', 'status')->leftjoin('inventories','inventories.inv_product_id','=','products.barcode')->where('products.sellable', 1)->groupby('barcode')->unionAll($products2)->get();

        return view('deals.add',compact('products'));
    }

    public function store(Request $request){
        //return $request;
        $deal = new Deal;
        $deal->barcode = $request->barcode;
        $deal->name = $request->name;
        $deal->sale_price = $request->sale_price;
        $deal->user_id = Auth::id();
        if($deal->save()){
           // return count($request->txt_product_id);
            for($i = 0; $i < count($request->txt_product_id); $i++ ){
                $dealdetails = new Dealdetail;
                $dealdetails->deal_id = $request->barcode;
                $dealdetails->product_id = $request->txt_product_id[$i];
                $dealdetails->qty = $request->qty[$i];
                $dealdetails->product_desc = $request->txt_product_name[$i];
                $dealdetails->cost_price = $request->costprice[$i];
                $dealdetails->total = $request->linetotal[$i];
                $dealdetails->save();
            }
            
            
        }

        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'New Deal was successfully added!');
        return redirect('deal/add');    
    }

    public function destroy($id)
    {
        $deal =DB::table('deals')->where('barcode',$id)->delete();
        DB::table('dealdetails')->where('deal_id',$id)->delete();
        return redirect('deal/show');
    }

    public function show($id){
        $deals = Deal::where('id', $id)->first();
        $dealdetails = Dealdetail::where('deal_id', $deals->barcode)->get();
        $products2 = Recipe::select('barcode','name as product_description','expiry_date', 'status');
        
        $products = Product::select('barcode', 'product_description', 'expiry_date', 'status')->leftjoin('inventories','inventories.inv_product_id','=','products.barcode')->where('products.sellable', 1)->groupby('barcode')->unionAll($products2)->get();
        
        return view('deals/edit',compact('deals', 'dealdetails', 'products'));
    }


    public function update(Request $request){

        $update_deal = Deal::where('barcode', $request->barcode)->update([
            'barcode' => $request->barcode,
            'name' => $request->name,
            'sale_price' => $request->sale_price
        ]);
        // return redirect()->back();
        $count_check = 1;
        for ($i=0; $i < count($request->txt_product_id); $i++) { 
            
            if($count_check > $request->counter){
                $dealdetails = new Dealdetail;
                $dealdetails->deal_id = $request->barcode;
                $dealdetails->product_id = $request->txt_product_id[$i];
                $dealdetails->qty = $request->qty[$i];
                $dealdetails->product_desc = $request->txt_product_name[$i];
                $dealdetails->cost_price = $request->costprice[$i];
                $dealdetails->total = $request->linetotal[$i];
                $dealdetails->save();
            }else{
                $update = Dealdetail::where('deal_id', $request->barcode)->where('product_id', $request->txt_product_id[$i])->update([
                    'qty' => $request->qty[$i],
                    'total' => $request->linetotal[$i],
                ]);
            }
            $count_check++;
        }

        // if($update_deal && $update){
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Deal was successfully updated!');
            return redirect()->back();
        // }
    }

    public function barcode_check(Request $request){
        $recipes = Deal::where('barcode', $request->barcode)->first();
         $check = '';
        if($recipes == ''){
            $check =  "Not Exist";
        }else{
            $check =  "Exist";
        }

        echo $check;
    }

    public function data(Request $request){
        $name = Product::where('products.barcode', $request->product_id)->first();
        
        return array($name->product_description,$name->cost_price);
    }

    public function deleterow(Request $request){

        $delete = Dealdetail::where('deal_id', $request->barcode)->where('product_id', $request->product_id)->delete();

    }
}
