@extends('layouts.app')
@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Product</h1>
    </div>
    <form  action="{{ route('product.store') }}"  method="post" id="productSaveForm" >
        <section>
            <input type="hidden" name="product_id" value="{{$product->id}}">
            <div class="row">
                <div class="col-md-6">
                    <!--                    Product-->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Product</h6>
                        </div>
                        <div class="card-body border">
                            <div class="form-group">
                                <label for="product_name">Product Name</label>
                                <input type="text"
                                       name="title"
                                       id="product_name"
                                       required
                                       placeholder="Product Name"
                                       class="form-control"
                                       value="{{$product->title}}"
                                       >
                            </div>
                            <div class="form-group">
                                <label for="product_sku">Product SKU</label>
                                <input type="text" name="sku"
                                        id="product_sku"
                                        required
                                        placeholder="Product Name"
                                        class="form-control"
                                        value="{{$product->sku}}"
                                       >
                            </div>
                            <div class="form-group mb-0">
                                <label for="product_description">Description</label>
                                <textarea name="description"
                                          id="product_description"
                                          required
                                          rows="4"
                                          class="form-control">{{$product->description}}</textarea>
                            </div>
                        </div>
                    </div>
                    <!--                    Media-->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"><h6
                                class="m-0 font-weight-bold text-primary">Media</h6></div>
                        <div class="card-body border">
                            <div id="file-upload" class="dropzone dz-clickable">
                                <div class="dz-default dz-message"><span>Drop files here to upload</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--                Variants-->
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6
                                class="m-0 font-weight-bold text-primary">Variants</h6>
                        </div>
                        <div class="card-body pb-0" id="variant-sections">
                            @foreach($product->variant_tags as $index=>$variantTags)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Option</label>
                                        <select id="select2-option-{{$index}}" data-index="{{$index}}" name="product_variant[{{$index}}][option]" class="form-control custom-select select2 select2-option">
                                            @foreach($variants as $variant)
                                                @php
                                                    $isSelected="";
                                                    if($variant->id==$variantTags['option']){
                                                        $isSelected="selected";
                                                    }
                                                @endphp
                                            <option  {{$isSelected}} value="{{$variant->id}}">{{$variant->title}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="d-flex justify-content-between">
                                            <span>Value</span>
                                            <a href="#" class="remove-btn" data-index="{{$index}}" onclick="removeVariant(event, this);">Remove</a>
                                        </label>
                                        <select id="select2-value-{{$index}}" data-index="{{$index}}" name="product_variant[{{$index}}][value][]" class="select2 select2-value form-control custom-select" multiple="multiple">
                                            @foreach($variantTags['tags'] as $tag)
                                            <option selected value="{{$tag}}">{{$tag}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="card-footer bg-white border-top-0" id="add-btn">
                            <div class="row d-flex justify-content-center">
                                <button class="btn btn-primary add-btn" onclick="addVariant(event);">
                                    Add another option
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow">
                        <div class="card-header text-uppercase">Preview</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr class="text-center">
                                        <th width="33%">Variant</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                    </tr>
                                    </thead>
                                    <tbody id="variant-previews">
                                        @foreach($product->price_combine as $priceCombine)
                                        <tr>
                                            <th>
                                                <input type="hidden" name="product_preview[{{$loop->index}}][variant]" value="{{$priceCombine['tag_combine']}}">
                                                <span class="font-weight-bold">{{$priceCombine['tag_combine']}}</span>
                                            </th>
                                            <td>
                                                <input type="text" class="form-control" value="{{$priceCombine['price']}}" name="product_preview[{{$loop->index}}][price]" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" value="{{$priceCombine['stock']}}" name="product_preview[{{$loop->index}}][stock]">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-lg btn-primary" id="productSaveBtn">Save</button>
            <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
        </section>
    </form>
@endsection
@push('page_js')
    <script type="text/javascript" src="{{ asset('js/product.js') }}?time=1"></script>
    <script type="text/javascript"> 
        $(document).ready(function(){
            $("#file-upload").dropzone({
                headers: {'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')},
                url: "{{ route('file-upload') }}",
                method: "post",
                addRemoveLinks: true,
                success: function (file, response) {
                    //
                },
                error: function (file, response) {
                    //
                }
            });
            $(document).on("click","#productSaveBtn",function(){
               store_func("#productSaveForm");
            })
        })
        function store_func(formId,config=false){
            defaultConfig = {
                url:$(formId).attr('action'),
                method:'POST',
                append:false,
                loaderText:"Working...",
                reload:false,
                modal:'hide',
                confirmText:'Do you want to Submit'
            };
            if(confirm("Are You Sure")){
                let form=$(formId)[0];
                let formData = new FormData(form);
                $.ajax({
                    headers: {'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')},
                    type: config.method==undefined?defaultConfig.method:config.method,
                    enctype: 'multipart/form-data',
                    url: config.url==undefined?defaultConfig.url:config.url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    async:false,
                    timeout: 800000,
                    beforeSend: function() {
                        //return false;
                    },
                    success: function (data) {
                        
                        if(data.success){
                           toastr.success("Product Succesfully Updated");
                           setTimeout(function(){
                            location.href="{{route('product.index')}}";
                           },1500)
                        }else{
                            
                        }
                    },
                    error: function (response, ajaxOptions, thrownError) {
                        $('.overlay-wrapper').hide();
                        //$(formId).parents('.modal').modal('show');
                        if(response.status==422){
                            let catchError=jQuery.parseJSON(response.responseText);
                            let errorMsg='<ul>'
                            $.each(catchError.errors,function(key,value){
                                errorMsg+="<li>"+value+"</li>" ;
                            })
                            errorMsg+="</ul>"
                            toastr.error(errorMsg)
                        }else{
                            toastr.error(response.responseText)
                        }
                    },
                    complete: function(){
                    }
                })
            }
        }
    </script>
@endpush
