<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Http\Request;
use DB;

class ProductController extends Controller
{
   
    //@objective:  Display a listing of the resource.
    //@method:GET
    //@Endpoint: product
    //Response:ProductList
    public function index()
    {
        $request = request();
        $productsQuery = Product::query();
        $filtering=false;
        if($request->title){
            $filtering=true;
            $productsQuery->where('title','like',"%".$request->title."%");
        }
        if($request->date){
            $filtering=true;
            $productsQuery->whereDate('created_at',$request->date);
        }
       if($request->price_from and $request->price_to){
            $filtering=true;
            $productsQuery->whereHas('prices',function($query)use($request){
                $query->whereBetween('price',[$request->price_from,$request->price_to]);
            });
            $productsQuery->with([
                'prices'=>function($query) use($request){
                    $query->whereBetween('price',[$request->price_from,$request->price_to]);
                },
                'prices.productVariantInfoOne',
                'prices.productVariantInfoTwo',
                'prices.productVariantInfoThree'
            ]);
        }else{
             $productsQuery->with([
                'prices',
                'prices.productVariantInfoOne',
                'prices.productVariantInfoTwo',
                'prices.productVariantInfoThree'
            ]);
        }
        if($request->variant){
            $filtering=true;
            $variantName = $request->variant;
            $products = $productsQuery->get();
        }else{
            if(!$filtering){
                $products = $productsQuery->paginate(2);
            }else{
                $products = $productsQuery->get();
            }
        }
        $groupWiseAllProductsVariants = ProductVariant::with(['variantInfo'])->get(['variant_id','variant'])->unique('variant')->groupBy('variantInfo.title');
        return view('products.index',compact('products','groupWiseAllProductsVariants','filtering'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

   //@Objective: Store Or Update Product
    //@method:POST
    //@Endpoint: product
    //@response: {'success':true}
    public function store(Request $request)
    {
        $request->validate([
            'title'=>"required",
            'sku'=>"required"
        ]);
        $validationPass = $this->productVariantValidation($request->product_variant);
        if(!$validationPass){
            return response()->json([
                'success'=>$validationPass,
                'errors'=>['Please Input Product Variant Correctly'],
            ],422);
        }
        $productInfoData = $request->only(['title','sku','description']);
        DB::beginTransaction();
        if($request->product_id){
            $product = Product::find($request->product_id);
            $product->update($productInfoData);
            $type="update";
        }else{
            $type="insert";
            $product = Product::create($productInfoData);
        }
       
        if($product){
            $this->saveProductVariant($request->product_variant,$product->id,$type);
            $this->saveProductVariantPrice($request->product_preview,$product->id,$type);
            DB::commit();
           $success=true;$httpCode=200;
        }else{
            DB::rollback();
            $success=false;$httpCode=500;
        }

        return response()->json([
            'success'=>$success,
        ],$httpCode);
    }

    //@Objective: Validation  For productVariant when product insert or update
    //@method:POST
    //@Endpoint: product
    //@response: boolean;
    public function productVariantValidation($productVariants){
       // dd($productVariants);
        $validationPass = true;
        foreach($productVariants as $productVariant){
            if(count($productVariant)<2){
                $validationPass = false;break;
            }
        }
        return $validationPass;
    }

    //@Objective: Saving or updating productVariant when product insert or update
    //@method:POST
    //@Endpoint: product
    //@response: boolean;
    public function saveProductVariant($productVariants,$productId,$type="insert"){
        if($type=='update'){
            ProductVariant::where('product_id',$productId)->delete();
        }
        $timeStamp = date("Y-m-d h:i:s");
        $allProductVariant=[];
        $allTags=[];
        foreach($productVariants as $pVariant){
            $variantId = $pVariant['option'];
            foreach($pVariant['value'] as $tag){
                $data['variant_id'] = $variantId;
                $data['variant'] = $tag;
                $data['product_id'] = $productId;
                $data['created_at'] = $timeStamp;
                $data['updated_at'] = $timeStamp;
                $allTags[]=$tag;
                $allProductVariant[]=$data;
            }
        };
        if(count($allProductVariant)>0){
            if(ProductVariant::insert($allProductVariant)){
                return true;
            };
        }
    }

    //@Objective: Saving or updating save p roduct variant price when product insert or update
    //@method:POST
    //@Endpoint: product
    //@response: boolean;
    public function saveProductVariantPrice($productVariantPrices,$productId,$type="insert"){
        if($type=='update'){
            ProductVariantPrice::where('product_id',$productId)->delete();
        }
        $productVariants = ProductVariant::where('product_id',$productId)->get();
        //dd($productVariants);
        $timeStamp = date("Y-m-d h:i:s");
        $allVariantPrices=[];
        foreach($productVariantPrices as $variantPrice){
            $titles = explode('/',$variantPrice['variant']);
            $keys = ['1'=>'one','2'=>'two','3'=>'three'];
            foreach($titles as $index=>$title){
                if($title==''){continue;}
                $serial = $index+1;
                $productVariant = $productVariants->where('variant',$title)->first();
                if($productVariant){
                    $productVariantId = $productVariant->id;
                }else{
                    $productVariantId = null;
                }
                $columnName = "product_variant_".$keys["$serial"];
                $data[$columnName] = $productVariantId;
            }
            $data['price'] = $variantPrice['price'];
            $data['stock'] = $variantPrice['stock'];
            $data['product_id'] = $productId;
            $data['created_at'] = $timeStamp;
            $data['updated_at'] = $timeStamp;
            $allVariantPrices[]=$data;
        }
        if(count($allVariantPrices)>0){
            if(ProductVariantPrice::insert($allVariantPrices)){
                return true;
            };
        }
    }

    //@Objective: to upload a image when drop image by js dropjone and save file name in session;
    //@method:POST
    //@Endpoint: dropzone
    //@response:
    public function saveAttachment(){
        $basePath = base_path();
        $directory="/public/attachments/";
        $destinationPath=$basePath.$directory;
        $timeStamp = date("Y-m-d h:i:s");
        $imageName = time().'.'.request()->file->getClientOriginalExtension();
        if(request()->file->move($destinationPath, $imageName)){
            $data['product_id']='';
            $data['file_path']=$destinationPath.$imageName;
            $data['thumbnail']=1;
            $data['created_at'] = $timeStamp;
            $data['updated_at'] = $timeStamp;
            if(session('image_uploaded')){
                $allData = session('image_uploaded');
                $allData[]=$data;
                session()->put('image_uploaded',$allData);
            }else{
                session()->put('image_uploaded',[$data]);
            }
        };
    }
    
    //@Objective: to save image name which are uploaded by  drop image by js dropjone and remove name from session
    //@method:POST
    //@Endpoint: product
    //@response:
    public function __saveAttachment($productId){
        if(request()->product_id){
            \App\Models\ProductImage::where('product_id',$productId)->delete();
        }
        if(session()->get('image_uploaded')){
            $allImages = session()->get('image_uploaded');
            $allData = [];
            foreach($allImages as $image){
                $image['product_id'] = $productId;
                $allData[]=$image;
            }
            if(count($allData)>0){
                \App\Models\ProductImage::insert($allData);
                return true;
            }
            session()->forget('image_uploaded');
        }
    }
    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

   //@Objective: fetch a product data with its variants price etc.
    //@method:GET
    //@Endpoint: product/id/edit
    //@response: view file;
    public function edit($id)
    {
        $variants = Variant::all();
        $product = Product::where('id',$id)->with(['variants','prices'])->first();
        $variantId=[];
        $vaiantsGroups = $product->variants->groupBy('variant_id');
        $allPrices = $product->prices;
        $allData=[];
        foreach($vaiantsGroups as $key=>$vaiantsGroup){
            $data = [
                'option'=>$key,
                'tags'=>$vaiantsGroup->pluck('variant')->toArray(),
            ];
            $allData[]=$data;
        }
        $product->variant_tags=$allData;
        $allVariants = $product->variants;
        foreach($allPrices as $price){
            $varinatCombine = $allVariants->whereIn('id',[$price->product_variant_one,$price->product_variant_two,$price->product_variant_three])->pluck('variant')->toArray();
            $data1['tag_combine'] = implode('/',$varinatCombine).'/';
            $data1['price'] = $price->price;
            $data1['stock'] = $price->stock;
            $allData1[$data1['tag_combine']]=$data1;
        }
        $product->price_combine=$allData1;
        //return $product;
        return view('products.edit', compact('product','variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
