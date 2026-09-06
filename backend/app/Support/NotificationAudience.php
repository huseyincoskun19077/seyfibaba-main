<?php

namespace App\Support;

class NotificationAudience
{
    /** Satıcı paneli / satıcı bildirimleri */
    public const SELLER_TYPES = [
        'seller_new_order',
        'stock_alert',
        'kyc_status',
        'kyc_reminder',
        'seller_withdraw_approved',
    ];

    /** Alıcı uygulaması bildirimleri (satış bildirimleri hariç) */
    public static function buyerQuery($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('data->type')
                ->orWhereNotIn('data->type', self::SELLER_TYPES);
        });
    }

    public static function sellerQuery($query)
    {
        return $query->whereIn('data->type', self::SELLER_TYPES);
    }
}
