<?php

namespace App\Http\Controllers;
use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        $users=User::paginate(100);
        return view('user/view',compact('users'));
    }

    public function create(){

    	return view('user/add');
    }
    public function store(Request $request){
    	$users=new User;
    	$users->name=$request->name;
    	$users->email=$request->email;
    	$users->password=bcrypt($request->password);
    	$users->role=$request->role;
    	if($users->save()){
    		$request->session()->flash('message.level', 'success');
        	$request->session()->flash('message.content', 'New user Has Been Added');
            return redirect('user/add');
    	}
    }

    public function show($user_id)
    {
        $user =User::where('id',$user_id)->get();
        return view('user/edit',['users'=>$user]);
    }

    public function destroy($user_id)
    {
        User::where('id',$user_id)->delete();
        return redirect('user/view');
    }

    public function update(){
       echo "to be update";
    }
}
