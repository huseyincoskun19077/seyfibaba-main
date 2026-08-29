<?php

namespace App\Support;

use Illuminate\Support\Collection;

class OrderQuantityHelper
{
    /**
     * @param  iterable|Collection|null  $cartProducts
     */
    public static function fromCartProducts($cartProducts): int
    {
        return (int) collect($cartProducts)->sum(function ($item) {
            $row = is_array($item) ? $item : (array) $item;

            return (int) ($row['qty'] ?? 1);
        });
    }

    /**
     * @param  iterable|Collection|null  $orderProducts
     */
    public static function fromOrderProducts($orderProducts): int
    {
        $total = (int) collect($orderProducts)->sum(function ($item) {
            if (is_array($item)) {
                return (int) ($item['qty'] ?? 1);
            }

            return (int) ($item->qty ?? 1);
        });

        return max(0, $total);
    }
}
