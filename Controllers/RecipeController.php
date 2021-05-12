<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use App\Chartofaccount;
use App\Subcategory;
use App\Category;
use App\Product;
use App\Recipe;
use App\Recipeproduct;
use App\User;
use App\Inventory;
class RecipeController extends Ledgerfunctions
{
    Public function __construct(){
        $this->middleware('auth');
    }

    public function index()
    {
        $recipies=Recipe::select('recipes.*','categories.name as category')->leftjoin('categories','categories.id','=','recipes.category')->get();
        $recipeProducts = RecipeProduct::leftjoin('products','products.product_id','=','recipeproducts.product')->get();
        return view('recipe/view' , compact('recipies','recipeProducts'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $products = Product::all();
        return view('recipe.add',compact('categories','products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check_duplicate=Recipe::where('name',$request->name)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Recipe Already Available Please Try Changed Category Name');
            return redirect('recipe/add');
        }
        else{
        $recipe=new Recipe;
        $recipe->barcode=$request->barcode;
        $recipe->name=$request->name;
        $recipe->category=$request->category;
        $recipe->subcategory=$request->subcategory;
        $recipe->sale_price=$request->sale_price;
        $recipe->cost_price=$request->cost_price;
        $this->product_chart_of_account('recipe_'.$request->barcode,'recipe_'.$request->name,'recipe_'.$request->name.'_account');
        if ($recipe->save()) {
            // $recipe = Recipe::orderby('id','desc')->first()->id;
            // for($i=0;$i < count($request->product);$i++)
            // {
            //     $recipeProduct =new Recipeproduct;
            //     $recipeProduct->recipe = $recipe;
            //     $recipeProduct->product = $request->product[$i];
            //     $recipeProduct->save();
            // }
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New recipe was successfully added!');
        }
        return redirect('recipe/add');
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
    public function show($recipe)
    {
        $categories = Category::all();
        $products = Product::all();
        $recipies=Recipe::select('recipes.*','subcategories.name as subcategoryname','categories.name as category','categories.id as category_id')->leftjoin('categories','categories.id','=','recipes.category')->leftjoin('subcategories','subcategories.id','=','recipes.subcategory')->where('recipes.id',$recipe)->get();
        $recipeProducts = RecipeProduct::leftjoin('products','products.barcode','=','recipeproducts.product')->where('recipeproducts.recipe',$recipe)->get();
        return view('recipe/edit',compact('categories','products','recipies','recipeProducts'));
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
         $update=DB::table('recipes')
            ->where('id', $request->rec_id)
            ->update(['name'=>$request->name,'category'=>$request->category,'subcategory'=>$request->subcategory,'sale_price'=>$request->sale_price,  'barcode'=>$request->barcode , 'cost_price'=>$request->cost_price]);
            // if(isset($request->product)){
            //   if(DB::table('recipeproducts')->where('recipe',$request->rec_id)->delete()){
            //     for($i=0;$i < count($request->product);$i++)
            //     {
                    
            //             $recipeProduct =new Recipeproduct;
            //             $recipeProduct->recipe = $request->rec_id;
            //             $recipeProduct->product = $request->product[$i];
            //             $recipeProduct->save();
            //         }
                   
            //     }
            // }
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Sub Category  successfully Updated!');
        }
        return redirect('recipe/show/'.$request->txt_rec_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($recipe)
    {
        DB::table('recipes')->where('id',$recipe)->delete();
        DB::table('recipeproducts')->where('id',$recipe)->delete();
        
        return redirect('recipe/show');
    }

    public function barcode_check(Request $request){
        $recipes = Recipe::where('barcode', $request->barcode)->first();
         $check = '';
        if($recipes == ''){
            $check =  "Not Exist";
        }else{
            $check =  "Exist";
        }

        echo $check;
    }

    public function additems(Request $request){
         $recipies=Recipe::select('recipes.*','categories.name as category')->leftjoin('categories','categories.id','=','recipes.category')->get();

         return view('recipe/addrecipe' , compact('recipies'));
    }

    public function recipe_detail(Request $request){
        $recipes_detail = Recipe::where('recipes.barcode', $request->recipe)->first();
        return $recipes_detail->name;
    }

    public function item_store(Request $request){
        $date = date('d/m/Y', time());
        $refrence_id=0;
        $test = env('DB_DATABASE');
        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='recipes'");
        
        foreach ($bill_id as $id) {
            $refrence_id = $id->AUTO_INCREMENT;
        } 
           $arr_productid = $request->recipe;
           $arr_productid[]=$request->recipe1;
           $arr_productprice = $request->cost_price;
           $arr_productprice[] =$request->cost_price1;
           $arr_productqty = $request->qty;
           $arr_productqty[] = $request->qty1;
           $arr_amount= $request->linetotal;
           $arr_amount[] = $request->linetotal1;
           for ($i = 0; $i < count($arr_productid); $i++){
                $this->Addinventory($arr_productid[$i],01,Auth::User()->name,$arr_productprice[$i],$arr_productqty[$i],$date);
                $this->cost_sale_ledger($arr_productprice[$i] * $arr_productqty[$i],$refrence_id,$date,1);
                $this->assetledger('recipe_'.$arr_productid[$i],1,"assets",$arr_productprice[$i] * $arr_productqty[$i],null,2,$refrence_id,$date);
           }
        

        return redirect()->back();

                    
    }

      public function print(Request $request){

        $test = env('DB_DATABASE');

        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='recipes'");
        $sale_id = 0;
        foreach ($bill_id as $id) {
            $sale_id = $id->AUTO_INCREMENT;
        }      

       $refrence_id=$sale_id;

       $tempproid=0;
       $fin_price=0;
       $tempprice=0;
       $tempqty=0;
       $tempboxes=0;
       $tempamount=0;
       $tempexpire=0;
       $arr_productid = $request->recipe;
       $arr_productid[]=$request->recipe1;
       $arr_productprice = $request->cost_price;
       $arr_productprice[] =$request->cost_price1;
       $arr_productqty = $request->qty;
       $arr_productqty[] = $request->qty1;
       $arr_amount= $request->linetotal;
       $arr_amount[] = $request->linetotal1;
       $arr_product_desc = $request->recipe_name;
       $arr_product_desc[]=$request->recipe_name1;
       $arr_sticker= $request->sticker;
       $arr_sticker[] = $request->sticker1;
       $arr_expire  =   $request->expiry_date;
       $arr_expire[]  =   $request->expiry_date1;
       $arr_flag = array();
       //checking if fileds are not empty to avoid store null data

       if($arr_productid !="" && $arr_productprice !="" && $arr_productqty !=""){
        //creating flag element in array
       for ($i = 0; $i < count($arr_productid); $i++){
            $arr_flag[$i] = 0;
       }
       //looping through product arrays to sum up qty and price of same product_id 
       for ($i = 0; $i < count($arr_productid); $i++){
            //temp id storing first itrations product id
            $tempid = $arr_productid[$i];
            //temp id storing first itrations product price
            $tempprice = $arr_productprice[$i];

            $tempexpire = $arr_expire[$i];
            //loop again to track same product id
            for($j = 0; $j < count($arr_productid); $j++){
                //checking if product id field isn't empty
                if($arr_productid[$j] !=""){
                    //checking if first itrations product id is equal to second itrations product id and flag is not equal to 1
                    if($arr_productid[$j] == $tempid   && $arr_flag[$j] != 1){

                        $tempproid=$arr_productid[$i];
                        //summing up qty of same products
                        $tempqty += $arr_productqty[$j];
                        // expiry
                        
                        //summing up inline ammount of same products
                        $tempamount += $arr_amount[$j];
                        //getting price of each product
                        $fin_price = $tempamount/$tempqty;
                        //assigning flag =1
                        $arr_flag[$j] = 1;
                    }
                }
            }
            //check if product qty is not equal to 0
            if($tempqty != 0){
            // echo "product_id = ".$tempproid."&nbsp quantity = ".$tempqty."&nbsp tempamount = ".$tempamount."&nbsp final_price = ".$fin_price."<br>";
            
            
             $tempboxes=0;
             $tempqty = 0;
             $tempamount =0;
             $fin_price=0;
             }
            }
        }
           $count=count($arr_productid);
           $items_data = '';
           $total_qty = 0;
           $json = '';
           $product_desc = 0;
           $expirydate = 0;
           for($i = 0; $i < count($arr_productid); $i++){
             // $product = Product::where('product_id', $arr_productid[$i])->first();
             // return count($request->txt_product);
             // if($request->arr_product_desc[$i] == ''){
                $product_name = $arr_productid[$i];
             // }else{
               $product_desc = $arr_product_desc[$i]; 
             // }
               $expirydate = $arr_expire[$i];


            if($arr_sticker[$i] != ''){
              $total_qty += $arr_sticker[$i]; 
                for ($j=0; $j < $arr_sticker[$i]; $j++) { 
                    if($j != $arr_sticker[$i] - 1){
                    $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"},';

                    
                }else{
                    if($i != count($arr_sticker) -1){
                     $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"},';
                    }else{
                       $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"}'; 
                    }
                    
                }
                }
             }else{
                    if($i != count($arr_productid) - 1){
                        $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"},';
                        
                    }else{
                        $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"}';
                        
                    }
            }
        };

        $withname = 0;
        $withrate = 0;
        $withdate = 0;
        $preview = 0;


        if($request->withdate != ''){
            $withdate = $request->withdate;
        }
        if($request->withname != ''){
            $withname = $request->withname;
        }
        if($request->withrate != ''){
            $withrate = $request->withrate;
        }
        if($request->print_opt != ''){
            $preview = $request->print_opt;
        }   

            $items_data = rtrim($items_data, ',');

             $json = '{"TemplateID":2,"ProductCount":"'.$total_qty.'","WithName":"'.$withname.'","WithDate":"'.$withdate.'","WithRate":"'.$withrate.'","Invoice":"'.$refrence_id.'","Date":"'.$request->txt_date.'","Name":"In House","Preview":"'.$preview.'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","total":"'.$request->total.'"}';
        
        return $json;
    
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
    public function cost_sale_ledger($total,$refrenceid,$date,$datebit){

        $this->expensledger(10102,5,"expense",null,$total,2,$refrenceid,$date);
        
    }
}
