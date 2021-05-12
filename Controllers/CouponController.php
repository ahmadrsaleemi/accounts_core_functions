<?php

namespace App\Http\Controllers;

use App\Coupon;
use Illuminate\Http\Request;
use DB;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $coupons = Coupon::all();
        return view('coupon/view' , compact('coupons'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $test = env('DB_DATABASE');
        $auto_increment=DB::select("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='".$test."' AND TABLE_NAME ='coupons'");
        return view('coupon.add',compact('auto_increment'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'txt_coupon_id'    => 'required',
            'txt_coupon_value' =>'required', 
        ]);
        $check_duplicate=Coupon::where('coupon_id',$request->txt_coupon_id)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Coupon Already Available Please Try Changed Coupon Id');
            return redirect('coupon/add');
        }
        else{
        $coupon=new Coupon;
        $coupon->coupon_id    = $request->txt_coupon_id;
        $coupon->coupon_name  = $request->txt_coupon_name;
        $coupon->value = $request->txt_coupon_value;
        if ($coupon->save()) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Coupon was successfully added!');
        }
        return redirect('coupon/add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $coupons = Coupon::where('coupon_id' , $id)->get();
        return view('coupon/edit' , compact('coupons'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function edit(Coupon $coupon)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Coupon $coupon)
    {
        $this->validate($request,
            ['txt_coupon_id'=>'required',
            'txt_coupon_value'=>'required',
        ]);
     
        $update=DB::table('coupons')
            ->where('id', $request->txt_coupon_id)
            ->update(['coupon_id'=>$request->txt_coupon_id,'coupon_name' => $request->txt_coupon_name,'value' => $request->txt_coupon_value]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Coupon Details successfully Updated!');
        }
        return redirect('coupon/show/'.$request->txt_coupon_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $coupon =DB::table('coupons')->where('id',$id)->delete();
        return redirect('coupon/show');
    }
}
