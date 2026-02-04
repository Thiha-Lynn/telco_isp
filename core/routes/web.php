<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/clear', function() {
        $run = Artisan::call('config:clear');
        $run = Artisan::call('cache:clear');
        $run = Artisan::call('config:cache');
        return 'FINISHED';  
    });
    
    
// 14-03-2022 manish
Route::get('/directpay', 'Controller@directpay')->name('directpay.html');
     
Route::post('/directkbzpay', 'Controller@submitdirectpay')->name('directpay');

Route::get('/privacy-policies', 'User\UserController@privacy_policies');
Route::get('/privacy-policy', 'User\UserController@privacy_policies');


Route::get('login', function () {
    return view('admin.login');
})->name('login');


Route::get('/redirect', 'Front\FrontendController@RedirectUrl');

Route::get('/invoice/{id}', 'Front\FrontendController@invoice');

/*=======================================================
 Front Routes
=======================================================*/
Route::group(['middleware' => 'setlang'], function () {
 //Route::get('/', 'Front\FrontendController@index')->name('front.index');
    Route::get('/', function () {
    return view('admin.login');
    })->name('front.index');
    Route::get('/notify', 'Front\FrontendController@notify')->name('user.notify');
    Route::get('/faq', 'Front\FrontendController@faq')->name('front.faq');
    Route::get('/about', 'Front\FrontendController@about')->name('front.about');
    Route::get('/service', 'Front\FrontendController@service')->name('front.service');
    Route::get('/service/{slug}', 'Front\FrontendController@service_details')->name('front.service.details');
    Route::get('/package', 'Front\FrontendController@package')->name('front.package');
    Route::get('/media', 'Front\FrontendController@media')->name('front.media');
    Route::get('/branch', 'Front\FrontendController@branch')->name('front.branch');
    Route::get('/team', 'Front\FrontendController@team')->name('front.team');
    Route::get('/contact', 'Front\FrontendController@contact')->name('front.contact');
    Route::post('/contact/submit', 'Front\FrontendController@contactSubmit')->name('front.contact.submit');
    Route::post('/newsletter/store', 'Admin\NewsletterController@store')->name('front.newsletter');
    Route::get('/package/checkout/{id}', 'Front\FrontendController@packagecheckout')->name('front.packagecheckout');
    Route::get('/billpay', 'Front\FrontendController@billpay')->name('front.billpay');
    Route::get('/speed-test', 'Front\FrontendController@speed')->name('front.speed.test');
    
    
    
    Route::get('/kbz-mobile', 'Front\FrontendController@kbzmobile')->name('kbzmobile');
    Route::post('/kbz-mobile-pay', 'Front\FrontendController@kbzmobile_pay')->name('kbzmobile_pay');

    Route::get('/products', 'Front\ProductController@products')->name('front.products');
    Route::get('product/{slug}', 'Front\ProductController@product_details')->name('front.product.details');
    Route::get('/add-to-cart/{id}', 'Front\ProductController@addToCart')->name('add.cart');
    Route::get('/cart', 'Front\ProductController@cart')->name('front.cart');
    Route::get('/checkout', 'Front\ProductController@checkout')->name('front.checkout');
    Route::post('/cart/update', 'Front\ProductController@updatecart')->name('cart.update');
    Route::get('/cart/item/remove/{id}', 'Front\ProductController@cartitemremove')->name('cart.item.remove');
    Route::get('cart/qty/get/ajax', 'Front\ProductController@cartQtyGet')->name('cart.qty.get');
    Route::get('/checkout', 'Front\ProductController@checkout')->name('front.checkout');

    // Blog route
    Route::get('/blog', 'Front\FrontendController@blogs')->name('front.blogs');
    Route::get('/blog-details/{slug}', 'Front\FrontendController@blogdetails')->name('front.blogdetails');
    Route::get('/changelanguage/{lang}', 'Front\FrontendController@changeLanguage')->name('changeLanguage');


    // package payment gateway route
    // PayPal routes temporarily disabled - needs migration to new SDK
    // Route::post('/package/paypal/submit', 'Payment\Package\PaypalController@store')->name('package.paypal.submit');
    // Route::get('/package/paypal/cancle', 'Payment\Package\PaypalController@paycancle')->name('package.payment.cancle');
    // Route::get('/package/paypal/return', 'Payment\Package\PaypalController@payreturn')->name('package.payment.return');
    // Route::get('/package/paypal/notify', 'Payment\Package\PaypalController@notify')->name('package.payment.notify');
    Route::post('/package/stripe/submit', 'Payment\Package\StripeController@store')->name('package.stripe.submit');
  


    // Pay bil payment gateway route
    // PayPal routes temporarily disabled - needs migration to new SDK
    // Route::post('/paybill/paypal/submit', 'Payment\Paybill\PaypalController@store')->name('paybill.paypal.submit');
    // Route::get('/paybill/paypal/cancle', 'Payment\Paybill\PaypalController@paycancle')->name('paybill.payment.cancle');
    // Route::get('/paybill/paypal/return', 'Payment\Paybill\PaypalController@payreturn')->name('paybill.payment.return');
    // Route::get('/paybill/paypal/notify', 'Payment\Paybill\PaypalController@notify')->name('paybill.payment.notify');
    Route::post('/paybill/stripe/submit', 'Payment\Paybill\StripeController@store')->name('paybill.stripe.submit');


    // product payment gateway route
    // PayPal routes temporarily disabled - needs migration to new SDK
    // Route::post('/product/paypal/submit', 'Payment\Product\PaypalController@store')->name('product.paypal.submit');
    // Route::get('/product/order/paypal/cancle', 'Payment\Product\PaypalController@paycancle')->name('product.payment.cancle');
    // Route::get('/product/paypal/return', 'Payment\Product\PaypalController@payreturn')->name('product.payment.return');
    // Route::get('/product/paypal/notify', 'Payment\Product\PaypalController@notify')->name('product.payment.notify');
    Route::post('/product/stripe/submit', 'Payment\Product\StripeController@store')->name('product.stripe.submit');
   

    Route::prefix('user')->group(function () {

        
        Route::get('/login', 'User\LoginController@showLoginForm')->name('user.login');
        Route::post('/login/submit', 'User\LoginController@login')->name('user.login.submit');

        Route::get('/register', 'User\RegisterController@showRegisterForm')->name('user.register');
        Route::post('/register/submit', 'User\RegisterController@register')->name('user.register.submit');
        Route::get('/register/verify/{token}', 'User\RegisterController@token')->name('user.register.token');

        Route::get('/reset', 'User\UserController@resetform')->name('user.reset');
        Route::post('/reset', 'User\UserController@reset')->name('user.reset.submit');

        Route::get('/profile', 'User\UserController@profile')->name('user.profile');
        Route::post('/profile', 'User\UserController@profileupdate')->name('user.profile.update');

        Route::get('/forgot', 'User\ForgotController@showforgotform')->name('user.forgot');
        Route::post('/forgot', 'User\ForgotController@forgot')->name('user.forgot.submit');

        Route::get('/logout', 'User\LoginController@logout')->name('user.logout');


        Route::get('/dashboard', 'User\UserController@index')->name('user.dashboard');
        
         Route::get('/clear', function() {
        $run = Artisan::call('config:clear');
        $run = Artisan::call('cache:clear');
        $run = Artisan::call('config:cache');
        return 'FINISHED';  
    });
    
    
        Route::get('/edit-profile', 'User\UserController@editprofile')->name('user.editprofile');
        Route::post('/update-profile/{id}', 'User\UserController@updateprofile')->name('user.updateprofile');
        Route::get('/package-order', 'User\UserController@packageorder')->name('user.packageorder');
        Route::get('/bill-pay', 'User\UserController@bill_pay')->name('user.billpay');
        Route::get('/bill-pay/view/{id}/', 'User\UserController@billpay_view')->name('user.billpay_view');
        Route::get('/change-password', 'User\UserController@change_password')->name('user.change_password');
        Route::post('/update-password/{id}', 'User\UserController@update_password')->name('user.update_password');

        Route::get('/product-orders', 'User\UserController@product_order')->name('user.product.order');
        Route::get('/product-order/{id}', 'User\UserController@product_order_details')->name('user.product.orderDetails');
    });


});


