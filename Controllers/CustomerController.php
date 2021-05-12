<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Chartofaccount;
use Illuminate\Http\Request;
use Auth;
use DB;

class CustomerController extends Controller
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
        $query=DB::raw('SELECT customers.*,users.name From customers LEFT JOIN users ON customers.user_id = users.id');
        $customers=DB::select($query);
        return view('customers/view',compact('customers'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //Auto increment to add new invoice with new id
        $test = env('DB_DATABASE');
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='customers'");
        return view('customers/add',compact('auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
         $check_duplicate=Customer::where('customer_id',$request->txt_customer_id)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Customer Already Available Please Try Changed Customer Id');
            return redirect('customer/add');
        }
        else{


        $customer=new Customer;
        $customer->customer_id=$request->txt_customer_id;
        $customer->customer_name=$request->txt_customer_name;

        if($request->txt_credit_sales !=""){
              $customer->credit_limit=$request->txt_credit_limit;
              $customer->credit_sales=true;
        }
        else{
            $customer->credit_limit=PHP_INT_MAX;
            $customer->credit_sales=false;
        }
        if($request->txt_no_credit_limit !=""){
              $customer->no_credit_limit=true;
        }
        else{
            $customer->no_credit_limit=false;
        }       
        
        $customer->user_id=Auth::id();
        $customer->contact_number=$request->cono;
        $customer->email=$request->email;
        $customer->address=$request->customer_address;
        
        if($customer->save()){
            $this->customer_chart_of_account('customer_'.$request->txt_customer_id,'customer_'.$request->txt_customer_name,'customer_'.$request->txt_customer_name);
            
        }
        if(isset($request->ajax)){
            echo "customer Saved";
        }
        else{
                $request->session()->flash('message.level', 'success');
                $request->session()->flash('message.content', 'New Customer was successfully added!');
                return redirect('customer/add');
        }
        
        }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show($customer)
    {
        $customers =Customer::where('id',$customer)->get();
        return view('customers/edit',compact('customers'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request,[
            'txt_customer_id'=>'required',
            'txt_customer_name'=>'required',
            'txt_credit_limit'=>'required'
        ]);
        $update_customer=Customer::where('id',$request->rec_id)->update([
            'contact_number'=>$request->cono,
            'address'=>$request->customer_address,
            'customer_id'=>$request->txt_customer_id,
            'customer_name'=>$request->txt_customer_name,
            'credit_limit'=>$request->txt_credit_limit,
            'credit_sales'=>true,
            'no_credit_limit'=>false,
            'email' => $request->email,
        ]);
        // $update_customer=Customer::where('id',$request->rec_id)->update(['customer_id'=>$request->txt_customer_id,'customer_name'=>$request->txt_customer_name,'credit_limit'=>$request->txt_credit_limit,'credit_sales'=>true,'no_credit_limit'=>false,'rate'=>$request->rate]);
        if($update_customer){
             $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Customer was updated added!');
        }
        return redirect('customer/show/'.$request->rec_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy($customer)
    {
        Customer::where('id',$customer)->delete();
        return redirect('customer/show');
    }

     public function customer_chart_of_account($account_id,$account_title,$account_description)
        {
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


    public function getrate(Request $request){
        return Customer::where('customer_id',$request->customerid)->pluck('rate');
        // echo $request->customerid;
    }  
    public function allcustomers(Request $request){
        $customers=Customer::orderby('created_at', 'DESC')->first();

        // echo"<option value=''>Select</option>";
        // foreach ($customers as $customer) {
        return array($customers->customer_id, $customers->customer_name);
            // echo "<option value=".$customers->customer_id." selected>".$customers->customer_name."</option>";
        // }
    } 
    public function add_customer_modal(){
        $test = env('DB_DATABASE');
        $auto_increment_customer=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='customers'");
        $id  = 0;
        foreach ($auto_increment_customer as $ai) {
            $id = $ai->AUTO_INCREMENT;
        }

        echo json_encode($id);
    }
}
