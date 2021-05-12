<?php

namespace App\Http\Controllers;
use App\Order;
use Illuminate\Http\Request;
use App\Customer;
use App\Product;
use App\Orderdetail;
use DB;
use App\Inventory;
use App\Ledger;
use Auth;
use App\Fiscalyear;

class OrderController extends Ledgerfunctions
{
  //index function to show all sales
  public function index()
  {
     $query=DB::raw('SELECT orders.*,customers.customer_name,users.name From orders LEFT JOIN customers ON orders.customer=customers.customer_id LEFT JOIN users ON orders.user_id = users.id where void=false');
       $orders=DB::select($query);
    return view('order/view' , compact('orders'));
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
      //all customers from customers table
      $customers=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->get();
      $walkincus=Customer::orderByRaw("FIELD(customer_name ,'Like', 'Walk') ASC")->first();
      //chart of accounts from cprelation table
      $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
      //Auto increment to add new invoice with new id
      $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='accounts_threehands_peshawer' AND TABLE_NAME ='orders'");
      //all products from products table
      $products=Inventory::select('inventories.*','products.product_description')->leftjoin('products','products.product_id','=','inventories.inv_product_id')->get();
      //$products=Inventory::leftjoin('products','products.product_id','=','inventories.inv_product_id')->groupby('inv_product_id')->get();
      return view('order.add',compact('customers','products','auto_increment','chartofaccounts','walkincus','fy'));
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
    public function store(Request $request)
    {

       //here validating empty fields
        $this->validate($request,
            ['txt_customer_id'=>'required']);
        $transectiontype=2;
         //storing data in databas table sales
        $order=new Order;
        $order->customer=$request->txt_customer_id;
        $order->date=$request->txt_date;
        $order->subtotal=$request->total; 
        $order->discount=$request->discount;
        $order->cashpaid=$request->cash_paid;
        $order->duebalance=$request->total_amount;
        $order->previousbalance=$request->prebalance;
        $order->newbalance=$request->newbalance;
        $order->signedby=$request->signedby;
        $order->coa=$request->coa;
        $order->void=false;
        $order->user_id=Auth::id();
        //checking if it was a credit sale or not
        if(isset($request->creditsale)){
            $order->credit_sales=true;
        }
        else{
        $order->credit_sales=false;
        }
        //checking if sales data saved successfully and proceed
        if($order->save())
        {
        //$reference id variable to refrence ledger entries
       $refrence_id=$request->txt_inv_no;
         //if credit sale check box is checked
        if(isset($request->creditsale)){
        $this->credit_sale_ladger('customer_'.$request->txt_customer_id,$request->total_amount,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
        }
       $this->cashpaidledger($request->coa,$request->cash_paid,$refrence_id,$transectiontype,$request->txt_date,$request->date_bit);
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
       $arr_flag = array();

       //creating flag element in array
       for ($i = 0; $i < count($arr_productid); $i++){
            $arr_flag[$i] = 0;
       }
       // for ($i = 0; $i < count($arr_productid); $i++){
       //          echo $arr_productid[$i] . "&nbsp;" . $arr_productprice[$i] . "&nbsp;" . $arr_productqty[$i]  . "&nbsp;" . $arr_flag[$i];
       //              echo "<br>";
       // }
       //              echo "Data Begins";
       //              echo "<br>";
       //looping through product arrays to sum up qty and price of same product_id

       $_costofsale = 0;

       for ($i = 0; $i < count($arr_productid); $i++){
                    $orderdetail=new Orderdetail;
                    $orderdetail->inv_id=$request->txt_inv_no;
                    $orderdetail->product=$arr_productid[$i];
                    $orderdetail->sale_price=$arr_productprice[$i];
                    $orderdetail->qty=$arr_productqty[$i];
                    $orderdetail->boxes=$arr_productbox[$i];
                    $orderdetail->inlinetotal=$arr_inlintotal[$i];
                    $orderdetail->save();
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
                    $tempbox += $arr_productbox[$j];
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
                    //add sale products
                    // $saleproducts=new Saleproduct;
                    // $saleproducts->inv_id=$request->txt_inv_no;
                    // $saleproducts->product=$arr_productid[$i];
                    // $saleproducts->sale_price=$tempprice/$count;
                    // $saleproducts->qty=$tempqty;
                    // $saleproducts->boxes=$tempbox;
                    // $saleproducts->inlinetotal=$tempamount;
                    // $saleproducts->save();

                    //update inventory by calling "update_inventory" function
                    //$this->update_inventory($arr_productid[$i],$tempqty,$refrence_id,$request->total,$_costofsale,$request->txt_date,$request->date_bit);

                    $tempqty = 0;
                    $tempprice = 0;
                    $tempamount=0;
                    $tempbox=0;
                }
            }
       }

        
        $this->sale_ledger($request->total,$refrence_id,$request->txt_date,$request->date_bit);
        $this->cost_sale_ledger($_costofsale,$refrence_id,$request->txt_date,$request->date_bit);
        
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'New Order Has Been Added');
        }
    //    // echo "<script>window.open('alert:id=".$request->txt_inv_no."|1', '_blank')</script>";
    //    echo "<script>window.open('alert:id=".$request->txt_inv_no."|1', '_blank')</script>";
    //     echo "<script>window.open('http://premierdairy.pk/threehands/public/sale/add','self')</script>";
        return redirect('order/add');
    }
    //update inventory in this "update_inventory" function
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
           $_costofsale +=$fin;
       //   //echo $fin;
       // //storing inventory ledger by calling inventoryledger function
       $this->inventoryledger("product_".$product_id,$fin,$refrence_id,$date,$datebit);
       
   }
   public function show($order)
    {
         $orders=Order::select('orders.*','customers.customer_name')->leftjoin('customers','orders.customer','customers.customer_id')->Where('orders.id',$order)->get();
         $orderdetails=Orderdetail::select('orderdetails.*','products.product_id','products.product_description')->leftjoin('products','orderdetails.product','products.product_id')->Where('orderdetails.inv_id',$order)->get();
         return view('order/edit',compact('orders','orderdetails'));
    }

    public function destroy($order)
    {
        //deleting sales and sales products
       Order::where('id',$order)->delete();
        Orderdetail::where('inv_id',$order)->delete();
        return redirect('order/show');
    }
   //customer ledger 
   public function cashpaidledger($account_id,$cashpaid,$refrenceid,$transectiontype,$date,$datebit){
         
            //check if cashpaid agains chart of account is not empty
        if($cashpaid > 0){ 
        //initializing balance variable 
            if ($datebit == 1) {

        $this->assetledger($account_id,1,"assets",$cashpaid,null,$transectiontype,$refrenceid,$date);
            }
            elseif ($datebit == 0) {
                $this->current_date_assetledger($account_id,1,"assets",$cashpaid,null,$transectiontype,$refrenceid,$date);
            }
        }
        }

        //storing inventory ledger
        public function inventoryledger($account_id,$credit_ammount,$refrenceid,$date,$datebit){
        if ($datebit == 1) {
        $this->assetledger($account_id,1,"assets",null,$credit_ammount,2,$refrenceid,$date);
        }
        elseif ($datebit == 0) {
            $this->current_date_assetledger($account_id,1,"assets",null,$credit_ammount,2,$refrenceid,$date);
        }
        }

        //storing credit sale ladger 
        public function credit_sale_ladger($account_id,$debit_ammount,$refrenceid,$transectiontype,$date,$datebit){
        if ($datebit == 1) {
        $this->assetledger($account_id,1,"assets",$debit_ammount,null,$transectiontype,$refrenceid,$date);
        }
        elseif ($datebit == 0) {
        $this->current_date_assetledger($account_id,1,"assets",$debit_ammount,null,$transectiontype,$refrenceid,$date);
        }
        }
        public function sale_ledger($total,$refrenceid,$date,$datebit){
            if($datebit == 1){
            $this->incomeledger(10101,4,"income",null,$total,2,$refrenceid,$date);
            }
            else if($datebit == 0){
            $this->current_date_incomeledger(10101,4,"income",null,$total,2,$refrenceid,$date);
            }
        }

        public function cost_sale_ledger($total,$refrenceid,$date,$datebit){
            if($datebit == 1){
            $this->expensledger(10102,5,"expense",$total,null,2,$refrenceid,$date);
            }
            else if($datebit == 0){
            $this->current_date_expensledger(10102,5,"expense",$total,null,2,$refrenceid,$date);
            }
        }

       
}
