<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Chartofaccount;
use App\Recipe;
use App\Recipeproduct;
use App\Inventory;
class ProductController extends Controller
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
        $query=DB::raw('SELECT products.*,users.name, inventories.inv_qty as qty From products LEFT JOIN users ON products.user_id = users.id LEFT JOIN inventories ON products.product_id = inventories.inv_product_id');
        $products=DB::select($query);
        return view('product/view' , compact('products'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $test = env('DB_DATABASE');
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='products'");
        $products=DB::table('products')->orderBy('product_id', 'desc')->first();
        return view('product.add',compact('products','auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
         
        $check_duplicate=Product::where('product_id',$request->txt_product_id)->first();
        if($check_duplicate !== null ){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Product  Already Available Please Try Changed Product Id');
            return redirect('product/add');
        }
        else{
        $product=new Product;
        $product->product_id=$request->txt_product_id;
        $product->product_description=$request->txt_product_description;
        $product->stock_alert=$request->txt_stock_alert;
        $product->qty=$request->qty;
        $product->sale_price=$request->sale_price;
        $product->cost_price=$request->cost_price;
        $product->discount_from=$request->discount_from;
        $product->discount_to=$request->discount_to;
        $product->discount_percent=$request->discount_percent;
        $product->status=0;
        if($request->barcode == ''){
                $product->barcode = $request->txt_product_id;
            }else{
                $product->barcode = $request->barcode;
            }
        if(isset($request->sellable))
        $product->sellable=true;
        else
        $product->sellable=false;
        // if($request->sale_price != ""){
        //     $recipe=new Recipe;
        //     $recipe->name=$request->txt_product_description;
        //     $recipe->product_id = $request->barcode;
        //     $recipe->category=1;
        //     $recipe->subcategory=1;
        //     $recipe->sale_price=$request->sale_price;
        //     if ($recipe->save()) {
        //         $recipe = Recipe::orderby('id','desc')->first()->id;
        //         $recipeProduct =new Recipeproduct;
        //         $recipeProduct->recipe = $recipe;
        //         $recipeProduct->product = $request->barcode;
        //         $recipeProduct->save();
        //     }
            
        // }

        $product->user_id=Auth::id();
        if ($product->save()) {
            
            $this->product_chart_of_account('product_'.$request->barcode,'product_'.$request->txt_product_description,'product_'.$request->txt_product_description.'_account');
             $this->Addinventory($request->barcode, $request->barcode, 0, $request->cost_price,0,date('d/m/Y'));
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Product was successfully added!');
        }
        return redirect('product/add');
        }
    }

    public function product_chart_of_account($account_id,$account_title,$account_description){
        $chartofaccount=new Chartofaccount();
        $chartofaccount->coa_id=$account_id;
        $chartofaccount->coa_title=$account_title;
        $chartofaccount->account_type=1;
        $chartofaccount->coa_description=$account_description;
        $chartofaccount->user_id=Auth::id();
        if($chartofaccount->save()){
            app('App\Http\Controllers\ChartofaccountController')->log_chartofaccount($account_id,$account_title,$account_description,1);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($product_id)
    {   
        $products =DB::table('products')->where('product_id',$product_id)->get();
        $sellable = '';
        $recipes = '';
        foreach ($products as $value) {
            $sellable = $value->sellable ;
        }
        if($sellable == 1){
            $recipes = Recipe::where('product_id', $product_id)->orderBy('created_at', 'DESC')->first();
        }

        // return $recipes;
        return view('product/edit',compact('products', 'recipes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request,
            ['txt_product_id'=>'required',
            'txt_product_description'=>'required',
            'txt_stock_alert'=>'required'
        ]);
        $sellable = false;
        if(isset($request->sellable)){
           $sellable=true;
       }
       // if($request->sale_price != ""){
       //      $recipe=new Recipe;
       //      $recipe->name=$request->txt_product_description;
       //      $recipe->product_id = $request->txt_product_id;
       //      $recipe->category=1;
       //      $recipe->subcategory=1;
       //      $recipe->sale_price=$request->sale_price;
       //      if ($recipe->save()) {
       //          $recipe = Recipe::orderby('id','desc')->first()->id;
       //          $recipeProduct =new Recipeproduct;
       //          $recipeProduct->recipe = $recipe;
       //          $recipeProduct->product = $request->txt_product_id;
       //          $recipeProduct->save();
       //      }
            
       //  }

         $update=DB::table('products')
            ->where('id', $request->txt_rec_id)
            ->update(['product_id'=>$request->txt_product_id,'product_description' => $request->txt_product_description,'stock_alert'=>$request->txt_stock_alert,'sale_price'=>$request->sale_price,'qty'=>$request->qty,'sellable'=>$sellable,'discount_from'=>$request->discount_from,'discount_to'=>$request->discount_to,'discount_percent'=>$request->discount_percent,'status' => $request->status,'barcode' => $request->barcode, 'cost_price' => $request->cost_price]);
            $inv_update = Inventory::where('inv_product_id', $request->bar_val)->update(['inv_product_id'=> $request->barcode]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Product Details successfully Updated!');
        }
        return redirect('product/show/'.$request->txt_product_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($product)
    {
        DB::table('products')->where('barcode',$product)->delete();
        // return $product;
        $inve_delete = Inventory::where('inv_product_id', $product)->delete();
        if($inve_delete){
            return redirect('product/show');
        }
    }

    public function allproducts(){
        $products=Product::all();
        foreach ($products as $product) {
            echo "<option value=".$product->barcode.">".$product->product_description."</option>";
        }
        echo '<option value="add_product" class="btn btn-info"></option>';
    }

    public function id_check(Request $request){
        $products = Product::where('product_id', $request->id)->first();
        $check = '';
        if($products == ''){
            $check =  "Not Exist";
        }else{
            $check =  "Exist";
        }

        echo $check;
    }

    public function barcode_check(Request $request){
        $products = Product::where('barcode', $request->barcode)->first();
         $check = '';
        if($products == ''){
            $check =  "Not Exist";
        }else{
            $check =  "Exist";
        }

        echo $check;
    }
    public function Addinventory($pid,$biil_id,$sup_id,$cost_price,$qty,$date){
        $inventory=new Inventory;
        $inventory->inv_product_id=$pid;
        $inventory->inv_bill_id=$biil_id;
        $inventory->inv_supplier_id=$sup_id;
        $inventory->inv_cost_price=$cost_price;
        $inventory->inv_qty=$qty;
        $inventory->inv_purchased_qty=$qty;
        $inventory->inv_bill_date=$date;
        $inventory->save();
    }
    public function barcodes(){
        $test = env('DB_DATABASE');
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='products'");
        $id  = 0;
        foreach ($auto_increment as $ai) {
            $id = $ai->AUTO_INCREMENT;
        }

        echo json_encode($id);
        
    }
}
