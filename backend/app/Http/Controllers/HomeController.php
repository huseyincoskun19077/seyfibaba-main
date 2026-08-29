<?php
namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Models\HomePageOneVisibility;

use App\Models\Brand;

use App\Models\Slider;

use App\Models\Category;

use App\Models\SubCategory;

use App\Models\ChildCategory;

use App\Models\PopularCategory;

use App\Models\Product;

use App\Models\SecondHandListing;

use App\Models\BannerImage;

use App\Models\Service;


use App\Models\AboutUs;

use App\Models\ContactPage;

use App\Models\BreadcrumbImage;

use App\Models\CustomPagination;

use App\Models\Faq;

use App\Models\CustomPage;

use App\Models\TermsAndCondition;
use App\Models\LegalDocument;

use App\Models\Vendor;

use App\Models\Subscriber;

use App\Mail\SubscriptionVerification;

use App\Mail\ContactMessageInformation;

use App\Helpers\MailHelper;

use App\Models\EmailTemplate;

use App\Models\ProductReview;

use App\Models\ProductSpecification;

use App\Models\ProductGallery;

use App\Models\Setting;

use App\Models\ContactMessage;


use App\Models\ProductVariant;

use App\Models\ProductVariantItem;

use App\Models\Testimonial;

use App\Models\GoogleRecaptcha;

use App\Models\Order;

use App\Models\ShopPage;
use App\Support\ProductFilterHelper;

use App\Models\SeoSetting;

use App\Models\FlashSale;
use App\Support\ProductSlug;

use App\Models\FlashSaleProduct;

use App\Rules\Captcha;

use Mail;

use Str;

use Session;

use Cart;

use Carbon\Carbon;

use Route;



use App\Models\FooterSocialLink;

use App\Models\AnnouncementModal;

use App\Models\MegaMenuCategory;

use App\Models\MenuVisibility;

use App\Models\GoogleAnalytic;

use App\Models\FacebookPixel;

use App\Models\TawkChat;

use App\Models\CookieConsent;

use App\Models\FeaturedCategory;

use App\Models\FooterLink;

use App\Models\Footer;

use App\Models\MultiCurrency;

use App\Models\Language;

use App\Models\MaintainanceText;
use App\Models\PusherCredentail;

use Artisan;

