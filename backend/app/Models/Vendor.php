<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;


    protected $guarded = [];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'kyc_submitted_at' => 'datetime',
        'kyc_approved_at' => 'datetime',
        'seller_terms_accepted_at' => 'datetime',
        'welcome_sms_sent' => 'boolean',
        'welcome_sms_sent_at' => 'datetime',
        'welcome_email_sent' => 'boolean',
        'welcome_email_sent_at' => 'datetime',
        'registration_category_ids' => 'array',
    ];

    protected $appends = ['averageRating'];

    public function getEffectiveCommissionRate()
    {
        if ($this->commission_rate !== null) {
            return (float) $this->commission_rate;
        }

        return (float) (Setting::query()->value('default_commission_rate') ?? 0);
    }

    public function getAverageRatingAttribute()
    {
        try {
            return $this->activeReviews()->avg('rating') ?: '0';
        } catch (\Throwable $e) {
            return '0';
        }
    }

    public function socialLinks(){
        return $this->hasMany(VendorSocialLink::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function products(){
        return $this->hasMany(Product::class,'vendor_id');
    }

    public function activeReviews(){
        return $this->hasMany(ProductReview::class,'product_vendor_id');
    }

    public function kycDocuments()
    {
        return $this->hasMany(SellerKycDocument::class, 'seller_id');
    }

    public function registeredByAdmin()
    {
        return $this->belongsTo(Admin::class, 'registered_by_admin_id');
    }

    public function callCenterCommission()
    {
        return $this->hasOne(CallCenterCommission::class);
    }

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function isCallCenterRegistration(): bool
    {
        return $this->registration_source === 'call_center';
    }

    public function isQuickOnboardingRegistration(): bool
    {
        return in_array($this->registration_source, ['call_center', 'public_web'], true);
    }

    public function needsTermsAcceptance(): bool
    {
        return $this->seller_terms_accepted_at === null;
    }
}
