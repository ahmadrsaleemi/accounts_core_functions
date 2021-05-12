<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use App\Chartofaccount;
use App\Category;
use App\Subcategory;
use App\Recipe;
use App\Recipeproduct;
class CategoryController extends Controller
{
    public function __construct(){
        $this->middleware('cors');
    }
    public function index()
    {
        $categories=Category::all();
        return view('category/view' , compact('categories'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('category.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check_duplicate=Category::where('name',$request->name)->first();
        if($check_duplicate !== null){
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'This Category  Already Available Please Try Changed Category Name');
            return redirect('category/add');
        }
        else{
        $category=new Category;
        $category->name=$request->name;
        if ($category->save()) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'New Product was successfully added!');
        }
        return redirect('category/add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($category)
    {
        $category =DB::table('categories')->where('id',$category)->get();
        //return $supplier;
        return view('category/edit',['categories'=>$category]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
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
         $update=DB::table('categories')
            ->where('id', $request->txt_rec_id)
            ->update(['name'=>$request->name]);
        if ($update) {
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Category  successfully Updated!');
        }
        return redirect('category/show/'.$request->txt_rec_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($category)
    {
        DB::table('categories')->where('id',$category)->delete();
        return redirect('category/show');
    }

    public function allproducts(){
        $products=Product::all();
        foreach ($products as $product) {
            echo "<option value=".$product->product_id.">".$product->product_description."</option>";
        }
        echo '<option value="add_product" class="btn btn-info"></option>';
    }

    public function subcategoryByCategory(Request $request){
        $subcategories=Subcategory::select('subcategories.*')->where('subcategories.category',$request->cat)->get();
        return $subcategories;
    }


    public function allCategories(){
        
        return Category::all();
    }

    public function recepiesByProducts($category){
       
        return Recipe::where('category',$category)->get();
    
    }
}
