<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Sale;
use App\Expense;
use Auth;
use App\Ledger;
use Carbon\Carbon;
use DB;
use App\Notification;
use App\Inventory;
use App\Product;
use App\Bill_product;
use Session;
// use App\Sale;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
      public function __construct()
    {
        $this->middleware('auth');
        
    }
    public function index()
    {


        $message="";
        $invquery=DB::raw('SELECT inventories.*,products.stock_alert,products.product_description FROM inventories left join products ON products.product_id=inventories.inv_product_id WHERE inventories.id IN ( SELECT MAX(inventories.id) FROM inventories GROUP BY inv_product_id)');
        $inventories =DB::select($invquery);
       
        foreach($inventories as $inventory){
            
            if($inventory->inv_qty < $inventory->stock_alert){
               $message= $inventory->product_description."  Stock Alert Remaining Qty is ".$inventory->inv_qty."  And Stock Alert is  ".$inventory->stock_alert;
               $checkduplicate=Notification::where('message',$message)->count();
               if($checkduplicate == null){
                $notification=new Notification;
                $notification->message=$message;
                $notification->dismiss=false;
                $notification->save();
               }
                
            }

        }

        $products = Bill_product::select('bill_products.*', 'products.product_description')->leftjoin('products', 'products.product_id', 'bill_products.product_id')->get();
        foreach ($products as $product) {
            if($product->expiry_date != ''){
                $ts1 = strtotime(date('Y-m-d'));
                $ts2 = strtotime($product->expiry_date);

                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);

                $month1 = date('m', $ts1);
                $month2 = date('m', $ts2);

                $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
                if($diff < 1){
                    $message= $product->product_description."  is about to Expire on ".$product->expiry_date;
                     $checkduplicate=Notification::where('message',$message)->count();
               if($checkduplicate == null){
                    $notification=new Notification;
                    $notification->message=$message;
                    $notification->dismiss=false;
                    $notification->save();
                }
               }
                }
        }
        $pre_order = Sale::select('sales.*', 'customers.customer_name')->leftjoin('customers', 'customers.customer_id', '=', 'sales.customer')->where('salestype' , '=', 'Pre Order')->where('date', '=', date('Y-m-d'))->get();

        foreach ($pre_order as $order) {
            $message = "Pre Order for ".$order->customer_name." Today Order No. ".$order->id."";
            $checkduplicate=Notification::where('message',$message)->count();
               if($checkduplicate == null){
                $notification=new Notification;
                $notification->message=$message;
                $notification->dismiss=false;
                $notification->save();
               }
        }

        $creditlimitquery=DB::raw('SELECT ledgers.balance,customers.customer_name,customers.credit_limit FROM ledgers left join customers ON customers.customer_id=substring_index(ledgers.account_id, "_", -1) WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && account_id LIKE "%customer_%"');
        $creditlimits =DB::select($creditlimitquery);
        foreach($creditlimits as $creditlimit){
            
            if($creditlimit->balance > $creditlimit->credit_limit){
               $message= $creditlimit->customer_name." Credit Limit Crossed current credit balance is  ".$creditlimit->balance."  And Credit Limit was  ".$creditlimit->credit_limit;
               $checkduplicate=Notification::where('message',$message)->count();
               if($checkduplicate == null){
                $notification=new Notification;
                $notification->message=$message;
                $notification->dismiss=false;
                $notification->save();
               }
                
            }

        }
        $incomes=0;
        $credit_sales=0;
        $cash_sales=0;
        $costofsales=0;
        $expenses=0;
        $query=0;
        $query2=0;
        $cosquery="";
        $totalcos=0;
        $revenue=0;
        $netprofit=0;
        $otheraccountbalancequery=0;
        $total_oaccblnc=0;
        $cashinhandquery=0;
        $total_cashinhand=0;
        $payablequery=0;
        $totalpayable=0;
        $recieveablequery=0;
        $totalrecieveable=0;
        $currentMonth = date('m');
        $topsoldproducts="";
        $topsoldqty="";
        $credit_sales=Sale::whereRaw('MONTH(date) = ?',[$currentMonth])->sum('subtotal');
        $query=DB::raw('SELECT chartofaccounts.*,ledgers.balance FROM chartofaccounts left join ledgers ON chartofaccounts.coa_id=ledgers.account_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=4 && ledgers.account_id !=10101 && ledgers.date > DATE_SUB(NOW(), INTERVAL 1 MONTH)');
        $incomes =DB::select($query);

        $query2=DB::raw('SELECT chartofaccounts.*,ledgers.balance FROM chartofaccounts left join ledgers ON chartofaccounts.coa_id=ledgers.account_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" &&  ledgers.date > DATE_SUB(NOW(), INTERVAL 1 MONTH)');

        $expenses=DB::select($query2);
        $total_income=0;$total_expenses=0;
        foreach($incomes as $income){
                 $total_income +=$income->balance;  
            }
        foreach($expenses as $expense){
                $total_expenses +=$expense->balance;
            }
        $revenue=$credit_sales+$total_income;

         $cosquery=DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102"  &&  MONTH(x.date) ='.date("m").'');

         $costofsales=DB::select($cosquery);
         foreach ($costofsales as $cos) {
              $totalcos =$cos->balance;
         }
         $netprofit = $credit_sales+$total_income-$totalcos-$total_expenses;

          $otheraccountbalancequery=DB::raw('SELECT ledgers.balance FROM  ledgers LEFT JOIN cprelations ON ledgers.account_id=cprelations.acc_id WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=1 && account_id !="52"');
          $otheraccountbalances=DB::select($otheraccountbalancequery);
          foreach($otheraccountbalances as $otheraccountbalance){
                $total_oaccblnc +=$otheraccountbalance->balance;
            }
        $cashinhandquery=DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance FROM ledgers where ledgers.account_id=52 ORDER BY date asc) x');
          $cashinhands=DB::select($cashinhandquery);
          foreach($cashinhands as $cashinhand){
                $total_cashinhand =$cashinhand->balance;
            }
        $payablequery=DB::raw('SELECT ledgers.balance FROM ledgers WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=2 && account_id LIKE "%supplier_%"');
            $payables=DB::select($payablequery);
            foreach ($payables as $payable) {
                $totalpayable +=$payable->balance;
            }
        $recieveablequery=DB::raw('SELECT ledgers.balance FROM ledgers WHERE ledgers.id IN ( SELECT MAX(ledgers.id) FROM ledgers GROUP BY account_id) && ledgers.account_type=1 && account_id LIKE "%customer_%"');
            $recieveables=DB::select($recieveablequery);
            foreach ($recieveables as $recieveable) {
                $totalrecieveable +=$recieveable->balance;
            }
            
            $jansaleincome=Sale::whereMonth('date',1)->sum('subtotal');
            $fabsaleincome=Sale::whereMonth('date',2)->sum('subtotal');
            $marchsaleincome=Sale::whereMonth('date',3)->sum('subtotal');
            $aprilsaleincome=Sale::whereMonth('date',4)->sum('subtotal');
            $maysaleincome=Sale::whereMonth('date',5)->sum('subtotal');
            $junsaleincome=Sale::whereMonth('date',6)->sum('subtotal');
            $julysaleincome=Sale::whereMonth('date',7)->sum('subtotal');
            $augsaleincome=Sale::whereMonth('date',8)->sum('subtotal');
            $sepsaleincome=Sale::whereMonth('date',9)->sum('subtotal');
            $octsaleincome=Sale::whereMonth('date',10)->sum('subtotal');
            $novsaleincome=Sale::whereMonth('date',11)->sum('subtotal');
            $decsaleincome=Sale::whereMonth('date',12)->sum('subtotal');
        
            $decexpense=Ledger::select('balance')->whereMonth('date',12)->where('account_id','10101')->orderby('date','desc')->first();
           //return $decexpense->balance;
            $janexpense=Ledger::select('balance')->whereMonth('date',1)->where('account_id','10101')->orderby('date','desc')->first();
            $fabexpense=Ledger::select('balance')->whereMonth('date',2)->where('account_id','10101')->orderby('date','desc')->first();
            $marchexpense=Ledger::select('balance')->whereMonth('date',3)->where('account_id','10101')->orderby('date','desc')->first();
            $aprilexpense=Ledger::select('balance')->whereMonth('date',4)->where('account_id','10101')->orderby('date','desc')->first();
            $mayexpense=Ledger::select('balance')->whereMonth('date',5)->where('account_id','10101')->orderby('date','desc')->first();
            $junexpense=Ledger::select('balance')->whereMonth('date',6)->where('account_id','10101')->orderby('date','desc')->first();
            $julyexpense=Ledger::select('balance')->whereMonth('date',7)->where('account_id','10101')->orderby('date','desc')->first();
            $augexpense=Ledger::select('balance')->whereMonth('date',8)->where('account_id','10101')->orderby('date','desc')->first();
            $sepexpense=Ledger::select('balance')->whereMonth('date',9)->where('account_id','10101')->orderby('date','desc')->first();
            $octexpense=Ledger::select('balance')->whereMonth('date',10)->where('account_id','10101')->orderby('date','desc')->first();
            $novexpense=Ledger::select('balance')->whereMonth('date',11)->where('account_id','10101')->orderby('date','desc')->first();
            //$decexpense=Expense::whereMonth('date',12)->sum('expense_amount');
            // $topsoldproducts=DB::raw('SELECT count("product") as designcount,product_description from saleproducts leftjoin() GROUP by product DESC limit 3');
            $soldqtyquery=Product::selectRaw('SUM(saleproducts.qty) as sum')->addSelect('products.product_description')->leftjoin('saleproducts', 'saleproducts.product', 'products.product_id')->groupBy('saleproducts.product')->orderby('saleproducts.qty', 'DESC')->limit(3)->get();
            // return $soldqtyquery;
            $notification=Notification::where('dismiss',0)->count();
            $orderinprogressquery =DB::raw("SELECT customers.customer_name from orders LEFT JOIN customers ON customers.customer_id=orders.customer GROUP BY orders.customer ORDER BY orders.id DESC LIMIT 3");
            $orderinprogress=DB::select($orderinprogressquery);
            $orderInProcess = Sale::select('sales.id as saleid','tables.waiter_name as waiter','employees.name')->leftjoin('tables','tables.id','=','sales.table')->leftjoin('employees','employees.id','=','sales.employee')->where('sales.status',false)->orderby('sales.id','desc')->limit(3)->get();
            
            $total_sales = Sale::selectRaw('SUM(subtotal) as total')->where('date', date('d/m/Y'))->get();

            $indv_sales = Sale::selectRaw('SUM(sales.subtotal) as total')->addSelect('users.name')->leftjoin('users', 'users.id', 'sales.user_id')->where('sales.date', date('d/m/Y'))->groupby('sales.user_id')->get();
           
            
            return view('home',compact('orderinprogress','credit_sales','cash_sales','total_expenses','revenue','netprofit','total_oaccblnc','total_cashinhand','totalpayable','totalrecieveable','jansaleincome','fabsaleincome','marchsaleincome','aprilsaleincome','maysaleincome','junsaleincome','julysaleincome','augsaleincome','sepsaleincome','octsaleincome','novsaleincome','decsaleincome','janexpense','fabexpense','marchexpense','aprilexpense','mayexpense','junexpense','julyexpense','augexpense','sepexpense','octexpense','novexpense','decexpense','soldqtyquery','notification','totalcos','orderInProcess','total_sales','indv_sales'));
    }
    public function orderInProgress(){
        $orderInProcess = Sale::select('sales.id as saleid','tables.waiter_name as waiter','employees.name')->leftjoin('tables','tables.id','=','sales.table')->leftjoin('employees','employees.id','=','sales.employee')->where('sales.status',false)->orderby('sales.id','desc')->get();
        return view('orderinprogress',compact('orderInProcess'));
    }
    public function notification(){
         $notification=Notification::where('dismiss',0)->orderby('id','desc')->paginate(30);
        return view('notifications',['notification'=>$notification]);
    }

    public function dismiss($notification_id){
        $update=Notification::where('id',$notification_id)->update(['dismiss'=>true]);
        if($update){
            return redirect('notification');
        }
    }

    public function backup(){
        define("DB_HOST", "localhost");
        define("DB_USERNAME", env('DB_USERNAME'));
        define("DB_PASSWORD", env('DB_PASSWORD'));
        define("DB_NAME", env('DB_DATABASE'));
        $con = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die ("Error : ");
        if(mysqli_connect_errno($con)) {
          echo "Failed to connect MySQL: " .mysqli_connect_error();
        } else {
          //If you want to export or backup the whole database then leave the $table variable as it is
          //If you want to export or backup few table then mention the names of the tables within the $table array like below
          //eg, $tables = array("wp_commentmeta", "wp_comments", "wp_options");
          $tables = array();
            $backup_file_name = DB_NAME.".sql";
            $this->backup_database($con, $tables, $backup_file_name);
          }
         }

    public function backup_database($con, $tables = "", $backup_file_name) {
          if(empty($tables)) {
            $tables_in_database = mysqli_query($con, "SHOW TABLES");
            if(mysqli_num_rows($tables_in_database) > 0) {
              while($row = mysqli_fetch_row($tables_in_database)) {
                array_push($tables, $row[0]);
              }
            } 
          } else {
            // Checking for any table that doesn't exists in the database
            $existed_tables = array();
            foreach($tables as $table) {
              if(mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE '".$table."'")) == 1) {
                array_push($existed_tables, $table);
              }
            }
            $tables = $existed_tables;
          }
          $contents = "--\n-- Database: `".DB_NAME."`\n--\n-- --------------------------------------------------------\n\n\n\n";
          foreach($tables as $table) {
            $result        = mysqli_query($con, "SELECT * FROM ".$table);
            $no_of_columns = mysqli_num_fields($result);
            $no_of_rows    = mysqli_num_rows($result);
            //Get the query for table creation
            $table_query     = mysqli_query($con, "SHOW CREATE TABLE ".$table);
            $table_query_res = mysqli_fetch_row($table_query);
            $contents .= "--\n-- Table structure for table `".$table."`\n--\n\n";
            $contents .= $table_query_res[1].";\n\n\n\n";
            /**
             *  $insert_limit -> Limits the number of row insertion in a single INSERT query. 
             *           Maximum 100 rowswe will insert in a single INSERT query.
             *  $insert_count -> Counts the number of rows are added to the INSERT query. 
             *                   When it will reach the insert limit it will set to 0 again.
             *  $total_count  -> Counts the overall number of rows are added to the INSERT query of a single table.
             */
            $insert_limit = 100;
            $insert_count = 0;
            $total_count  = 0;
            while($result_row = mysqli_fetch_row($result)) {
              /**
               * For the first time when $insert_count is 0 and when $insert_count reached the $insert_limit 
               * and again set to 0 this if condition will execute and append the INSERT query in the sql file. 
               */
              if($insert_count == 0) {
                $contents .= "--\n-- Dumping data for table `".$table."`\n--\n\n";
                $contents .= "INSERT INTO ".$table." VALUES ";
              }
              //Values part of an INSERT query will start from here eg. ("1","mitrajit","India"),
              $insert_query = "";
              $contents .= "\n(";
              for($j=0; $j<$no_of_columns; $j++) {
                //Replace any "\n" with "\\n" escape character.
                //addslashes() function adds escape character to any double quote or single quote eg, \" or \'
                $insert_query .= "'".str_replace("\n","\\n", addslashes($result_row[$j]))."',";
              }
              //Remove the last unwanted comma (,) from the query.
              $insert_query = substr($insert_query, 0, -1)."),";
              /*
               *  If $insert_count reached to the insert limit of a single INSERT query
               *  or $insert count reached to the number of total rows of a table
               *  or overall total count reached to the number of total rows of a table
               *  this if condition will exceute.
               */
              if($insert_count == ($insert_limit-1) || $insert_count == ($no_of_rows-1) || $total_count == ($no_of_rows-1)) {
                //Remove the last unwanted comma (,) from the query and append a semicolon (;) to it
                $contents .= substr($insert_query, 0, -1);
                $contents .= ";\n\n\n\n";
                $insert_count = 0;
              } else {
                $contents .= $insert_query;
                $insert_count++;
              }
              $total_count++;        
            }  
          }
          //Set the HTTP header of the page.
            header('Content-Type: application/octet-stream');   
            header("Content-Transfer-Encoding: Binary"); 
            header("Content-disposition: attachment; filename=\"".$backup_file_name."\"");  
            echo $contents; exit;
        }

    public function bill_check_update(Request $request){

      Session::put('bill_check', $request->print_value);
      return Session::get('bill_check');
    }
        
}
