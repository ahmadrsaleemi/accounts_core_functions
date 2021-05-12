<?php
namespace App\Http\Controllers;
use App\Purchase;
use App\Bill;
use App\Bill_product;
use Illuminate\Http\Request;
use App\Supplier;
use App\Product;
use App\Inventory;
use App\Supplieraccount;
use App\Chartofaccount;
use App\Ledger;
use App\Logpurchase;
use App\Logpurchaseproducts;
use App\Cprelation;
use App\logchartofaccount;
use App\Cashpaid;
use App\cashpaipproducts;
use Auth;
use DB;
use App\Fiscalyear;
use App\Recipe;
use App\Recipeproduct;
use App\Port;


class PurchaseController extends Ledgerfunctions
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

    //index function to show all purchases
    public function index()
    {
        
      
        $query=DB::raw('SELECT bills.*,suppliers.supplier_name,users.name From bills LEFT JOIN suppliers ON bills.supplier=suppliers.supplier_id LEFT JOIN users ON bills.user_id = users.id where void=false');
        $bills=DB::select($query);
        return view('purchase.view',compact('bills'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    //creat function to show form to add purchases
    public function create()
    {   
        
        $test = env('DB_DATABASE');
        $fy=Fiscalyear::orderby('id','desc')->first();
        if($fy != null){
        $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
        }
        else{
            $fy='2017,12,5';
        }
        //all suppliers from supplier table
        $suppliers=Supplier::all();
        //all products from products table
        $products=Product::all();

        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='bills'");
        //chart of accounts from cprelation table 
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();

        // $bill=DB::table('bills')->orderBy('id', 'desc')->first();
        return view('purchase.add',compact('suppliers','products','chartofaccounts','fy','auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       // return count($request->txt_product);
        //here validating fields
        $this->validate($request,
            ['txt_bill_no'=>'required',
            'txt_date'=>'required',
            'txt_supplier'=>'required',
            'total'=>'required'
        ]);
        $transectiontype=1;
        //check_duplicate to check if product_id is different
        $check_duplicate = null;
        //if product id already in database return false and show error message

        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Purchase Bill Already Available Please Try Changed Bill Id');
            return redirect('purchase/add');
        }

        //if product id is diffrent proceed for purchase storing
        else{

        if($request->cash_paid == ""){
            $cash_paid=0;
        }
        else{
            $cash_paid=$request->cash_paid;
        }

        $test = env('DB_DATABASE');

        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='bills'");
        $sale_id = 0;
        foreach ($bill_id as $id) {
            $sale_id = $id->AUTO_INCREMENT;
        }        
        //storing data in databas table bills
// return $request->comments;
        $date=date('Y-m-d H:i:s');
        $purchase=new Bill();
        $purchase->bll_id=$sale_id;
        $purchase->supplier=$request->txt_supplier;
        $purchase->advance=$cash_paid;
        $purchase->total_ammount=$request->total;
        $purchase->duebalance=$request->total_amount;
        $purchase->comments = $request->comments;
        $purchase->coa=$request->coa;
        $purchase->void=false;
        $purchase->date=$request->txt_date;
        $purchase->user_id=Auth::id();

         
        //checking if bills data saved successfully and proceed
        if($purchase->save())
        {
        //  $this->expensledger(10102,5,'expense',$request->total,null,1,$sale_id,$request->txt_date);
          if($cash_paid != 0){
            $this->storledger($request->txt_supplier,$request->coa,$cash_paid,$request->total_amount,1,$sale_id,$request->txt_date);
          }
        //$reference id variable to refrence ledger entries
        $refrence_id=$sale_id;
        //log of of purchase bill
        // $this->log_purchase($sale_id,$request->txt_supplier,$cash_paid,$request->total,false,$request->txt_date);

       $tempproid=0;
       $fin_price=0;
       $tempprice=0;
       $tempqty=0;
       $tempboxes=0;
       $tempamount=0;
       $tempexpire=0;
       $arr_productid = $request->txt_product;
       $arr_productid[]=$request->txt_product_id1;
       $arr_product_desc = $request->txt_product_description;
       $arr_product_desc[]=$request->txt_product_description1;
       $arr_productprice = $request->price;
       $arr_productprice[] =$request->price1;
       $arr_productqty = $request->qty;
       $arr_productqty[] = $request->qty1;
       $arr_expire  =   $request->expiry_date;
       $arr_expire[]  =   $request->expiry_date1;
       $arr_amount= $request->linetotal;
       $arr_amount[] = $request->linetotal1;
       $arr_sticker= $request->sticker;
       $arr_sticker[] = $request->sticker1;
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
            
            //saving bill products by calling "add_purchase_products" function
            $this->add_purchase_products($tempproid,$sale_id,$fin_price,$tempqty,$tempamount,$tempexpire);

            //saving bill products log by calling "log_purchase_products" function
            $this->log_purchase_products($tempproid,$sale_id,$fin_price,$tempqty);

            // //saving inventory ledger by calling "storproinveledger" function
            $this->storproinveledger('product_'.$tempproid,$fin_price,$tempqty,$refrence_id,$transectiontype,$request->txt_date);

            //saving bill products log by calling "log_purchase_products" function
             $this->Addinventory($tempproid,$sale_id,$request->txt_supplier,$fin_price,$tempqty,$request->txt_date,$tempexpire);
             $tempboxes=0;
             $tempqty = 0;
             $tempamount =0;
             $fin_price=0;
             }
            }
        }
           $count=count($arr_productid);
           $suppliers =Supplier::where('supplier_id',$request->txt_supplier)->first();
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
               $total_qty = $arr_productqty[$i];

            if($arr_sticker[$i] != ''){
                for ($j=0; $j < $arr_sticker[$i]; $j++) { 
                    if($j != $arr_sticker[$i] - 1){
                    $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"},';
                    // $total_qty += $arr_productqty[$i];
                }else{
                    if($i != count($arr_sticker) -1){
                     $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"},';
                    }else{
                       $items_data.='{"barcodeID":"'.$product_name.'","Description":"'.$product_desc.'","expiryDate":"'.$expirydate.'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_amount[$i].'"}'; 
                    }
                    // $total_qty += $arr_productqty[$i];
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

        if($request->withdate != ''){
            $withdate = $request->withdate;
        }
        if($request->withname != ''){
            $withname = $request->withname;
        }
        if($request->withrate != ''){
            $withrate = $request->withrate;
        }

             $json = '{"TemplateID":2,"ProductCount":"'.$total_qty.'","WithName":"'.$withname.'","WithDate":"'.$withdate.'","WithRate":"'.$withrate.'","Invoice":"'.$refrence_id.'","Date":"'.$request->txt_date.'","Name":"'.$suppliers->supplier_name.'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","total":"'.$request->total.'"}';
            
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Bill Has Been Added');
        }
        return $json;
    }
      
    }

    public function print(Request $request){

        $test = env('DB_DATABASE');

        $bill_id = DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='bills'");
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
       $arr_productid = $request->txt_product;
       $arr_productid[]=$request->txt_product_id1;
       $arr_product_desc = $request->txt_product_description;
       $arr_product_desc[]=$request->txt_product_description1;
       $arr_productprice = $request->sprice;
       $arr_productprice[] =$request->sprice1;
       $arr_productqty = $request->qty;
       $arr_productqty[] = $request->qty1;
       $arr_expire  =   $request->expiry_date;
       $arr_expire[]  =   $request->expiry_date1;
       $arr_amount= $request->linetotal;
       $arr_amount[] = $request->linetotal1;
       $arr_sticker= $request->sticker;
       $arr_sticker[] = $request->sticker1;
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
           $suppliers =Supplier::where('supplier_id',$request->txt_supplier)->first();
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

             $json = '{"TemplateID":2,"ProductCount":"'.$total_qty.'","WithName":"'.$withname.'","WithDate":"'.$withdate.'","WithRate":"'.$withrate.'","Invoice":"'.$refrence_id.'","Date":"'.$request->txt_date.'","Name":"'.$suppliers->supplier_name.'","Preview":"'.$preview.'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","total":"'.$request->total.'"}';
        
        return $json;
    
    }

    //add_purchase_products function written to add bill products
    public function add_purchase_products($tempproid,$bill_no,$fin_price,$tempqty,$inlinetotal,$expiry_date){
            if($tempproid !="" && $fin_price !="" && $tempqty !="" && $inlinetotal !=""){
            $bill_products=new Bill_product;
            $bill_products->product_id=$tempproid;
            $bill_products->bill_id=$bill_no;
            $bill_products->cost_price=$fin_price;
            $bill_products->qty=$tempqty;
            $bill_products->inlinetotal=$inlinetotal;
            $bill_products->expiry_date = $expiry_date;
            $bill_products->save();
            }
    }
    //log_purchase function written to add bills  log
    public function log_purchase($bill_id,$supplier_id,$advance,$total_ammount,$void,$date){

        $Log_purchase=new Logpurchase();
        $Log_purchase->bill_id=$bill_id;
        $Log_purchase->supplier=$supplier_id;
        $Log_purchase->advance=$advance;
        $Log_purchase->total_ammount=$total_ammount;
        $Log_purchase->void=$void;
        $Log_purchase->user_id=Auth::id();
        $Log_purchase->date=$date;
        
        $Log_purchase->save();
    }
    //log_purchase_products function written to add bill products log
    public function log_purchase_products($product_id,$bill_id,$cost_price,$qty){

        $Log_purchase_products=new Logpurchaseproducts();
        $Log_purchase_products->product_id=$product_id;
        $Log_purchase_products->bill_id=$bill_id;
        $Log_purchase_products->cost_price=$cost_price;
        $Log_purchase_products->qty=$qty;
        $Log_purchase_products->save();
    }

    //saving supplier and account ledger by calling "storledger" function
   public function storledger($supplier_id,$acc_id,$cashpaid,$dueammount,$transectiontype,$refrenceid,$date){
        //checking if dueamount not = 0 to store supplier ledger
      if($dueammount != 0){
        //assing supplier prefix to account_id to keep track of it in ledger
        $account_id="supplier_".$supplier_id;
        $this->liabilityledger($account_id,2,"liabilities",Null,$dueammount,$transectiontype,$refrenceid,$date);
        }
         //check if cashpaid agains chart of account is not empty
        if($cashpaid != null && $acc_id != 0){
            //assigning account id
            $account_id=$acc_id;
            //check if balnce is available already agaist this account id
            $this->assetledger($account_id,1,"assets",Null,$cashpaid,$transectiontype,$refrenceid,$date);
        }


    }
    //storing inventory ledger
    public function storproinveledger($account_id,$cost_price,$qty,$refrenceid,$transectiontype,$date){
    
        $this->assetledger($account_id,1,"assets",$cost_price*$qty,null,$transectiontype,$refrenceid,$date);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    //populating edit form agaist its id
    public function show($bill_id)
    {   
        //all suppliers from supplier table
        $suppliers=Supplier::all();
        //all products from products table
        $products=Product::all();
        //getting bill against $bill_id
        $bills=DB::table('bills')->leftjoin('suppliers','bills.supplier','suppliers.supplier_id')->Where('bll_id',$bill_id)->get();
        //getting bill products against $bill_id
        $bill_products=DB::table('bill_products')->select('bill_products.*','products.barcode','products.product_description')->Where('bill_products.bill_id',$bill_id)->leftjoin('products','bill_products.product_id','products.barcode')->get();
        //getting bill inventories against $bill_id
        $bill_inventories=DB::table('bills')->select('bills.bll_id','inventories.id')->where('bills.bll_id',$bill_id)->leftjoin('inventories','inventories.inv_bill_id','bills.bll_id')->get();
        //passing data to the view
        // return $bills;
        return view('purchase/edit',compact('suppliers','products','bills','bill_products','bill_inventories'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit($bill_id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    // updating bill and bill products
    public function update(Request $request)
    {
        //validating empty fields
        $this->validate($request,
            ['txt_date'=>'required',
            'txt_supplier'=>'required',
            'total'=>'required',
            'cash_paid'=>'required'
        ]);
        //getting last record of of bill
        $bill=DB::table('bills')->orderBy('id', 'desc')->first();
        //formatting date
        $date=date('Y-m-d H:i:s');
        //making instance of Bill class
        $purchase=new Bill();
        //query to update bill
        $bill_update=DB::table('bills')->Where('bll_id',$request->bill_no)->update(['bll_id'=>$request->txt_bill_no,'supplier'=>$request->txt_supplier,'advance'=>$request->cash_paid,'total_ammount'=>$request->total,'date'=>$request->txt_date]);
            //bill id as rec_id
            if(isset($_POST['rec_id'])){
                //updating bill products iteratively
                for($i=0;$i < count($request->rec_id);$i++){
                    //query to update bill products
                    $product_update=DB::table('bill_products')->Where(['id'=>$request->rec_id[$i]])->update(['product_id'=>$request->txt_product_id[$i],'bill_id'=>$request->txt_bill_no,'cost_price'=>$request->price[$i],'qty'=>$request->qty[$i]]);
                    //updating product  inventory
                    $this->Updateinventory($request->txt_product_id[$i],$request->txt_bill_no,$request->txt_supplier,$request->price[$i],$request->price[$i],$request->txt_date,$request->inventory[$i]);
                }
             if(isset($_POST['counter'])){
                for($i=0;$i < count($request->counter);$i++){
                    $bill_products=new Bill_product;
                    $bill_products->product_id=$request->txt_product[$i];
                    $bill_products->bill_id=$request->txt_bill_no;
                    $bill_products->cost_price=$request->pricee[$i];
                    $bill_products->qty=$request->qtyy[$i];
                    $bill_products->save();
                    $this->Addinventory($request->txt_product[$i],$request->txt_bill_no,$request->txt_supplier,$request->pricee[$i],$request->qtyy[$i],$request->txt_date);

                    
                }
            }

            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Purchase Bill Has Been Updated');
        }
       return redirect('purchase/show/'.$request->txt_bill_no);
    }

    
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy($bill_id)
    {
        //delete bills
        DB::table('bills')->where('bll_id',$bill_id)->delete();
        //delete bill products
        DB::table('bill_products')->where('bill_id',$bill_id)->delete();
        return redirect('purchase/show');
    }
    //ajax call to populate supllier list
    public function populate_supplier_description(Request $request){
        //return $request->supplier;

        $supplier =DB::table('suppliers')->where('supplier_id',$request->supplier)->get();
        //returning supplier addres via ajax call against supplier id
        foreach ($supplier as $sup) {
         return   $sup->addres;
        }
    }

    //ajax call to populate product list
    public function products(){

        $product =DB::table('products')->where('barcode',$_POST['product'])->get();
        foreach ($product as $pro) {
         // return   array(['sale_price'=>$pro->sale_price,'description'=>$pro->product_description]);

        //returning product sale price,product description via ajax call against supplier id
         echo json_encode(array(300,$pro->product_description,$pro->sale_price));
     }
    }

    public function update_saleprice(Request $request){

      $update = Product::where('barcode', $request->barcode)->update(['sale_price' => $request->saleprice]);
      if($update){
        return 1;
      }
    }

    //making bill void to make them invisible
    public function voidpurchase($bill_id){
         $date=date('Y-m-d H:i:s');
        
        DB::table('logpurchases')->where('bill_id',$bill_id)->update(['void'=>True,'updated_at'=>$date]);
        $purchases=logpurchase::where('bill_id',$bill_id)->get();
        foreach ($purchases as  $purchase) {
           $this->log_purchase($bill_id,$purchase->supplier,$purchase->advance,$purchase->total_ammount,false,$purchase->date);
        }
        DB::table('bills')->where('bll_id',$bill_id)->update(['void'=>True,'updated_at'=>$date]);
        return redirect('purchase/show');
    }
    //genrating inventory against purchased bills
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

    //updating inventory
    public function Updateinventory($pid,$biil_id,$sup_id,$cost_price,$qty,$date,$inventory_id){
        Inventory::where('id',$inventory_id)->update([ 'inv_product_id'=>$pid, 'inv_supplier_id'=>$sup_id, 'inv_bill_id'=>$biil_id, 'inv_cost_price'=>$cost_price, 'inv_qty'=>$qty, 'inv_bill_date'=>$date]);
    }
    //creating new supplier in model if not exist via ajax call in  supplier model
    public function addsupplier(Request $request){
        //checking if supplier not exist already
        $check_duplicate=Supplier::where('supplier_id',$request->supplier_id)->first();
        if($check_duplicate !== null){
            echo "0";
        }
        else{
            //storing suupliera
        $supplier=new Supplier;
        $supplier->supplier_id=$request->supplier_id;
        $supplier->supplier_name=$request->supplier_name;
        $supplier->addres=$request->supplier_address;
        $supplier->user_id=Auth::id();
        if ($supplier->save()) {
            //creating chart of account against supplier
            app('App\Http\Controllers\ProductController')->supplier_chart_of_account('supplier_'.$request->txt_supplier_id,$request->txt_supplier_name,'supplier_'.$request->txt_supplier_name);
            //creating log chart of account against supplier
               app('App\Http\Controllers\ChartofaccountController')->log_chartofaccount('supplier_'.$request->txt_supplier_id,$request->txt_supplier_name,'supplier_'.$request->txt_supplier_name,1);
               //if everything goes ok return 1
            echo "1";
        }
    }
    }
    //creating new product in model if not exist via ajax call in  product model
    public function addproduct(Request $request){
        //checking if product not exist already
        $check_duplicate=Product::where('product_id',$request->product_id)->first();

        if($check_duplicate !== null){
            echo "0";
        }
        else{
             //storing products
            $product=new Product;
        $product->product_id=$request->product_id;
        $product->product_description=$request->product_description;
        $product->stock_alert=$request->stock_alert;
        $product->qty=$request->stock_alert;
        $product->expiry_date = $request->expiry_date;
        if(isset($request->sellable))
        $product->sellable=true;
        else
        $product->sellable=false;
        if($request->sale_price != ""){
            $recipe=new Recipe;
            $recipe->name=$request->product_description;
            $recipe->category=1;
            $recipe->subcategory=1;
            $recipe->sale_price=$request->sale_price;
            if ($recipe->save()) {
                $recipe = Recipe::orderby('id','desc')->first()->id;
                $recipeProduct =new Recipeproduct;
                $recipeProduct->recipe = $recipe;
                $recipeProduct->product = $request->product_id;
                $recipeProduct->save();
            }
            
        }

        $product->user_id=Auth::id();
            if ($product->save()) {
                //creating chart of account against product

                app('App\Http\Controllers\ProductController')->product_chart_of_account('product_'.$request->product_id,'product_'.$request->product_description,'product_'.$request->product_description.'_account');
                //creating log chart of account against product
 
               app('App\Http\Controllers\ChartofaccountController')->log_chartofaccount('product_'.$request->product_id,'product_'.$request->product_description,'product_'.$request->product_description.'_account',1);
               //if everything goes ok return 1
                
                echo "1";
            }
       }
    }

    //getting available amount of account cash is to be paid
    public function gettotalbalance(Request $request){
        //populate available balance on load page against showed chartof account
        if(isset($request->coa1)){
        $coa_id= $request->coa1;     
        }
        //populate available balance on change chart of account
        elseif(isset($request->coa)){
            $coa_id= $request->coa;
        }
        else{
          $coa_id="";  
        }
        if($coa_id != ""){
      $total=0;
      //getting latest balance amount
      $last_record=Ledger::where('account_id',$coa_id)->orderby('date', 'desc')->first();
      if($last_record != null)
         echo $last_record->balance;
        else
            echo 0;}
    }

    

    public function cashpaid(){
        $fy=Fiscalyear::orderby('id','desc')->first();
        if($fy != null){
        $fy=date('20y,m,d', strtotime($fy->fy.'-1 year'));
        }
        else{
            $fy='2017,12,5';
        }
    //all suppliers from supplier table
    $suppliers=Chartofaccount::all();
    //all products from products table
    $products=Product::all();

    
    //chart of accounts from cprelation table 
    $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        return view('purchase/paymentpaid',compact('suppliers','products','chartofaccounts','fy'));
    }


    public function supplierbills(Request $request){
        $bills=Bill::where('supplier',$request->supplier)->get();
        return $bills;
    }

    public function bill_detail(Request $request){
        $bills=Bill::leftjoin('chartofaccounts','chartofaccounts.coa_id','=','bills.coa')->where('bll_id',$request->bill_id)->first();
        echo json_encode(array($bills->date,$bills->total_ammount,$bills->advance,$bills->duebalance,$bills->coa,$bills->coa_title));
    }

    public function bill_products(Request $request){
    $bill_products=Bill_product::leftjoin('products','products.product_id','=','bill_products.product_id')->where('bill_id',$request->bill_id)->get();
       return $bill_products;
    }

    public function store_cashpaid(Request $request){
        $date=date('Y-m-d H:i:s');
        $fileName = '';
        if($request->invoice_file != null){
            $fileName = time().'.'.$request->invoice_file->extension(); 
            $request->invoice_file->move(public_path('uploads/cashpaid'), $fileName);
        }
        // return $fileName ;
        $cashpaid=new Cashpaid();
        $cashpaid->cp_supplier_id=$request->supplier;
        $cashpaid->cp_date=$request->date;
        $cashpaid->cp_cashpaid=$request->cash_paid;
        $cashpaid->dp_coa=$request->coa;
        $cashpaid->invoice = $fileName;
        $cashpaid->void=false;
        $cashpaid->user_id=Auth::id();
         
   
        
        //checking if bills data saved successfully and proceed
        if($cashpaid->save())
        {
         $transectiontype=8;
         Bill::where('bll_id',$request->bill_id)->update(['duebalance'=>$request->total_amount]);
         $this->storcashpaidledger($request->supplier,$request->coa,$request->cash_paid,1,$transectiontype,$request->date);
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'New cashpaid Has Been Added');
         }
        return redirect('purchase/payment');
        }

    public function showcashpaid(Request $request){

        $cashpaids = Cashpaid::all();
        return view('purchase.showcashpaid', compact('cashpaids'));
    }
    public function add_cashpaidproducts($tempproid,$refrence_id,$fin_price,$tempqty,$tempamount,$bill){
        if($tempproid !="" && $fin_price !="" && $tempqty !="" && $tempamount !=""){
            $cashpaipproducts=new Cashpaipproducts;
            $cashpaipproducts->cpp_product_id=$tempproid;
            $cashpaipproducts->cashpaid=$refrence_id;
            $cashpaipproducts->cpp_billid=$bill;
            $cashpaipproducts->cpp_costprice=$fin_price;
            $cashpaipproducts->cpp_qty=$tempqty;
            $cashpaipproducts->cpp_inlinetotal=$tempamount;
            $cashpaipproducts->save();
            }
    }

     public function storcashpaidledger($supplier_id,$acc_id,$cashpaid,$refrenceid,$transectiontype,$date){
         //check if cashpaid agains chart of account is not empty
        if($cashpaid != null){
            //assigning account id
            $account_id=$acc_id;
            // $this->liabilityledger("supplier_".$supplier_id,2,"liabilities",$cashpaid,null,$transectiontype,$refrenceid,$date);
            $this->assetledger($supplier_id,1,"assets",$cashpaid,null,$transectiontype,$refrenceid,$date);
            $this->assetledger($account_id,1,"assets",Null,$cashpaid,$transectiontype,$refrenceid,$date);
        }


    }

    public function getsupplierbalance(Request $request){
        $customers=Chartofaccount::where('coa_id', $request->supplier)->first();
        $sbal=Ledger::where('account_id',$request->supplier)->orderby('date','desc')->first();
        if($sbal != null){
             return array($customers->coa_title, $sbal->balance);
        }
        else{
            return array($customers->coa_title, 0);
        }
        
    }
}

