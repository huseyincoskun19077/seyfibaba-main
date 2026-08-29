<?php

namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\ProductGallery;
use App\Models\Brand;
use App\Models\ProductSpecificationKey;
use App\Models\ProductSpecification;
use App\Models\User;
use App\Models\Vendor;
use App\Models\OrderProduct;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\Wishlist;
use App\Models\Setting;
use App\Models\ShoppingCart;
use App\Models\FlashSaleProduct;
use App\Models\ShoppingCartVariant;
use App\Models\CompareProduct;
use Image;
use File;
use Str;
use Auth;
use App\Support\ProductSlug;


class SellerProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('api')->user()->seller;

        // Mobil / hafif liste: sayfalama + sadece kart alanları (appends/N+1 yok)
        if ($request->boolean('light') || $request->has('page')) {
            $perPage = (int) $request->input('per_page', 20);
            $perPage = max(5, min($perPage, 50));
            $q = trim((string) $request->input('q', ''));
            $filter = (string) $request->input('filter', 'all');

            $query = Product::query()
                ->where('vendor_id', $seller->id)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'thumb_image',
                    'price',
                    'offer_price',
                    'qty',
                    'status',
                    'approve_by_admin',
                ])
                ->orderByDesc('id');

            if ($q !== '') {
                $query->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', '%'.$q.'%')
                        ->orWhere('slug', 'like', '%'.$q.'%')
                        ->orWhere('sku', 'like', '%'.$q.'%');
                });
            }

            if ($filter === 'active') {
                $query->where('status', 1);
            } elseif ($filter === 'inactive') {
                $query->where('status', '!=', 1);
            } elseif ($filter === 'low') {
                $query->where('qty', '>', 0)->where('qty', '<=', 5);
            } elseif ($filter === 'out') {
                $query->where('qty', '<=', 0);
            }

            $paginator = $query->paginate($perPage);

            // averageRating / totalSold append'lerini tetikleme
            $items = collect($paginator->items())->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'thumb_image' => $product->thumb_image,
                    'price' => $product->price,
                    'offer_price' => $product->offer_price,
                    'qty' => $product->qty,
                    'status' => $product->status,
                    'approve_by_admin' => $product->approve_by_admin,
                ];
            })->values();

            return response()->json([
                'products' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        $products = Product::with('category','seller','brand')->orderBy('id','desc')->where('vendor_id',$seller->id)->get();
        $orderProducts = OrderProduct::whereIn('product_id', $products->pluck('id'))->get();
        return response()->json(['products' => $products, 'orderProducts' => $orderProducts]);
    }

    public function pendingProduct(){
        return response()->json(['products' => [], 'orderProducts' => [], 'message' => 'Ürün onayı kaldırıldı.']);
    }

    public function create()
    {
        $categories = Category::query()->active()->orderBy('name')->get();
        $brands = Brand::query()->where('status', 1)->orderBy('name')->get();
        $specificationKeys = ProductSpecificationKey::all();

        return response()->json(['categories' => $categories , 'brands' => $brands, 'specificationKeys' => $specificationKeys], 200);
    }


    public function getSubcategoryByCategory($id){
        $subCategories = SubCategory::query()
            ->where('category_id', $id)
            ->active()
            ->orderBy('name')
            ->get();
        return response()->json(['subCategories'=>$subCategories]);
    }

    public function getChildcategoryBySubCategory($id){
        $childCategories = ChildCategory::query()
            ->where('sub_category_id', $id)
            ->active()
            ->orderBy('name')
            ->get();
        return response()->json(['childCategories'=>$childCategories]);
    }

    public function store(Request $request)
    {
        // KYC onayı olmadan ürün eklemeyi engelle (WEB ile aynı kural)
        $seller = Auth::guard('api')->user()->seller;
        if (! $seller || $seller->kyc_status !== 'approved') {
            return response()->json([
                'message' => 'Ürün ekleyebilmek için satıcı doğrulamanızı tamamlamanız gerekmektedir. Lütfen belgelerinizi ve IBAN bilgilerinizi yükleyin.',
                'redirect' => 'kyc',
            ], 403);
        }

        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
        ]);

        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products',
            'thumb_image' => 'required',
            'category' => ['required', Rule::exists('categories', 'id')->where('status', 1)],
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'sale_unit_qty' => 'nullable|integer|min:1|max:9999',
        ];
        $customMessages = [
            'short_name.required' => trans('Short name is required'),
            'short_name.unique' => trans('Short name is required'),
            'name.required' => trans('Name is required'),
            'name.unique' => trans('Name is required'),
            'slug.required' => trans('Slug is required'),
            'slug.unique' => trans('Slug already exist'),
            'category.required' => trans('Category is required'),
            'thumb_image.required' => trans('thumbnail is required'),
            'short_description.required' => trans('Short description is required'),
            'long_description.required' => trans('Long description is required'),
            'price.required' => trans('Price is required'),
            'status.required' => trans('Status is required'),
            'quantity.required' => trans('Quantity is required'),
        ];
        $this->validate($request, $rules,$customMessages);


        $seller = Auth::guard('api')->user()->seller;
        $product = new Product();
        if($request->thumb_image){
            $extention = $request->thumb_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumb_image)
                ->save(public_path().'/'.$image_name);
            $product->thumb_image=$image_name;
        }

        $product->vendor_id = $seller->id;
        $product->short_name = $request->short_name;
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->category_id = $request->category;
        $product->sub_category_id = $request->sub_category ? $request->sub_category : 0;
        $product->child_category_id = $request->child_category ? $request->child_category : 0;
        $product->brand_id = $request->brand ? $request->brand : 0;
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->offer_price = $request->offer_price;
        $product->sale_unit_qty = max(1, (int) ($request->input('sale_unit_qty', 1) ?: 1));
        $product->qty = $request->quantity ? $request->quantity : 0;
        $product->short_description = $request->short_description;
        $product->long_description = $request->long_description;
        $product->tags = $request->tags;
        // KYC onaylı satıcı ürünü doğrudan yayına alınır
        $product->status = 1;
        $product->approve_by_admin = 1;
        $product->weight = $request->filled('weight') ? $request->weight : 0;
        $product->is_undefine = 1;
        $product->is_specification = $request->is_specification ? 1 : 0;
        $product->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $product->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $product->is_top = $request->top_product ? 1 : 0;
        $product->new_product = $request->new_arrival ? 1 : 0;
        $product->is_best = $request->best_product ? 1 : 0;
        $product->is_featured = $request->is_featured ? 1 : 0;
        $product->save();

        if($request->is_specification){
            $exist_specifications=[];
            if($request->keys){
                foreach($request->keys as $index => $key){
                    if($key){
                        if($request->specifications[$index]){
                            if(!in_array($key, $exist_specifications)){
                                $productSpecification= new ProductSpecification();
                                $productSpecification->product_id = $product->id;
                                $productSpecification->product_specification_key_id = $key;
                                $productSpecification->specification = $request->specifications[$index];
                                $productSpecification->save();
                            }
                            $exist_specifications[] = $key;
                        }
                    }
                }
            }
        }
        $notification = trans('Created Successfully');
        return response()->json(['message' => $notification],200);
    }

    public function show($id)
    {
        $seller = Auth::guard('api')->user()->seller;
        $product = Product::with('category','brand','gallery','specifications','reviews','variants','variantItems')
            ->where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(['error' => 'Something went wrong'], 403);
        }

        return response()->json(['product' => $product], 200);
    }

    public function edit($id)
    {
        $seller = Auth::guard('api')->user()->seller;
        $product = Product::with('category','brand','gallery','variants','variantItems')
            ->where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(['error' => 'Something went wrong'], 403);
        }
        $categories = Category::query()->active()->orderBy('name')->get();
        $subCategories = SubCategory::query()
            ->where('category_id', $product->category_id)
            ->active()
            ->orderBy('name')
            ->get();
        $childCategories = ChildCategory::query()
            ->where('sub_category_id', $product->sub_category_id)
            ->active()
            ->orderBy('name')
            ->get();
        $brands = Brand::query()->where('status', 1)->orderBy('name')->get();
        $specificationKeys = ProductSpecificationKey::all();
        $productSpecifications = ProductSpecification::where('product_id',$product->id)->get();
        return response()->json(['product' => $product, 'categories' => $categories , 'brands' => $brands, 'specificationKeys' => $specificationKeys, 'productSpecifications' => $productSpecifications, 'subCategories' => $subCategories, 'childCategories' => $childCategories , ], 200);

    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
        ]);


        $seller = Auth::guard('api')->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$product->id,
            'category' => ['required', Rule::exists('categories', 'id')->where('status', 1)],
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'sale_unit_qty' => 'nullable|integer|min:1|max:9999',
        ];
        $customMessages = [
            'short_name.required' => trans('Short name is required'),
            'short_name.unique' => trans('Short name is required'),
            'name.required' => trans('Name is required'),
            'name.unique' => trans('Name is required'),
            'slug.required' => trans('Slug is required'),
            'slug.unique' => trans('Slug already exist'),
            'category.required' => trans('Category is required'),
            'thumb_image.required' => trans('thumbnail is required'),
            'banner_image.required' => trans('Banner is required'),
            'short_description.required' => trans('Short description is required'),
            'long_description.required' => trans('Long description is required'),
            'brand.required' => trans('Brand is required'),
            'price.required' => trans('Price is required'),
            'quantity.required' => trans('Quantity is required'),
            'status.required' => trans('Status is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        if($request->thumb_image){
            $old_thumbnail = $product->thumb_image;
            $extention = $request->thumb_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumb_image)
                ->save(public_path().'/'.$image_name);
            $product->thumb_image=$image_name;
            $product->save();
            if($old_thumbnail){
                if(File::exists(public_path().'/'.$old_thumbnail))unlink(public_path().'/'.$old_thumbnail);
            }
        }


        $product->short_name = $request->short_name;
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->category_id = $request->category;
        $product->sub_category_id = $request->sub_category ? $request->sub_category : 0;
        $product->child_category_id = $request->child_category ? $request->child_category : 0;
        $product->brand_id = $request->brand ? $request->brand : 0;
        $product->qty = $request->quantity ? $request->quantity : 0;
        $product->sale_unit_qty = max(1, (int) ($request->input('sale_unit_qty', 1) ?: 1));
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->offer_price = $request->offer_price;
        $product->short_description = $request->short_description;
        $product->long_description = $request->long_description;
        $product->tags = $request->tags;
        // Admin reddettiyse satıcı status değiştiremez; status gelmezse mevcut değer korunur
        if ($product->approve_by_admin == 1 && $request->filled('status')) {
            $product->status = (int) $request->status;
        }
        $product->weight = $request->filled('weight') ? $request->weight : 0;
        $product->is_specification = $request->is_specification ? 1 : 0;
        $product->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $product->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $product->is_top = $request->top_product ? 1 : 0;
        $product->new_product = $request->new_arrival ? 1 : 0;
        $product->is_best = $request->best_product ? 1 : 0;
        $product->is_featured = $request->is_featured ? 1 : 0;
        $product->save();

        $exist_specifications=[];
        if($request->keys){
            foreach($request->keys as $index => $key){
                if($key){
                    if($request->specifications[$index]){
                        if(!in_array($key, $exist_specifications)){
                            $existSroductSpecification = ProductSpecification::where(['product_id' => $product->id,'product_specification_key_id' => $key])->first();
                            if($existSroductSpecification){
                                $existSroductSpecification->specification = $request->specifications[$index];
                                $existSroductSpecification->save();
                            }else{
                                $productSpecification = new ProductSpecification();
                                $productSpecification->product_id = $product->id;
                                $productSpecification->product_specification_key_id = $key;
                                $productSpecification->specification = $request->specifications[$index];
                                $productSpecification->save();
                            }
                        }
                        $exist_specifications[] = $key;
                    }
                }
            }
        }
        $notification = trans('Update Successfully');
        return response()->json(['message' => $notification],200);
    }

    public function destroy($id)
    {
        $seller = Auth::guard('api')->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        if (OrderProduct::where('product_id', $id)->exists()) {
            return response()->json(['message' => 'Satışı olan ürün silinemez. Pasife alabilirsiniz.'], 422);
        }

        $gallery = $product->gallery;
        $old_thumbnail = $product->thumb_image;

        try {
            ProductVariantItem::where('product_id', $id)->delete();
            ProductVariant::where('product_id', $id)->delete();
            ProductReport::where('product_id', $id)->delete();
            FlashSaleProduct::where('product_id', $id)->delete();
            ProductReview::where('product_id', $id)->delete();
            ProductSpecification::where('product_id', $id)->delete();
            Wishlist::where('product_id', $id)->delete();
            CompareProduct::where('product_id', $id)->delete();

            if (class_exists(\App\Models\StockNotify::class)) {
                \App\Models\StockNotify::where('product_id', $id)->delete();
            }

            $cartProducts = ShoppingCart::where('product_id', $id)->get();
            foreach ($cartProducts as $cartProduct) {
                ShoppingCartVariant::where('shopping_cart_id', $cartProduct->id)->delete();
                $cartProduct->delete();
            }

            foreach ($gallery as $image) {
                $old_image = $image->image;
                $image->delete();
                if ($old_image && File::exists(public_path().'/'.$old_image)) {
                    @unlink(public_path().'/'.$old_image);
                }
            }

            $product->delete();

            if ($old_thumbnail && File::exists(public_path().'/'.$old_thumbnail)) {
                @unlink(public_path().'/'.$old_thumbnail);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Ürün silinemedi. Lütfen tekrar deneyin.'], 500);
        }

        $notification = trans('Delete Successfully');
        return response()->json(['message' => $notification],200);

    }

    public function changeStatus(Request $request, $id){
        $seller = Auth::guard('api')->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        if ((int) $product->approve_by_admin === 0) {
            if ($request->boolean('activate') || ((int) $product->status === 0 && ! $request->boolean('deactivate'))) {
                return response()->json(['message' => 'Bu ürün admin tarafından pasife alındı.'], 403);
            }
        }

        if ($request->boolean('deactivate')) {
            if ((int) $product->status === 0) {
                return response()->json(['message' => 'Ürün zaten pasif.'], 200);
            }
            $product->status = 0;
            $product->save();

            return response()->json(['message' => trans('Inactive Successfully')], 200);
        }

        if ($request->boolean('activate')) {
            $product->status = 1;
            $product->save();

            return response()->json(['message' => trans('Active Successfully')], 200);
        }

        if ((int) $product->status === 1) {
            $product->status = 0;
            $product->save();

            return response()->json(['message' => trans('Inactive Successfully')], 200);
        }

        $product->status = 1;
        $product->save();

        return response()->json(['message' => trans('Active Successfully')], 200);
    }

    public function removedProductExistSpecification($id){
        $productSpecification = ProductSpecification::find($id);
        $productSpecification->delete();
        $message = trans('Removed Successfully');
        return response()->json($message);
    }

}
