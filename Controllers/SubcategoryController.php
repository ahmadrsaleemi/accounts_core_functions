<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use App\Chartofaccount;
use App\Subcategory;
use App\Category;

class SubcategoryController extends Controller
{
    Public function __construct(){
        $this->middleware('auth');
    }
    public function index()
    {
        $subcategories=Subcategory::select('subcategories.*','categories.name as category')->leftjoin('categories','categories.id','=','subcategories.category')->get();
        return view('subcategory/view' , compact('subcategories'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        return view('subcategory.add',compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check_duplicate=Subcategory::where('name',$request->name)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Category  Already Available Please Try Changed Category Name');
            return redirect('subcategory/add');
        }
        else{
        $subcategory=new Subcategory;
        $subcategory->name=$request->name;
        $subcategory->category=$request->category;
        if ($subcategory->save()) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Subcategory was successfully added!');
        }
        return redirect('subcategory/add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($subcategory)
    {
        $categories = Category::all();
        $subcategories=Subcategory::select('subcategories.*','categories.name as category','categories.id as category_id')->leftjoin('categories','categories.id','=','subcategories.category')->where('subcategories.id',$subcategory)->get();
        //return $supplier;
        return view('subcategory/edit',['subcategories'=>$subcategories,'categories'=>$categories]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
         $update=DB::table('subcategories')
            ->where('id', $request->txt_rec_id)
            ->update(['name'=>$request->name,'category'=>$request->category]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Sub Category  successfully Updated!');
        }
        return redirect('subcategory/show/'.$request->txt_rec_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($category)
    {
        DB::table('subcategories')->where('id',$category)->delete();
        
        return redirect('subcategory/show');
    }
}
