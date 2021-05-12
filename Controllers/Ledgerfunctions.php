<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Purchase;
use App\Bill;
use App\Bill_product;
use App\Supplier;
use App\Product;
use App\Inventory;
use App\Supplieraccount;
use App\Chartofaccount;
use App\Ledger;
use App\logpurchase;
use App\Logpurchaseproducts;
use App\Cprelation;
use App\logchartofaccount;
use App\Cashpaid;
use App\cashpaipproducts;
use Auth;
use DB;
use App\Fiscalyear;
use App\Employeeactivity;
class Ledgerfunctions extends Controller
{
     public function __construct()
    {
        $this->middleware('auth');
        
    }
     public function assetledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        $balance=0;
        if($acc_type == "assets"){
            //assing  prefix to account_id to keep track of it in ledger
            if(strpos($account_id, 'asset') === false){
                $account_id=$account_id;    
            }
           //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();
             $last_record2=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();

             //if record is available
            if($last_record != null){
            if($last_record->date < $date." ".date('H:i:s')){
                   //if debit field is not empty
                    if($debit != null){
                        //add balance to that record 
                        $balance =$last_record->balance+$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$last_record->balance-$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,1,$debit,$credit,$balance,$transection,$ref_id,$date);
                }
             }
            elseif($last_record2 != null){
                
            if($last_record2->date > $date." ".date('H:i:s')){
                $firstrecord=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('id', 'desc')->first();
                if($firstrecord != null){              
                if($debit != null){
                        //add balance to that record 
                        $balance =$firstrecord->balance+$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$firstrecord->balance-$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,1,$debit,$credit,$balance,$transection,$ref_id,$date);
                    
                }
                else{
                        if($debit != null){
                            $balance =$balance+$debit;
                        }
                        //if credit field is not empty
                        elseif($credit != null){
                            //minus balance to that record 
                            $balance =$balance-$credit;
                        }
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date','asc')->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                    $this->storeledger($account_id,1,$debit,$credit,$balance,$transection,$ref_id,$date);
                 }
                }
                    
            }
            else{
                //if debit field is not empty
                if($debit != null){
                    $balance =$balance+$debit;
                }
                //if credit field is not empty
                elseif($credit != null){
                    //minus balance to that record 
                    $balance =$balance-$credit;
                }
             $this->storeledger($account_id,1,$debit,$credit,$balance,$transection,$ref_id,$date);
            }
        }
    }
    public function liabilityledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        $balance=0;
        if($acc_type == "liabilities"){
            //assing  prefix to account_id to keep track of it in ledger
            if(strpos($account_id, 'liabilities') === false){
                $account_id=$account_id;    
            }
           //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();
             $last_record2=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();

             //if record is available
            if($last_record != null){
            if($last_record->date < $date." ".date('H:i:s')){
                   //if debit field is not empty
                    if($debit != null){
                        //add balance to that record 
                        $balance =$last_record->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$last_record->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,2,$debit,$credit,$balance,$transection,$ref_id,$date);
                }
             }
            elseif($last_record2 != null){
                
            if($last_record2->date > $date." ".date('H:i:s')){
                $firstrecord=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('id', 'desc')->first();
                if($firstrecord != null){              
                if($debit != null){
                        //add balance to that record 
                        $balance =$firstrecord->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$firstrecord->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,2,$debit,$credit,$balance,$transection,$ref_id,$date);
                    
                }
                else{
                       
                 if($debit != null){
                            $balance =$balance-$debit;
                        }
                        //if credit field is not empty
                        elseif($credit != null){
                            //minus balance to that record 
                            $balance =$balance+$credit;
                        }
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date','asc')->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                    $this->storeledger($account_id,2,$debit,$credit,$balance,$transection,$ref_id,$date);
                 }
                }
                    
            }
            else{
                //if debit field is not empty
                if($debit != null){
                    $balance =$balance-$debit;
                }
                //if credit field is not empty
                elseif($credit != null){
                    //minus balance to that record 
                    $balance =$balance+$credit;
                }
             $this->storeledger($account_id,2,$debit,$credit,$balance,$transection,$ref_id,$date);
            }
        }
    }

    function capitalledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        $balance=0;
        if($acc_type == "capital"){
            //assing  prefix to account_id to keep track of it in ledger
            if(strpos($account_id, 'capital') === false){
                $account_id=$account_id;    
            }
           //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();
             $last_record2=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();

             //if record is available
            if($last_record != null){
            if($last_record->date < $date." ".date('H:i:s')){
                   //if debit field is not empty
                    if($debit != null){
                        //add balance to that record 
                        $balance =$last_record->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$last_record->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,3,$debit,$credit,$balance,$transection,$ref_id,$date);
                }
             }
            elseif($last_record2 != null){
                
            if($last_record2->date > $date." ".date('H:i:s')){
                $firstrecord=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('id', 'desc')->first();
                if($firstrecord != null){              
                if($debit != null){
                        //add balance to that record 
                        $balance =$firstrecord->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$firstrecord->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,3,$debit,$credit,$balance,$transection,$ref_id,$date);
                    
                }
                else{
                        if($debit != null){
                            $balance =$balance-$debit;
                        }
                        //if credit field is not empty
                        elseif($credit != null){
                            //minus balance to that record 
                            $balance =$balance+$credit;
                        }
                        $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date','asc')->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                    $this->storeledger($account_id,3,$debit,$credit,$balance,$transection,$ref_id,$date);
                 }
                }
                    
            }
            else{
                //if debit field is not empty
                if($debit != null){
                    $balance =$balance-$debit;
                }
                //if credit field is not empty
                elseif($credit != null){
                    //minus balance to that record 
                    $balance =$balance+$credit;
                }
             $this->storeledger($account_id,3,$debit,$credit,$balance,$transection,$ref_id,$date);
            }
        }
    }
  public function incomeledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        $balance=0;
        if($acc_type == "income"){
            //assing  prefix to account_id to keep track of it in ledger
            if(strpos($account_id, 'income') === false){
                $account_id=$account_id;    
            }
           //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();
             $last_record2=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();

             //if record is available
            if($last_record != null){
            if($last_record->date < $date." ".date('H:i:s')){
                   //if debit field is not empty
                    if($debit != null){
                        //add balance to that record 
                        $balance =$last_record->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$last_record->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,4,$debit,$credit,$balance,$transection,$ref_id,$date);
                }
             }
            elseif($last_record2 != null){
                
            if($last_record2->date > $date." ".date('H:i:s')){
                $firstrecord=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('id', 'desc')->first();
                if($firstrecord != null){              
                if($debit != null){
                        //add balance to that record 
                        $balance =$firstrecord->balance-$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$firstrecord->balance+$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,4,$debit,$credit,$balance,$transection,$ref_id,$date);
                    
                }
                else{
                        if($debit != null){
                            $balance =$balance-$debit;
                        }
                        //if credit field is not empty
                        elseif($credit != null){
                            //minus balance to that record 
                            $balance =$balance+$credit;
                        }
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date','asc')->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->credit_ammount]);
                               $balancess = $balance + $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->debit_ammount]);
                                $balancess = $balancess - $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->credit_ammount]);
                                $balancess = $balancess + $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                    $this->storeledger($account_id,4,$debit,$credit,$balance,$transection,$ref_id,$date);
                 }
                }
                    
            }
            else{
                //if debit field is not empty
                if($debit != null){
                    $balance =$balance-$debit;
                }
                //if credit field is not empty
                elseif($credit != null){
                    //minus balance to that record 
                    $balance =$balance+$credit;
                }
             $this->storeledger($account_id,4,$debit,$credit,$balance,$transection,$ref_id,$date);
            }
        }
    }
  public function expensledger($account_id,$account_type2,$acc_type,$debit,$credit,$transection,$ref_id,$date){
        $balance=0;
        if($acc_type == "expense"){
            //assing  prefix to account_id to keep track of it in ledger
            if(strpos($account_id, 'expense') === false){
                $account_id=$account_id;    
            }
           //check if balnce is available already agaist this account id
            $last_record=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();
             $last_record2=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date', 'desc')->first();

             //if record is available
            if($last_record != null){
            if($last_record->date < $date." ".date('H:i:s')){
                   //if debit field is not empty
                    if($debit != null){
                        //add balance to that record 
                        $balance =$last_record->balance+$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$last_record->balance-$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,$account_type2,$debit,$credit,$balance,$transection,$ref_id,$date);
                }
             }
            elseif($last_record2 != null){
                
            if($last_record2->date > $date." ".date('H:i:s')){
                $firstrecord=Ledger::where('account_id',$account_id)->where('date','<',$date." ".date('H:i:s'))->orderby('id', 'desc')->first();
                if($firstrecord != null){              
                if($debit != null){
                        //add balance to that record 
                        $balance =$firstrecord->balance+$debit;
                    }
                    //if credit field is not empty
                    elseif($credit != null){
                        //minus balance to that record 
                        $balance =$firstrecord->balance-$credit;
                    }
                // echo $last_record->date."<br>".$date." ".date('H:i:s');die();
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                $this->storeledger($account_id,$account_type2,$debit,$credit,$balance,$transection,$ref_id,$date);
                    
                }
                else{
                        if($debit != null){
                            $balance =$balance+$debit;
                        }
                        //if credit field is not empty
                        elseif($credit != null){
                            //minus balance to that record 
                            $balance =$balance-$credit;
                        }
                $ledgergreaterdate=Ledger::where('account_id',$account_id)->where('date','>',$date." ".date('H:i:s'))->orderby('date','asc')->get();
                $count=1;
                    foreach ($ledgergreaterdate as $value) {
                        if($count == 1){
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance+$value->debit_ammount]);
                                //$balancess=$value->balance;
                                $balancess = $balance + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balance-$value->credit_ammount]);
                               $balancess = $balance - $value->credit_ammount;
                            }

                        }
                        else{
                           
                            //if debit field is not empty
                            if($value->debit_ammount != null){
                            //add balance to that record 
                                Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess+$value->debit_ammount]);
                                $balancess = $balancess + $value->debit_ammount;
                                
                            }
                            //if credit field is not empty
                            elseif($value->credit_ammount != null){
                            //minus balance to that record
                               Ledger::where('account_id',$account_id)->where('id',$value->id)->where('date','>',$date." ".date('H:i:s'))->update(['balance'=>$balancess-$value->credit_ammount]);
                                $balancess = $balancess - $value->credit_ammount;
                            }
                        }
                    $count++;
                        
                    }
                    $this->storeledger($account_id,$account_type2,$debit,$credit,$balance,$transection,$ref_id,$date);
                 }
                }
                    
            }
            else{
                //if debit field is not empty
                if($debit != null){
                    $balance =$balance+$debit;
                }
                //if credit field is not empty
                elseif($credit != null){
                    //minus balance to that record 
                    $balance =$balance-$credit;
                }
             $this->storeledger($account_id,$account_type2,$debit,$credit,$balance,$transection,$ref_id,$date);
            }
        }
    }

    public  function storeledger($account_id,$account_type,$debit,$credit,$balance,$transection,$ref_id,$date){
            $ledger=new Ledger;
            $ledger->account_id=$account_id;
            $ledger->account_type=$account_type;
            $ledger->debit_ammount=$debit;
            $ledger->credit_ammount=$credit;
            $ledger->balance=$balance;
            $ledger->transection_type=$transection;
            $ledger->ref_id=$ref_id;
            $ledger->date=$date." ".date('H:i:s');
            $ledger->user_id=Auth::id();
            $ledger->save();
    }

    public function employeeActivity($employee,$employeetype,$transectiontype,$date,$value){
        $empactivity = new Employeeactivity;
        $empactivity->employee = $employee;
        $empactivity->employeetype = $employeetype;
        $empactivity->transectiontype = $transectiontype;
        $empactivity->date = $date;
        $empactivity->value = $value;
        $empactivity->save();
    }
}
