<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Employee;
use DB;
use App\Payroll;
use App\Payrolladvance;
use App\Ledger;
use App\Bonus;
class PayrollController extends Ledgerfunctions
{
	public function index(){
    	//chart of accounts from cprelation table 
		$chartofaccounts=DB::table('cprelations')->select('cprelations.*','chartofaccounts.account_type')->leftjoin('chartofaccounts','chartofaccounts.coa_id','cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();
		$employees=Employee::get();
		$countofdays=date('t');
		return view('payroll/add',compact('employees','countofdays','chartofaccounts'));
	}

	public function savepayroll(Request $request){
		$employeeType = Employee::where('id',$request->emp)->first()->mm;
		$payroll = new Payroll;
		$payroll->employee_id =$request->emp;
		$payroll->salary= $request->salary;
		$payroll->liability= $request->liability;
		$payroll->advance =$request->advancetaken;
		$payroll->adv_adjustment = $request->adadv;
		//$this->employeeActivity($request->emp,$employeeType,5,date('20y-m-d'),$request->adadv);
		$payroll->coa =$request->coa;
		$payroll->otbounus =0;
		$payroll->cashpaid =$request->cpaid;
		//$this->employeeActivity($request->emp,$employeeType,6,date('20y-m-d'),$request->cpaid);
		$payroll->total =$request->total;
		

		if($payroll->save()){
			$ref_id=Payroll::orderby('id','desc')->first();
			$employee_salary=$request->total;
			if($employee_salary > 0){				
				$this->liabilityledger('liability_employee_'.$request->emp,2,"liabilities",$employee_salary,null,21,$ref_id->id,date('20y-m-d'));
				$this->assetledger($request->coa,1,"assets",null,$request->cpaid,21,$ref_id->id,date('20y-m-d'));
			}
			if($request->adadv > 0){
				$advance =new Payrolladvance;
				$advance->employee_id=$request->emp;
				$advance->amount=-($request->adadv);
				$advance->save();
				// $this->assetledger($request->coa,1,"assets",$request->adadv,null,21,$ref_id->id,date('20y-m-d'));
				$this->assetledger('padvance101',1,"assets",null,$request->adadv,21,$ref_id->id,date('20y-m-d'));
			}
			if($request->advamount > 0){
				$advance =new Payrolladvance;
				$advance->employee_id=$request->emp;
				$advance->amount=$request->advamount;
				if($advance->save()){
					$ref_id=Payrolladvance::orderby('id','desc')->first();
					$this->assetledger('padvance101',1,"assets",$request->advamount,null,20,$ref_id->id,date('20y-m-d'));

					$this->assetledger($request->coa,1,"assets",null,$request->advamount,20,$ref_id->id,date('20y-m-d'));
				}
			}
			$request->session()->flash('message.level','success');
			$request->session()->flash('message.content','Payroll Saved');
			return redirect('payroll/add');
		}
		
		
		
	}
	public function getemplyeesalary(Request $request){
		$employee=Employee::where('id',$request->emp)->first();
		$advance=Payrolladvance::where('employee_id',$request->emp)->sum('amount');
		$liability = Ledger::where('account_id','liability_employee_'.$request->emp)->orderby('date','DESC')->first();
		if($liability != null){
			echo json_encode(array($employee->salary,$advance,$liability->balance));}
			else{
				echo json_encode(array($employee->salary,$advance,0));
			}
		}

		public function payadvance(Request $request){
			$advance =new Payrolladvance;
			$advance->employee_id=$request->emp;
			$advance->amount=$request->advance;
			if($advance->save()){
				$employeeType = Employee::where('id',$request->emp)->first()->mm;
				//$this->employeeActivity($request->emp,$employeeType,3,date('20y-m-d'),$request->advance);
				$ref_id=Payrolladvance::orderby('id','desc')->first();
				$this->assetledger('padvance101',1,"assets",$request->advance,null,20,$ref_id->id,date('20y-m-d'));
				$this->assetledger($request->coa,1,"assets",null,$request->advance,20,$ref_id->id,date('20y-m-d'));

				echo '1';
			}
			else{
				echo '0';
			}
		}

		public function saveBonus(Request $request){
			$bonus = new Bonus;
			$bonus->emp = $request->emp;
			$bonus->bonus = $request->bonus;
			$bonus->save();
			$employeeType = Employee::where('id',$request->emp)->first()->mm;
			//$this->employeeActivity($request->emp,$employeeType,4,date('20y-m-d'),$request->bonus);
			$this->expensledger('salary101',5,"expense",$request->bonus,null,20,$request->emp,date('y-m-d'));
			$this->liabilityledger('liability_employee_'.$request->emp,5,"liabilities",null,$request->bonus,20,$request->emp,date('y-m-d'));
		}
	}
