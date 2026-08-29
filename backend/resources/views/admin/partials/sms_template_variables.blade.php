@php
    $variableMap = [
        'seller_reminder_login' => [
            ['var' => '{{contact_name}}', 'meaning' => 'Yetkili adı soyadı'],
            ['var' => '{{shop_name}}', 'meaning' => 'Firma / dükkan adı'],
            ['var' => '{{login_url}}', 'meaning' => 'Satıcı giriş adresi (seyfibaba.com/satici-giris)'],
            ['var' => '{{login_phone}}', 'meaning' => 'Giriş telefonu (10 hane)'],
            ['var' => '{{password}}', 'meaning' => 'Tek kullanımlık giriş şifresi'],
        ],
        'seller_reminder_kyc' => [
            ['var' => '{{contact_name}}', 'meaning' => 'Yetkili adı soyadı'],
            ['var' => '{{shop_name}}', 'meaning' => 'Firma / dükkan adı'],
            ['var' => '{{login_url}}', 'meaning' => 'Satıcı giriş adresi'],
        ],
        'seller_reminder_product' => [
            ['var' => '{{contact_name}}', 'meaning' => 'Yetkili adı soyadı'],
            ['var' => '{{shop_name}}', 'meaning' => 'Firma / dükkan adı'],
            ['var' => '{{login_url}}', 'meaning' => 'Satıcı giriş adresi'],
        ],
    ];

    $slugVariables = $variableMap[$template->slug ?? ''] ?? [];
@endphp

@if(! empty($slugVariables))
    @foreach($slugVariables as $row)
        <tr>
            <td>{{ $row['var'] }}</td>
            <td>{{ $row['meaning'] }}</td>
        </tr>
    @endforeach
@elseif ($template->id == 1)
    <tr>
        <td>{{ '{{user_name}}' }}</td>
        <td>{{ __('admin.User Name') }}</td>
    </tr>
    <tr>
        <td>{{ '{{otp_code}}' }}</td>
        <td>{{ __('OTP') }}</td>
    </tr>
@elseif ($template->id == 2)
    <tr>
        <td>{{ '{{name}}' }}</td>
        <td>{{ __('admin.User Name') }}</td>
    </tr>
    <tr>
        <td>{{ '{{otp_code}}' }}</td>
        <td>{{ __('OTP') }}</td>
    </tr>
@elseif ($template->id == 3)
    <tr>
        <td>{{ '{{user_name}}' }}</td>
        <td>{{ __('admin.User Name') }}</td>
    </tr>
    <tr>
        <td>{{ '{{order_id}}' }}</td>
        <td>{{ __('Order Tracking Id') }}</td>
    </tr>
@endif