use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    protected function normalizeLanguagePayload(array $language): array
    {
        $normalized = [];

        foreach ($language as $key => $value) {
            $normalizedKey = str_replace(
                ['-', ',', '.', "'", '!', '?', ' '],
                [' ', ' ', ' ', '', '', '', '_'],
                (string) $key
            );

            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Aktif markalar — filtre/ürün listesi ile aynı kaynak (logo boş olabilir).
     */
    private function activeBrandsForStorefront()
    {
        return Brand::query()
            ->where('status', 1)
            ->orderBy('name')
            ->select('id', 'name', 'slug', 'logo')
            ->get()
            ->map(function ($brand) {
                $brand->logo = $brand->logo ?? '';

                return $brand;
            })
            ->values();
    }

    public function brandList()
    {
        return response()->json([
            'brands' => $this->activeBrandsForStorefront(),
        ]);
    }

    public function productCount()
    {
        $count = Product::where(['status' => 1, 'approve_by_admin' => 1])->count();
        return response()->json(['count' => $count]);
    }

    public function productSitemap()
    {
        $products = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereNotIn('slug', ['test-urunu-5-tl'])
            ->whereNotNull('thumb_image')
            ->where('thumb_image', '!=', '')
            ->whereHas('seller', fn ($query) => $query->where('status', 1))
            ->whereHas('category', fn ($query) => $query->where('status', 1))
            ->select('slug', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['products' => $products]);
    }

    public function secondHandSitemap()
    {
        $listings = SecondHandListing::query()
            ->where('status', SecondHandListing::STATUS_ACTIVE)
            ->select('id', 'updated_at')
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        return response()->json(['listings' => $listings]);
    }




    public function websiteSetup(Request $request){
      try {

        if($request->lang_code){
            $language = include(resource_path('lang/'.$request->lang_code.'/user.php'));
            $default_language = Language::where('lang_code',$request->lang_code)->first();
        }else{
            $language = include(resource_path('lang/en/user.php'));
            $default_language = Language::where('is_default','Yes')->first();
        }

        $language = $this->normalizeLanguagePayload($language);

        $currencies = MultiCurrency::where('status',1)->orderBy('currency_name','asc')->get();

        $settingColumns = [
            'currency_id', 'logo', 'favicon', 'enable_user_register', 'phone_number_required',
            'default_phone_code', 'enable_multivendor', 'text_direction', 'timezone', 'topbar_phone',
            'topbar_email', 'currency_icon', 'currency_name', 'show_product_progressbar', 'theme_one',
            'theme_two', 'seller_condition', 'map_status', 'bank_transfer_discount_percent',
            // map_key ve bank_transfer_info public API'de yok — gizli tutulur
        ];
        foreach ([
            'mobile_hub_bg_top', 'mobile_hub_bg_bottom', 'mobile_hub_feature_start',
            'mobile_hub_feature_end', 'mobile_hub_shop_image', 'mobile_hub_crm_image',
            'mobile_hub_secondhand_image',
            'mobile_onboarding_bg', 'mobile_onboarding_image_1',
            'mobile_onboarding_image_2', 'mobile_onboarding_image_3',
        ] as $column) {
            if (Schema::hasColumn('settings', $column)) {
                $settingColumns[] = $column;
            }
        }

        $setting = Setting::with('currency')->select($settingColumns)->first();
        if ($setting) {
            // Eski istemciler alan beklerse boş gelsin; anahtar asla sızmasın
            $setting->setAttribute('map_key', null);
            $setting->setAttribute('bank_transfer_info', null);
        }

        $announcementModal = AnnouncementModal::first();

        $productCategories = Category::with('activeSubCategories.activeChildCategories')->where(['status' => 1])->select('id','name','slug','icon')->get();

        $megaMenuCategories = MegaMenuCategory::with('category','subCategories.subCategory')->orderBy('serial','asc')->where('status',1)->get();

        $megaMenuBanner = BannerImage::find(23);

        $customPages = CustomPage::where('status',1)->get();

        $cookie_consent = CookieConsent::first();

        $maintainance = MaintainanceText::first();



        $flashSale = FlashSale::select('status','offer','end_time')->first();

        $flashSaleProducts = FlashSaleProduct::where('status',1)->select('product_id')->get();

        $flashSaleActive = $flashSale->status == 1 ? true : false;



        $seo_setting = SeoSetting::all();



        $shop_page = ShopPage::first();

        $filter_price_range = $shop_page->filter_price_range;





        $first_col_links = FooterLink::where('column',1)->get();

        $footer = Footer::first();

        $columnTitle = $footer->first_column;

        $footer_first_col = array(

            'col_links' => $first_col_links,

            'columnTitle' => $columnTitle

        );

        $footer_first_col = (object)$footer_first_col;



        $second_col_links = FooterLink::where('column',2)->get();

        $columnTitle = $footer->second_column;

        $footer_second_col = array(

            'col_links' => $second_col_links,

            'columnTitle' => $columnTitle

        );

        $footer_second_col = (object)$footer_second_col;



        $third_col_links = FooterLink::where('column',3)->get();

        $columnTitle = $footer->third_column;

        $footer_third_col = array(

            'col_links' => $third_col_links,

            'columnTitle' => $columnTitle

        );

        $footer_third_col = (object)$footer_third_col;



        $social_links = FooterSocialLink::all();



        $image_content = Setting::select('empty_cart','empty_wishlist', 'change_password_image', 'become_seller_avatar', 'become_seller_banner','login_image','error_page')->first();

        // Only expose public Pusher fields to the browser — app_secret and app_id must never leave the server
        $pusher = PusherCredentail::query()->first();
        $pusher_info = $pusher ? $pusher->toPublicArray() : null;

        // VarsayÄ±lan dili Ã¶nce gelecek ÅŸekilde sÄ±rala
        $language_list = Language::where('status', 1)
            ->orderByDesc('is_default')
            ->orderBy('lang_name', 'asc')
            ->get();
        
        $subscriptionBanner = BannerImage::select('id','image','banner_location','header','title')->find(27);


        return response()->json([

            'default_language' => $default_language,

            'language_list' => $language_list,

            'language' => $language,

            'setting' => $setting,

            'currencies' => $currencies,

            'maintainance' => $maintainance,

            'flashSaleActive' => $flashSaleActive,

            'flashSale' => $flashSale,

            'flashSaleProducts' => $flashSaleProducts,

            'announcementModal' => $announcementModal,

            'productCategories' => $productCategories,

            'megaMenuCategories' => $megaMenuCategories,

            'megaMenuBanner' => $megaMenuBanner,

            'customPages' => $customPages,

            // Tracking / chat kimlikleri public website-setup'ta yok
            'googleAnalytic' => null,
            'facebookPixel' => null,
            'tawk_setting' => null,

            'cookie_consent' => $cookie_consent,

            'seo_setting' => $seo_setting,

            'filter_price_range' => $filter_price_range,

            'footer_first_col' => $footer_first_col,

            'footer_second_col' => $footer_second_col,

            'footer_third_col' => $footer_third_col,

            'footer' => $footer,

            'social_links' => $social_links,

            'image_content' => $image_content,
            
            'pusher_info' => $pusher_info,
            
            'subscriptionBanner' => $subscriptionBanner,

        ]);

      } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('websiteSetup failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        return response()->json([
            'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
        ], 500);
      }
    }



    public function subCategoriesByCategory($id){

        $subCategories = SubCategory::where(['category_id' => $id, 'status' => 1])->get();

        return response()->json(['subCategories' => $subCategories]);

    }



    public function childCategoriesBySubCategory($id){

        $childCategories = ChildCategory::where(['sub_category_id' => $id, 'status' => 1])->get();

        return response()->json(['childCategories' => $childCategories]);

    }



    public function categoryList(){

        $categories = Category::where('status', 1)
            ->with(['activeSubCategories.activeChildCategories'])
            ->get();

        return response()->json(['categories' => $categories]);

    }

    public function category($id){

        $category = Category::find($id);

        return response()->json(['category' => $category]);

    }





    public function subCategory($id){

        $sub_category = SubCategory::find($id);

        return response()->json(['sub_category' => $sub_category]);

    }



    public function childCategory($id){

        $child_category = ChildCategory::find($id);

        return response()->json(['child_category' => $child_category]);

    }





    public function productByCategory($id){

        $category = Category::find($id);

        $products = Product::with('activeVariants.activeVariantItems')->where(['category_id' => $id, 'status' => 1,'approve_by_admin' => 1])->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id')->orderBy('id','desc')->get();

        return response()->json(['category' => $category, 'products' => $products]);

    }

    private function homepageFlagProducts(string $column, $qty)
    {
        $limit = max(1, (int) $qty);
        $select = ['id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id'];
        $base = Product::with('activeVariants.activeVariantItems')
            ->select($select)
            ->where('status', 1)
            ->where('approve_by_admin', 1);
        $picked = (clone $base)->where($column, 1)->orderByDesc('id')->take($limit)->get();
        if ($picked->count() >= $limit) {
            return $picked;
        }

        $need = $limit - $picked->count();
        $extra = (clone $base)
            ->when($picked->isNotEmpty(), function ($q) use ($picked) {
                $q->whereNotIn('id', $picked->pluck('id'));
            })
            ->inRandomOrder()
            ->take($need)
            ->get();

        return $picked->concat($extra)->values();
    }

    public function index()

    {



        $sliderVisibilty = HomePageOneVisibility::find(1);

        $sliders = Slider::orderBy('serial','asc')->where(['status' => 1])->get()->take($sliderVisibilty->qty);

        $sliderVisibilty = $sliderVisibilty->status == 1 ? true : false;



        $sliderBannerOne = BannerImage::select('id','product_slug','image','banner_location','title_one','title_two','badge','status')->find(16);

        $sliderBannerTwo = BannerImage::select('id','product_slug','image','banner_location','title_one','title_two','badge','status')->find(17);



        $serviceVisibilty = HomePageOneVisibility::find(2);

        $services = Service::where('status',1)->get()->take($serviceVisibilty->qty);

        $serviceVisibilty = $serviceVisibilty->status == 1 ? true : false;



        $popularCategoryVisibilty = HomePageOneVisibility::find(4);

        $popularCategories = PopularCategory::with('category')->get();

        $category_arr = [];

        foreach($popularCategories as $popularCategory){

            $category_arr[] = $popularCategory->category_id;

        }

        $setting = Setting::first();



        $popularCategoryProducts = $this->homepageFlagProducts('is_top', $popularCategoryVisibilty->qty ?? 8);

        $popularCategoryVisibilty = $popularCategoryVisibilty->status == 1 ? true : false;

        $popularCategorySidebarBanner = $setting->popular_category_banner;



        $brandVisibility = HomePageOneVisibility::find(5);

        $brands = $this->activeBrandsForStorefront();

        $brandVisibility = $brandVisibility?->status == 1;



        $flashSale = FlashSale::first();

        $flashSaleSidebarBanner = BannerImage::select('id','link as play_store','image','banner_location','status','title as app_store')->find(24);



        $topRatedVisibility = HomePageOneVisibility::find(6);

        $topRatedProducts = $this->homepageFlagProducts('is_top', $topRatedVisibility->qty ?? 8);

        $topRatedVisibility = $topRatedVisibility->status == 1 ? true : false;



        $sellerVisibility = HomePageOneVisibility::find(7);

        $sellers = collect();

        $sellerVisibility = false;



        $twoColumnBannerOne = BannerImage::select('id','product_slug','image','banner_location','status','title_one','title_two','badge')->find(19);

        $twoColumnBannerTwo = BannerImage::select('id','product_slug','image','banner_location','status','title_one','title_two','badge')->find(20);



        $featuredCategorySidebarBanner = $setting->featured_category_banner;



        $featuredProductVisibility = HomePageOneVisibility::find(8);

        $featuredCategories = FeaturedCategory::with('category')->get();

        $category_arr = [];

        foreach($featuredCategories as $featuredCategory){

            $category_arr[] = $featuredCategory->category_id;

        }





        $featuredCategoryProducts = $this->homepageFlagProducts('is_featured', $featuredProductVisibility->qty ?? 8);

        $featuredProductVisibility = $featuredProductVisibility->status == 1 ? true : false;



        $singleBannerOne = BannerImage::select('id','product_slug','image','banner_location','status','title_one','title_two')->find(21);



        $newArrivalProductVisibility = HomePageOneVisibility::find(9);

        $newArrivalProducts = $this->homepageFlagProducts('new_product', $newArrivalProductVisibility->qty ?? 8);

        $newArrivalProductVisibility = $newArrivalProductVisibility->status == 1 ? true : false;



        $singleBannerTwo = BannerImage::select('id','product_slug','image','banner_location','status','title_one')->find(22);



        $bestProductVisibility = HomePageOneVisibility::find(10);

        $bestProducts = $this->homepageFlagProducts('is_best', $bestProductVisibility->qty ?? 8);

        $discountedProducts = Product::with('activeVariants.activeVariantItems')
            ->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id')
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->where('offer_price', '>', 0)
            ->whereColumn('offer_price', '<', 'price')
            ->orderByDesc('id')
            ->take(2)
            ->get();

        $bestProductVisibility = $bestProductVisibility->status == 1 ? true : false;

        // Ana sayfa "Tüm Ürünler" bloğu — vitrin bayraklarına bağlı değil (yeni/gözde/kategori seçimi vb.)
        $allProductsHomeLimit = 120;
        $allProducts = Product::with('activeVariants.activeVariantItems')->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id','vendor_id')->where('status', 1)->where('approve_by_admin', 1)->inRandomOrder()->take($allProductsHomeLimit)->get();


        $seoSetting = SeoSetting::find(1);



        $setting = Setting::first();

        $section_title = json_decode($setting->homepage_section_title);
        if (is_array($section_title)) {
            foreach ($section_title as $section) {
                if (is_object($section) && ($section->key ?? '') === 'Popular_Category') {
                    $section->custom = 'En popüler ürünler';
                    $section->default = 'En popüler ürünler';
                }
                if (is_object($section) && ($section->key ?? '') === 'Top_Rated_Products') {
                    $section->custom = 'En popüler ürünler';
                    $section->default = 'En popüler ürünler';
                }
            }
        }

        Artisan::call('optimize:clear');



        $homepage_categories = Category::where(['status' => 1])->select('id','name','slug','description','icon','image')->get()->take(15);





        return response()->json([

            'section_title' => $section_title,

            'seoSetting' => $seoSetting,

            'sliderVisibilty' => $sliderVisibilty,

            'sliders' => $sliders,

            'sliderBannerOne' => $sliderBannerOne,

            'sliderBannerTwo' => $sliderBannerTwo,

            'serviceVisibilty' => $serviceVisibilty,

            'services' => $services,

            'homepage_categories' => $homepage_categories,

            'popularCategorySidebarBanner' => $popularCategorySidebarBanner,

            'popularCategoryVisibilty' => $popularCategoryVisibilty,

            'popularCategories' => $popularCategories,

            'popularCategoryProducts' => $popularCategoryProducts,

            'brandVisibility' => $brandVisibility,

            'brands' => $brands,

            'flashSale' => $flashSale,

            'flashSaleSidebarBanner' => $flashSaleSidebarBanner,

            'topRatedVisibility' => $topRatedVisibility,

            'topRatedProducts' => $topRatedProducts,

            'sellerVisibility' => $sellerVisibility,

            'sellers' => $sellers,

            'twoColumnBannerOne' => $twoColumnBannerOne,

            'twoColumnBannerTwo' => $twoColumnBannerTwo,

            'featuredProductVisibility' => $featuredProductVisibility,

            'featuredCategorySidebarBanner' => $featuredCategorySidebarBanner,

            'featuredCategories' => $featuredCategories,

            'featuredCategoryProducts' => $featuredCategoryProducts,

            'singleBannerOne' => $singleBannerOne,

            'newArrivalProductVisibility' => $newArrivalProductVisibility,

            'newArrivalProducts' => $newArrivalProducts,

            'bestProductVisibility' => $bestProductVisibility,

            'singleBannerTwo' => $singleBannerTwo,

            'bestProducts' => $bestProducts,

            'discountedProducts' => $discountedProducts,

            'allProducts' => $allProducts,

   

        ]);

    }



    public function aboutUs(){

        $aboutUs = AboutUs::first();

        $seoSetting = SeoSetting::find(2);

        $testimonials = Testimonial::where(['status' => 1])->get();

        $services = Service::where('status',1)->get();

        return response()->json([

            'aboutUs' => $aboutUs,

            'seoSetting' => $seoSetting,

            'testimonials' => $testimonials,

            'services' => $services,

        ]);

    }



    public function contactUs(){

        $contact = ContactPage::first();

        $recaptchaSetting = GoogleRecaptcha::first();

        $seoSetting = SeoSetting::find(3);



        return response()->json([

            'contact' => $contact,

            'recaptchaSetting' => $recaptchaSetting,

            'seoSetting' => $seoSetting

        ]);

    }



    public function sendContactMessage(Request $request){

        $rules = [

            'name'=>'required',

            'email'=>'required',

            'subject'=>'required',

            'message'=>'required',

            'g-recaptcha-response'=>new Captcha()

        ];

        $this->validate($request, $rules);



        $setting = Setting::first();

        if($setting->enable_save_contact_message == 1){

            $contact = new ContactMessage();

            $contact->name = $request->name;

            $contact->email = $request->email;

            $contact->subject = $request->subject;

            $contact->phone = $request->phone;

            $contact->message = $request->message;

            $contact->save();

        }



        MailHelper::setMailConfig();

        $template = EmailTemplate::where('id',2)->first();

        $message = $template->description;

        $subject = $template->subject;

        $message = str_replace('{{name}}',$request->name,$message);

        $message = str_replace('{{email}}',$request->email,$message);

        $message = str_replace('{{phone}}',$request->phone,$message);

        $message = str_replace('{{subject}}',$request->subject,$message);

        $message = str_replace('{{message}}',$request->message,$message);



        Mail::to($setting->contact_email)->send(new ContactMessageInformation($message,$subject));



        $notification = trans('user_validation.Message send successfully');

        return response()->json(['notification' => $notification]);

    }

    public function sendProductInquiry(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:120',
            'message' => 'nullable|string|max:2000',
            'product_id' => 'required|integer',
        ]);

        $product = Product::query()->find($request->product_id);
        if (! $product || (int) $product->status !== 1 || (int) $product->approve_by_admin !== 1 || ! $product->isSalonFurnitureInquiryEligible()) {
            return response()->json([
                'notification' => 'Bu ürün için bilgi talebi açılamaz.',
                'message' => 'Bu ürün için bilgi talebi açılamaz.',
            ], 422);
        }

        $email = trim((string) $request->email);
        if ($email === '') {
            $email = 'bilgi-talebi@seyfibaba.com';
        }

        $userMessage = trim((string) $request->message);
        if ($userMessage === '') {
            $userMessage = 'Ürün hakkında bilgi almak istiyorum.';
        }

        $productUrl = storefront_product_url($product->slug);
        $fullMessage = $userMessage
            ."\n\nÜrün: ".$product->name
            ."\nLink: ".$productUrl
            ."\nTelefon: ".$request->phone;

        $subject = 'Kuaför mobilyası bilgi talebi: '.$product->name;

        $contact = new ContactMessage();
        $contact->name = $request->name;
        $contact->email = $email;
        $contact->subject = mb_substr($subject, 0, 191);
        $contact->phone = $request->phone;
        $contact->message = $fullMessage;
        $contact->save();

        try {
            $setting = Setting::first();
            MailHelper::setMailConfig();
            $template = EmailTemplate::where('id', 2)->first();
            if ($template && $setting?->contact_email) {
                $mailBody = $template->description;
                $mailSubject = $template->subject ?: $subject;
                $mailBody = str_replace('{{name}}', $request->name, $mailBody);
                $mailBody = str_replace('{{email}}', $email, $mailBody);
                $mailBody = str_replace('{{phone}}', $request->phone, $mailBody);
                $mailBody = str_replace('{{subject}}', $subject, $mailBody);
                $mailBody = str_replace('{{message}}', $fullMessage, $mailBody);
                Mail::to($setting->contact_email)->send(new ContactMessageInformation($mailBody, $mailSubject));
            }
        } catch (\Throwable $e) {
            // Talep kaydı oluştu; mail hatası müşteriyi durdurmasın.
        }

        return response()->json([
            'notification' => 'Bilgi talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz.',
        ]);
    }

    private function slimProductCards($products)
    {
        return collect($products)->map(function ($item) {
            if (! $item instanceof Product) {
                return $item;
            }
            $item->setAppends(['averageRating', 'unit_price', 'offer_unit_price']);
            $item->makeHidden(['long_description']);

            return $item;
        })->values();
    }

    private function officialWhatsappDigits(): string
    {
        $digits = '908503035073';
        $contact = ContactPage::query()->first();
        if ($contact && ! empty($contact->whatsapp)) {
            $raw = preg_replace('/\D+/', '', (string) $contact->whatsapp);
            if ($raw) {
                if (str_starts_with($raw, '0') && strlen($raw) === 11) {
                    $raw = '90'.substr($raw, 1);
                } elseif (strlen($raw) === 10) {
                    $raw = '90'.$raw;
                }
                $digits = $raw;
            }
        }

        return $digits;
    }

    public function faq(){

        $faqs = FAQ::orderBy('id','desc')->where('status',1)->get();

        return response()->json(['faqs' => $faqs]);

    }



    public function trackOrderResponse($id){

        if(!$id){

            $message = trans('user_validation.Order id is required');

            return response()->json(['status'=> 0, 'message' => $message], 422);

        }

        $authUser = Auth::guard('api')->user();
        if (!$authUser) {
            return response()->json(['status'=> 0, 'message' => 'Giriş yapmanız gerekiyor'], 401);
        }

        $order = Order::where('order_id', $id)->first();

        if (!$order) {
            $message = trans('user_validation.Order not found');
            return response()->json(['status'=> 0, 'message' => $message], 404);
        }

        if ((int)$order->user_id !== (int)$authUser->id) {
            return response()->json(['status'=> 0, 'message' => 'Unauthorized'], 403);
        }

        if($order){

            return response()->json(['order'=> $order], 200);

        }else{

            $message = trans('user_validation.Order not found');

            return response()->json(['status'=> 0, 'message' => $message], 404);

        }

    }





    public function allCustomPage(){

        $pages = CustomPage::where(['status' => 1])->get();

        return response()->json(['pages'=> $pages]);

    }



    public function customPage($slug){

        $page = CustomPage::where(['slug' => $slug, 'status' => 1])->first();

        return response()->json(['page'=> $page]);

    }



    public function termsAndCondition(){

        $legal = LegalDocument::published()->where('slug', 'terms')->first();
        if ($legal) {
            return response()->json([
                'terms_conditions' => (object) ['terms_and_condition' => $legal->content],
                'seoSetting' => [
                    'seo_title' => $legal->meta_title ?: $legal->title,
                    'seo_description' => $legal->meta_description,
                ],
            ]);
        }

        $terms_conditions = TermsAndCondition::select('terms_and_condition')->first();

        return response()->json(['terms_conditions'=> $terms_conditions]);

    }



    public function sellerTemsCondition(){

        $legal = LegalDocument::published()->where('slug', 'seller-terms')->first();
        if ($legal) {
            return response()->json([
                'seller_tems_conditions' => (object) ['seller_condition' => $legal->content],
                'seoSetting' => [
                    'seo_title' => $legal->meta_title ?: $legal->title,
                    'seo_description' => $legal->meta_description,
                ],
            ]);
        }

        $seller_tems_conditions = Setting::select('seller_condition')->first();

        return response()->json(['seller_tems_conditions'=> $seller_tems_conditions]);

    }







    public function privacyPolicy(){

        $legal = LegalDocument::published()->where('slug', 'privacy-policy')->first();
        if ($legal) {
            return response()->json([
                'privacyPolicy' => (object) [
                    'privacy_policy' => $legal->content,
                    'seoSetting' => [
                        'seo_title' => $legal->meta_title ?: $legal->title,
                        'seo_description' => $legal->meta_description,
                    ],
                ],
            ]);
        }

        $privacyPolicy = TermsAndCondition::select('privacy_policy')->first();

        return response()->json(['privacyPolicy'=> $privacyPolicy]);

    }



    public function seller(){



        $sellers = Vendor::orderBy('id','desc')->where('status',1)->select('id','banner_image','shop_name','slug','open_at','closed_at','address','email','logo','phone')->paginate(20);

        $seoSetting = SeoSetting::find(5);



        return response()->json([

            'sellers' => $sellers,

            'seoSetting' => $seoSetting,

        ]);



    }



    public function sellerDetail(Request $request, $shop_name){

        $slug = $shop_name;

        $seller = Vendor::where(['status' => 1, 'slug' => $slug])->select('id','banner_image','shop_name','slug','open_at','closed_at','address','email','phone','seo_title','seo_description','logo')->first();

        if(!$seller){

            return response()->json(['message' => 'Seller not found'],403);

        }



        $searchCategoryArr = [];

        $searchBrandArr = [];

        $categories = Category::with('activeSubCategories.activeChildCategories')->where(['status' => 1])->select('id','name','slug','description','icon')->get();

        $brands = $this->activeBrandsForStorefront();

        $activeVariants = ProductFilterHelper::filterableVariants();



        $paginateQty = CustomPagination::whereId('2')->first()->qty;

        $products = Product::with('activeVariants.activeVariantItems')->where(['status' => 1, 'vendor_id' => $seller->id, 'approve_by_admin' => 1]);
        $products = ProductFilterHelper::applySorting($products, $request->shorting_id);



        if($request->category) {

            $category = Category::where('slug',$request->category)->first();

            if($category){
                $products = $products->where('category_id', $category->id);
                $searchCategoryArr[] = $category->id;
            }

        }



        if($request->sub_category) {

            $sub_category = SubCategory::where('slug',$request->sub_category)->first();

            if($sub_category){
                $products = $products->where('sub_category_id', $sub_category->id);
                $searchCategoryArr[] = $sub_category->category_id;
            }

        }



        if($request->child_category) {

            $child_category = ChildCategory::where('slug',$request->child_category)->first();

            if($child_category){
                $products = $products->where('child_category_id', $child_category->id);
                $searchCategoryArr[] = $child_category->category_id;
            }

        }



        if($request->brand) {

            $brand = Brand::where('slug',$request->brand)->first();

            if($brand){
                $products = $products->where('brand_id', $brand->id);
                $searchBrandArr[] = $brand->id;
            }

        }

        if($request->variantItems){
            $variantItems = is_array($request->variantItems) ? $request->variantItems : explode(',', $request->variantItems);
            $variantItems = array_values(array_filter($variantItems));

            if(!empty($variantItems)){
                $products = $products->whereHas('variantItems', function($query) use ($variantItems){
                    $query->whereIn('name', $variantItems);
                });
            }
        }

        if($request->brands){
            $brandIds = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $brandIds = array_values(array_filter($brandIds));

            if(!empty($brandIds)){
                $products = $products->whereIn('brand_id', $brandIds);
                $searchBrandArr = array_values(array_unique(array_merge($searchBrandArr, $brandIds)));
            }
        }

        if($request->categories){
            $categoryIds = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            $categoryIds = array_values(array_filter($categoryIds));

            if(!empty($categoryIds)){
                $products = $products->whereIn('category_id', $categoryIds);
                $searchCategoryArr = array_values(array_unique(array_merge($searchCategoryArr, $categoryIds)));
            }
        }

        if($request->filled('min_price') && is_numeric($request->min_price)){
            $products = ProductFilterHelper::applyPriceFilter($products, $request->min_price, null);
        }

        if($request->filled('max_price') && is_numeric($request->max_price)){
            $products = ProductFilterHelper::applyPriceFilter($products, null, $request->max_price);
        }



        if($request->search) {
            $searchTerm = $request->search;
            $trMap = ['ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c','ş'=>'s','ı'=>'i','â'=>'a','î'=>'i','û'=>'u'];
            $searchAscii = strtr(mb_strtolower($searchTerm), $trMap);
            $products = $products->where(function($q) use ($searchTerm, $searchAscii) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%')
                  ->orWhere('short_description', 'LIKE', '%'.$searchTerm.'%');
                if ($searchAscii !== mb_strtolower($searchTerm)) {
                    $q->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name,'ğ','g'),'ü','u'),'ö','o'),'ç','c'),'ş','s'),'ı','i'),'â','a')) LIKE ?", ['%'.$searchAscii.'%']);
                }
            });
        }



        $paginateQty = CustomPagination::whereId('2')->first()->qty;



        $products = $products->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id');

        if($request->per_page){

            $products = $products->paginate($request->per_page);

        }else{

            $products = $products->paginate($paginateQty);

        }

        $products = $products->appends($request->all());



        $sellerReviewQty = ProductReview::where('status',1)->where('product_vendor_id',$seller->id)->count();

        $sellerTotalReview = ProductReview::where('status',1)->where('product_vendor_id',$seller->id)->sum('rating');



        $shopPageCenterBanner = BannerImage::select('product_slug','image','banner_location','status','after_product_qty','title_one')->find(25);

        $shopPageSidebarBanner = BannerImage::select('product_slug','image','banner_location','status','title_one','title_two')->find(26);



        return response()->json([

            'seller' => $seller,

            'sellerReviewQty' => $sellerReviewQty,

            'sellerTotalReview' => $sellerTotalReview,

            'searchCategoryArr' => $searchCategoryArr,

            'searchBrandArr' => $searchBrandArr,

            'categories' => $categories,

            'brands' => $brands,

            'activeVariants' => $activeVariants,

            'products' => $products,

            'shopPageCenterBanner' => $shopPageCenterBanner,

            'shopPageSidebarBanner' => $shopPageSidebarBanner,

        ]);

    }



    public function variantItemsByVariant($name){

        $variantItemsForSearch = ProductVariantItem::with('product','variant')->groupBy('name')->select('name','id')->where('product_variant_name', $name)->get();



        return response()->json(['variantItemsForSearch' => $variantItemsForSearch]);

    }



    public function product(Request $request){

        $searchCategoryArr = [];

        $searchBrandArr = [];

        $categories = Category::with('activeSubCategories.activeChildCategories')->where(['status' => 1])->select('id','name','slug','description','icon')->get();

        $brands = $this->activeBrandsForStorefront();

        $activeVariants = ProductFilterHelper::filterableVariants();



        $paginateQty = CustomPagination::whereId('2')->first()->qty;

        $products = Product::with('activeVariants.activeVariantItems')->where(['status' => 1, 'approve_by_admin' => 1]);
        $products = ProductFilterHelper::applySorting($products, $request->shorting_id);



        if($request->category) {

            $category = Category::where('slug',$request->category)->first();

            if($category){
                $products = $products->where('category_id', $category->id);
                $searchCategoryArr[] = $category->id;
            }

        }



        if($request->sub_category) {

            $sub_category = SubCategory::where('slug',$request->sub_category)->first();

            if($sub_category){
                $products = $products->where('sub_category_id', $sub_category->id);
                $searchCategoryArr[] = $sub_category->category_id;
            }

        }



        if($request->child_category) {

            $child_category = ChildCategory::where('slug',$request->child_category)->first();

            if($child_category){
                $products = $products->where('child_category_id', $child_category->id);
                $searchCategoryArr[] = $child_category->category_id;
            }

        }





        $popularCategoryArr = [];

        if($request->highlight){



            if($request->highlight == 'popular_category'){

                $products = $products->where('is_top',1);

            }



            if($request->highlight == 'top_product'){

                $products = $products->where('is_top',1);

            }



            if($request->highlight == 'new_arrival'){

                $products = $products->where('new_product',1);

            }



            if($request->highlight == 'featured_product'){

                $products = $products->where('is_featured',1);

            }



            if($request->highlight == 'best_product'){

                $products = $products->where('is_best',1);

            }



            if($request->highlight == 'discounted'){

                $products = ProductFilterHelper::applyDiscountedFilter($products);

            }



        }





        if($request->brand) {

            $brand = Brand::where('slug',$request->brand)->first();

            if($brand){
                $products = $products->where('brand_id', $brand->id);
                $searchBrandArr[] = $brand->id;
            }

        }

        if($request->variantItems){
            $variantItems = is_array($request->variantItems) ? $request->variantItems : explode(',', $request->variantItems);
            $variantItems = array_values(array_filter($variantItems));

            if(!empty($variantItems)){
                $products = $products->whereHas('variantItems', function($query) use ($variantItems){
                    $query->whereIn('name', $variantItems);
                });
            }
        }

        if($request->brands){
            $brandIds = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $brandIds = array_values(array_filter($brandIds));

            if(!empty($brandIds)){
                $products = $products->whereIn('brand_id', $brandIds);
                $searchBrandArr = array_values(array_unique(array_merge($searchBrandArr, $brandIds)));
            }
        }

        if($request->categories){
            $categoryIds = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            $categoryIds = array_values(array_filter($categoryIds));

            if(!empty($categoryIds)){
                $products = $products->whereIn('category_id', $categoryIds);
                $searchCategoryArr = array_values(array_unique(array_merge($searchCategoryArr, $categoryIds)));
            }
        }

        if($request->filled('min_price') && is_numeric($request->min_price)){
            $products = ProductFilterHelper::applyPriceFilter($products, $request->min_price, null);
        }

        if($request->filled('max_price') && is_numeric($request->max_price)){
            $products = ProductFilterHelper::applyPriceFilter($products, null, $request->max_price);
        }



        if($request->search) {
            $searchTerm = $request->search;
            $trMap = ['ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c','ş'=>'s','ı'=>'i','â'=>'a','î'=>'i','û'=>'u'];
            $searchAscii = strtr(mb_strtolower($searchTerm), $trMap);
            $products = $products->where(function($q) use ($searchTerm, $searchAscii) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%')
                  ->orWhere('short_description', 'LIKE', '%'.$searchTerm.'%');
                if ($searchAscii !== mb_strtolower($searchTerm)) {
                    $q->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name,'ğ','g'),'ü','u'),'ö','o'),'ç','c'),'ş','s'),'ı','i'),'â','a')) LIKE ?", ['%'.$searchAscii.'%']);
                }
            });
        }



        $products = $products->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id');

        $products = $products->paginate($paginateQty);

        $products = $products->appends($request->all());

        $seoSetting = SeoSetting::find(9);



        $shopPageCenterBanner = BannerImage::select('product_slug','image','banner_location','status','after_product_qty','title_one')->find(25);

        $shopPageSidebarBanner = BannerImage::select('product_slug','image','banner_location','status','title_one','title_two')->find(26);



        $shopPage = ShopPage::first();

        return response()->json([

            'searchCategoryArr' => $searchCategoryArr,

            'searchBrandArr' => $searchBrandArr,

            'categories' => $categories,

            'brands' => $brands,

            'activeVariants' => $activeVariants,

            'products' => $products,

            'seoSetting' => $seoSetting,

            'shopPageCenterBanner' => $shopPageCenterBanner,

            'shopPageSidebarBanner' => $shopPageSidebarBanner,

            'shopPage' => [
                'filter_price_range' => (int) ($shopPage?->filter_price_range ?? 100000),
            ],

        ]);



        return response()->json(['banner' => $banner, 'products' => $products, 'productCategories' => $productCategories, 'brands' => $brands, 'shop_page' => $shop_page, 'variantsForSearch' => $variantsForSearch, 'seoSetting' => $seoSetting, 'currencySetting' => $currencySetting, 'setting' => $setting]);

    }



    public function searchProduct(Request $request){

        $paginateQty = CustomPagination::whereId('2')->first()->qty;

        if($request->variantItems){

            $products = Product::with('activeVariants.activeVariantItems')->whereHas('variantItems', function($query) use ($request){

                $sortArr = [];

                if($request->variantItems){

                    foreach($request->variantItems as $variantItem){

                        $sortArr[] = $variantItem;

                    }

                    $query->whereIn('name', $sortArr);

                }

            })->where('status',1)->where('approve_by_admin',1);

        }else{

            $products = Product::with('activeVariants.activeVariantItems')->where('status',1)->where('approve_by_admin', 1);

        }



        $products = ProductFilterHelper::applySorting($products, $request->shorting_id);

        if($request->category) {

            $category = Category::where('slug',$request->category)->first();

            if($category) $products = $products->where('category_id', $category->id);

        }

        if($request->sub_categories) {

            $subCategoryIds = SubCategory::whereIn('slug', (array)$request->sub_categories)->pluck('id');

            if($subCategoryIds->isNotEmpty()) $products = $products->whereIn('sub_category_id', $subCategoryIds);

        } elseif($request->sub_category) {

            $sub_category = SubCategory::where('slug',$request->sub_category)->first();

            if($sub_category) $products = $products->where('sub_category_id', $sub_category->id);

        }



        if($request->child_category) {

            $child_category = ChildCategory::where('slug',$request->child_category)->first();

            if($child_category) $products = $products->where('child_category_id', $child_category->id);

        }

        if($request->brand) {

            $brand = Brand::where('slug',$request->brand)->first();

            if($brand) $products = $products->where('brand_id', $brand->id);

        }



        $brandSortArr = [];

        if($request->brands){

            foreach($request->brands as $brand){

                $brandSortArr[] = $brand;

            }

            $products = $products->whereIn('brand_id', $brandSortArr);

        }



        $categorySortArr = [];

        if($request->categories){

            foreach($request->categories as $brand){
                $categorySortArr[] = $brand;
            }
            $products = $products->whereIn('category_id', $categorySortArr);

        }

        if ($request->shop_name) {
            $vendor = Vendor::where('slug', $request->shop_name)->first();
            if ($vendor) {
                $products = $products->where('vendor_id', $vendor->id);
            }
        }




        $popularCategoryArr = [];

        if($request->highlight){


            if($request->highlight == 'popular_category'){

                $products = $products->where('is_top',1);

            }



            if($request->highlight == 'top_product'){

                $products = $products->where('is_top',1);

            }



            if($request->highlight == 'new_arrival'){

                $products = $products->where('new_product',1);

            }



            if($request->highlight == 'featured_product'){

                $products = $products->where('is_featured',1);

            }



            if($request->highlight == 'best_product'){

                $products = $products->where('is_best',1);

            }



            if($request->highlight == 'discounted'){

                $products = ProductFilterHelper::applyDiscountedFilter($products);

            }



        }





        $products = ProductFilterHelper::applyPriceFilter(
            $products,
            $request->min_price,
            $request->max_price
        );



        if($request->shop_name){

            $slug = $request->shop_name;

            $seller = Vendor::where(['slug' => $slug])->first();

            $products = $products->where('vendor_id', $seller->id);

        }



        if($request->search){
            $products = $products->where(function($query) use ($request){
                $query->where('name', 'LIKE', '%'. $request->search. "%")
                                    ->orWhere('long_description','LIKE','%'.$request->search.'%');
            });
        }



        $products = $products->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best','category_id','sub_category_id','child_category_id','brand_id');

        if($request->per_page){

            $products = $products->paginate($request->per_page);

        }else{

            $products = $products->paginate($paginateQty);

        }



        $products = $products->appends($request->all());



        return response()->json(['products' => $products]);



    }



    public function productDetail($slug){
        $slugCandidates = ProductSlug::candidates($slug);
        $product = Product::with('category','subCategory','brand','activeVariants.activeVariantItems')
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->where(function ($query) use ($slug, $slugCandidates) {
                $query->where('slug', $slug);

                if (!empty($slugCandidates)) {
                    $query->orWhereIn('slug', $slugCandidates);
                }
            })
            ->where(function ($query) {
                $query->where('vendor_id', 0)
                    ->orWhereHas('seller', function ($seller) {
                        $seller->where('status', 1);
                    });
            })
            ->first();

        if(!$product){
            $normalized = ProductSlug::normalize($slug);
            if ($normalized !== '') {
                $product = Product::with('category','subCategory','brand','activeVariants.activeVariantItems')
                    ->where('status', 1)
                    ->where('approve_by_admin', 1)
                    ->where(function ($query) {
                        $query->where('vendor_id', 0)
                            ->orWhereHas('seller', function ($seller) {
                                $seller->where('status', 1);
                            });
                    })
                    ->orderByDesc('id')
                    ->limit(300)
                    ->get()
                    ->first(function ($item) use ($normalized, $slug, $slugCandidates) {
                        $itemSlug = (string) $item->slug;

                        return $itemSlug === $slug
                            || in_array($itemSlug, $slugCandidates, true)
                            || ProductSlug::normalize($itemSlug) === $normalized;
                    });
            }
        }

        if(!$product){

            $notification = trans('user_validation.Something went wrong');

            return response()->json(['message' => $notification],404);

        }

        $product->setAppends(['averageRating', 'unit_price', 'offer_unit_price']);

        $productReviews = ProductReview::with('user')->where(['status' => 1, 'product_id' =>$product->id])->limit(10)->get();



        $totalProductReviewQty = ProductReview::where(['status' => 1, 'product_id' =>$product->id])->count();

        $totalReview = ProductReview::where(['status' => 1, 'product_id' =>$product->id])->sum('rating');

        try {
            $recaptchaSetting = GoogleRecaptcha::first();
        } catch (\Throwable $e) {
            $recaptchaSetting = null;
        }

        $relatedBase = function () use ($product) {
            return Product::with('activeVariants.activeVariantItems')
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->where('id', '!=', $product->id);
        };

        $relatedProducts = collect();
        $subCategoryId = (int) ($product->sub_category_id ?? 0);
        if ($subCategoryId > 0) {
            $relatedProducts = $relatedBase()
                ->where('sub_category_id', $subCategoryId)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }
        if ($relatedProducts->isEmpty()) {
            $categoryId = (int) ($product->category_id ?? 0);
            if ($categoryId > 0) {
                $relatedProducts = $relatedBase()
                    ->where('category_id', $categoryId)
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get();
            }
        }

        try {
            $defaultProfile = BannerImage::whereId('15')->select('image')->first();
        } catch (\Throwable $e) {
            $defaultProfile = null;
        }

        try {
            $specifications = ProductSpecification::with('key')->where('product_id', $product->id)->get();
        } catch (\Throwable $e) {
            $specifications = collect();
        }

        $gellery = ProductGallery::where('product_id', $product->id)->get();

        $is_seller_product = $product->vendor_id == 0 ? false : true;

        $this_seller_products = [];

        if($is_seller_product){

            $this_seller_products = Product::with('activeVariants.activeVariantItems')
                ->where(['vendor_id' => $product->vendor_id, 'status' => 1, 'approve_by_admin' => 1])
                ->where('id', '!=', $product->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get();

        }





        $seller = $is_seller_product
            ? Vendor::with('user')->where('id', $product->vendor_id)->first()
            : null;

        $sellerTotalProducts = 0;

        if($is_seller_product){

            $sellerTotalProducts = Product::where(['status' => 1, 'vendor_id' => $product->vendor_id, 'approve_by_admin' => 1])->count();

        }

        $sellerReviewQty = 0;

        if($is_seller_product){

            $sellerReviewQty = ProductReview::where(['status' => 1, 'product_vendor_id' => $product->vendor_id])->count();

        }

        $sellerTotalReview = 0;

        if($is_seller_product){

            $sellerTotalReview = ProductReview::where(['status' => 1, 'product_vendor_id' => $product->vendor_id])->sum('rating');

        }





        $tags = ProductSlug::tagsToText($product->tags);

        $allowFurnitureInquiry = false;
        try {
            $allowFurnitureInquiry = $product->isSalonFurnitureInquiryEligible();
        } catch (\Throwable $e) {
            $allowFurnitureInquiry = false;
        }
        $product->setAttribute('allow_furniture_inquiry', $allowFurnitureInquiry);
        $product->setAttribute(
            'furniture_inquiry_whatsapp',
            $allowFurnitureInquiry ? $this->officialWhatsappDigits() : null
        );

        $relatedProducts = $this->slimProductCards($relatedProducts);
        $this_seller_products = $this->slimProductCards($this_seller_products);

        return response()->json([

            'product' => $product,

            'gellery' => $gellery,

            'tags' => $tags,

            'totalProductReviewQty' => $totalProductReviewQty,

            'totalReview' => $totalReview,

            'productReviews' => $productReviews,

            'specifications' => $specifications,

            'recaptchaSetting' => $recaptchaSetting,

            'relatedProducts' => $relatedProducts,

            'defaultProfile' => $defaultProfile,

            'is_seller_product' => $is_seller_product,

            'seller' => $seller,

            'sellerTotalProducts' => $sellerTotalProducts,

            'this_seller_products' => $this_seller_products,

            'sellerReviewQty' => $sellerReviewQty,

            'sellerTotalReview' => $sellerTotalReview

        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    }





    public function productReviewList($id){

        $reviews = ProductReview::with('user:id,name,image')->where(['product_id' => $id, 'status' => 1])->paginate(10);

        // Müşteri gizliliği: isim maskeleme (#39)
        $reviews->getCollection()->transform(function ($review) {
            if ($review->user) {
                $review->user->name = $this->maskName($review->user->name);
            }
            return $review;
        });

        return response()->json(['reviews' => $reviews]);

    }

    /**
     * İsim maskeleme: "Hüseyin Coşkun" → "H.C."
     */
    private function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $initials = array_map(function ($part) {
            return mb_strtoupper(mb_substr($part, 0, 1));
        }, $parts);
        return implode('.', $initials) . '.';
    }



    public function addToCompare($id){

        $compare_array = [];

        foreach(Cart::instance('compare')->content() as $content){

            $compare_array[] = $content->id;

        }



        if(3 <= Cart::instance('compare')->count()){

            $notification = trans('user_validation.Already 3 items added');

            return response()->json(['status' => 0, 'message' => $notification]);

        }



        if(in_array($id,$compare_array)){

            $notification = trans('user_validation.Already added this item');

            return response()->json(['status' => 0, 'message' => $notification]);

        }else{

            $product = Product::with('tax')->find($id);

            $data=array();

            $data['id'] = $id;

            $data['name'] = 'abc';

            $data['qty'] = 1;

            $data['price'] = 1;

            $data['weight'] = 1;

            $data['options']['product'] = $product;

            Cart::instance('compare')->add($data);

            $notification = trans('user_validation.Item added successfully');

            return response()->json(['status' => 1, 'message' => $notification]);

        }



    }



    public function compare(){

        $banner = BreadcrumbImage::where(['id' => 6])->first();

        $compare_contents = Cart::instance('compare')->content();

        $currencySetting = Setting::first();

        return view('compare', compact('banner','compare_contents','currencySetting'));

    }



    public function removeCompare($id){

        Cart::instance('compare')->remove($id);

        $notification = trans('user_validation.Item remmoved successfully');

        $notification = array('messege'=>$notification,'alert-type'=>'success');

        return redirect()->back()->with($notification);

    }





    public function flashSale(){

        $flashSale = FlashSale::first();

        $flashSaleProducts = FlashSaleProduct::where('status',1)->get();

        $product_arr = [];

        foreach($flashSaleProducts as $flashSaleProduct){

            $product_arr[] = $flashSaleProduct->product_id;

        }



        $paginateQty = CustomPagination::whereId('2')->first()->qty;

        $products = Product::with('activeVariants.activeVariantItems')->whereIn('id', $product_arr)->orderBy('id','desc')->where(['status' => 1, 'approve_by_admin' => 1])->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sale_unit_qty','sold_qty', 'price', 'offer_price','is_undefine','is_featured','new_product', 'is_top', 'is_best')->paginate($paginateQty);



        $seoSetting = SeoSetting::find(8);



        return response()->json([

            'flashSale' => $flashSale,

            'products' => $products,

            'seoSetting' => $seoSetting

        ]);

    }



    public function subscribeRequest(Request $request){

        if($request->email != null){

            $isExist = Subscriber::where('email', $request->email)->count();

            if($isExist == 0){

                $subscriber = new Subscriber();

                $subscriber->email = $request->email;

                $subscriber->verified_token = random_int(100000, 999999);

                $subscriber->save();

                try {
                    MailHelper::setMailConfig();

                    $template=EmailTemplate::where('id',3)->first();

                    if($template) {
                        $message=$template->description;
                        $subject=$template->subject;
                        Mail::to($subscriber->email)->send(new SubscriptionVerification($subscriber,$message,$subject));
                    }
                } catch (\Exception $e) {
                    // Mail failed but subscription is saved — auto-verify since mail won't arrive
                    $subscriber->verified_token = null;
                    $subscriber->is_verified = 1;
                    $subscriber->save();
                }

                return response()->json(['message' => trans('user_validation.Subscription successfully, please verified your email')]);



            }else{

                return response()->json(['message' => trans('user_validation.Email already exist'),403],403);

            }

        }else{

            return response()->json(['message' => trans('user_validation.Email Field is required')],403);

        }

    }



    public function subscriberVerifcation(Request $request, $token){



        $subscriber = Subscriber::where(['verified_token' => $token, 'email' => $request->email])->first();

        if($subscriber){

            $subscriber->verified_token = null;

            $subscriber->is_verified = 1;

            $subscriber->save();



            $setting = Setting::first();

            $frontend_url = $setting->frontend_url;



            return redirect($frontend_url);

        }else{

            $setting = Setting::first();

            $frontend_url = $setting->frontend_url;

            return redirect($frontend_url);

        }



    }

    public function liveTrackOrder(Request $request){

        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Giriş yapmanız gerekiyor'], 401);
        }

        $order = Order::with('deliveryman')
        ->where('order_id', $request->order_id)
        ->where('user_id', $user->id)
        ->first();
        if ($order) {
            $response = [
                'user_latitude' => $order->latitude,
                'user_longitude' => $order->longitude,
                'deliveryman_latitude' => $order->deliveryman->latitude ?? null,
                'deliveryman_longitude' => $order->deliveryman->longitude ?? null,
            ];

            return response()->json(['data' => $response]);
        }

    return response()->json(['message' => 'Order not found'], 404);
    }











































}

