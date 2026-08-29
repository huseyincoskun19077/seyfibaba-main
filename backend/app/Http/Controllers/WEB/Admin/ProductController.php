<?php

namespace App\Http\Controllers\WEB\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\ProductGallery;
use App\Models\Brand;
use App\Models\ProductSpecificationKey;
use App\Models\ProductSpecification;
use App\Models\OrderProduct;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use App\Models\OrderProductVariant;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\Wishlist;
use App\Models\Setting;
use App\Models\HomePageOneVisibility;
use App\Models\FlashSaleProduct;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartVariant;
use App\Models\CompareProduct;
use Image;
use File;
use Str;

use App\Exports\ProductExport;
use App\Imports\ProductImport;
use App\Http\Requests\Seller\UploadBulkProductImportRequest;
use App\Models\BulkImport;
use App\Models\Vendor;
use App\Services\BulkProductImportService;
use App\Support\ProductSlug;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $vitrine = (string) $request->get('vitrine', 'homepage');
        $search = trim((string) $request->get('search', ''));
        $flagColumns = ['is_top', 'is_featured', 'is_best', 'new_product'];

        $products = Product::with(['category', 'seller', 'brand'])
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('short_name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when($vitrine === 'homepage' || $vitrine === '', function ($query) {
                $query->where(function ($flags) {
                    $flags->where('is_top', 1)
                        ->orWhere('is_featured', 1)
                        ->orWhere('is_best', 1)
                        ->orWhere('new_product', 1);
                });
            })
            ->when(in_array($vitrine, $flagColumns, true), function ($query) use ($vitrine) {
                $query->where($vitrine, 1);
            })
            ->orderBy('id', 'desc')
            ->paginate(40)
            ->withQueryString();

        $pageIds = $products->pluck('id');
        $orderProducts = OrderProduct::query()
            ->whereIn('product_id', $pageIds)
            ->get(['id', 'product_id']);
        $setting = Setting::first();
        $frontend_url = rtrim((string) ($setting->frontend_url ?? ''), '/').'/urun/';
        $productViews = \App\Models\ProductView::all();
        $shoppingCarts = \App\Models\ShoppingCart::all();
        $homepageFlagCounts = $this->homepageFlagCounts();

        return view('admin.product', compact(
            'products',
            'orderProducts',
            'setting',
            'frontend_url',
            'productViews',
            'shoppingCarts',
            'homepageFlagCounts',
            'vitrine',
            'search'
        ));
    }

    public function sellerProduct(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', 'all');
        $categoryId = (string) $request->get('category_id', 'all');

        $products = Product::with(['category', 'subCategory', 'seller.user', 'brand'])
            ->where('vendor_id', '!=', 0)
            ->where('approve_by_admin', 1)
            ->whereHas('seller')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('short_name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhereHas('seller', function ($seller) use ($search) {
                            $seller->where('shop_name', 'like', '%'.$search.'%')
                                ->orWhereHas('user', function ($user) use ($search) {
                                    $user->where('name', 'like', '%'.$search.'%')
                                        ->orWhere('email', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->when(in_array($status, ['0', '1'], true), function ($query) use ($status) {
                $query->where('status', (int) $status);
            })
            ->when($categoryId !== '' && $categoryId !== 'all', function ($query) use ($categoryId) {
                $query->where('category_id', (int) $categoryId);
            })
            ->orderBy('id', 'desc')
            ->paginate(40)
            ->withQueryString();

        $orderProducts = OrderProduct::select('id', 'product_id')->get();
        $setting = Setting::first();
        $frontend_url = rtrim($setting?->frontend_url ?? config('app.frontend_url', config('app.url')), '/').'/urun/';
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.seller_product', compact(
            'products',
            'orderProducts',
            'setting',
            'frontend_url',
            'categories'
        ));
    }

    public function sellerPendingProduct(){
        $products = Product::with('category','seller','brand')
            ->where('vendor_id','!=',0)
            ->where('approve_by_admin',0)
            ->whereHas('seller')
            ->orderBy('id','desc')
            ->paginate(40)
            ->withQueryString();
        $orderProducts = OrderProduct::select('id', 'product_id')->get();
        $setting = Setting::first();
        $frontend_url = rtrim($setting?->frontend_url ?? config('app.frontend_url', config('app.url')), '/').'/urun/';

        return view('admin.pending_product',compact('products','orderProducts','setting','frontend_url'));

    }

    public function stockoutProduct(){
        $products = Product::with('category','seller','brand')->where('vendor_id',0)->where('qty',0)->get();
        $orderProducts = OrderProduct::all();
        $setting = Setting::first();
        $frontend_url = $setting->frontend_url;
        $frontend_url = rtrim($frontend_url, '/').'/urun/';

        return view('admin.stockout_product',compact('products','orderProducts','setting','frontend_url'));

    }



    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        $specificationKeys = ProductSpecificationKey::all();
        $setting = Setting::first();
        $aiEnabled = (bool) $setting->openai_enabled || (bool) $setting->claude_enabled;
        $sellers = $this->sellerOptions();

        return view('admin.create_product',compact('categories','brands','specificationKeys','aiEnabled','sellers'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
            'vendor_id' => (int) ($request->vendor_id ?? 0),
        ]);

        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products',
            'thumb_image' => 'required',
            'category' => 'required',
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'required|numeric',
            'quantity' => 'required|numeric',
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::when((int) $request->vendor_id > 0, ['exists:vendors,id']),
            ],
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
            'weight.required' => trans('admin_validation.Weight is required'),
            'vendor_id.exists' => 'Seçilen satıcı bulunamadı.',
        ];
        $this->validate($request, $rules,$customMessages);

        $product = new Product();
        if($request->thumb_image){
            $extention = $request->thumb_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumb_image)
                ->save(public_path().'/'.$image_name);
            $product->thumb_image=$image_name;
        }

        $product->vendor_id = (int) ($request->vendor_id ?? 0);
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
        $product->qty = $request->quantity ? $request->quantity : 0;
        $product->short_description = $request->short_description;
        $product->long_description = clean($request->long_description);
        $product->status = $request->status;
        $product->weight = $request->weight;
        $product->is_undefine = 1;
        $product->is_specification = $request->is_specification ? 1 : 0;
        $product->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $product->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $product->is_top = $request->top_product ? 1 : 0;
        $product->new_product = $request->new_arrival ? 1 : 0;
        $product->is_best = $request->best_product ? 1 : 0;
        $product->is_featured = $request->is_featured ? 1 : 0;
        $product->approve_by_admin = 1;
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
        $notification = trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product.index')->with($notification);
    }

    public function show($id)
    {
        $product = Product::with('category','brand','gallery','specifications','reviews','variants','variantItems')->find($id);
        if($product->vendor_id == 0){
            $notification = 'Something went wrong';
            return response()->json(['error'=>$notification],403);
        }

        return response()->json(['product' => $product], 200);
    }


    public function edit($id)
    {
        $product = Product::with('category','brand','gallery','variants','variantItems')->find($id);
        $categories = Category::all();
        $subCategories = SubCategory::where('category_id',$product->category_id)->get();
        $childCategories = ChildCategory::where('sub_category_id', $product->sub_category_id)->get();
        $brands = Brand::all();
        $specificationKeys = ProductSpecificationKey::all();
        $productSpecifications = ProductSpecification::where('product_id',$product->id)->get();

        $setting = Setting::first();
        $aiEnabled = (bool) $setting->openai_enabled || (bool) $setting->claude_enabled;
        $sellers = $this->sellerOptions();

        return view('admin.edit_product',compact('categories','brands','specificationKeys','product','subCategories','childCategories','productSpecifications','aiEnabled','sellers'));

    }


    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => ProductSlug::normalize($request->slug ?: $request->name),
            'vendor_id' => (int) ($request->vendor_id ?? 0),
        ]);

        $product = Product::find($id);
        $rules = [
            'short_name' => 'required',
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$product->id,
            'category' => 'required',
            'short_description' => 'required',
            'long_description' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
            'weight' => 'required',
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::when((int) $request->vendor_id > 0, ['exists:vendors,id']),
            ],
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
            'weight.required' => trans('admin_validation.Weight is required'),
            'vendor_id.exists' => 'Seçilen satıcı bulunamadı.',
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


        $product->vendor_id = (int) ($request->vendor_id ?? 0);
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
        $product->short_description = $request->short_description;
        $product->long_description = clean($request->long_description);
        $product->tags = $request->tags;
        $product->status = $request->status;
        $product->weight = $request->weight;
        $product->is_specification = $request->is_specification ? 1 : 0;
        $product->seo_title = $request->seo_title ? $request->seo_title : $request->name;
        $product->seo_description = $request->seo_description ? $request->seo_description : $request->name;
        $product->is_top = $request->top_product ? 1 : 0;
        $product->new_product = $request->new_arrival ? 1 : 0;
        $product->is_best = $request->best_product ? 1 : 0;
        $product->is_featured = $request->is_featured ? 1 : 0;
        if($product->vendor_id != 0){
            $product->approve_by_admin = $request->approve_by_admin ?? 1;
        }
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
        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product.index')->with($notification);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        $gallery = $product->gallery;
        $old_thumbnail = $product->thumb_image;
        $product->delete();
        if($old_thumbnail){
            if(File::exists(public_path().'/'.$old_thumbnail))unlink(public_path().'/'.$old_thumbnail);
        }
        foreach($gallery as $image){
            $old_image = $image->image;
            $image->delete();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }
        ProductVariant::where('product_id',$id)->delete();
        ProductVariantItem::where('product_id',$id)->delete();
        FlashSaleProduct::where('product_id',$id)->delete();
        ProductReport::where('product_id',$id)->delete();
        ProductReview::where('product_id',$id)->delete();
        ProductSpecification::where('product_id',$id)->delete();
        Wishlist::where('product_id',$id)->delete();
        $cartProducts = ShoppingCart::where('product_id',$id)->get();
        foreach($cartProducts as $cartProduct){
            ShoppingCartVariant::where('shopping_cart_id', $cartProduct->id)->delete();
            $cartProduct->delete();
        }
        CompareProduct::where('product_id',$id)->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product.index')->with($notification);
    }

    public function changeStatus($id){
        $product = Product::find($id);
        if($product->status == 1){
            $product->status = 0;
            // Satıcı ürünü admin tarafından pasife alındıysa tekrar sadece admin açabilsin
            if ((int) $product->vendor_id !== 0) {
                $product->approve_by_admin = 0;
            }
            $product->save();
            $message = trans('admin_validation.InActive Successfully');
        }else{
            $product->status = 1;
            if ((int) $product->vendor_id !== 0) {
                $product->approve_by_admin = 1;
            }
            $product->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

    public function toggleHomepageFlag(Request $request, $id)
    {
        $allowed = ['is_top', 'is_featured', 'is_best', 'new_product'];
        $flag = (string) $request->input('flag');
        if (! in_array($flag, $allowed, true)) {
            return response()->json(['message' => 'Geçersiz vitrin alanı'], 422);
        }

        $product = Product::findOrFail($id);
        $product->{$flag} = $request->boolean('value') ? 1 : 0;
        $product->save();

        return response()->json([
            'message' => 'Kaydedildi',
            'counts' => $this->homepageFlagCounts(),
        ]);
    }

    public function updateHomepageQty(Request $request)
    {
        $map = [
            'is_top' => 4,
            'is_featured' => 8,
            'is_best' => 10,
            'new_product' => 9,
        ];
        $flag = (string) $request->input('flag');
        if (! isset($map[$flag])) {
            return response()->json(['message' => 'Geçersiz vitrin alanı'], 422);
        }

        $qty = max(1, min(24, (int) $request->input('qty', 4)));
        $section = HomePageOneVisibility::find($map[$flag]);
        if ($section) {
            $section->qty = $qty;
            $section->save();
        }

        return response()->json([
            'message' => 'Anasayfa adedi güncellendi',
            'counts' => $this->homepageFlagCounts(),
        ]);
    }

    private function homepageFlagCounts(): array
    {
        $activeTotal = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->count();

        $make = function (string $column, int $visibilityId) use ($activeTotal) {
            $selected = Product::query()->where($column, 1)->count();
            $qty = max(1, (int) (HomePageOneVisibility::find($visibilityId)->qty ?? 4));

            return [
                'selected' => $selected,
                'qty' => $qty,
                'homepage' => min($qty, $activeTotal),
            ];
        };

        return [
            'is_top' => $make('is_top', 4),
            'is_featured' => $make('is_featured', 8),
            'is_best' => $make('is_best', 10),
            'new_product' => $make('new_product', 9),
        ];
    }

    public function productApproved($id){
        $product = Product::find($id);
        if($product->approve_by_admin == 1){
            $product->approve_by_admin = 0;
            $product->save();
            $message = trans('admin_validation.Reject Successfully');
        }else{
            $product->approve_by_admin = 1;
            $product->save();
            $message = trans('admin_validation.Approved Successfully');
        }
        return response()->json($message);
    }



    public function removedProductExistSpecification($id){
        $productSpecification = ProductSpecification::find($id);
        $productSpecification->delete();
        $message = trans('admin_validation.Removed Successfully');
        return response()->json($message);
    }




    public function product_import_page()
    {
        $imports = BulkImport::query()
            ->where('user_type', 'admin')
            ->orderByDesc('id')
            ->paginate(20);
        $sellers = $this->sellerOptions();

        return view('admin.product_import_page', compact('imports', 'sellers'));
    }

    public function product_export()
    {
        $is_dummy = false;
        return Excel::download(new ProductExport($is_dummy), 'products.xlsx');
    }


    public function demo_product_export()
    {
        $is_dummy = true;
        return Excel::download(new ProductExport($is_dummy), 'products.xlsx');
    }



    public function product_bulk_import_template(BulkProductImportService $bulkImportService)
    {
        return response($bulkImportService->templateCsv(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admin-product-import-template.csv"',
        ]);
    }



    public function product_import(UploadBulkProductImportRequest $request, BulkProductImportService $bulkImportService)
    {
        try{
            $adminId = (int) Auth::guard('admin')->id();
            $vendorId = (int) $request->input('vendor_id', 0);
            $vendor = $vendorId > 0 ? Vendor::query()->find($vendorId) : null;
            if ($vendorId > 0 && ! $vendor) {
                return redirect()->back()->with([
                    'messege' => 'Seçilen satıcı bulunamadı.',
                    'alert-type' => 'error',
                ]);
            }

            $bulkImport = $bulkImportService->createImportRecord($adminId, 'admin', $request->file('import_file'));
            $bulkImportService->queueProcess($bulkImport, $vendor);

            $sellerNote = $vendor
                ? (' Ürünler «'.$vendor->shop_name.'» satıcısı adına yüklenecek.')
                : ' Ürünler admin ürünü olarak (satıcısız) yüklenecek.';

            $notification = array(
                'messege' => $bulkImportService->processingMessage($bulkImport).$sellerNote,
                'alert-type' => 'info',
            );
            return redirect()->back()->with($notification);

        }catch(Exception $ex){
            $notification=trans('Please follow the instruction and input the value carefully');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }


    }

    private function sellerOptions()
    {
        return Vendor::query()
            ->with('user:id,name')
            ->orderBy('shop_name')
            ->get(['id', 'shop_name', 'user_id', 'status']);
    }



}
