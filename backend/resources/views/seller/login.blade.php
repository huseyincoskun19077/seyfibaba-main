@include('admin.header')
<div id="app">
    <section class="section">
        <div class="auth-section-wrapper">
            <div class="login-thumb">
              <img class="img" src="{{ asset('backend/img/login-thumb.png') }}" alt="login-thumb"/>
            </div>
            <div class="form-area-wrapper">
                <div class="form-content-wrapper">
                    <div class="logo">
                        <img src="{{ asset($setting->logo) }}" alt="logo"/>
                    </div>
                    <div class="card card-primary card-wrapper-auth">
                        <div class="card-body">
                            <div class="tex-content">
                                <h1>Satıcı Girişi</h1>
                            </div>
                            <form class="needs-validation" novalidate="" id="adminLoginForm">
                                @csrf

                                <div class="form-group">
                                    <label for="sellerLoginIdentifier">E-posta veya Telefon<sup>*</sup></label>
                                    <input
                                        id="sellerLoginIdentifier"
                                        type="text"
                                        class="form-control"
                                        name="login"
                                        tabindex="1"
                                        autofocus
                                        autocomplete="username"
                                        value="{{ old('login', old('email')) }}"
                                    >
                                </div>

                                <div class="form-group">
                                    <div class="d-block">
                                        <label for="sellerLoginPassword" class="control-label">Şifre<sup>*</sup></label>
                                    </div>
                                    <input
                                        id="sellerLoginPassword"
                                        type="password"
                                        class="form-control"
                                        name="password"
                                        tabindex="2"
                                        autocomplete="current-password"
                                    >
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="remember">{{__('admin.Remember Me')}}</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button id="adminLoginBtn" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        {{__('admin.Login')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="simple-footer">
                        {{ $setting->copyright }}
                    </div>
                </div>
            </div>
            <div class="simple-footer">
                {{ $setting->copyright }}
            </div>
        </div>
    </section>
 </div>


<script>
    (function($) {
    "use strict";
    $(document).ready(function () {
        function submitSellerLogin() {
            $.ajax({
                url: "{{ route('seller.login') }}",
                type:"post",
                data:$('#adminLoginForm').serialize(),
                success:function(response){
                    if(response.success){
                        window.location.href = response.redirect || "{{ route('seller.dashboard')}}";
                        toastr.success(response.success)
                    }
                    if(response.error){
                        toastr.error(response.error)
                    }
                },
                error:function(response){
                    if(response.responseJSON && response.responseJSON.errors){
                        if(response.responseJSON.errors.login) toastr.error(response.responseJSON.errors.login[0])
                        if(response.responseJSON.errors.email) toastr.error(response.responseJSON.errors.email[0])
                        if(response.responseJSON.errors.password) toastr.error(response.responseJSON.errors.password[0])
                    } else if(response.responseJSON && response.responseJSON.error) {
                        toastr.error(response.responseJSON.error)
                    }
                }
            });
        }

        $("#adminLoginBtn").on('click',function(e) {
            e.preventDefault();
            submitSellerLogin();
        });

        $(document).on('keyup', '#sellerLoginIdentifier, #sellerLoginPassword', function (e) {
            if(e.keyCode == 13){
                e.preventDefault();
                submitSellerLogin();
            }
        });
    });

    })(jQuery);
</script>

@include('admin.footer')