/*=======================================================
Admin Routes
=======================================================*/

Route::group(['prefix' => 'admin', 'middleware' => 'guest:admin'], function () {
    Route::get('/', 'Admin\LoginController@login')->name('admin.login');
    Route::post('/login', 'Admin\LoginController@authenticate')->name('admin.auth');
});
Route::group(['prefix' => '{locale}/admin', 'where' => ['locale' => '[a-zA-Z]{2}'], 'middleware' => ['auth:admin','setlocale']], function() { 
//Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin','setlocale']], function () {

    //Admin Logout Route
    Route::get('/logout', 'Admin\LoginController@logout')->name('admin.logout');
    Route::get('/dashboard', 'Admin\DashboardController@dashboard')->name('admin.dashboard');
    // Payment Routes  
     Route::get('/payment-query', 'Admin\DashboardController@payment_query')->name('admin.payment_query');
     
    Route::get('/search-payment-record', 'Admin\DashboardController@search_payment_query')->name('admin.search_payment_record');

     
    Route::get('/payment-detail-query/{id}', 'Admin\DashboardController@payment_detail_query')->name('admin.payment_fault_query');
    
    Route::get('/payment-user-detail/{id}', 'Admin\DashboardController@payment_user_detail')->name('admin.payment_user_detail');



     //Route::get('/payment-query/{username}', 'Admin\MbtController@paymentRecords')->name('admin.payment_query');
     Route::get('/bind-payment-query-user', 'Admin\DashboardController@get_bind_payment_user')->name('admin.get_bind_payment_user');
     
     Route::post('/upadte-payment-query', 'Admin\DashboardController@update_payment_query')->name('admin.update_payment_query');
     
     // Fault Query
     Route::get('/fault-query', 'Admin\DashboardController@fault_query')->name('admin.fault_query');
     Route::get('/search-fault-query', 'Admin\DashboardController@search_fault_query')->name('admin.search_fault_query');
     Route::post('/update-fault-query', 'Admin\DashboardController@update_fault_query')->name('admin.update_fault_query');
     
    // Promotions
    Route::get('/promotion', 'Admin\PromotionController@index')->name('admin.promotion');
    Route::get('/promotion/add', 'Admin\PromotionController@add')->name('admin.promotion.add');
    Route::post('/promotion/store', 'Admin\PromotionController@store')->name('admin.promotion.store');
    Route::post('/promotion/delete/{id}/', 'Admin\PromotionController@delete')->name('admin.promotion.delete');
    Route::get('/promotion/edit/{id}/', 'Admin\PromotionController@edit')->name('admin.promotion.edit');
    Route::post('/promotion/update/{id}/', 'Admin\PromotionController@update')->name('admin.promotion.update');
    
    // Payment Process
    Route::get('/payment-process', 'Admin\PaymentController@index')->name('admin.payment.process');
    Route::get('/payment-process/add', 'Admin\PaymentController@add')->name('admin.payment.process.add');
    Route::post('/payment-process/store', 'Admin\PaymentController@store')->name('admin.payment.process.store');
    Route::post('/payment-process/delete/{id}/', 'Admin\PaymentController@delete')->name('admin.payment.process.delete');
    Route::get('/payment-process/edit/{id}/', 'Admin\PaymentController@edit')->name('admin.payment.process.edit');
    Route::post('/payment-process/update/{id}/', 'Admin\PaymentController@update')->name('admin.payment.process.update');
    
    // Pending Payments
    Route::get('/cbpay', 'Admin\PendingController@cbpay')->name('admin.cbpay');
    Route::get('/update/cbpay', 'Admin\PendingController@update_cbpay')->name('admin.update.cbpay');
    Route::get('/kbzpay', 'Admin\PendingController@kbzpay')->name('admin.kbzpay');
    Route::get('/update/kbzpay', 'Admin\PendingController@update_kbzpay')->name('admin.update.kbzpay');
    Route::get('/wavepay', 'Admin\PendingController@wavepay')->name('admin.wavepay');
    Route::get('/update/wavepay', 'Admin\PendingController@update_wavepay')->name('admin.update.wavepay');
    
    // Maintainance
    Route::get('maintainance-settings', 'Admin\MaintainanceController@maintainance_settings')->name('admin.maintainance.setting');
    Route::get('add-maintainance-settings', 'Admin\MaintainanceController@add_maintainance_settings')->name('admin.add.maintainance.setting');
    Route::post('store-maintainance-settings', 'Admin\MaintainanceController@store_maintainance_settings')->name('admin.store.maintainance.setting');
    Route::get('edit-maintainance-settings/{id}', 'Admin\MaintainanceController@edit_maintainance_settings')->name('admin.edit.maintainance.setting');
    Route::post('update-maintainance-settings/{id}', 'Admin\MaintainanceController@update_maintainance_settings')->name('admin.update.maintainance.setting');
    Route::get('delete-maintainance-settings/{id}', 'Admin\MaintainanceController@delete_maintainance_settings')->name('admin.delete.maintainance.setting');
    
    // Extra Months
    Route::get('/extra-months', 'Admin\PromotionController@extra_months')->name('admin.extra.months');
    Route::get('/extra-months-edit/{id}/', 'Admin\PromotionController@extra_months_edit')->name('admin.extra.months.edit');
    Route::post('/extra-months-update/{id}/', 'Admin\PromotionController@extra_months_update')->name('admin.extra.months.update');
     
     // Reply User Message
     Route::get('/message-to-user', 'Admin\DashboardController@message')->name('admin.message');
     Route::get('/get-user-details','Admin\DashboardController@get_user_details');
     Route::get('/get-user-chat','Admin\DashboardController@get_user_chat');
     Route::get('/insert-user-chat','Admin\DashboardController@insert_user_chat');
     Route::get('/fetch-user', 'Admin\DashboardController@fetch_user');
     Route::get('/fetch-all-user', 'Admin\DashboardController@fetch_all_user');
     
      // User header notification 
     Route::get('/user-message-notification', 'Admin\DashboardController@notification');
    //  Route::get('/get-user-details','Admin\DashboardController@get_user_details');
    //  Route::get('/get-user-chat','Admin\DashboardController@get_user_chat');
    //  Route::get('/insert-user-chat','Admin\DashboardController@insert_user_chat');
     
    
    // Publis Information
    Route::get('/marketting-information','Admin\DashboardController@marketting_information')->name('admin.marketting_information');
    Route::get('/user-notification','Admin\DashboardController@user_notification')->name('admin.user_notification');
    Route::post('/user-store-notification','Admin\DashboardController@StoreUserNotification')->name('admin.store.notification');
    Route::post('/user-store-all-notification','Admin\DashboardController@StoreMultiUserNotification')->name('admin.store.multi.notification');
    Route::post('/notification-filter','Admin\DashboardController@NotificationFilter')->name('get.notification.filter');
    
     //Install Query
     Route::get('/install-query', 'Admin\DashboardController@install_query')->name('admin.install_query');
     Route::get('/search-query', 'Admin\DashboardController@search_install_query')->name('admin.search');

     Route::post('/upadte-install-query', 'Admin\DashboardController@update_install_query')->name('admin.update_install_query');
     
     
    // Export 
    Route::get('/export-users-excel', 'Admin\ExportController@export_users_excel')->name('admin.export_users_excel');
    Route::get('/export-users-pdf', 'Admin\ExportController@export_users_pdf')->name('admin.export_users_pdf');
    
    // 27-08-22
    Route::get('/export-fault-excel', 'Admin\ExportController@export_fault_excel')->name('admin.export_fault_query_excel');
    Route::get('/export-fault-pdf', 'Admin\ExportController@export_fault_pdf')->name('admin.export_fault_pdf');
    
    
      //25-10-22
    Route::get('/export-payment-excel', 'Admin\ExportController@export_payment_excel')->name('admin.export_payment_query_excel');
    // Route::get('/export-fault-pdf', 'Admin\ExportController@export_fault_pdf')->name('admin.export_fault_pdf');
    
    Route::get('/export-binduser-excel', 'Admin\ExportController@export_binduser_excel')->name('admin.export_binduser_query_excel');

    Route::get('/export-install-excel', 'Admin\ExportController@export_install_excel')->name('admin.export_install_query_excel');
    
    
     // User Query   
     Route::get('/user-details/{id}', 'Admin\DashboardController@user_details')->name('admin.userdetails');
     Route::get('/user-query', 'Admin\DashboardController@user_query')->name('admin.user_query');
     Route::get('/bind-user-query', 'Admin\DashboardController@bind_user_query')->name('admin.bind_user_query');
     
    Route::get('/search-bind-user', 'Admin\DashboardController@search_bind_user')->name('admin.search_bind_user');



     Route::get('/user-update', 'Admin\DashboardController@user_update')->name('admin.user_update');
     Route::post('/user-update-query', 'Admin\DashboardController@update_users_query')->name('admin.update_users_query');
     Route::post('/admin-user-update-query', 'Admin\DashboardController@admin_update_user_query')->name('admin.admin_update_user_query');
     Route::get('/bind-user-details/{id}', 'Admin\DashboardController@bind_user_details')->name('admin.binduserdetails');
     Route::get('/user-disable', 'Admin\DashboardController@user_disabled')->name('admin.user_disabled');
     
     // Role Manage  
     
     
     Route::get('/user-role-delete/{role_id}', 'Admin\DashboardController@delete')->name('admin.delete_role');


     Route::get('/user-role-add', 'Admin\DashboardController@role_add')->name('admin.role_add');
     Route::get('/user-role-manage', 'Admin\DashboardController@role_manage')->name('admin.role_manage');
     Route::get('/user-role-update/{role_id}', 'Admin\DashboardController@update_role')->name('admin.update_role');
     Route::post('/user-role-store', 'Admin\DashboardController@store_role')->name('admin.store_role');
     Route::post('/user-role-edit', 'Admin\DashboardController@edit_role')->name('admin.edit_role');
     Route::get('/user-role-disable', 'Admin\DashboardController@disable_role')->name('admin.disable_role');
    // Add Permission
     Route::get('/user-add-permission', 'Admin\DashboardController@add_permission')->name('admin.add_permission');
    // Admin Profile Routs
    Route::get('/profile', function() {
        return redirect()->route('admin.editProfile', app()->getLocale());
    })->name('admin.profile');
    Route::get('/profile/edit', 'Admin\ProfileController@editProfile')->name('admin.editProfile');
    Route::post('/profile/update', 'Admin\ProfileController@updateProfile')->name('admin.updateProfile');
    Route::get('/profile/password/edit', 'Admin\ProfileController@editPassword')->name('admin.editPassword');
    Route::post('/profile/password/update', 'Admin\ProfileController@updatePassword')->name('admin.updatePassword');

    // Basic Information Route
    Route::get('/basicinfo', 'Admin\SettingController@basicinfo')->name('admin.basicinfo');
    Route::post('/basicinfo/update/{id}', 'Admin\SettingController@updateBasicinfo')->name('admin.setting.updateBasicinfo');
    Route::post('/commoninfo/update', 'Admin\SettingController@updateCommoninfo')->name('admin.setting.commoninfo');
    Route::get('/app-banner', 'Admin\SettingController@app_banner')->name('admin.app_banner');
    Route::post('/upload-banner', 'Admin\SettingController@upload_banner')->name('admin.upload_banner');
    Route::get('/delete-banner/{id}', 'Admin\SettingController@delete_banner')->name('admin.delete_banner');
    Route::post('/edit-banner', 'Admin\SettingController@edit_banner')->name('admin.edit_banner');
    
    //Prefenectial
    Route::get('/preferential-activities', 'Admin\SettingController@Preferential_activities')->name('admin.Preferential_activities');
    Route::post('/upload-preferential', 'Admin\SettingController@upload_Preferential')->name('admin.upload_Preferential');
    Route::post('/edit-preferential', 'Admin\SettingController@edit_preferential')->name('admin.editpreferential');
    Route::get('/delete-preferential/{id}', 'Admin\SettingController@delete_Preferential')->name('admin.deletepreferential');

    
    //Error message
    Route::get('/error-message', 'Admin\SettingController@error_message')->name('admin.errormessage');
    Route::post('/upload-error-message', 'Admin\SettingController@upload_message')->name('admin.uploadmessage');
    Route::post('/edit-error-message', 'Admin\SettingController@edit_error_message')->name('admin.editerrormessage');
    Route::get('/delete-error-message/{id}', 'Admin\SettingController@delete_error_message')->name('admin.deleteerrormessage');


// Section Title Route
    Route::get('/sectiontitle', 'Admin\SettingController@sectiontitle')->name('admin.sectiontitle');
    Route::post('/sectiontitle/update', 'Admin\SettingController@updateSectiontitle')->name('admin.setting.updateSectiontitle');

    // SEO Information Route
    Route::get('/seoinfo', 'Admin\SettingController@seoinfo')->name('admin.seoinfo');
    Route::post('/seoinfo/update/{id}', 'Admin\SettingController@updateSeoinfo')->name('admin.setting.updateSeoinfo');

    // Socila Links Route
    Route::get('/slinks', 'Admin\SocialController@slinks')->name('admin.slinks');
    Route::get('/slinks/add', 'Admin\SocialController@addSlinks')->name('admin.slinks.add');
    Route::post('/slinks/store', 'Admin\SocialController@storeSlinks')->name('admin.storeSlinks');
    Route::post('/slinks/update/{id}/', 'Admin\SocialController@updateSlinks')->name('admin.updateSlinks');
    Route::get('/slinks/edit/{id}/', 'Admin\SocialController@editSlinks')->name('admin.editSlinks');
    Route::post('/slinks/delete/{id}/', 'Admin\SocialController@deleteSlinks')->name('admin.deleteSlinks');

    // Scripts Route
    Route::get('/scripts', 'Admin\SettingController@scripts')->name('admin.scripts');
    Route::post('/scripts/update', 'Admin\SettingController@updateScripts')->name('admin.commonsetting.updateScripts');

    // Page Visibility  Title Route
    Route::get('/page-visibility', 'Admin\SettingController@pagevisibility')->name('admin.pagevisibility');
    Route::post('/page-visibility/update', 'Admin\SettingController@updatepagevisibility')->name('admin.pagevisibility.update');

    // Custom CSS
    Route::get('/custom-css', 'Admin\SettingController@custom_css')->name('admin.custom.css');
    Route::post('/custom-css/update', 'Admin\SettingController@custom_css_update')->name('admin.custom.css.update');

    // Register User start
    Route::get('register/users/form','Admin\RegisterUserController@getform')->name('admin.user.form');
    Route::post('register/users/store','Admin\RegisterUserController@storeuser')->name('admin.user.store');
    Route::get('register/users','Admin\RegisterUserController@index')->name('admin.register.user');
    Route::get('register/user/details/{id}','Admin\RegisterUserController@view')->name('register.user.view');
    
    Route::get('register/user/delete/{id}','Admin\RegisterUserController@delete')->name('register.user.delete');
    
    Route::get('register/user/edit_role/{id}','Admin\RegisterUserController@edit_role')->name('register.user.edit_role');


    Route::post('register/user/edit','Admin\RegisterUserController@edit')->name('register.user.edit');

    
    Route::get('register/user/package-buy','Admin\RegisterUserController@package_buy')->name('register.user.package_buy');
    Route::get('register/user/package-not-buy','Admin\RegisterUserController@package_not_buy')->name('register.user.package_not_buy');

    // Admin Cookie Alert Routes
    Route::get('/cookie-alert', 'Admin\SettingController@cookiealert')->name('admin.cookie.alert');
    Route::post('/cookie-alert/update/{langid}/', 'Admin\SettingController@updatecookie')->name('admin.cookie.update');
    
    // Bank Settings
    Route::get('bank/settings','Admin\SettingController@banksettings')->name('bank.settings');
    Route::post('bank/settings/store','Admin\SettingController@banksetting_store')->name('admin.bank.settings.store');

    // About Us Route
    Route::get('/about', 'Admin\AboutController@about_me')->name('admin.about_me');
    Route::post('/about/update', 'Admin\AboutController@update_about_me')->name('admin.update_about_me');

    // Contact Info Route
    Route::get('/about/contact-info', 'Admin\AboutController@contact_info')->name('admin.contact_info');
    Route::post('/about/contact-info/update', 'Admin\AboutController@contact_info_update')->name('admin.contact_info_update');

    // Slider Route
    Route::get('/slider', 'Admin\SliderController@slider')->name('admin.slider');
    Route::get('/slider/add', 'Admin\SliderController@add')->name('admin.slider.add');
    Route::post('/slider/store', 'Admin\SliderController@store')->name('admin.slider.store');
    Route::post('/slider/delete/{id}/', 'Admin\SliderController@delete')->name('admin.slider.delete');
    Route::get('/slider/edit/{id}/', 'Admin\SliderController@edit')->name('admin.slider.edit');
    Route::post('/slider/update/{id}/', 'Admin\SliderController@update')->name('admin.slider.update');

    // About Route
    Route::get('/about', 'Admin\AboutController@about')->name('admin.about');
    Route::get('/about/add', 'Admin\AboutController@add')->name('admin.about.add');
    Route::post('/about/store', 'Admin\AboutController@store')->name('admin.about.store');
    Route::post('/about/delete/{id}/', 'Admin\AboutController@delete')->name('admin.about.delete');
    Route::get('/about/edit/{id}/', 'Admin\AboutController@edit')->name('admin.about.edit');
    Route::post('/about/update/{id}/', 'Admin\AboutController@update')->name('admin.about.update');
    Route::post('/about/aboutcontent/{id}/', 'Admin\AboutController@aboutcontent')->name('admin.aboutcontent.update');

    // Package Route
    Route::get('/package', 'Admin\PackagController@package')->name('admin.package');
    Route::get('/package/add', 'Admin\PackagController@add')->name('admin.package.add');
    Route::post('/package/store', 'Admin\PackagController@store')->name('admin.package.store');
    Route::post('/package/delete/{id}/', 'Admin\PackagController@delete')->name('admin.package.delete');
    Route::get('/package/edit/{id}/', 'Admin\PackagController@edit')->name('admin.package.edit');
    Route::post('/package/update', 'Admin\PackagController@update')->name('admin.package.update');
    Route::post('/package/plancontent/{id}/', 'Admin\PackagController@plancontent')->name('admin.plancontent.update');

    // Offer Route
    Route::get('/offer', 'Admin\OfferController@offer')->name('admin.offer');
    Route::get('/offer/add', 'Admin\OfferController@add')->name('admin.offer.add');
    Route::post('/offer/store', 'Admin\OfferController@store')->name('admin.offer.store');
    Route::post('/offer/delete/{id}/', 'Admin\OfferController@delete')->name('admin.offer.delete');
    Route::get('/offer/edit/{id}/', 'Admin\OfferController@edit')->name('admin.offer.edit');
    Route::post('/offer/update/{id}/', 'Admin\OfferController@update')->name('admin.offer.update');
    Route::post('/offer/offercontent/{id}/', 'Admin\OfferController@offercontent')->name('admin.offercontent.update');

    // Service Route
    Route::get('/service', 'Admin\ServiceController@service')->name('admin.service');
    Route::get('/service/add', 'Admin\ServiceController@add')->name('admin.service.add');
    Route::post('/service/store', 'Admin\ServiceController@store')->name('admin.service.store');
    Route::post('/service/delete/{id}/', 'Admin\ServiceController@delete')->name('admin.service.delete');
    Route::get('/service/edit/{id}/', 'Admin\ServiceController@edit')->name('admin.service.edit');
    Route::post('/service/update/{id}/', 'Admin\ServiceController@update')->name('admin.service.update');
    Route::post('/service/servicecontent/{id}/', 'Admin\ServiceController@servicecontent')->name('admin.servicecontent.update');

    // Testimonial Route
    Route::get('/testimonial', 'Admin\TestimonialController@testimonial')->name('admin.testimonial');
    Route::get('/testimonial/add', 'Admin\TestimonialController@add')->name('admin.testimonial.add');
    Route::post('/testimonial/store', 'Admin\TestimonialController@store')->name('admin.testimonial.store');
    Route::post('/testimonial/delete/{id}/', 'Admin\TestimonialController@delete')->name('admin.testimonial.delete');
    Route::get('/testimonial/edit/{id}/', 'Admin\TestimonialController@edit')->name('admin.testimonial.edit');
    Route::post('/testimonial/update/{id}/', 'Admin\TestimonialController@update')->name('admin.testimonial.update');
    Route::post('/testimonial/testimonialcontent/{id}/', 'Admin\TestimonialController@testimonialcontent')->name('admin.testimonialcontent.update');

    // Entertainment Route
    Route::get('/entertainment', 'Admin\EntertainmentController@entertainment')->name('admin.entertainment');
    Route::get('/entertainment/add', 'Admin\EntertainmentController@add')->name('admin.entertainment.add');
    Route::post('/entertainment/store', 'Admin\EntertainmentController@store')->name('admin.entertainment.store');
    Route::post('/entertainment/delete/{id}/', 'Admin\EntertainmentController@delete')->name('admin.entertainment.delete');
    Route::get('/entertainment/edit/{id}/', 'Admin\EntertainmentController@edit')->name('admin.entertainment.edit');
    Route::post('/entertainment/update/{id}/', 'Admin\EntertainmentController@update')->name('admin.entertainment.update');
    Route::post('/entertainment/entertainmentcontent/{id}/', 'Admin\EntertainmentController@entertainmentcontent')->name('admin.entertainmentcontent.update');

    // Fun Fact Route
    Route::get('/funfact', 'Admin\FunfactController@funfact')->name('admin.funfact');
    Route::get('/funfact/add', 'Admin\FunfactController@add')->name('admin.funfact.add');
    Route::post('/funfact/store', 'Admin\FunfactController@store')->name('admin.funfact.store');
    Route::post('/funfact/delete/{id}/', 'Admin\FunfactController@delete')->name('admin.funfact.delete');
    Route::get('/funfact/edit/{id}/', 'Admin\FunfactController@edit')->name('admin.funfact.edit');
    Route::post('/funfact/update/{id}/', 'Admin\FunfactController@update')->name('admin.funfact.update');
    Route::post('/funfact/funfactcontent/{id}/', 'Admin\FunfactController@funfactcontent')->name('admin.funfactcontent.update');

    // Media Zone Route
    Route::get('/media', 'Admin\MediaController@media')->name('admin.media');
    Route::get('/media/add', 'Admin\MediaController@add')->name('admin.media.add');
    Route::post('/media/store', 'Admin\MediaController@store')->name('admin.media.store');
    Route::post('/media/delete/{id}/', 'Admin\MediaController@delete')->name('admin.media.delete');
    Route::get('/media/edit/{id}/', 'Admin\MediaController@edit')->name('admin.media.edit');
    Route::post('/media/update/{id}/', 'Admin\MediaController@update')->name('admin.media.update');
    Route::post('/media/mediacontent/{id}/', 'Admin\MediaController@mediacontent')->name('admin.mediacontent.update');

    // Branch Route
    Route::get('/branch', 'Admin\BranchController@branch')->name('admin.branch');
    Route::get('/branch/add', 'Admin\BranchController@add')->name('admin.branch.add');
    Route::post('/branch/store', 'Admin\BranchController@store')->name('admin.branch.store');
    Route::post('/branch/delete/{id}/', 'Admin\BranchController@delete')->name('admin.branch.delete');
    Route::get('/branch/edit/{id}/', 'Admin\BranchController@edit')->name('admin.branch.edit');
    Route::post('/branch/update/{id}/', 'Admin\BranchController@update')->name('admin.branch.update');

    // Members Route
    Route::get('/team', 'Admin\TeamController@team')->name('admin.team');
    Route::get('/team/add', 'Admin\TeamController@add')->name('admin.team.add');
    Route::post('/team/store', 'Admin\TeamController@store')->name('admin.team.store');
    Route::post('/team/delete/{id}/', 'Admin\TeamController@delete')->name('admin.team.delete');
    Route::get('/team/edit/{id}/', 'Admin\TeamController@edit')->name('admin.team.edit');
    Route::post('/team/update/{id}/', 'Admin\TeamController@update')->name('admin.team.update');

    // Members Route
    Route::get('/faq', 'Admin\FaqController@faq')->name('admin.faq');
    Route::get('/faq/add', 'Admin\FaqController@add')->name('admin.faq.add');
    Route::post('/faq/store', 'Admin\FaqController@store')->name('admin.faq.store');
    Route::post('/faq/delete/{id}/', 'Admin\FaqController@delete')->name('admin.faq.delete');
    Route::get('/faq/edit/{id}/', 'Admin\FaqController@edit')->name('admin.faq.edit');
    Route::post('/faq/update/{id}/', 'Admin\FaqController@update')->name('admin.faq.update');

    // Blog Category Route
    Route::get('/blog/blog-category', 'Admin\BcategoryController@bcategory')->name('admin.bcategory');
    Route::get('/blog/blog-category/add', 'Admin\BcategoryController@add')->name('admin.bcategory.add');
    Route::post('/blog/blog-category/store', 'Admin\BcategoryController@store')->name('admin.bcategory.store');
    Route::post('/blog/blog-category/delete/{id}/', 'Admin\BcategoryController@delete')->name('admin.bcategory.delete');
    Route::get('/blog/blog-category/edit/{id}/', 'Admin\BcategoryController@edit')->name('admin.bcategory.edit');
    Route::post('/blog/blog-category/update/{id}/', 'Admin\BcategoryController@update')->name('admin.bcategory.update');

    // Blog  Route
    Route::get('/blog', 'Admin\BlogController@blog')->name('admin.blog');
    Route::get('/blog/add', 'Admin\BlogController@add')->name('admin.blog.add');
    Route::post('/blog/store', 'Admin\BlogController@store')->name('admin.blog.store');
    Route::post('/blog/delete/{id}/', 'Admin\BlogController@delete')->name('admin.blog.delete');
    Route::get('/blog/edit/{id}/', 'Admin\BlogController@edit')->name('admin.blog.edit');
    Route::post('/blog/update/{id}/', 'Admin\BlogController@update')->name('admin.blog.update');
    Route::post('/blog/blogcontent/{id}/', 'Admin\BlogController@blogcontent')->name('admin.blogcontent.update');
    Route::get('blog/get/categoty/{id}', 'Admin\BlogController@blog_get_category')->name('admin.blog.blog_get_category');

    // Dynamic Page  Route
    Route::get('/dynamic-page', 'Admin\DynamicpageController@dynamic_page')->name('admin.dynamic-page');
    Route::get('/dynamic-page/add', 'Admin\DynamicpageController@add')->name('admin.dynamic_page.add');
    Route::post('/dynamic-page/store', 'Admin\DynamicpageController@store')->name('admin.dynamic_page.store');
    Route::post('/dynamic-page/delete/{id}/', 'Admin\DynamicpageController@delete')->name('admin.dynamic_page.delete');
    Route::get('/dynamic-page/edit/{id}/', 'Admin\DynamicpageController@edit')->name('admin.dynamic_page.edit');
    Route::post('/dynamic-page/update/{id}/', 'Admin\DynamicpageController@update')->name('admin.dynamic_page.update');

    // Newsletter Route
    Route::get('/subscriber', 'Admin\NewsletterController@newsletter')->name('admin.newsletter');
    Route::get('/mailsubscriber', 'Admin\NewsletterController@mailsubscriber')->name('admin.mailsubscriber');
    Route::post('/subscribers/sendmail', 'Admin\NewsletterController@subscsendmail')->name('admin.subscribers.sendmail');

    Route::get('/subscriber/add', 'Admin\NewsletterController@add')->name('admin.newsletter.add');
    Route::post('/subscriber/store', 'Admin\NewsletterController@store')->name('admin.newsletter.store');
    Route::post('/subscriber/delete/{id}/', 'Admin\NewsletterController@delete')->name('admin.newsletter.delete');
    Route::get('/subscriber/edit/{id}/', 'Admin\NewsletterController@edit')->name('admin.newsletter.edit');
    Route::post('/subscriber/update/{id}/', 'Admin\NewsletterController@update')->name('admin.newsletter.update');


    // Admin Footer Logo Text Routes
    Route::get('/footer', 'Admin\FooterController@index')->name('admin.footer.index');
    Route::post('/footer/update/{id}', 'Admin\FooterController@update')->name('admin.footer.update');


    // Currency  Route
    Route::get('/currency', 'Admin\CurrencyController@currency')->name('admin.currency');
    Route::get('/currency/add', 'Admin\CurrencyController@add')->name('admin.currency.add');
    Route::post('/currency/store', 'Admin\CurrencyController@store')->name('admin.currency.store');
    Route::post('/currency/delete/{id}/', 'Admin\CurrencyController@delete')->name('admin.currency.delete');
    Route::get('/currency/edit/{id}/', 'Admin\CurrencyController@edit')->name('admin.currency.edit');
    Route::post('/currency/update/{id}/', 'Admin\CurrencyController@update')->name('admin.currency.update');
    Route::get('/currency/status/set/{id}', 'Admin\CurrencyController@status')->name('admin.currency.status');

    // Payment Settings Route
    Route::get('/payment/gateways', 'Admin\PaymentGatewayController@index')->name('admin.payment.index');
    Route::get('/payment/gateways/edit/{id}', 'Admin\PaymentGatewayController@edit')->name('admin.payment.edit');
    Route::post('/payment/gateways/update/{id}', 'Admin\PaymentGatewayController@update')->name('admin.payment.update');
    Route::get('/payment/gateways/{delete}', 'Admin\PaymentGatewayController@delete')->name('admin.payment.delete');


    // Shipping Method  Route
    Route::get('/shipping/methods/', 'Admin\ShippingMethodController@shipping')->name('admin.shipping.index');
    Route::get('/shipping/method/add', 'Admin\ShippingMethodController@add')->name('admin.shipping.add');
    Route::post('/shipping/method/store', 'Admin\ShippingMethodController@store')->name('admin.shipping.store');
    Route::post('/shipping/method/delete/{id}/', 'Admin\ShippingMethodController@delete')->name('admin.shipping.delete');
    Route::get('/shipping/method/edit/{id}/', 'Admin\ShippingMethodController@edit')->name('admin.shipping.edit');
    Route::post('/shipping/method/update/{id}/', 'Admin\ShippingMethodController@update')->name('admin.shipping.update');
    Route::get('/shipping/method/status/set/{id}', 'Admin\ShippingMethodController@status')->name('admin.shipping.status');


    // ADMIN EMAIL SETTINGS SECTION
    Route::get('/email-templates', 'Admin\EmailController@index')->name('admin.mail.index');
    Route::get('/email-templates/{id}', 'Admin\EmailController@edit')->name('admin.mail.edit');
    Route::post('/email-templates/{id}', 'Admin\EmailController@update')->name('admin.mail.update');
    Route::get('/email-config', 'Admin\EmailController@config')->name('admin.mail.config');
    Route::post('/email-config/submit', 'Admin\EmailController@configUpdate')->name('admin.mail.config.update');
    Route::get('/groupemail', 'Admin\EmailController@groupemail')->name('admin.group.show');
    Route::post('/groupemailpost', 'Admin\EmailController@groupemailpost')->name('admin.group.submit');
    
    // App Label Language Routes
    Route::get('/languages', 'Admin\LanguageController@index')->name('admin.language.index');
    Route::get('/language/add', 'Admin\LanguageController@add')->name('admin.language.add');
    Route::get('/language/app-edit/{id}', 'Admin\LanguageController@EditAppLang')->name('admin.language.Appedit');
    Route::post('/language/store', 'Admin\LanguageController@store')->name('admin.language.store');
    Route::post('/language/store/app', 'Admin\LanguageController@UpadteAppLang')->name('admin.language.store.app');
    
    // Admin Language Routes
    // Route::get('/languages', 'Admin\LanguageController@index')->name('admin.language.index');
   // Route::get('/language/add', 'Admin\LanguageController@add')->name('admin.language.add');
    Route::get('/language/{id}/edit', 'Admin\LanguageController@edit')->name('admin.language.edit');
    Route::get('/language/{id}/edit/keyword', 'Admin\LanguageController@editKeyword')->name('admin.language.editKeyword');
   // Route::post('/language/store', 'Admin\LanguageController@store')->name('admin.language.store');

    Route::post('/language/upload', 'Admin\LanguageController@upload')->name('admin.language.upload');
    Route::post('/language/{id}/uploadUpdate', 'Admin\LanguageController@uploadUpdate')->name('admin.language.uploadUpdate');

    Route::post('/language/{id}/default', 'Admin\LanguageController@default')->name('admin.language.default');

    Route::post('/language/{id}/delete', 'Admin\LanguageController@delete')->name('admin.language.delete');
    Route::post('/language/{id}/update', 'Admin\LanguageController@update')->name('admin.language.update');
    Route::post('/language/{id}/update/keyword', 'Admin\LanguageController@updateKeyword')->name('admin.language.updateKeyword');


    // Order Manage Route
    Route::get('/package/all-order', 'Admin\OrderController@package_allorder')->name('admin.package.allorder');
    Route::post('/package/delete-order/{id}/', 'Admin\OrderController@delete_order')->name('admin.package.delete_order');
    Route::get('/package/view-order/{id}/', 'Admin\OrderController@view_order')->name('admin.package.view_order');
    Route::get('/package/order-edit-status/{id}/', 'Admin\OrderController@order_edit_status')->name('admin.package.order_edit_status');

    Route::post('/package/order-update-status/', 'Admin\OrderController@order_update_status')->name('admin.package.order_update_status');

    Route::get('/package/pending-order', 'Admin\OrderController@package_pendingorder')->name('admin.package.pendingorder');
    Route::get('/package/inprogress-order', 'Admin\OrderController@package_inprogress_order')->name('admin.package.inprogress');
    Route::get('/package/compleated-order', 'Admin\OrderController@package_compleated_order')->name('admin.package.compleated');

    // Bill Pay Route
    Route::get('/bill-pay', 'Admin\OrderController@bill_pay')->name('admin.billpay');
    Route::get('/bill-pay/view/{id}/', 'Admin\OrderController@billpay_view')->name('admin.billpay_view');
    Route::post('/bill-pay/delete/{id}/', 'Admin\OrderController@billpay_delete')->name('admin.billpay_delete');
    Route::get('/bill-add', 'Admin\OrderController@bill_add')->name('admin.bill_add');
    Route::post('/bill-save', 'Admin\OrderController@bill_save')->name('admin.bill_save');


    // Product  Route
    Route::get('/product', 'Admin\ProductController@products')->name('admin.product');
    Route::get('/product/add', 'Admin\ProductController@add')->name('admin.product.add');
    Route::post('/product/store', 'Admin\ProductController@store')->name('admin.product.store');
    Route::post('/product/delete/{id}/', 'Admin\ProductController@delete')->name('admin.product.delete');
    Route::get('/product/edit/{id}/', 'Admin\ProductController@edit')->name('admin.product.edit');
    Route::post('/product/update/{id}/', 'Admin\ProductController@update')->name('admin.product.update');

    // Product Order
    Route::get('/product/all/orders', 'Admin\ProductOrderController@all')->name('admin.all.product.orders');
    Route::get('/product/pending/orders', 'Admin\ProductOrderController@pending')->name('admin.pending.product.orders');
    Route::get('/product/processing/orders', 'Admin\ProductOrderController@processing')->name('admin.processing.product.orders');
    Route::get('/product/completed/orders', 'Admin\ProductOrderController@completed')->name('admin.completed.product.orders');
    Route::get('/product/rejected/orders', 'Admin\ProductOrderController@rejected')->name('admin.rejected.product.orders');
    Route::post('/product/orders/status', 'Admin\ProductOrderController@status')->name('admin.product.orders.status');
    Route::get('/product/orders/detais/{id}', 'Admin\ProductOrderController@details')->name('admin.product.details');
    Route::post('/product/order/delete', 'Admin\ProductOrderController@orderDelete')->name('admin.product.order.delete');
    Route::post('/product/order/bulk-delete', 'Admin\ProductOrderController@bulkOrderDelete')->name('admin.product.order.bulk.delete');


    // Admin Cache Clear Routes
    Route::get('/cache-clear', 'Admin\CacheController@clear')->name('admin.cache.clear');

    // Admin Backup Routes
    Route::get('/backup', 'Admin\BackupController@index')->name('admin.backup.index');
    Route::post('/backup/store', 'Admin\BackupController@store')->name('admin.backup.store');
    Route::post('/backup/{id}/delete', 'Admin\BackupController@delete')->name('admin.backup.delete');
    Route::post('/backup/download', 'Admin\BackupController@download')->name('admin.backup.download');
//});
});

Route::group(['middleware' => 'setlang'], function () {

    Route::get('/{slug}', 'Front\FrontendController@front_dynamic_page')->name('front.front_dynamic_page');

});