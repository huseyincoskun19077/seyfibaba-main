<?php

namespace App\Http\Controllers\WEB\Seller;
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
use Illuminate\Support\Facades\Log;
use Throwable;

use App\Http\Requests\Seller\UploadBulkProductImportRequest;
use App\Models\BulkImport;
use App\Services\BulkProductImportService;
use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Services\ProductImageStorage;
use App\Services\SimpleProductColorService;
use App\Support\ProductSlug;

class SellerProductController extends Controller
{
    use AuthorizesSellerProduct;
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        $q = trim((string) $request->input('q', ''));
        $filter = (string) $request->input('filter', 'all');

        $query = Product::with('category', 'subCategory', 'seller', 'brand')
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('short_name', 'like', '%'.$q.'%')
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

        $products = $query->paginate(20)->withQueryString();
        $orderProducts = OrderProduct::whereIn('product_id', $products->pluck('id'))->get();
        $setting = Setting::first();

        return view('seller.product', compact('products', 'orderProducts', 'setting', 'q', 'filter'));
    }

    public function pendingProduct(){
        return redirect()
            ->route('seller.product.index')
            ->with(['messege' => 'Ürün onayı kaldırıldı. Tüm ürünleriniz ana listede görünür.', 'alert-type' => 'info']);
    }

    public function stockoutProduct(){
        $seller = Auth::guard('web')->user()->seller;
        $products = Product::with('category','seller','brand')->orderBy('id','desc')->where('qty',0)->where('vendor_id',$seller->id)->get();
        $setting = Setting::first();

        return view('seller.stockout_product',compact('products','setting'));
    }



    public function create()
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller || $seller->kyc_status !== 'approved') {
            $notification = array('messege' => 'Ürün ekleyebilmek için hesap doğrulamanızı (KYC) tamamlamanız gerekmektedir. Lütfen belgelerinizi ve IBAN bilgilerinizi yükleyin.', 'alert-type' => 'error');
            return redirect()->route('seller.kyc')->with($notification);
        }

        $categories = Category::query()->active()->orderBy('name')->get();
        $brands = Brand::all();
        $specificationKeys = ProductSpecificationKey::all();
        $setting = Setting::first();
        $aiEnabled = (bool) $setting->openai_enabled || (bool) $setting->claude_enabled;
        $commissionRate = $seller->getEffectiveCommissionRate() ?: 10;

        return view('seller.create_product',compact('categories','brands','specificationKeys','aiEnabled','commissionRate'));
    }


    public function getSubcategoryByCategory($id){
        $subCategories = SubCategory::query()
            ->where('category_id', $id)
            ->active()
            ->orderBy('name')
            ->get();
        $response='<option value="">'.trans('admin_validation.Select Sub Category').'</option>';
        foreach($subCategories as $subCategory){
            $response .= "<option value=".$subCategory->id.">".$subCategory->name."</option>";
        }
        return response()->json(['subCategories'=>$response]);
    }

    public function getChildcategoryBySubCategory($id){
        $childCategories = ChildCategory::query()
            ->where('sub_category_id', $id)
            ->active()
            ->orderBy('name')
            ->get();
        $response='<option value="">'.trans('admin_validation.Select Child Category').'</option>';
        foreach($childCategories as $childCategory){
            $response .= "<option value=".$childCategory->id.">".$childCategory->name."</option>";
        }
        return response()->json(['childCategories'=>$response]);
    }

    public function store(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller || $seller->kyc_status !== 'approved') {
            $notification = array('messege' => 'Ürün ekleyebilmek için hesap doğrulamanızı (KYC) tamamlamanız gerekmektedir. Lütfen belgelerinizi ve IBAN bilgilerinizi yükleyin.', 'alert-type' => 'error');
            return redirect()->route('seller.kyc')->with($notification);
        }

        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
        ]);

        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products',
            'thumb_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'category' => ['required', Rule::exists('categories', 'id')->where('status', 1)],
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'weight' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'sale_unit_qty' => 'nullable|integer|min:1|max:9999',
            'colors' => 'nullable|array|max:20',
            'colors.*.name' => 'nullable|string|max:80',
            'colors.*.price' => 'nullable|numeric|min:0',
            'colors.*.qty' => 'nullable|integer|min:0',
            'colors.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
        ];
        $customMessages = [
            'short_name.required' => trans('admin_validation.Short name is required'),
            'short_name.unique' => trans('admin_validation.Short name is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'name.unique' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'thumb_image.required' => trans('admin_validation.thumbnail is required'),
            'short_description.required' => trans('admin_validation.Short description is required'),
            'long_description.required' => trans('admin_validation.Long description is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'quantity.required' => trans('admin_validation.Quantity is required'),
        ];
        $this->validate($request, $rules,$customMessages);


        $seller = Auth::guard('web')->user()->seller;
        $product = new Product();
        if ($request->thumb_image) {
            try {
                $product->thumb_image = app(ProductImageStorage::class)
                    ->store($request->file('thumb_image'), $request->name ?: 'product');
            } catch (Throwable $e) {
                Log::error('Seller product thumbnail upload failed on store', [
                    'seller_id' => $seller->id ?? null,
                    'message' => $e->getMessage(),
                ]);
                $notification = [
                    'messege' => $e->getMessage() ?: 'Kapak görseli yüklenemedi. JPEG/PNG/WebP kullanın ve tekrar deneyin.',
                    'alert-type' => 'error',
                ];

                return redirect()->back()->withInput()->with($notification);
            }
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
        $product->offer_price = $request->filled('offer_price') ? $request->offer_price : 0;
        $product->sale_unit_qty = max(1, (int) ($request->input('sale_unit_qty', 1) ?: 1));
        $product->qty = $request->quantity ? $request->quantity : 0;
        $product->short_description = $request->short_description;
        $product->long_description = clean($request->long_description);
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

        app(SimpleProductColorService::class)->sync(
            $product,
            app(SimpleProductColorService::class)->payloadFromRequest($request)
        );

        if ($request->hasFile('images')) {
            $storage = app(ProductImageStorage::class);
            foreach ($request->file('images') as $image) {
                if (! $image) {
                    continue;
                }
                try {
                    $image_name = $storage->store($image, 'Gallery');
                    $gallery = new ProductGallery();
                    $gallery->product_id = $product->id;
                    $gallery->image = $image_name;
                    $gallery->save();
                } catch (Throwable $e) {
                    Log::error('Seller product gallery upload failed on store', [
                        'product_id' => $product->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

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
        $notification = 'Ürününüz başarıyla eklendi ve yayına alındı.';
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('seller.product.index')->with($notification);
    }

    public function show($id)
    {
        $product = $this->findSellerProduct($id, ['category','brand','gallery','specifications','reviews','variants','variantItems']);
        if (! $product) {
            return response()->json(['error' => trans('admin_validation.Something went wrong')], 403);
        }

        return response()->json(['product' => $product], 200);
    }

    public function edit($id)
    {
        $product = $this->findSellerProduct($id, ['category','brand','gallery','variants','variantItems']);
        if (! $product) {
            return $this->denySellerProductAccess();
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
        $brands = Brand::all();
        $specificationKeys = ProductSpecificationKey::all();
        $productSpecifications = ProductSpecification::where('product_id',$product->id)->get();


        $setting = Setting::first();
        $aiEnabled = (bool) $setting->openai_enabled || (bool) $setting->claude_enabled;
        $seller = Auth::guard('web')->user()->seller;
        $commissionRate = $seller ? ($seller->getEffectiveCommissionRate() ?: 10) : 10;
        $colorRows = app(SimpleProductColorService::class)->existingRows($product);

        return view('seller.edit_product',compact('categories','brands','specificationKeys','product','subCategories','childCategories','productSpecifications','aiEnabled','commissionRate','colorRows'));

    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
        ]);


        $product = $this->findSellerProduct($id);
        if (! $product) {
            return $this->denySellerProductAccess();
        }

        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$product->id,
            'category' => ['required', Rule::exists('categories', 'id')->where('status', 1)],
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'weight' => 'nullable|numeric',
            'quantity' => 'required|numeric',
            'sale_unit_qty' => 'nullable|integer|min:1|max:9999',
            'colors' => 'nullable|array|max:20',
            'colors.*.name' => 'nullable|string|max:80',
            'colors.*.price' => 'nullable|numeric|min:0',
            'colors.*.qty' => 'nullable|integer|min:0',
            'colors.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
        ];
        $customMessages = [
            'short_name.required' => trans('admin_validation.Short name is required'),
            'short_name.unique' => trans('admin_validation.Short name is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'name.unique' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'thumb_image.required' => trans('admin_validation.thumbnail is required'),
            'banner_image.required' => trans('admin_validation.Banner is required'),
            'short_description.required' => trans('admin_validation.Short description is required'),
            'long_description.required' => trans('admin_validation.Long description is required'),
            'brand.required' => trans('admin_validation.Brand is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'quantity.required' => trans('admin_validation.Quantity is required'),
            'status.required' => trans('admin_validation.Status is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        try {
        if ($request->thumb_image) {
            try {
                $old_thumbnail = $product->thumb_image;
                $product->thumb_image = app(ProductImageStorage::class)
                    ->store($request->file('thumb_image'), $request->name ?: 'product');
                $product->save();
                if ($old_thumbnail) {
                    if (File::exists(public_path().'/'.$old_thumbnail)) {
                        unlink(public_path().'/'.$old_thumbnail);
                    }
                }
            } catch (Throwable $e) {
                Log::error('Seller product thumbnail upload failed on update', [
                    'product_id' => $product->id,
                    'message' => $e->getMessage(),
                ]);
                $notification = [
                    'messege' => $e->getMessage() ?: 'Kapak görseli güncellenemedi. JPEG/PNG/WebP kullanın ve tekrar deneyin.',
                    'alert-type' => 'error',
                ];

                return redirect()->back()->withInput()->with($notification);
            }
        }


        $product->short_name = $request->short_name;
        $product->name = $request->name;
        $product->slug = $request->slug;

        $categoryId = (int) $request->category;
        $subCategoryId = $request->sub_category ? (int) $request->sub_category : 0;
        $childCategoryId = $request->child_category ? (int) $request->child_category : 0;

        if ($subCategoryId > 0 && ! SubCategory::query()
            ->where('id', $subCategoryId)
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->exists()) {
            $subCategoryId = 0;
            $childCategoryId = 0;
        }

        if ($childCategoryId > 0 && ($subCategoryId <= 0 || ! ChildCategory::query()
            ->where('id', $childCategoryId)
            ->where('sub_category_id', $subCategoryId)
            ->where('status', 1)
            ->exists())) {
            $childCategoryId = 0;
        }

        $product->category_id = $categoryId;
        $product->sub_category_id = $subCategoryId;
        $product->child_category_id = $childCategoryId;
        $product->brand_id = $request->brand ? $request->brand : 0;
        $product->qty = $request->quantity ? $request->quantity : 0;
        $product->sale_unit_qty = max(1, (int) ($request->input('sale_unit_qty', 1) ?: 1));
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->offer_price = $request->filled('offer_price') ? $request->offer_price : 0;
        $product->short_description = $request->short_description;
        $product->long_description = clean($request->long_description);
        $product->tags = $request->tags;

        $product->weight = $request->filled('weight') ? $request->weight : 0;
        $product->is_specification = $request->is_specification ? 1 : 0;
        $product->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $product->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $product->is_top = $request->top_product ? 1 : 0;
        $product->new_product = $request->new_arrival ? 1 : 0;
        $product->is_best = $request->best_product ? 1 : 0;
        $product->is_featured = $request->is_featured ? 1 : 0;
        if ($product->approve_by_admin == 1 && $request->filled('status')) {
            $product->status = (int) $request->status;
        }
        $product->save();

        app(SimpleProductColorService::class)->sync(
            $product,
            app(SimpleProductColorService::class)->payloadFromRequest($request)
        );

        $exist_specifications=[];
        if ($request->boolean('is_specification') && is_array($request->keys)) {
            $specifications = $request->input('specifications', []);
            foreach ($request->keys as $index => $key) {
                if ($key && isset($specifications[$index]) && $specifications[$index] !== '') {
                    if (! in_array($key, $exist_specifications, true)) {
                        $existSroductSpecification = ProductSpecification::where(['product_id' => $product->id,'product_specification_key_id' => $key])->first();
                        if ($existSroductSpecification) {
                            $existSroductSpecification->specification = $specifications[$index];
                            $existSroductSpecification->save();
                        } else {
                            $productSpecification = new ProductSpecification();
                            $productSpecification->product_id = $product->id;
                            $productSpecification->product_specification_key_id = $key;
                            $productSpecification->specification = $specifications[$index];
                            $productSpecification->save();
                        }
                    }
                    $exist_specifications[] = $key;
                }
            }
        }
        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        $activeTab = in_array($request->input('active_tab'), ['content', 'images', 'seo'], true)
            ? $request->input('active_tab')
            : 'content';

        return redirect()
            ->route('seller.product.edit', $product->id)
            ->with($notification)
            ->with('active_tab', $activeTab);
        } catch (Throwable $e) {
            Log::error('Seller product update failed', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with([
                'messege' => 'Ürün güncellenemedi: ' . ($e->getMessage() ?: 'Bilinmeyen hata'),
                'alert-type' => 'error',
            ]);
        }
    }

    public function destroy($id)
    {
        $seller = Auth::guard('web')->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->first();

        if (! $product) {
            $notification = ['messege' => trans('admin_validation.Something went wrong'), 'alert-type' => 'error'];
            return redirect()->route('seller.product.index')->with($notification);
        }

        if (OrderProduct::where('product_id', $id)->exists()) {
            $notification = ['messege' => 'Satışı olan ürün silinemez. Pasife alabilirsiniz.', 'alert-type' => 'error'];
            return redirect()->route('seller.product.index')->with($notification);
        }

        $gallery = $product->gallery;
        $old_thumbnail = $product->thumb_image;

        try {
            // Related rows first, product last — avoids 500 after partial delete.
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
        } catch (Throwable $e) {
            Log::error('Seller product delete failed', [
                'product_id' => $id,
                'seller_id' => $seller->id ?? null,
                'message' => $e->getMessage(),
            ]);
            $notification = ['messege' => 'Ürün silinemedi. Lütfen tekrar deneyin.', 'alert-type' => 'error'];
            return redirect()->route('seller.product.index')->with($notification);
        }

        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('seller.product.index')->with($notification);

    }

    public function duplicate($id)
    {
        $seller = Auth::guard('web')->user()->seller;
        $sourceProduct = Product::with(['gallery', 'specifications', 'variants.variantItems'])
            ->where('id', $id)
            ->where('vendor_id', $seller->id)
            ->first();

        if (! $sourceProduct) {
            $notification = trans('admin_validation.Something went wrong');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];
            return redirect()->route('seller.product.index')->with($notification);
        }

        try {
            $duplicatedProduct = null;

            \DB::transaction(function () use ($sourceProduct, &$duplicatedProduct) {
                $duplicatedProduct = $sourceProduct->replicate();

                $duplicatedProduct->name = trim($sourceProduct->name.' (Kopya)');
                $duplicatedProduct->short_name = trim(($sourceProduct->short_name ?: $sourceProduct->name).' (Kopya)');
                $duplicatedProduct->slug = ProductSlug::normalize($duplicatedProduct->name);
                $duplicatedProduct->sku = $sourceProduct->sku ? ($sourceProduct->sku.'-CP'.rand(10, 99)) : null;
                $duplicatedProduct->status = 0;
                $duplicatedProduct->approve_by_admin = 1;
                $duplicatedProduct->thumb_image = $this->duplicateImagePath($sourceProduct->thumb_image, 'thumb-copy');

                $baseSlug = $duplicatedProduct->slug;
                $index = 1;
                while (Product::where('slug', $duplicatedProduct->slug)->exists()) {
                    $duplicatedProduct->slug = $baseSlug.'-'.$index;
                    $index++;
                }

                $duplicatedProduct->save();

                foreach ($sourceProduct->specifications as $specification) {
                    $newSpecification = $specification->replicate();
                    $newSpecification->product_id = $duplicatedProduct->id;
                    $newSpecification->save();
                }

                foreach ($sourceProduct->gallery as $galleryImage) {
                    $newGalleryImage = $galleryImage->replicate();
                    $newGalleryImage->product_id = $duplicatedProduct->id;
                    $newGalleryImage->image = $this->duplicateImagePath($galleryImage->image, 'gallery-copy');
                    $newGalleryImage->save();
                }

                $variantIdMap = [];
                foreach ($sourceProduct->variants as $variant) {
                    $newVariant = $variant->replicate();
                    $newVariant->product_id = $duplicatedProduct->id;
                    $newVariant->save();
                    $variantIdMap[$variant->id] = $newVariant->id;
                }

                foreach ($sourceProduct->variantItems as $variantItem) {
                    $newVariantItem = $variantItem->replicate();
                    $newVariantItem->product_id = $duplicatedProduct->id;
                    $newVariantItem->product_variant_id = $variantIdMap[$variantItem->product_variant_id] ?? null;
                    $newVariantItem->save();
                }
            });

            $notification = ['messege' => 'Ürün kopyalandı. Düzenleyip kaydedebilirsiniz.', 'alert-type' => 'success'];
            return redirect()->route('seller.product.edit', $duplicatedProduct->id)->with($notification);
        } catch (Throwable $e) {
            Log::error('Seller product duplicate failed', [
                'source_product_id' => $sourceProduct->id,
                'seller_id' => $seller->id ?? null,
                'message' => $e->getMessage(),
            ]);

            $notification = ['messege' => 'Ürün kopyalanamadı. Lütfen tekrar deneyin.', 'alert-type' => 'error'];
            return redirect()->route('seller.product.index')->with($notification);
        }
    }

    public function changeStatus(Request $request, $id){
        $seller = Auth::guard('web')->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->first();
        if (! $product) {
            return response()->json(trans('admin_validation.Something went wrong'), 403);
        }

        $publishStatus = app(\App\Support\ProductSellerPublishStatus::class);

        if ($request->boolean('deactivate')) {
            if ((int) $product->status === 0) {
                return response()->json('Ürün zaten pasif.');
            }
            $product->status = 0;
            $product->save();

            return response()->json(trans('admin_validation.Inactive Successfully'));
        }

        $tryingToActivate = $request->boolean('activate')
            || (! $request->boolean('deactivate') && (int) $product->status === 0);

        if ($tryingToActivate) {
            if ($publishStatus->isBlockedByAdmin($product)) {
                return response()->json('Bu ürün admin tarafından pasife alındı. Tekrar aktive etmek için destek ile iletişime geçin.', 403);
            }

            $issues = $publishStatus->issues($product);
            if ($issues !== []) {
                return response()->json('Yayına almak için eksikleri tamamlayın: ' . implode(', ', $issues), 422);
            }

            $product->status = 1;
            $product->approve_by_admin = 1;
            $product->save();

            return response()->json(trans('admin_validation.Active Successfully'));
        }

        if ((int) $product->status === 1) {
            $product->status = 0;
            $product->save();

            return response()->json(trans('admin_validation.Inactive Successfully'));
        }

        $product->status = 1;
        $product->save();

        return response()->json(trans('admin_validation.Active Successfully'));
    }

    public function removedProductExistSpecification($id){
        $productSpecification = ProductSpecification::find($id);
        if (! $productSpecification) {
            return response()->json(trans('admin_validation.Something went wrong'), 404);
        }
        $product = $this->findSellerProductById($productSpecification->product_id);
        if (! $product) {
            return response()->json(trans('admin_validation.Something went wrong'), 403);
        }
        $productSpecification->delete();
        $message = trans('admin_validation.Removed Successfully');
        return response()->json($message);
    }


    public function product_import_page()
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller || $seller->kyc_status !== 'approved') {
            $notification = array('messege' => 'Toplu yükleme için KYC doğrulamanız onaylanmış olmalı.', 'alert-type' => 'error');
            return redirect()->route('seller.kyc')->with($notification);
        }

        $imports = BulkImport::query()
            ->where('user_id', Auth::guard('web')->id())
            ->where('user_type', 'seller')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $bulkImportService = app(BulkProductImportService::class);
        $bulkImportService->failStaleProcessingImports((int) Auth::guard('web')->id(), 'seller');

        return view('seller.product_import_page', compact('seller', 'imports'));
    }

    public function product_bulk_import_template(BulkProductImportService $bulkImportService)
    {
        return response($bulkImportService->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="seyfibaba-urun-sablonu.csv"',
        ]);
    }

    public function product_bulk_import_sample()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductBulkImportSampleExport(),
            'seyfibaba-ornek-urun-yukleme.xlsx'
        );
    }

    public function product_export()
    {
        abort(403, 'Ürün dışa aktarma geçici olarak devre dışı.');
    }


    public function demo_product_export()
    {
        abort(403, 'Ürün dışa aktarma geçici olarak devre dışı.');
    }



    public function product_import(UploadBulkProductImportRequest $request, BulkProductImportService $bulkImportService)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller || $seller->kyc_status !== 'approved') {
            $notification = array('messege' => 'Toplu yükleme için KYC doğrulamanız onaylanmış olmalı.', 'alert-type' => 'error');
            return redirect()->route('seller.kyc')->with($notification);
        }

        try {
            $bulkImport = $bulkImportService->createImportRecord(
                (int) Auth::guard('web')->id(),
                'seller',
                $request->file('import_file')
            );
            $bulkImportService->queueProcess($bulkImport, $seller);

            $notification = [
                'messege' => $bulkImportService->processingMessage($bulkImport),
                'alert-type' => 'info',
                'bulk_import_id' => $bulkImport->id,
            ];

            return redirect()->route('seller.product-import-page')->with($notification);
        } catch (Throwable $e) {
            Log::error('Seller bulk import failed', ['message' => $e->getMessage()]);

            return redirect()->back()->with([
                'messege' => 'Dosya işlenemedi. Şablonu kontrol edip tekrar deneyin.',
                'alert-type' => 'error',
            ]);
        }
    }

    public function aiGenerateContent(Request $request)
    {
        return app(\App\Http\Controllers\AiContentController::class)->generate($request);
    }

    public function updateThumbnail(Request $request, $id)
    {
        $request->validate(['thumb_image' => 'required|image|max:5120']);

        $seller = auth()->user()->seller;
        $product = Product::where('id', $id)->where('vendor_id', $seller->id)->firstOrFail();

        try {
            $old = $product->thumb_image;
            $name = app(ProductImageStorage::class)->store($request->file('thumb_image'), 'thumb-'.$id);
            $product->thumb_image = $name;
            $product->save();

            if ($old && File::exists(public_path('/' . $old))) {
                unlink(public_path('/' . $old));
            }

            return response()->json(['success' => true, 'message' => 'Thumbnail güncellendi.', 'image' => asset($name)]);
        } catch (Throwable $e) {
            Log::error('Seller product thumbnail ajax update failed', [
                'product_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Kapak görseli güncellenemedi.',
            ], 500);
        }
    }

    private function duplicateImagePath(?string $originalPath, string $prefix = 'copy'): ?string
    {
        if (! $originalPath) {
            return null;
        }

        $originalAbsolutePath = public_path('/'.$originalPath);
        if (! File::exists($originalAbsolutePath)) {
            return $originalPath;
        }

        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'] ?? 'uploads/custom-images';
        $extension = $pathInfo['extension'] ?? 'jpg';
        $filename = Str::slug($pathInfo['filename'] ?? 'image');

        $newRelativePath = $directory.'/'.$prefix.'-'.$filename.'-'.date('Y-m-d-h-i-s-').rand(1000, 9999).'.'.$extension;
        $newAbsolutePath = public_path('/'.$newRelativePath);
        $targetDirectory = dirname($newAbsolutePath);
        if (! File::isDirectory($targetDirectory)) {
            File::makeDirectory($targetDirectory, 0755, true);
        }

        File::copy($originalAbsolutePath, $newAbsolutePath);

        return $newRelativePath;
    }

}
