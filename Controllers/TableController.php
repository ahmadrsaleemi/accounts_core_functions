<?php

namespace App\Http\Controllers;

use App\Table;
use Illuminate\Http\Request;
use DB;
use App\Employee;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tables = Table::select('tables.*','employees.name')->leftjoin('employees', 'employees.id','=','tables.waiter_id')->get();
        // return $tables;
        return view('table/view' , compact('tables'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='accounts_hotel_' AND TABLE_NAME ='tables'");
        $waiters=DB::table('employees')->where('type', 1)->get();
        // return $waiters;
        return view('table.add',compact('waiters','auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,
            [
            'txt_table_id'=>'required',
            'txt_waiter_id'=>'required',
        ]);
         $check_duplicate=Table::where('table_id',$request->txt_table_id)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Table Already Available Please Try Changed Table Id');
            return redirect('table/add');
        }
        else{
        $table=new Table;
        $table->table_id=$request->txt_table_id;
        $table->waiter_id=$request->txt_waiter_id;
        $table->waiter_name=$request->waiter_name;
        if ($table->save()) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Table was successfully added!');
        }
        return redirect('table/add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Table  $table
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tables = Table::select('tables.*','employees.name')->leftjoin('employees', 'employees.id','=','tables.waiter_id')->where('table_id',$id)->get();
        $employees = Employee::all();

        return view('table/edit' , compact('tables' , 'employees'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Table  $table
     * @return \Illuminate\Http\Response
     */
    public function edit(Table $table)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Table  $table
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Table $table)
    {
         $this->validate($request,
            ['txt_table_id'=>'required',
            'txt_waiter_id'=>'required',
        ]);
     
        $update=DB::table('tables')
            ->where('id', $request->txt_table_id)
            ->update(['table_id'=>$request->txt_table_id,'waiter_id' => $request->txt_waiter_id,'waiter_name'=> $request->waiter_name]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Table Details successfully Updated!');
        }
        return redirect('table/show/'.$request->txt_table_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Table  $table
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $table =DB::table('tables')->where('id',$id)->delete();
        return redirect('table/show');
    }

    public function waiter_name(Request $request)
    {

        $waiter_name = Employee::select('name')->where('id' , $request->waiter_id)->first()->name;
        return $waiter_name;
    }
}
