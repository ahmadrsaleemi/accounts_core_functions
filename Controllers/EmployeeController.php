<?php

namespace App\Http\Controllers;

use App\Employee;
use Illuminate\Http\Request;
use App\Attendence;
use App\Machin;
use App\Machinelog;
use App\Stich;
use App\Customer;
use App\Stichinvoice;
use App\Sale;
use App\Employeeadvance;
use App\Chartofaccount;
use Auth;
use App\Design;
use App\Designdetail;
use App\Stitchinvdesign;
use App\Ledger;
use DB;
use App\Tissue;
use App\Payroll;
use App\Employeeactivity;
use App\Saleproduct;
use App\Inventory;
use App\Bill_product;
use App\Product;
class EmployeeController extends Ledgerfunctions
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
    
    
    public function index()
    {
        $employees=Employee::get();
        
        return view('employees/view',compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('employees/add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    $check_duplicate=Employee::where('name','=',$request->name)->first();
        if($check_duplicate != null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'Employee With The Name Already Available');
            return redirect('employee/add');
        }
        else{
                $employees=new Employee;
                $employees->name=$request->name;
                $employees->salary=$request->salary;
                $employees->shift='day';
                $employees->phone=$request->phone;
                $employees->address=$request->address;
                $employees->id_card=$request->id_card;
                $employees->salary_type=$request->salary_type;
                // $employees->rate = $request->rate;
                // if(isset($request->mm) && $request->mm != ""){
                // $employees->mm=true;
                // }
                // else{
                //     $employees->mm=false;
                // }
                $employees->type=$request->type;
                if($employees->save()){
                $employee_id=Employee::orderby('id','desc')->first();
                // //expense chart of account
                // $this->employee_chart_of_account('expense_employee_'.$employee_id->id,$request->name,$request->name."expense_account",5);
                //liability chart of account 
                $this->employee_chart_of_account('liability_employee_'.$employee_id->id,$request->name,$request->name."liability_account",2);
                    $request->session()->flash('message.level', 'success');
                    $request->session()->flash('message.content', 'New employee Has Been Added');
                    
                } 
            return redirect('employee/add');
        }
        
    }
    public function employee_chart_of_account($account_id,$account_title,$account_description,$accounttype){
        $chartofaccount=new Chartofaccount();
        $chartofaccount->coa_id=$account_id;
        $chartofaccount->coa_title=$account_title;
        $chartofaccount->account_type=$accounttype;
        $chartofaccount->coa_description=$account_description;
        $chartofaccount->user_id=Auth::id();
        if($chartofaccount->save()){
            app('App\Http\Controllers\ChartofaccountController')->log_chartofaccount($account_id,$account_title,$account_description,5);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function show($employee)
    {
        $employee =employee::where('id',$employee)->get();
        return view('employees/edit',['employees'=>$employee]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    { 
        $update=Employee::where('id', $request->empid)
            ->update(['name'=>$request->name,'salary' => $request->salary,'type'=>$request->type,'shift'=>$request->shift,'phone' => $request->phone, 'address' => $request->address, 'id_card' => $request->id_card, 'salary_type' => $request->salary_type]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Employee Details successfully Updated!');
        }
        return redirect('employee/view/'.$request->empid);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function destroy($employee)
    {
        Employee::where('id',$employee)->delete();
        return redirect('employee/view');
    }


    public function showattendenceform()
    {
        return view('employees/attendenceform');
    }

    public function showattenedce(Request $request){
       $employees=Employee::all();

       return $employees;
    }

    public function showattenedcebydate(Request $request){
        return view('employees/allattendences');
    }
    public function allattendence(Request $request){
       
        return Attendence::select('attendences.*','employees.name')->Leftjoin('employees','attendences.empid','=','employees.id')->where('date',$request->date)->get();
    }

    public function updateattendecs($employee,$date,$status){
        if($status == 1){
             $update = Attendence::where('empid',$employee)->whereDate('date',$date)->update(['attendence'=>false]);
             $this->reversestorestitchunitledger($employee,$date);
            
        }
        else if($status == 0){
           $update = Attendence::where('empid',$employee)->whereDate('date',$date)->update(['attendence'=>true]); 
           $this->storestitchunitledger($employee,$date);
           
        }
        return redirect('employee/showattenedcebydate');
        
    }
    public function alotmachine(){
         return view('employees/allocatemachin');
    }
    public function showmachine(Request $request){
        $employees=Employee::select('employees.*','machines.machineno')->where('shift',$request->shift)->leftjoin('machines','employees.id','=','machines.empid')->get();
       return $employees;
    }

    
        public function savemachin(Request $request){
            $checkduplicate=Machine::where('empid',$request->empid)->count();
            if($checkduplicate > 0){
                Machine::where('empid',$request->empid)->update(['machineno'=>$request->machine]);
            }
            else{
                $m=new Machine;
                $m->empid=$request->empid;
                $m->machineno=$request->machine;
                $m->date=$request->date;
                $m->save();
            }
            $this->machinelog($request->empid,$request->machine,$request->date);
        }

    public function machinelog($empid,$machine,$date){
        $mlog=new Machinelog;
        $mlog->empid=$empid;
        $mlog->machineno=$machine;
        $mlog->date=$date;
        $mlog->save();
    }
    
    public function stichesform(){
        $machins=Machin::all();
        return view('employees/stichesform',compact('machins'));
    }

    public function editstitchform(){
        $machins=Machin::all();
        $employees = Employee::all();
        return view('employees/editstitchesform',compact('machins','employees'));
    }

    public function employeestitches(Request $request){
        $date = $request->date;
        $query=DB::raw('SELECT stiches.id as sid,stiches.created_at, stiches.emp_1, stiches.emp_2, stiches.emp_3, stiches.machine,stiches.stiches ,emp1.name as emp1name,emp2.name as emp2name,emp3.name as emp3name FROM stiches LEFT JOIN employees emp1 ON (emp1.id = stiches.emp_1) LEFT JOIN employees emp2 ON (emp2.id = stiches.emp_2) LEFT JOIN employees emp3 ON (emp3.id = stiches.emp_3) WHERE stiches.date ="'.$date.'"');
        $stitches =DB::select($query);
        return $stitches;
        //echo json_encode(array($date));

    }

    public function updatestitch(Request $request){
        $date = $request->date;
        $employee = $request->employee;
        for ($i=0; $i < count($request->machin); $i++) { 
         Stich::where('id',$request->rec_id[$i])->update(['stiches'=>$request->stitch[$i]]);
         }
         $request->session()->flash('message.level', 'success');
                    $request->session()->flash('message.content', 'Stitches Updated successfully');
        return redirect('employees/editstitchform');
    }
    public function empmachin(Request $request){
        $empmachine=Machine::where('empid',$request->emp)->first();
        echo $empmachine->machineno;
    }
    public function employeesbyshift(Request $request){
        if($request->shift == 1){
            $employees =Employee::where('shift','day')->where('mm',true)->get();
        }
        if($request->shift == 2){
            $employees =Employee::where('shift','night')->get();
        }
        return $employees;
    }
    public $salaryamount=0;
    public $salaryAmountOfAbsentEmployee=0;
    public function storestich(Request $request)
    {
     $presentEmployees =[];
     $absentEmployees ="";
     if(isset($request->stitches) && $request->stitches != ""){
     for ($i=0; $i < count($request->machin); $i++) {
        if($request->employee_one[$i] != "" || $request->employee_two[$i] != "" || $request->employee_three[$i] != "" )
            {
                $stiches=new Stich;
                
                if($request->employee_one[$i] != ""){
                $stiches->emp_1 = $request->employee_one[$i];
                $presentEmployees[]=$request->employee_one[$i];
                }
                else{
                     $stiches->emp_1=0;
                }
                if($request->employee_two[$i] != ""){
                $stiches->emp_2 = $request->employee_two[$i];
                $presentEmployees[]=$request->employee_two[$i];
                }
                else{
                     $stiches->emp_2 =0;
                }
                if($request->employee_three[$i] != ""){
                $stiches->emp_3 = $request->employee_three[$i];
                $presentEmployees[]=$request->employee_three[$i];
                
                }
                else{
                     $stiches->emp_3 =0;
                }
               
                if($request->stitches[$i] > 0){
                $stiches->emp_4 =00;
                $stiches->date=$request->date;
                $stiches->machine=$request->machin[$i];
                $stiches->stiches=$request->stitches[$i];
                $stiches->save();
                }
                if($request->employee_one[$i] != ""){
                $this->doattendence($request->employee_one[$i],$request->date);
                $this->storestitchunitledger($request->employee_one[$i],$request->stitches[$i],$request->date);
                }
                if($request->employee_two[$i] != ""){
                $this->doattendence($request->employee_two[$i],$request->date);
                $this->storestitchunitledger($request->employee_two[$i],$request->stitches[$i],$request->date);
               }
               if($request->employee_three[$i] != ""){
                $this->doattendence($request->employee_three[$i],$request->date);
                $this->storestitchunitledger($request->employee_three[$i],$request->stitches[$i],$request->date);
               }
                
            }
               // echo $request->stitches[$i]."<br>";
         
       $request->session()->flash('message.level', 'success');
       $request->session()->flash('message.content', 'Employee Stitches Has Been Added');
     }
     if($this->salaryamount > 0)
        $this->expensledger('salary101',5,"expense",$this->salaryamount,null,10,1,$request->date);
        sleep(1);
    
       $absentEmployees =  Employee::select('id')->whereNotIn('id',$presentEmployees)->where('mm',1)->get();
        $this->storeAbsentEmployeesLedger($absentEmployees,$request->date);
       return redirect('employee/stichesform');
      }
    }
    public function doattendence($employee,$date){
        $dt1 = strtotime($date);
        $dt2 = date("l", $dt1);
        $dt3 = strtolower($dt2);
        $checkduplicate=Attendence::where('empid',$employee)->where('date',$date)->count();
        if($checkduplicate == null){
            $attendence=new Attendence;
            $attendence->empid=$employee;
            $attendence->attendence=true;
            
            $attendence->date=$date;
            if($dt3 == "friday"){
            $attendence->overtime=true;
            }
            else{
                $attendence->overtime=false;
            }
            $attendence->save();
            }
        }
        
        function storeAbsentEmployeesLedger($absentEmployees,$date){
            $countofdays=date('t',strtotime($date));
            $salaryamount = 0;
            foreach($absentEmployees as $aemp){
                $salary = Employee::where('id',$aemp->id)->first();
                $amounttobeentered=$salary->salary/$countofdays*0.5;
                $salaryamount  +=$amounttobeentered;
                $this->liabilityledger('liability_employee_'.$aemp->id,5,"liabilities",$amounttobeentered,null,10,$aemp->id,$date);
            }
            
            if($salaryamount > 0)
                $this->expensledger('salary101',5,"expense",null,$salaryamount,10,1,$date);
            
        }
        public function storestitchunitledger($employee,$date){
         $salary = Employee::where('id',$employee)->first();
         $countofdays=date('t',strtotime($date));
        //  $countofdays=$this->countDays(date('Y'),date('m',strtotime($date)), array(7, 5));
         // $d=cal_days_in_month(CAL_GREGORIAN,date('m',strtotime($date)),date('Y',strtotime($date)));
         // $onedaysalary=$salary->salary/$d;
         // $workingdayssalary=$countofdays*$onedaysalary;
         $dt1 = strtotime($date);
         $dt2 = date("l", $dt1);
         $dt3 = strtolower($dt2);
         $amounttobeentered=($salary->salary/$countofdays);
         $this->salaryamount +=$amounttobeentered;
         $this->employeeActivity($employee,0,1,$date,$amounttobeentered);
         $this->expensledger('salary101',5,"expense",$amounttobeentered,null,10,$employee,$date);
         $this->liabilityledger('liability_employee_'.$employee,5,"liabilities",null,$amounttobeentered,10,$employee,$date);
         // echo 'Whole Days Salary '.$employee.'  '.$salary->salary."<br>";
    
         // echo 'Working Days Salary'.$employee.'  '.$workingdayssalary."<br>";
        }
        public function doattendencefromattendenceform(Request $request){
        $dt1 = strtotime($request->date);
        $dt2 = date("l", $dt1);
        $dt3 = strtolower($dt2);
        $attend='';
        for ($i=0; $i < count($request->empid); $i++) { 
            $checkduplicate=Attendence::where('empid',$request->empid[$i])->where('date',$request->date)->count();
            if($checkduplicate == null){
                $attendence=new Attendence;
                $attendence->empid=$request->empid[$i];
                if(isset($request->attend[$i])){
                    $attendence->attendence=1;
                    $attend=1;
                }
                else
                {
                   $attendence->attendence=0;
                   $attend=0;
                }
                $attendence->overtime=false;
                $attendence->date=$request->date;
                $attendence->save();
                
            }
            $this->storestitchunitledgerfromattendenceform($request->empid[$i],$request->date,$attend);
        }
        if($this->salaryamount != 0 )
            $this->expensledger('salary101',5,"expense",$this->salaryamount,null,10,1,$request->date);
        }
    
    public function reversestorestitchunitledger($employee,$date){
     $salary = Employee::where('id',$employee)->first();
    //  $countofdays=$this->countDays(2018,date('m',strtotime($date)), array(7, 5));
    $countofdays=date('t',strtotime($date));
     // $d=cal_days_in_month(CAL_GREGORIAN,date('m',strtotime($date)),date('Y',strtotime($date)));
     // $onedaysalary=$salary->salary/$d;
     // $workingdayssalary=$countofdays*$onedaysalary;
     $dt1 = strtotime($date);
     $dt2 = date("l", $dt1);
     $dt3 = strtolower($dt2);
     if($dt3 == "friday"){
     $amounttobeentered=$salary->salary/$countofdays*1.5;
     }
     else{
        $amounttobeentered=($salary->salary/$countofdays);
     }
     $this->expensledger('salary101',5,"expense",null,$amounttobeentered,10,$employee,$date);
     $this->liabilityledger('liability_employee_'.$employee,5,"liabilities",$amounttobeentered,null,10,$employee,$date);
     // echo 'Whole Days Salary '.$employee.'  '.$salary->salary."<br>";

     // echo 'Working Days Salary'.$employee.'  '.$workingdayssalary."<br>";
    }

   

    public function storestitchunitledgerfromattendenceform($employee,$date,$attend){
     $salary = Employee::where('id',$employee)->first();
    //  $countofdays=$this->countDays(2018,date('m',strtotime($date)), array(7, 5));
     $countofdays=date('t',strtotime($date));
     $amounttobeentered=($salary->salary/$countofdays);
     if($attend == 1){
        $this->employeeActivity($employee,0,1,$date,$amounttobeentered);
        $this->salaryamount =$this->salaryamount+$amounttobeentered;
        $this->liabilityledger('liability_employee_'.$employee,5,"liabilities",null,$amounttobeentered,10,$employee,$date);
     }
     else{
        $this->employeeActivity($employee,0,0,$date,$amounttobeentered);
     }
     
    }

    public function stichingunit(){
        $customers=Customer::all();
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='accounts_stitch' AND TABLE_NAME ='stichinvs'");
        $designs=Design::all();
        return view('employees/stichingunit',compact('customers','auto_increment','designs'));
    }

    public function getdesigndetails(Request $request){
         echo json_encode(Designdetail::where('designcode',$request->design)->where('no_stitch','!=',0)->get());

    }
    public $design="";
    public function stichingbill(Request $request){

        if(isset($request->tissue)){
            $tissue = new Tissue;
            $tissue->sale_id=$request->inv_no;
            $tissue->discription=$request->tissue;
            $tissue->length=$request->price;
            $tissue->price=$request->qty;
            $tissue->save();
        }

        $stichinv=new Stichinv;
        $stichinv->id=$request->inv_no;
        $stichinv->customer_id=$request->customer;
        $stichinv->date=$request->date;
        $stichinv->total=$request->ftotal;
        $stichinv->user_id=Auth::id();
        if($stichinv->save()){
        $this->credit_sale_ladger('customer_'.$request->customer,$request->ftotal,11,$request->inv_no,$request->date);
        $this->sale_ledger($request->ftotal,$request->inv_no,$request->date);
        if(isset($request->desgin)){
                for($i=0;$i < count($request->desgin);$i++){
                   $design=$request->desgin[$i];
                   $desins=new Stitchinvdesign;
                   $desins->inv_id=$request->inv_no;
                   $desins->design=$request->desgin[$i];
                   $desins->save();
                   
               }
           }
        if(isset($request->description) && count($request->description) > 0){
                    for($j=0;$j < count($request->description);$j++){
                        $stichinvoice = new Stichinvoice;
                        $stichinvoice->inv_id=$request->inv_no;
                        $stichinvoice->description=$request->description[$j];
                        $stichinvoice->no_stitch=$request->stitch[$j];
                        $stichinvoice->amount=$request->amount[$j];
                        $stichinvoice->lenght=$request->length[$j];
                        $stichinvoice->design=$request->linkeddesign[$j];
                        $stichinvoice->total=$request->inlinetotal[$j];
                        $stichinvoice->save();
                    }      
                }
                  
            
        }
       
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'Invoice Genrated');
        echo "<script>window.open('alert:id=".$request->inv_no."|1', '_blank')</script>";

         echo "<script>window.open('http://premierdairy.pk/stitch/public/employee/stichingunit','self')</script>";
        //return $this->stichingunit();
    }


    //storing credit sale ladger 
    public function credit_sale_ladger($account_id,$debit_ammount,$refrenceid,$transectiontype,$date){

        $this->assetledger($account_id,1,"assets",$debit_ammount,null,11,$refrenceid,$date);
    }

     public function sale_ledger($total,$refrenceid,$date){
        $this->incomeledger(10101,4,"income",null,$total,11,$refrenceid,$date);
    }


    public function customer_invoices(Request $request){
        // return $request->customer_id;
        $sales=Sale::where('customer',$request->customer_id)->get();
        return $sales;
    }


    public function stich_invoice(Request $request){
        $sales=Sale::where('id',$request->sale_invoice_number)->first();
        //echo "working";
        echo json_encode(array($sales->date,$sales->subtotal));
    }

    public function stich_items(Request $request){
         $saleproducts=Saleproduct::select('saleproducts.*', 'bill_products.cost_price')->leftjoin('bill_products', 'bill_products.product_id', 'saleproducts.product')->where('saleproducts.inv_id',$request->sale_id)->GROUPBY('saleproducts.id')->get();
         return $saleproducts;
    }


    public function returnsale(Request $request){


        // $this->assetledger('customer_'.$request->cus,1,"assets",null,$request->total,4,$request->inv,$request->date);
        // $this->incomeledger(10101,4,"income",$request->check_total,null,2,1,$request->date);

        
        

                // }

           $items_data = '';
           $total_qty = 0; 
           $arr_productid = $request->product;
           $arr_productid[]=$request->product1;
           $arr_productdes = $request->pro_description;
           $arr_productdes[]=$request->pro_description1;
           $arr_productprice = $request->price;
           $arr_productprice[] =$request->price1;
           $arr_productqty = $request->qty;
           $arr_productqty[] = $request->qty1;
           $arr_productbox = $request->s_price;
           $arr_productbox[] = $request->s_price1;
           $arr_inlintotal=$request->linetotal;
           $arr_inlintotal[] = $request->linetotal1;

        for ($i=0; $i < count($arr_productid); $i++) { 
            
            // return count($arr_productid);
            // $product_name = ''; 
            // $product = Product::where('barcode', $arr_productid[$i])->first();
            // $product_name = $product->product_description;
            
            // if($i != count($arr_productid) - 1){
            //             $items_data.='{"Description":"'.$product_name.'","Price":"'.$request->price.'","Quantity":"'.$request->qty.'","Amount":"'.$request->price * $request->qty.'"},';
            //             $total_qty += $request->qty; 
            //         }else{

             if( $i != count($arr_productid) - 1){
                    $items_data.='{"Description":"'.$arr_productdes[$i].'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"},';
                    $total_qty += $arr_productqty[$i]; 
                }else{
                    $items_data.='{"Description":"'.$arr_productdes[$i].'","Price":"'.$arr_productprice[$i].'","Quantity":"'.$arr_productqty[$i].'","Amount":"'.$arr_inlintotal[$i].'"}';
                    $total_qty += $arr_productqty[$i]; 

                }

            $supplier_id = 0;
            $supplier_cost = 0;
            $bill = 0;
            $supplier_data = Inventory::where('inv_product_id', $arr_productid[$i])->orderby('created_at', 'DESC')->first(); 
            $refrence_id = 1;
            $supplier_id = $supplier_data->inv_supplier_id;
            $supplier_cost = $supplier_data->inv_cost_price;
            $bill = $supplier_data->inv_bill_id;
            $_costofsale = 0;
            $this->cost_of_product($arr_productid[$i],$arr_productqty[$i],$refrence_id,$arr_inlintotal[$i],$_costofsale,$request->date,$request->date_bit);

            
            
            // if(strlen($arr_productid[$i]) <= 4){
            // $this->assetledger('recipe_'.$arr_productid[$i],1,"assets",$_costofsale * $arr_productqty[$i],null,4,1,$request->date);
            // }else{
            // $this->assetledger('product_'.$arr_productid[$i],1,"assets",$_costofsale * $arr_productqty[$i],null,4,1,$request->date);
            // }
            $this->cost_sale_ledger($_costofsale * $arr_productqty[$i],$refrence_id,$request->date,$request->date_bit);

            // if($supplier_id != 0){
            //     $this->Addinventory($arr_productid[$i],$bill,$supplier_id,$supplier_cost,$arr_productqty[$i],$request->date);
            // }else{
            //     $this->Addinventory($arr_productid[$i],$bill,"Return Sale",$supplier_cost,$arr_productqty[$i],$request->date);
                $this->update_inventory($arr_productid[$i],$arr_productqty[$i],$refrence_id,$request->total,$_costofsale,$request->date,$request->date_bit);
            // }
            
          

        // $costprice = 0;
        // for($i=0; $i<count($arr_productid); $i++){

        //     $this->Addinventory($arr_productid[$i],1,"Return Sale",$request->sale_price[$i],$request->return_qty[$i],$request->date);

        //     $this->assetledger('product_'.$arr_productid[$i],1,"assets",$request->cost_price[$i]*$request->return_qty[$i],null,1,1,$request->date);

        //     $costprice += $request->cost_price[$i]*$request->return_qty[$i];
        // }

          // $this->expensledger(10102,5,"expense",null,$costprice,4,1,$request->date);
    }
        $this->sale_ledger_return($request->total,$refrence_id,$request->txt_date,$request->date_bit);
        $this->assetledger($request->coa,1,"assets",null,$request->total,4,1,$request->date);
        $json = '{"TemplateID":3,"ProductCount":"1","Date":"'.Date('d/m/Y').'","User":"'.Auth::user()->name.'","Items":['.$items_data.'],"total_qty":"'.$total_qty.'","total":"'.$request->total.'"}';
        $request->session()->flash('message.level', 'success');
        $request->session()->flash('message.content', 'Sale Returned');
        return $json;
        return redirect('employees/returnsaleview');
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
         $inventory=Inventory::where('inv_product_id',$product_id)->orderby('updated_at', 'DESC')->first();
         // foreach ($inventories as $inventory) {
            //storing inventory id in $inventory_id
             // $inventory_id= $inventory->id;
            //checking if sold item quantot is less then qty available in inventory agaainst that product
          if($inventory->inv_qty > $qty && $qty != 0 || $inventory->inv_qty == $qty && $qty != 0){
                //updated qty
                $final_qty=$inventory->inv_qty + $input_qty;
                //updated inventory of particular product with final_qty in below query
                $dbcp=$inventory->inv_cost_price;
                DB::table('inventories')->where('id',$inventory->id)->update(['inv_qty'=>$final_qty,'updated_at'=>$updated_at]);

                //empting sold product qty
                $qty=$qty-$qty;
               
            }
             //checking if sold item quantot is greater then qty available in inventory agaainst that product
            elseif($inventory->inv_qty < $qty) {
                //updated qty
                  $final_qty=$inventory->inv_qty + $input_qty;
                  
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
                
              // }
              //echo $wholeqty."<br>";
          // if($input_qty > $wholeqty){
          //   $minus_quantity=$wholeqty-$input_qty;
          //   $last_record=DB::table('inventories')->where('inv_product_id',$product_id)->orderby('id', 'desc')->first();

          //       //updated inventory of particular product with $minus_quantity in below query
          //     DB::table('inventories')->where('id',$last_record->id)->update(['inv_qty'=>$input_qty,'updated_at'=>$updated_at]);
          //   }
            
        // //calculating final price
        $fin =$res_price+($input_qty-$originalqty)*$dbcp;
            $_costofsale += $dbcp * $input_qty;
            // return $dbcp;
        //   //echo $fin;
        // //storing inventory ledger by calling inventoryledger function
            if(strlen($product_id) <= 4){
                 $this->assetledger("recipe_".$product_id,1,"assets",$dbcp * $input_qty,null,2,$refrence_id,$date);
            }else{
                $this->inventoryledger("product_".$product_id,$dbcp * $input_qty,$refrence_id,$date,$datebit);
            }
        
        
    }
    public function inventoryledger($account_id,$credit_ammount,$refrenceid,$date,$datebit){
        $this->assetledger($account_id,1,"assets",$credit_ammount,null,2,$refrenceid,$date);
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
    //storing credit sale ladger 
    public function credit_sale_ladger_return($account_id,$credit_ammount,$refrenceid,$transectiontype,$date){

        $this->assetledger($account_id,1,"assets",null,$credit_ammount,$transectiontype,$refrenceid,$date);
    }

     public function sale_ledger_return($total,$refrenceid,$date){
        $this->incomeledger(10101,4,"income",$total,null,2,$refrenceid,$date);
    }

    
    public function returnsaleview(){
        $products =  Product::where('products.sellable','=',1)->get();
        $customers=Customer::all();
        // $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();

        $chartofaccounts = Chartofaccount::where('account_type', 1)->get();

        return view('employees/returnsale',['products'=>$products,'customers'=>$customers,'chartofaccounts'=>$chartofaccounts]);
    }

    public function showadvanceform(){
        //chart of accounts from cprelation table 
        $chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
        $employees=Employee::get();
        return view('employees/advance',compact('employees','chartofaccounts'));
    }

    public function storeadvance(Request $request){
        $advance=new Employeeadvance;
         $advance->employee_id=$request->emp;
         $advance->date=$request->date." ".date('H:i:s');
         $advance->amount=$request->advance;
         $advance->coa=$request->coa;
         if($advance->save()){
            $refrenceid=Employeeadvance::orderby('id','desc')->first();
            $this->storadvanceledger('expense_employee_'.$request->emp,5,$request->advance,$refrenceid->id,10,$request->date,$request->coa);
            $request->session()->flash('message.level','success');
            $request->session()->flash('message.content','Employee Advance Saved!');
            return redirect('employee/advance');
         }

    }
    public function storadvanceledger($employee_id,$acc_id,$advance,$refrenceid,$transectiontype,$date,$fromaccount){

        $this->assetledger($fromaccount,1,"assets",Null,$advance,$transectiontype,$refrenceid,$date);
        $this->expensledger($employee_id,5,"expense",$advance,null,$transectiontype,$refrenceid,$date);
    }
   public function employeepayslip(){
        $countofdays=$this->countDays(date('y'),date('m'), array([]));
       // $countofdays=date('t');
        $employees=Employee::get();
        return view('employees/payslip',compact('employees','countofdays'));
    }

    function countDays($year, $month, $ignore) {
    $count = 0;
    $counter = mktime(0, 0, 0, $month, 1, $year);
        while (date("n", $counter) == $month) {
            if (in_array(date("w", $counter), $ignore) == false) {
                $count++;
            }
            $counter = strtotime("+1 day", $counter);
        }
        return $count;
    }


    public function employeepaydetails(Request $request){
        $data = '';
        $countofdays=$this->countDays(date('y'),$request->month, array([]));
        $employees =Employee::select('name','salary','id')->get();
        $recordCount = count($employees);
        $count =1;
        $data .='[';
        foreach ($employees as $value) {
        $data .='{"name":"'.$value->name.'","salary":'.$value->salary.',"id":'.$value->id.',';

        $bonuses=DB::table('bonuses')->whereRaw('MONTH(bonuses.created_at) = ?',[$request->month])->where('emp',$value->id)->sum('bonus');
            
        $data .='"bonus":'.$bonuses.',';

        $presents=Attendence::whereRaw('MONTH(attendences.date) = ?',[$request->month])->where('empid',$value->id)->sum('attendence');

        $data .='"attendence":'.$presents.',';

        $overtimes=Attendence::whereRaw('MONTH(attendences.date) = ?',[$request->month])->where('empid',$value->id)->sum('overtime');

        $data .='"overtime":'.$overtimes.',';
        
        $advance =DB::table('payrolladvances')->whereRaw('MONTH(payrolladvances.created_at) = ?',[$request->month])->where('employee_id',$value->id)->sum('amount');

        $data .='"advances":'.$advance.'';
        

        $liability = Ledger::where('account_id','liability_employee_'.$value->id)->orderby('date','DESC')->first();
        //return $liability;
        if($liability != null)
            $data .=',"liability":'.$liability->balance.'';
        else
        $data .=',"liability":0';
        
        $data .="}";
        if($count < $recordCount)
            $data .=",";
        $count++;
        
        }
        $data .=']';
        echo $data;
        // echo json_encode(array($countofdays,$employees,$bonuses,$presents,$advance,$duesalary));
        
    }

    public function viewstitchinv(){
        $sales=Stichinv::select('stichinvs.*','customers.customer_name','users.name')->leftjoin('customers','customers.customer_id','=','stichinvs.customer_id')->leftjoin('users','users.id','=','stichinvs.user_id')->get();
        return view('employees/viewstitchinv',['sales'=>$sales]);
    }

    public function getcustomerbalance(Request $request){

        $sbal=Ledger::where('account_id','customer_'.$request->customer_id)->orderby('date','desc')->first();
        if($sbal != null){
            echo $sbal->balance;
        }
        else{
            echo "null";
        }
    }
 public function cost_of_product($product_id,$qty,$refrence_id,$total, &$_costofsale,$date,$datebit){

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
                // DB::table('inventories')->where('id',$inventory->id)->update(['inv_qty'=>$final_qty,'updated_at'=>$updated_at]);

                //empting sold product qty
                $qty=$qty-$qty;
               
            }
             //checking if sold item quantot is greater then qty available in inventory agaainst that product
            elseif($inventory->inv_qty < $qty) {
                //updated qty
                  $final_qty=$inventory->inv_qty-$inventory->inv_qty;
                  
                  //updated inventory of particular product with $final_qty in below query
                 // DB::table('inventories')->where('id',$inventory->id)->update(['inv_qty'=>$final_qty,'updated_at'=>$updated_at]);

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
              // DB::table('inventories')->where('id',$last_record->id)->update(['inv_qty'=>$minus_quantity,'updated_at'=>$updated_at]);
            }
            
        // //calculating final price
        $fin =$res_price+($input_qty-$originalqty)*$dbcp;
            $_costofsale +=$dbcp;
        //   //echo $fin;
        // //storing inventory ledger by calling inventoryledger function
        // $this->inventoryledger("product_".$product_id,$fin,$refrence_id,$date,$datebit);
        
    }
    // public function employeeActivityReport(){
    //     $employees = Employee::all();
    //     return view('employees.employeeactivity',compact('employees'));
    // }

    // public function employeeactivitydata(Request $request){
    //     $employeeName = Employee::where('id',$request->employee)->first()->name;
    //     if($request->fromDate != '' && $request->toDate != '' )
    //       echo json_encode(array($employeeName,Employeeactivity::where('employee',$request->employee)->where('date','>=',$request->fromDate)->where('date','<=',$request->toDate)->get()));
    //     else
    //     echo json_encode(array($employeeName,Employeeactivity::where('employee',$request->employee)->get()));
    // }


    // public function employeeActivitySummary(){
    //     $employees = Employee::all();
    //     return view('employees.employeeactivitysummary',compact('employees'));
    // }

    // public function employeeSummaryData(Request $request){
    //     $employeeName = Employee::where('id',$request->employee)->first()->name;

    //     if($request->fromDate){
    //         $attendences =Employeeactivity::where('employee',$request->employee)->where('transectiontype',1)->where('date','>=',$request->fromDate)->sum('value');
    //         $advances =Employeeactivity::where('employee',$request->employee)->where('transectiontype',4)->where('date','>=',$request->fromDate)->sum('value');
    //         $bonuses =Employeeactivity::where('employee',$request->employee)->where('transectiontype',3)->where('date','>=',$request->fromDate)->sum('value');
    //         $cashpay =Employeeactivity::where('employee',$request->employee)->where('transectiontype',6)->where('date','>=',$request->fromDate)->sum('value');
    //         $dues =Employeeactivity::where('employee',$request->employee)->where('transectiontype',2)->where('date','>=',$request->fromDate)->sum('value');
    //         echo json_encode(array($employeeName,$attendences,$advances,$bonuses,$cashpay,$dues));
    //     }
    //     else{
    //          $attendences =Employeeactivity::where('employee',$request->employee)->where('transectiontype',1)->sum('value');
    //          $advances =Employeeactivity::where('employee',$request->employee)->where('transectiontype',4)->sum('value');
    //          $bonuses =Employeeactivity::where('employee',$request->employee)->where('transectiontype',3)->sum('value');
    //          $cashpay =Employeeactivity::where('employee',$request->employee)->where('transectiontype',6)->sum('value');
    //          $dues =Employeeactivity::where('employee',$request->employee)->where('transectiontype',2)->sum('value');
    //          echo json_encode(array($employeeName,$attendences,$advances,$bonuses,$cashpay,$dues));
    //     }
        
    // }

}
