<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Http\Request;
use App\Customer;
use App\Product;
use App\Saleproduct;
use DB;
use App\Ledger;
use Auth;
use Session;
use App\Cashrecieved;
use App\Cashrecievedproduct;
use App\Fiscalyear;
use App\Chartofaccount;
use App\Recipe;
use App\Inventory;
use App\Coupon; 
use App\Table;
use App\Employee;
use App\Port;
use App\Deal;
use App\Dealdetail;
class SaleController extends Ledgerfunctions
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
    //index function to show all sales
    public function index()
    {   
       $query=DB::raw('SELECT sales.*,customers.customer_name,users.name From sales LEFT JOIN customers ON sales.customer=customers.customer_id LEFT JOIN users ON sales.user_id = users.id where void=false');
         $sales=DB::select($query);
      return view('sale/view' , compact('sales'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // $port = 8811;
        // $port_number = Port::where('user_id', Auth::user()->id)->first();
        // if($port_number != Null){
        //     $port = $port_number->port;

        // }

        $test = env('DB_DATABASE');
        $fy=Fiscalyear::orderby('id','desc')->first();
        if($fy != null){
        $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
        }
        else{
            $fy='2017,12,5';
        }
        //all customers from customers table
        $customers=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->get();
        $walkincus=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->first();
        //chart of accounts from cprelation table
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        //Auto increment to add new invoice with new id
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='sales'");
        //all products from products table
        // $products=Product::select('barcode','product_description', 'expiry_date')->leftjoin('inventories','inventories.inv_product_id','=','products.barcode')->groupby('barcode')->get();
        // $products = array();
        $recipes = Recipe::select('barcode','name as product_description','expiry_date', 'status');
        $deals = Deal::select('barcode', 'name as product_description', 'expiry_date', 'status');

        // $products =Inventory::select('barcode', 'product_description', 'expiry_date')->leftjoin('products','products.barcode','=','inventories.inv_product_id')->groupby('products.barcode')->unionAll($recipes)->get();
        $products = Product::select('barcode', 'product_description', 'expiry_date', 'status')->leftjoin('inventories','inventories.inv_product_id','=','products.barcode')->where('products.sellable', 1)->groupby('barcode')->unionAll($recipes)->unionAll($deals)->get();
        // return $products;
        // $products1=Inventory::leftjoin('products','products.barcode','=','inventories.inv_product_id')->groupby('inv_product_id')->get();
        // foreach ($products1 as $p) {
            
        //     array_push($products, $p->barcode);
        //     array_push($products, $p->product_description);

        // }
        // $products2 = Recipe::select('barcode','name as product_description')->get();
        // foreach ($products2 as $p) {
            
        //     array_push($products, $p->barcode);
        //     array_push($products, $p->product_description);
        // }
        // return $products; 
        $coupons = Coupon::all();
        $test = env('DB_DATABASE');
        $sellableProducts = Product::all();
        $auto_increment_customer=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='customers'");
        return view('sale.add',compact('customers','products','auto_increment','chartofaccounts','walkincus','fy','coupons','sellableProducts','auto_increment_customer'));
    }

    public function getTables(Request $request){
        return Table::all();
    }

    public function getEmployees(Request $request){
        return Employee::where('type',2)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        // return $request->cash_paid;

       //here validating empty fields
        $transectiontype=2;

        $refrence_id=0;
        $test = env('DB_DATABASE');

        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='sales'");
        
        foreach ($bill_id as $id) {
            $refrence_id = $id->AUTO_INCREMENT;
        } 
         //storing data in databas table sales
        $sale=new Sale;
        $sale->customer=$request->txt_customer_id;
        $sale->date=$request->txt_date;
        $sale->subtotal=$request->total; 
        $sale->discount=$request->discount_amount;
        $sale->cashpaid=$request->cash_paid;
        $sale->duebalance=$request->total_amount;
        $sale->coa=$request->coa;
        $sale->salestax=$request->salestax;
        $sale->salestype=$request->saletype;
        // $sale->re_wh = $request->re_wh;

        if($request->saletype == "Pre Order")
            $sale->pre_order_date=$request->pre_order_date;
        
        else
            $sale->pre_order_date=Null;

        if($request->saletype != "Counter Sale")
        $sale->status=false;
        else
        $sale->status=true;

        if(isset($request->table))
            $sale->table=$request->table;
        if(isset($request->employee))
            $sale->employee=$request->employee;


        $sale->void=false;
        $sale->user_id=Auth::id();
        //checking if it was a credit sale or not
        if(isset($request->credit_sale_checkbox) && $request->credit_sale_checkbox == 1){
            $sale->credit_sales=true;
        }
        else{
        $sale->credit_sales=false;
        }
        //checking if sales data saved successfully and proceed
        
        //$reference id variable to refrence ledger entries

           $finalarray = array();
           $tempprice=0;
           $tempqty=0;
           $tempresult=0;
           $tempamount=0;
           $tempbox=0;
           $arr_productid = $request->txt_product;
           $arr_productid[]=$request->txt_product_id1;
           $arr_productprice = $request->price;
           $arr_productprice[] =$request->price1;
           $arr_productqty = $request->qty;
           $arr_productqty[] = $request->qty1;
           $arr_productbox = $request->box;
           $arr_productbox[] = $request->box1;
           $arr_inlintotal=$request->linetotal;
           $arr_inlintotal[] = $request->linetotal1;

           $count=count($arr_productid);
           // return $count;
           $customers =Customer::where('customer_id',$request->txt_customer_id)->first();
           $product_name = '';

           $items_data = '';
           $total_qty = 0;
           for($i = 0; $i < count($request->txt_product); $i++){
             
            
             // $product = Product::where('product_id', $arr_productid[$i])->first();
             // return count($request->txt_product);
             if($request->product_description[$i] == ''){
                $product_name = $arr_productid[$i];
             }else{
               $product_name = $request->product_description[$i]; 
             }

            // if($request->sticker[$i] != ''){
            //     for ($j=0; $j < $request->sticker[$i]; $j++) { 
            //         $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"},';
            //         $total_qty += $arr_productqty[$i];
            //     }
            //  }else{
             if( $i != count($request->txt_product) - 1){
                    $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"},';
                    $total_qty += $arr_productqty[$i]; 
                }else{
                    $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"}';
                    $total_qty += $arr_productqty[$i]; 

                }
            // }
            // return $items_data;
        };

        // $withname = 0;
        // $withrate = 0;
        // $withdate = 0;

        // if($request->withdate != ''){
        //     $withdate = $request->withdate;
        // }
        // if($request->withname != ''){
        //     $withname = $request->withname;
        // }
        // if($request->withrate != ''){
        //     $withrate = $request->withrate;
        // }



           $json = '{"TemplateID":1,"ProductCount":"'.$count.'","Invoice":"'.$refrence_id.'","Date":"'.$request->txt_date.'","Name":"'.$customers->customer_name.'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","subtotal":"'.$request->total.'","discount":"'.$request->discount_amount.'","total":"'.$request->subtotal.'"}';
           // return $items_data;
            // $url = 'http://localhost:8811';
            // $ch = curl_init( $url );
            // curl_setopt( $ch, CURLOPT_POST, 1);
            // curl_setopt( $ch, CURLOPT_POSTFIELDS, $json);
            // curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
            // curl_setopt( $ch, CURLOPT_HEADER, 0);
            // curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
            // $response = curl_exec( $ch );

           echo $json;
           if($sale->save())
        {

           $arr_flag = array();

           //creating flag element in array
           for ($i = 0; $i < count($arr_productid); $i++){
                $arr_flag[$i] = 0;
           }

           $_costofsale = 0;
            for ($i = 0; $i < count($request->txt_product); $i++){

                $saleproducts=new Saleproduct;
                $saleproducts->inv_id=$refrence_id;
                $saleproducts->product=$request->txt_product[$i];
                $saleproducts->sale_price=$arr_productprice[$i];
                $saleproducts->qty=$arr_productqty[$i];
                $saleproducts->inlinetotal=$arr_inlintotal[$i];
                $saleproducts->save();
                //temp id storing first itrations product id
                $tempid = $arr_productid[$i];
                //loop again to track same product id
                $count=0;
                for($j = 0; $j < count($arr_productid); $j++){
                //checking if fileds are not empty to avoid store null data
                if($arr_productid[$j] != ""){
                    //checking if first itrations product id is equal to second itrations product id and flag is not equal to 1
                if($arr_productid[$j] == $tempid && $arr_flag[$j] != 1){
                    //summing up qty of same products
                    $tempqty += $arr_productqty[$j];
                    //summing up inline ammount of same products
                    $tempamount +=$arr_inlintotal[$i];
                     //getting price of each product
                    $tempprice += $arr_productprice[$j];
                    $arr_flag[$j] = 1;
                    $count ++;
                }
                }
            }
            $recipe_sale_price = 0;
            
            if ($tempqty != 0){
                //checking if fileds are not empty to avoid store null data
                if($arr_productid[$i] !="" && $tempprice !="" && $tempqty !=""){
                    $num_length = strlen($arr_productid[$i]);
                    // if($num_length != 6){
                        $inv_check = Inventory::where('inv_product_id', $arr_productid[$i])->first();
                        if($inv_check != ''){

                           $this->update_inventory($arr_productid[$i],$tempqty,$refrence_id,$request->total,$_costofsale,$request->txt_date,$request->date_bit);
                        }else if($num_length == 4){
                            // $recipe_sale_price = Recipe::where('barcode', $arr_productid[$i])->first();
                            // $_costofsale = $recipe_sale_price->sale_price;
                            $this->update_inventory($arr_productid[$i],$tempqty,$refrence_id,$request->total,$_costofsale,$request->txt_date,$request->date_bit);
                        }else if($num_length == 3){
                            $products = Dealdetail::where('deal_id', $arr_productid[$i])->get();
                            foreach ($products as $product) {
                                $inv_check = Inventory::where('inv_product_id', $product->product_id)->first();
                                if($inv_check != ''){

                                    $this->update_inventory($product->product_id,$product->qty,$refrence_id,$request->total,$_costofsale,$request->txt_date,$request->date_bit);
                                }

                            }
                        }
                        else{
                            $product_cost_price = Product::where('barcode', $arr_productid[$i])->first();
                            $_costofsale += $product_cost_price->cost_price * $tempqty;
                            $this->assetledger('product_'.$arr_productid[$i],1,"assets",null,$_costofsale,2,$refrence_id,$request->txt_date);
                        }
                        // }
                    $tempqty = 0;
                    $tempprice = 0;
                    $tempamount=0;
                    $tempbox=0;
                }
            }
       }

        if($request->saletype == "Counter Sale"){
             if($request->cash_paid > $request->subtotal){
                $this->cashpaidledger($request->coa,$request->total,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                // $this->sale_ledger($request->total,$refrence_id,$request->txt_date,$request->date_bit);
                $this->cost_sale_ledger($_costofsale,$refrence_id,$request->txt_date,$request->date_bit);
            }
            // else if($request->cash_paid < $request->subtotal){
                
            //     $this->credit_sale_ladger('customer_'.$request->txt_customer_id,$left,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
            // }
            else{
            if(isset($request->credit_sale_checkbox) && $request->credit_sale_checkbox == 1){
                // $left = $request->subtotal - $request->cashpaid;
                

                // $this->cashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);

                if($request->total_amount > 0){
                    if($_costofsale != 0){
                        $this->cost_sale_ledger($_costofsale,$refrence_id,$request->txt_date,$request->date_bit);

                    }
                    
                    $this->credit_sale_ladger('customer_'.$request->txt_customer_id,$request->total_amount,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);

                    $this->cashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);


                    if($request->discount_amount != 0){
                        $this->expensledger(24,5,"expense",$request->discount_amount,null,2,$refrence_id,$request->txt_date);
                    }
                    if($request->discount != 0){
                        $discount = $request->total - $request->cash_paid;
                        $this->expensledger(24,5,"expense",$discount,null,2,$refrence_id,$request->txt_date);
                    }
                }else{
                   if($_costofsale != 0){
                        $this->cost_sale_ledger($_costofsale,$refrence_id,$request->txt_date,$request->date_bit);

                    }
                    $this->cashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                    if($request->discount_amount != 0){
                        $this->expensledger(24,5,"expense",$request->discount_amount,null,2,$refrence_id,$request->txt_date);
                    }
                    if($request->discount != 0){
                        $discount = $request->total - $request->cash_paid;
                        $this->expensledger(24,5,"expense",$discount,null,2,$refrence_id,$request->txt_date);
                    }
                }
            } 

            
            }
        }  else{

            if($_costofsale != 0){
                        $this->cost_sale_ledger($_costofsale,$refrence_id,$request->txt_date,$request->date_bit);

                    }
                    
                    $this->credit_sale_ladger('customer_'.$request->txt_customer_id,$request->total_amount,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                     $this->cashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
        }
            
            
            

             $this->sale_ledger($request->total,$refrence_id,$request->txt_date,$request->date_bit);
             $request->session()->flash('message.level', 'success');
             $request->session()->flash('message.content', 'New Sale Has Been Added');
             // return $json;
             
        
        }
       if(!isset($request->ajax)){
           // return redirect('sale/add');
       }
       else{
        $data="";
        $count = 1;
        $a= Sale::select('sales.id','sales.date','sales.date','sales.subtotal','sales.cashpaid','sales.duebalance','customers.customer_name')->leftjoin('customers','customers.customer_id','=','sales.customer')->where('sales.id',$refrence_id)->first();
            
        $data='[{"details":{"id":'.$a->id.',"subtotal":'.$a->subtotal.',"cashpaid":'.$a->subtotal.',"duebalance":'.$a->subtotal.',"customer_name":"'.$a->customer_name.'"},"products":[';
        

        $products =Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','recipes.name')->leftjoin('recipes','recipes.id','=','saleproducts.product')->where('inv_id',$request->txt_inv_no)->get();
        
        $productcount=Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','saleproducts.description')->where('inv_id',$request->txt_inv_no)->count();
        foreach ($products as $product) {
            # code...
            $data .='{"sale_price":'.$product->sale_price.',"qty":'.$product->qty.',"inlinetotal":'.$product->inlinetotal.',"description":"'.$product->name.'"}';
            if($count < $productcount){
                $data .=",";
            }
            $count++;
        }
            
        $data .="]}]";
       echo $data;
            //echo $data;   
       }
        
    
          
    }
    public function print(Request $request){
         $transectiontype=2;

        $refrence_id=0;
        $test = env('DB_DATABASE');

        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='sales'");
        
        foreach ($bill_id as $id) {
            $refrence_id = $id->AUTO_INCREMENT;
        } 
         //storing data in databas table sales
        $sale=1;
        // $sale->customer=$request->txt_customer_id;
        // $sale->date=$request->txt_date;
        // $sale->subtotal=$request->total; 
        // $sale->discount=$request->discount_amount;
        // $sale->cashpaid=$request->cash_paid;
        // $sale->duebalance=$request->total_amount;
        // $sale->coa=$request->coa;
        // $sale->salestax=$request->salestax;
        // $sale->salestype=$request->saletype;
        // $sale->re_wh = $request->re_wh;

        // if($request->saletype == "Pre Order")
        //     $sale->pre_order_date=$request->pre_order_date;
        
        // else
        //     $sale->pre_order_date=Null;

        // if($request->saletype != "Counter Sale")
        // $sale->status=false;
        // else
        // $sale->status=true;

        // if(isset($request->table))
        //     $sale->table=$request->table;
        // if(isset($request->employee))
        //     $sale->employee=$request->employee;


        // $sale->void=false;
        // $sale->user_id=Auth::id();
        // //checking if it was a credit sale or not
        // if(isset($request->credit_sale_checkbox) && $request->credit_sale_checkbox == 1){
        //     $sale->credit_sales=true;
        // }
        // else{
        // $sale->credit_sales=false;
        // }
        //checking if sales data saved successfully and proceed
        
        //$reference id variable to refrence ledger entries

           $finalarray = array();
           $tempprice=0;
           $tempqty=0;
           $tempresult=0;
           $tempamount=0;
           $tempbox=0;
           $arr_productid = $request->txt_product;
           $arr_productid[]=$request->txt_product_id1;
           $arr_productprice = $request->price;
           $arr_productprice[] =$request->price1;
           $arr_productqty = $request->qty;
           $arr_productqty[] = $request->qty1;
           $arr_productbox = $request->box;
           $arr_productbox[] = $request->box1;
           $arr_inlintotal=$request->linetotal;
           $arr_inlintotal[] = $request->linetotal1;

           $count=count($arr_productid);
           // return $count;
           $customers =Customer::where('customer_id',$request->txt_customer_id)->first();
           $product_name = '';

           $items_data = '';
           $total_qty = 0;
           for($i = 0; $i < count($request->txt_product); $i++){
             
            
             // $product = Product::where('product_id', $arr_productid[$i])->first();
             // return count($request->txt_product);
             if($request->product_description[$i] == ''){
                $product_name = $arr_productid[$i];
             }else{
               $product_name = $request->product_description[$i]; 
             }

            // if($request->sticker[$i] != ''){
            //     for ($j=0; $j < $request->sticker[$i]; $j++) { 
            //         $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"},';
            //         $total_qty += $arr_productqty[$i];
            //     }
            //  }else{
             if( $i != count($request->txt_product) - 1){
                    $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"},';
                    $total_qty += $arr_productqty[$i]; 
                }else{
                    $items_data.='{"Description":"'.$product_name.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"}';
                    $total_qty += $arr_productqty[$i]; 

                }
            // }
            // return $items_data;
        };

        // $withname = 0;
        // $withrate = 0;
        // $withdate = 0;

        // if($request->withdate != ''){
        //     $withdate = $request->withdate;
        // }
        // if($request->withname != ''){
        //     $withname = $request->withname;
        // }
        // if($request->withrate != ''){
        //     $withrate = $request->withrate;
        // }



           $json = '{"TemplateID":1,"ProductCount":"'.$count.'","Invoice":"'.$refrence_id.'","Date":"'.$request->txt_date.'","Name":"'.$customers->customer_name.'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","subtotal":"'.$request->total.'","discount":"'.$request->discount_amount.'","total":"'.$request->subtotal.'"}';
           // return $items_data;
            // $url = 'http://localhost:8811';
            // $ch = curl_init( $url );
            // curl_setopt( $ch, CURLOPT_POST, 1);
            // curl_setopt( $ch, CURLOPT_POSTFIELDS, $json);
            // curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
            // curl_setopt( $ch, CURLOPT_HEADER, 0);
            // curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
            // $response = curl_exec( $ch );


           if($sale == 1)
        {

           $arr_flag = array();

           //creating flag element in array
           for ($i = 0; $i < count($arr_productid); $i++){
                $arr_flag[$i] = 0;
           }

           $_costofsale = 0;
            for ($i = 0; $i < count($request->txt_product); $i++){

                // $saleproducts=new Saleproduct;
                // $saleproducts->inv_id=$refrence_id;
                // $saleproducts->product=$request->txt_product[$i];
                // $saleproducts->sale_price=$arr_productprice[$i];
                // $saleproducts->qty=$arr_productqty[$i];
                // $saleproducts->inlinetotal=$arr_inlintotal[$i];
                // $saleproducts->save();
                //temp id storing first itrations product id
                $tempid = $arr_productid[$i];
                //loop again to track same product id
                $count=0;
                for($j = 0; $j < count($arr_productid); $j++){
                //checking if fileds are not empty to avoid store null data
                if($arr_productid[$j] != ""){
                    //checking if first itrations product id is equal to second itrations product id and flag is not equal to 1
                if($arr_productid[$j] == $tempid && $arr_flag[$j] != 1){
                    //summing up qty of same products
                    $tempqty += $arr_productqty[$j];
                    //summing up inline ammount of same products
                    $tempamount +=$arr_inlintotal[$i];
                     //getting price of each product
                    $tempprice += $arr_productprice[$j];
                    $arr_flag[$j] = 1;
                    $count ++;
                }
                }
            }
            
            if ($tempqty != 0){
                //checking if fileds are not empty to avoid store null data
                if($arr_productid[$i] !="" && $tempprice !="" && $tempqty !=""){
                    // $num_length = strlen((string)$arr_productid[$i]);
                    // if($num_length != 6){
                     
                        // }
                    $tempqty = 0;
                    $tempprice = 0;
                    $tempamount=0;
                    $tempbox=0;
                }
            }
       }

             $request->session()->flash('message.level', 'success');
             $request->session()->flash('message.content', 'New Sale Has Been Added');
             return $json;
        
        }
       if(!isset($request->ajax)){
           // return redirect('sale/add');
       }
       else{
        $data="";
        $count = 1;
        $a= Sale::select('sales.id','sales.date','sales.date','sales.subtotal','sales.cashpaid','sales.duebalance','customers.customer_name')->leftjoin('customers','customers.customer_id','=','sales.customer')->where('sales.id',$refrence_id)->first();
            
        $data='[{"details":{"id":'.$a->id.',"subtotal":'.$a->subtotal.',"cashpaid":'.$a->subtotal.',"duebalance":'.$a->subtotal.',"customer_name":"'.$a->customer_name.'"},"products":[';
        

        $products =Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','recipes.name')->leftjoin('recipes','recipes.id','=','saleproducts.product')->where('inv_id',$request->txt_inv_no)->get();
        
        $productcount=Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','saleproducts.description')->where('inv_id',$request->txt_inv_no)->count();
        foreach ($products as $product) {
            # code...
            $data .='{"sale_price":'.$product->sale_price.',"qty":'.$product->qty.',"inlinetotal":'.$product->inlinetotal.',"description":"'.$product->name.'"}';
            if($count < $productcount){
                $data .=",";
            }
            $count++;
        }
            
        $data .="]}]";
       echo $data;
            //echo $data;   
       }
        
    }
    public function update_inventory($product_id,$qty,$refrence_id,$total, &$_costofsale,$date,$datebit){

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
            $_costofsale += $dbcp * $input_qty;
            // return $dbcp;
        //   //echo $fin;
        // //storing inventory ledger by calling inventoryledger function
            if(strlen($product_id) <= 4){
                 $this->assetledger("recipe_".$product_id,1,"assets",null,$fin,2,$refrence_id,$date);
            }else{
                $this->inventoryledger("product_".$product_id,$fin,$refrence_id,$date,$datebit);
            }
        
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show($sale)
    {
         $sales=Sale::select('sales.*','customers.customer_name')->leftjoin('customers','sales.customer','customers.customer_id')->Where('sales.id',$sale)->get();
         $saleproducts=Saleproduct::select('saleproducts.*','products.barcode as product_id','products.product_description as product_description')->leftjoin('products','saleproducts.product','products.barcode')->Where('saleproducts.inv_id',$sale)->get();
         return view('sale/edit',compact('sales','saleproducts'));
    }

    public function process($sale)
    {
         $sales=Sale::select('sales.*','customers.customer_name')->leftjoin('customers','sales.customer','customers.customer_id')->Where('sales.id',$sale)->first();


         $saleproducts=Saleproduct::select('saleproducts.*','recipes.barcode as product_id','recipes.name as product_description','products.barcode as product_id','products.product_description as product_description')->leftjoin('recipes','saleproducts.product','recipes.id')->leftjoin('products','saleproducts.product','products.barcode')->Where('saleproducts.inv_id',$sale)->get();
        $count = 0;
        $count = count($saleproducts);
         // return $saleproducts;
        $products2 = Recipe::select('barcode','name as product_description','expiry_date');
        // $products =Inventory::select('barcode', 'product_description', 'expiry_date')->leftjoin('products','products.barcode','=','inventories.inv_product_id')->groupby('products.barcode')->unionAll($products2)->get();
        $products = Product::select('barcode', 'product_description', 'expiry_date')->leftjoin('inventories','inventories.inv_product_id','=','products.barcode')->where('products.sellable', 1)->groupby('barcode')->unionAll($products2)->get();

         $fy=Fiscalyear::orderby('id','desc')->first();
        if($fy != null){
        $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
        }
        else{
            $fy='2017,12,5';
        }
        //all customers from customers table
        $customers=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->get();
        $walkincus=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->first();
        //chart of accounts from cprelation table
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        //Auto increment to add new invoice with new id
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='accounts_hotel_' AND TABLE_NAME ='sales'");
        // $products = Recipe::all();
        $coupons = Coupon::all();
        $sellableProducts = Product::where('sellable',true)->get();
        return view('sale.process',compact('sales','saleproducts','customers','products','auto_increment','chartofaccounts','walkincus','fy','coupons','sellableProducts', 'count'));
         
        //return view('sale/process',compact('sales','saleproducts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        //
    }
    public function closeOrder(Request $request, $sale){
        $this->sale_ledger($request->total,$refrence_id,$request->txt_date,$request->date_bit);
        Sale::where('id',$sale)->update([
            'status'=>true
        ]);
        return redirect('orderinprogress');
    }
    
    public function updateSale(Request $request){
        $transectiontype=2;
        $refrence_id=$request->txt_inv_no;
         //storing data in databas table sales
        
        $sale= Sale::where('id',$refrence_id)->update([
            'subtotal'=>$request->total,
            'discount'=>$request->discount,
            // 'salestax'=>$request->salestax,
            'duebalance'=>$request->total_amount,
            'cashpaid' => $request->cash_paid,
            'status' => 1
        ]);
        if($sale){
            //$this->credit_sale_ladger('customer_'.$request->txt_customer_id,$request->total_amount,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit); 
            $deleteSaleDetails = Saleproduct::where('inv_id',$refrence_id)->delete();
        if($deleteSaleDetails){

            $finalarray = array();
            $tempprice=0;
            $tempqty=0;
            $tempresult=0;
            $tempamount=0;
            $tempbox=0;
            $qty_diff = 0;
            $total_diff = 0;
            $arr_productid = $request->txt_product;
            $arr_productprice = $request->price;
            $arr_productqty = $request->qty;
            $arr_qty_check = $request->qty_check;
            $arr_productbox = $request->box;
            $arr_inlintotal=$request->linetotal;
            $count=count($request->txt_product);
            $arr_flag = array();
            //creating flag element in array
            for ($i = 0; $i < count($arr_productid); $i++){
                $arr_flag[$i] = 0;
            }
            // return count($arr_productid);
            for ($i = 0; $i < count($arr_productid); $i++){

                    $saleproducts=new Saleproduct;
                    $saleproducts->inv_id=$request->txt_inv_no;
                    $saleproducts->product=$arr_productid[$i];
                    $saleproducts->sale_price=$arr_productprice[$i];
                    $saleproducts->qty=$arr_productqty[$i];
                    $saleproducts->inlinetotal=$arr_inlintotal[$i];
                    $saleproducts->save();
                    //temp id storing first itrations product id
                    $tempid = $arr_productid[$i];
                    //loop again to track same product id
                    $count=0;
                    for($j = 0; $j < count($arr_productid); $j++){
                    //checking if fileds are not empty to avoid store null data
                    if($arr_productid[$j] != ""){
                        //checking if first itrations product id is equal to second itrations product id and flag is not equal to 1
                    if($arr_productid[$j] == $tempid && $arr_flag[$j] != 1){
                        //summing up qty of same products
                        $tempqty += $arr_productqty[$j];
                        //summing up inline ammount of same products
                        $tempamount +=$arr_inlintotal[$i];
                        //getting price of each product
                        $tempprice += $arr_productprice[$j];
                        $arr_flag[$j] = 1;
                        $count ++;
                    }
                    }
                }
                if ($tempqty != 0){
                    //checking if fileds are not empty to avoid store null data
                    if($arr_productid[$i] !="" && $tempprice !="" && $tempqty !=""){
                        //$this->update_inventory($arr_productid[$i],$tempqty,$refrence_id,$request->total,$_costofsale,$request->txt_date,$request->date_bit);
                        $cost_of_sale = 0;
                        $supplier_data = Inventory::where('inv_product_id', $arr_productid[$i])->orderby('created_at', 'DESC')->first(); 
                        // return $arr_productid[$i];
                        $cost_of_sale = $supplier_data->inv_cost_price;
                        
                        // return $cost_of_sale;
                        if($arr_qty_check[$i] > $arr_productqty[$i] ){
                            $qty_diff = $arr_qty_check[$i]-$arr_productqty[$i];
                            // return $qty_diff;
                            // $this->Addinventory($arr_productid[$i],$request->txt_inv_no,"Return Sale",$cost_of_sale,$qty_diff,$request->txt_date);

                            $this->assetledger('customer_'.$request->txt_customer_id,1,"assets",null,$cost_of_sale * $qty_diff, $transectiontype,$refrence_id,$request->txt_date);

                            $this->assetledger('product_'.$arr_productid[$i],1,"assets",$cost_of_sale * $qty_diff,null,4,1,$request->txt_date);

                            $total_diff = $request->pre_total - $request->subtotal;

                            $this->assetledger($request->coa,1,"assets",null,$total_diff,4,1,$request->txt_date);

                            $this->sale_ledger_return($total_diff,$refrence_id,$request->txt_date,$request->date_bit);

                        }else if($arr_qty_check[$i] < $arr_productqty[$i]){
                            $qty_diff = $arr_productqty[$i]-$arr_qty_check[$i];
                            
                            $this->assetledger('customer_'.$request->txt_customer_id,1,"assets",$cost_of_sale * $qty_diff, null, $transectiontype,$refrence_id,$request->txt_date);

                            $this->assetledger('product_'.$arr_productid[$i],1,"assets",null,$cost_of_sale * $qty_diff,4,1,$request->txt_date);

                            $total_diff = $request->subtotal - $request->pre_total;
                                // return $total_diff€;
                            $this->assetledger($request->coa,1,"assets",$total_diff,null,4,1,$request->txt_date);

                            
                            $this->incomeledger(10101,4,"income",null,$total_diff,2,$refrence_id, $request->txt_date);

                            // $this->cashpaidledger($request->coa,$request->due_bal,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                            // $this->assetledger('customer_'.$request->txt_customer_id,1,"assets",null,$request->due_bal,$transectiontype,$refrence_id,$request->txt_date);
                        }else{
                            $this->cashpaidledger($request->coa,$request->due_bal,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                            // $this->credit_sale_ladger('customer_'.$request->txt_customer_id,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
                            $this->assetledger('customer_'.$request->txt_customer_id,1,"assets",null,$request->due_bal,$transectiontype,$refrence_id,$request->txt_date);
                        }
                        $tempqty = 0;
                        $tempprice = 0;
                        $tempamount=0;
                        $tempbox=0;
                    }
                }
                
            }
           
            //$this->sale_ledger($request->total,$refrence_id,$request->txt_date,$request->date_bit);

        }
        }
        if(!isset($request->ajax)){
             $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Sale Has Been Updated');
           return redirect('sale/process/'.$refrence_id);
       }
       else{
       $data="";
        $count = 1;
        $a= Sale::select('sales.id','sales.date','sales.date','sales.subtotal','sales.cashpaid','sales.duebalance','customers.customer_name')->leftjoin('customers','customers.customer_id','=','sales.customer')->where('sales.id',$refrence_id)->first();
            
        $data='[{"details":{"id":'.$a->id.',"subtotal":'.$a->subtotal.',"cashpaid":'.$a->subtotal.',"duebalance":'.$a->subtotal.',"customer_name":"'.$a->customer_name.'"},"products":[';
        

        $products =Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','recipes.name')->leftjoin('recipes','recipes.id','=','saleproducts.product')->where('inv_id',$refrence_id)->get();
        
        $productcount=Saleproduct::select('saleproducts.sale_price','saleproducts.qty','saleproducts.inlinetotal','saleproducts.description')->where('inv_id',$refrence_id)->count();
        foreach ($products as $product) {
            # code...
            $data .='{"sale_price":'.$product->sale_price.',"qty":'.$product->qty.',"inlinetotal":'.$product->inlinetotal.',"description":"'.$product->name.'"}';
            if($count < $productcount){
                $data .=",";
            }
            $count++;
        }
            
        $data .="]}]";
       echo $data;
       }
        // return redirect('sale/show');
        // return redirect('sale/process/'.$refrence_id);
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
    public function sale_ledger_return($total,$refrenceid,$date){
        $this->incomeledger(10101,4,"income",$total,null,2,$refrenceid,$date);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy($sale)
    {
        //deleting sales and sales products
       Sale::where('id',$sale)->delete();
        Saleproduct::where('inv_id',$sale)->delete();
        return redirect('sale/show');
    }

    //making sale invisible 
    public function voidsale($sale){
        Sale::where('id',$sale)->update(['void'=>True]);
        return redirect('sale/show');
    }


    public function credit_cash(){
        $customers =Customer::where('customer_id',$_POST['cus'])->first();
        $previousbalance=Ledger::where('account_id','customer_'.$_POST['cus'])->orderby('date','DESC')->first();
        if($previousbalance != null){
         return array(1,$customers->customer_name,$previousbalance->balance);
        }
        else{
            return array(1,$customers->customer_name,0);
        }
    }
    //customer ledger 
    public function cashpaidledger($account_id,$cashpaid,$refrenceid,$transectiontype,$date,$datebit){
         
         //check if cashpaid agains chart of account is not empty
        if($cashpaid > 0){ 
            $this->assetledger($account_id,1,"assets",$cashpaid,null,$transectiontype,$refrenceid,$date);
        }
    }
    
    //storing inventory ledger
    public function inventoryledger($account_id,$credit_ammount,$refrenceid,$date,$datebit){
        $this->assetledger($account_id,1,"assets",null,$credit_ammount,2,$refrenceid,$date);
    }

    //storing credit sale ladger 
    public function credit_sale_ladger($account_id,$debit_ammount,$refrenceid,$transectiontype,$date,$datebit){
        $this->assetledger($account_id,1,"assets",$debit_ammount,null,$transectiontype,$refrenceid,$date);
    }
    public function paymentrecieved(){
        $sales=Sale::all();
        $fy=Fiscalyear::orderby('id','desc')->first();
            if($fy != null){
            $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
            }
            else{
                $fy='2017,12,5';
            }
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        // $customers=Chartofaccount::where('coa_id','Not Like','%product_%')->where('coa_id','!=','padvance101')->where('account_type',1)->get();
        $customers=Chartofaccount::all();

        return view('sale.paymentrecieved',compact('sales','chartofaccounts','customers','fy'));
    }
    public function customer_invoices(Request $request){
        $sales=SALE::where('customer',$request->customer_id)->get();
        return $sales;
    }
    public function sale_invoice(Request $request){
        $sales=SALE::where('sales.id',$request->sale_invoice_number)->first();
        //echo "working";
        echo json_encode(array($sales->date,$sales->subtotal,$sales->cashpaid,$sales->duebalance,$sales->coa));
    }

    public function sale_products(Request $request){
        $saleproducts=Saleproduct::leftjoin('products','products.product_id','=','saleproducts.product')->where('inv_id',$request->sale_id)->get();
         return $saleproducts;
    }


    public function store_cashrecieved(Request $request)
    {
        //here validating empty fields
        $this->validate($request,
            ['customer'=>'required']);
        $transectiontype=9;
        $fileName ='';
        if($request->invoice_file != null){
            $fileName = time().'.'.$request->invoice_file->extension(); 
            $request->invoice_file->move(public_path('uploads/cashrecieved'), $fileName);
        }
         //storing data in databas table sales
        $cashrecieved=new Cashrecieved;
        $cashrecieved->cr_customer=$request->customer;
        $cashrecieved->cr_date=$request->date;
        $cashrecieved->cr_cashpaid=$request->cash_paid;
        $cashrecieved->cr_coa=$request->coa;
        $cashrecieved->invoice = $fileName;
        $cashrecieved->void=false;
        $cashrecieved->user_id=Auth::id();

        //checking if cashrecieveds data saved successfully and proceed
        if($cashrecieved->save())
        {
            Sale::where('id',$request->inv)->update(['duebalance'=>$request->total_amount]);
           
        //$reference id variable to refrence ledger entries
         $refrence_id=Cashrecieved::orderby('id','desc')->limit(1)->first();
          $this->cashrecievedcashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->customer,$request->date);
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'Cash Recieved');
        }
         return redirect('sale/payment');
    }

    public function showcashrecieved(Request $request){

        $cashrecieveds = Cashrecieved::all();
        return view('sale.showcashrecieved', compact('cashrecieveds'));
    }
     //customer ledger 
    public function cashrecievedcashpaidledger($account_id,$cashpaid,$refrenceid,$transectiontype,$customer,$date){
         //check if cashpaid agains chart of account is not empty
        if($cashpaid > 0){ 
        //initializing balance variable 
        $this->assetledger($account_id,1,"assets",$cashpaid,null,$transectiontype,$refrenceid,$date);
        $this->assetledger($customer,1,"assets",null,$cashpaid,$transectiontype,$refrenceid,$date);
        }
    }

    public function sale_ledger($total,$refrenceid,$date,$datebit){
        
        $this->incomeledger(10101,4,"income",null,$total,2,$refrenceid,$date);
       
    }

    public function cost_sale_ledger($total,$refrenceid,$date,$datebit){

        $this->expensledger(10102,5,"expense",$total,null,2,$refrenceid,$date);
        
    }
  
    
    public function recipeDetail(Request $request){
        $av_qty  = 0;
        if($request->len == 4){
             $product = Recipe::where('barcode',$request->product)->first();
             $av_qty = Inventory::where('inv_product_id', $request->product)->sum('inv_qty');
            // $discount = Product::where('barcode', $request->product)->first();
            if($product != ''){
                echo json_encode(array($product->sale_price,$product->name,200,0,0,0,$av_qty));
            }else{
                echo 0;
            }
           

        } else if($request->len == 3){
            $product = Deal::where('barcode', $request->product)->first();
            if($product != ''){
                echo json_encode(array($product->sale_price,$product->name,200,0,0,0,$av_qty));
            }else{
                echo 0;
            }
        } else{

            // $product = Recipe::where('product_id',$request->product)->first();
            $discount = Product::where('barcode', $request->product)->first();
            $av_qty = Inventory::where('inv_product_id', $request->product)->sum('inv_qty');
            // return $discount;
            if($discount != ''){
                echo json_encode(array($discount->sale_price,$discount->product_description,200, $discount->discount_from, $discount->discount_to, $discount->discount_percent,$av_qty));
            }else{
                echo 0;
            }
            

        }
       
    }
    public function getcustomerdata(Request $request){

        $customers=Chartofaccount::where('coa_id', $request->customer)->first();
        $previousbalance=Ledger::where('account_id',$request->customer)->orderby('date','DESC')->first();
        if($previousbalance != null){
         return array($customers->coa_title, $previousbalance->balance);
        }
        else{
            return array($customers->coa_title, 0);
        }
        

    }


    
}
