<?php

namespace App\Http\Controllers\Admin;

use Validator;
use App\Models\Setting;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use DB;
use App\Models\BankSetting;
use App\Models\PaymentGatewey;

class SettingController extends Controller
{
   public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function basicinfo(Request $request){
        //dd($request->language);
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
        $basicinfo = Setting::where('language_id', $langId)->first();
        $commoninfo = Setting::where('id', 1)->first();
        return view('admin.settings.basicinfo', compact('basicinfo', 'commoninfo'));
    }

    ///Update Basic Info
    public function updateCommoninfo(Request $request){
       
       
         $request->validate([
            'number' => 'required|max:250',
            'email' => 'required|max:250',
            'contactemail' => 'required|max:250',
            'base_color' => 'required',
            'header_logo' => 'mimes:jpeg,jpg,png',
            'fav_icon' => 'mimes:jpeg,jpg,png',
            'breadcrumb_image' => 'mimes:jpeg,jpg,png'
         ]);

      
         $commoninfo = Setting::where('id', 1)->first();
       
         if($request->hasFile('header_logo')){
            @unlink('assets/front/img/'. $commoninfo->header_logo);
            $file = $request->file('header_logo');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'header_logo_'.time().rand().'.'.$extension;
            $file->move('assets/front/img/', $header_logo);
            $commoninfo->header_logo = $header_logo;
        }
        
         if($request->hasFile('fav_icon')){
            @unlink('assets/front/img/'. $commoninfo->fav_icon);
            $file = $request->file('fav_icon');
            $extension = $file->getClientOriginalExtension();
            $fav_icon = 'fav_icon_'.time().rand().'.'.$extension;
            $file->move('assets/front/img/', $fav_icon);
            $commoninfo->fav_icon = $fav_icon;
        }

         if($request->hasFile('breadcrumb_image')){
            @unlink('assets/front/img/'. $commoninfo->breadcrumb_image);
            $file = $request->file('breadcrumb_image');
            $extension = $file->getClientOriginalExtension();
            $breadcrumb_image = 'breadcrumb_image_'.'.'.$extension;
            $file->move('assets/front/img/', $breadcrumb_image);
            $commoninfo->breadcrumb_image = $breadcrumb_image;
        }

        $commoninfo->number = $request->number;
        $commoninfo->email = $request->email;
        $commoninfo->contactemail = $request->contactemail;

        $new_base_color = ltrim($request->base_color, '#');
        $commoninfo->base_color = $new_base_color;


        $commoninfo->save();

         $notification = array(
            'messege' => 'Basic Info Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.basicinfo').'?language='.$this->lang->code)->with('notification', $notification);
    }

    // Update Section Title
    public function sectiontitle(){
       return view('admin.settings.sectiontitle');
    }
    public function updateSectiontitle(Request $request){
      
       $request->validate([
         'education_title' => 'required|max:150',
         'experince_title' => 'required|max:150',
         'service_title' => 'required|max:150',
         'portfolio_title' => 'required|max:150',
         'resume_title' => 'required|max:150',
         'client_title' => 'required|max:150',
         'testimonial_title' => 'required|max:150',
         'blog_title' => 'required|max:150',
         'contact_title' => 'required|max:150',
       ]);

       $basicsettings = Setting::first();

       $basicsettings->education_title = $request->education_title;
       $basicsettings->experince_title = $request->experince_title;
       $basicsettings->service_title = $request->service_title;
       $basicsettings->portfolio_title = $request->portfolio_title;
       $basicsettings->resume_title = $request->resume_title;
       $basicsettings->client_title = $request->client_title;
       $basicsettings->testimonial_title = $request->testimonial_title;
       $basicsettings->blog_title = $request->blog_title;
       $basicsettings->contact_title = $request->contact_title;
       $basicsettings->save();

       Session::flash('success', 'Section title Updated Successfully!');
       return redirect()->route('admin.sectiontitle');

    }

    // Update SEO Information
    public function seoinfo(Request $request){
      $langCode = $request->language ?? $this->lang->code;
      $lang = Language::where('code', $langCode)->first();
      if (!$lang) {
          $lang = $this->lang;
      }
      $langId = $lang->id;
      $seo = Setting::where('language_id', $langId)->first();
       return view('admin.settings.seo', compact('seo'));
    }
    public function updateSeoinfo(Request $request, $id){
      
      $seo = Setting::where('language_id', $id)->first();

      $seo->meta_keywords = $request->meta_keywords;
      $seo->meta_description = $request->meta_description;
      $seo->save();

      $notification = array(
         'messege' => 'SEO Info Updated Successfully!',
         'alert' => 'success'
     );
     return redirect(route('admin.seoinfo').'?language='.$this->lang->code)->with('notification', $notification);

   }

   // Update General Settings
   public function gsettings(){
      return view('admin.settings.gsettings');
   }

   // Update Scripts
   public function scripts(){
      return view('admin.settings.scripts');
   }

   public function updateScripts(Request $request){

    

      $scriptsettings = Setting::first();


      $scriptsettings->disqus = $request->disqus;
      $scriptsettings->tawk_to = $request->tawk_to;

      
      
      if($request->is_tawk_to == 'on'){
         $scriptsettings->is_tawk_to = 1;
      }else{
         $scriptsettings->is_tawk_to = 0;
      }

      if($request->is_disqus == 'on'){
         $scriptsettings->is_disqus = 1;
      }else{
         $scriptsettings->is_disqus = 0;
      }

      $scriptsettings->save();

      $notification = array(
         'messege' => 'Scripts Updated Successfully!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
     
   }



   public function updateBasicinfo(Request $request, $l,$id){
       //dd($request->all());
       $request->validate([
         'website_title' => 'required|max:250',
         'address' => 'required|max:250',
         'number_days' => 'required',
         'discount' => 'required',
         'commercial_tax' => 'required',
         'new_user_days' => 'required',
         'new_user_days1' => 'required',
       ]);
       
       $basicinfo = Setting::where('language_id', $id)->first();
  
  
        if($request->hasFile('app_logo')){
           
            $file = $request->file('app_logo');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'app_logo_'.time().rand().'.'.$extension;
            $file->move('assets/front/app/', $header_logo);
          
        }else
        {
            $header_logo = $basicinfo->app_logo;
        }
       
       $basicinfo->website_title = $request->website_title;
       $basicinfo->app_logo = $header_logo;
       $basicinfo->address = $request->address;
       
       $package_expiry_days = $request->package_expiry_days;
       $basicinfo->package_expiry_days = $package_expiry_days;
       $basicinfo->ip_address = $request->ip_address;
       $basicinfo->number_days = $request->number_days;
       $basicinfo->new_user_days = $request->new_user_days;
       $basicinfo->new_user_days1 = $request->new_user_days1;
       $basicinfo->discount = $request->discount;
       $basicinfo->commercial_tax = $request->commercial_tax;
       $basicinfo->maintenance_mode = $request->maintenance_mode;
       $basicinfo->save();


      $notification = array(
            'messege' => 'Basic Info Updated successfully!',
            'alert' => 'success'
        );
         return redirect()->back()->with('notification', $notification);
        // return redirect(route('admin.basicinfo').'?language='.$this->lang->code)->with('notification', $notification);
   }

   // Page Visibility 
   public function pagevisibility(){
      return view('admin.settings.page-visibility');
   }

   public function updatepagevisibility(Request $request){

      $pagevisibility = Setting::first();



	   if($request->is_about_section == 'on'){
		   $pagevisibility->is_about_section = 1;
	   }else{
		   $pagevisibility->is_about_section = 0;
      }

      
	   if($request->is_package_section == 'on'){
		   $pagevisibility->is_package_section = 1;
	   }else{
		   $pagevisibility->is_package_section = 0;
      }

	   if($request->is_offer_section == 'on'){
		   $pagevisibility->is_offer_section = 1;
	   }else{
		   $pagevisibility->is_offer_section = 0;
      }

	   if($request->is_counter_section == 'on'){
		   $pagevisibility->is_counter_section = 1;
	   }else{
		   $pagevisibility->is_counter_section = 0;
      }

	   if($request->is_service_section == 'on'){
		   $pagevisibility->is_service_section = 1;
	   }else{
		   $pagevisibility->is_service_section = 0;
      }

	   if($request->is_testimonial_section == 'on'){
		   $pagevisibility->is_testimonial_section = 1;
	   }else{
		   $pagevisibility->is_testimonial_section = 0;
      }

	   if($request->is_blog_section == 'on'){
		   $pagevisibility->is_blog_section = 1;
	   }else{
		   $pagevisibility->is_blog_section = 0;
      }


	   if($request->is_about_page == 'on'){
		   $pagevisibility->is_about_page = 1;
	   }else{
		   $pagevisibility->is_about_page = 0;
      }
	   if($request->is_media_page == 'on'){
		   $pagevisibility->is_media_page = 1;
	   }else{
		   $pagevisibility->is_media_page = 0;
      }

	   if($request->is_shop_page == 'on'){
		   $pagevisibility->is_shop_page = 1;
	   }else{
		   $pagevisibility->is_shop_page = 0;
      }

	   if($request->is_faq_page == 'on'){
		   $pagevisibility->is_faq_page = 1;
	   }else{
		   $pagevisibility->is_faq_page = 0;
      }

	   if($request->is_team_page == 'on'){
		   $pagevisibility->is_team_page = 1;
	   }else{
		   $pagevisibility->is_team_page = 0;
      }

	   if($request->is_branch_page == 'on'){
		   $pagevisibility->is_branch_page = 1;
	   }else{
		   $pagevisibility->is_branch_page = 0;
      }

	   if($request->is_blog_page == 'on'){
		   $pagevisibility->is_blog_page = 1;
	   }else{
		   $pagevisibility->is_blog_page = 0;
      }

	   if($request->is_contact_page == 'on'){
		   $pagevisibility->is_contact_page = 1;
	   }else{
		   $pagevisibility->is_contact_page = 0;
      }

	   if($request->is_speed_test == 'on'){
		   $pagevisibility->is_speed_test = 1;
	   }else{
		   $pagevisibility->is_speed_test = 0;
      }

	   if($request->is_blog_share_links == 'on'){
		   $pagevisibility->is_blog_share_links = 1;
	   }else{
		   $pagevisibility->is_blog_share_links = 0;
      }

	   if($request->is_cooki_alert == 'on'){
		   $pagevisibility->is_cooki_alert = 1;
	   }else{
		   $pagevisibility->is_cooki_alert = 0;
      }

	   if($request->is_testimonial_bg == 'on'){
		   $pagevisibility->is_testimonial_bg = 1;
	   }else{
		   $pagevisibility->is_testimonial_bg = 0;
      }

	   if($request->is_counter_bg == 'on'){
		   $pagevisibility->is_counter_bg = 1;
	   }else{
		   $pagevisibility->is_counter_bg = 0;
      }

	   if($request->is_package_bg == 'on'){
		   $pagevisibility->is_package_bg = 1;
	   }else{
		   $pagevisibility->is_package_bg = 0;
      }

	

      
      $pagevisibility->save();

      $notification = array(
         'messege' => 'Page visibility Updated Successfully!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
   }


   // Custom CSS
   public function custom_css()
   {
       $custom_css = '/* Write Custom Css Here */';
       if (file_exists('assets/front/css/dynamic-css.css')) {
           $custom_css = file_get_contents('assets/front/css/dynamic-css.css');
       }
       return view('admin.settings.custom-css')->with(['custom_css' => $custom_css]);
   }

   public function custom_css_update(Request $request)
   {
       file_put_contents('assets/front/css/dynamic-css.css', $request->custom_css_area);

       $notification = array(
         'messege' => 'Custom Style Added Success!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
   }


   public function cookiealert(Request $request)
   {
      $langCode = $request->language ?? $this->lang->code;
      $lang = Language::where('code', $langCode)->first();
      if (!$lang) {
          $lang = $this->lang;
      }
      $langId = $lang->id;
     
      $data['cookie'] = Setting::where('language_id', $langId)->first();

       return view('admin.settings.cookie', $data);
   }

   public function updatecookie(Request $request, $langid)
   {
       $request->validate([
           'cookie_alert_text' => 'required'
       ]);

       $be = Setting::where('language_id', $langid)->firstOrFail();
       $be->cookie_alert_text = $request->cookie_alert_text;
       $be->save();

       $notification = array(
         'messege' => 'Cookie alert updated successfully!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
   }
   
   public function app_banner()
   {
       $banner = DB::table('testimonials')->where('rating',1)->get();
      // dd($banner);
        return view('admin.banner.index',compact('banner'));
   }
   
   
   
   
   
  public function Preferential_activities()
   {
       
       $banner = DB::table('Preferentialactivities')->where('rating',1)->get();
        return view('admin.banner.preferential_activities',compact('banner'));
   
   }
   
    public function upload_Preferential(Request $request)
   {
       if($request->hasFile('upload_banner')){
           
            $file = $request->file('upload_banner');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'app_banner_'.time().rand().'.'.$extension;
            $file->move('assets/front/banner/', $header_logo);
          
        }
        $basicinfo = DB::table('Preferentialactivities')->insert(['name'=>$request->banner_title,'image'=>$header_logo,'rating'=>1]);
         $notification = array(
         'messege' => 'App Banner Add successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
      
   }
   
  public function delete_Preferential($ln , $id)
  {
      $banner = DB::table('Preferentialactivities')->where('id',$id)->delete();
      $notification = array(
         'messege' => 'Preferential activities Delete successfully!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
  }
  
  
  
  public function edit_Preferential(Request $request)
   {
      if($request->hasFile('edit_upload_banner')){
           
            $file = $request->file('edit_upload_banner');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'app_banner_'.time().rand().'.'.$extension;
            $file->move('assets/front/banner/', $header_logo);
          
        }else
        {
            $header_logo = $request->old_url;
        }
        $basicinfo = DB::table('Preferentialactivities')->where('id',$request->banner_id)->update(['name'=>$request->edit_banner_title,'image'=>$header_logo,'rating'=>1]);
         $notification = array(
         'messege' => 'Preferential activities update successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
      
   }
   
   /* Error message */
   
   
    public function error_message()
   {
       
        $banner = DB::table('error_code')->get();
        return view('admin.banner.error_code',compact('banner'));
   
   }
   
    public function upload_message(Request $request)
    {
        
         $basicinfo = DB::table('error_code')->insert(['key'=>$request->key,'value'=>$request->value,'burmese_language'=>$request->Burmese,'chinese_language'=>$request->Chinese]);
         $notification = array(
         'messege' => 'Error message Add successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
    }
     
    public function delete_error_message($ln , $id)
    {
          $banner = DB::table('error_code')->where('error_id',$id)->delete();
          $notification = array(
             'messege' => 'Error Code Delete successfully!',
             'alert' => 'success'
         );
         return redirect()->back()->with('notification', $notification);
     }
  
  
  
  public function edit_error_message(Request $request)
  {
  
        $basicinfo = DB::table('error_code')->where('error_id',$request->error_id)->update(['key'=>$request->key,'value'=>$request->value,'burmese_language'=>$request->Burmese,'chinese_language'=>$request->Chinese]);
         $notification = array(
         'messege' => 'Error code activities update successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
      
  }
   
   

   
   /* End  Error message */

   
   public function upload_banner(Request $request)
   {
       if($request->hasFile('upload_banner')){
           
            $file = $request->file('upload_banner');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'app_banner_'.time().rand().'.'.$extension;
            $file->move('assets/front/banner/', $header_logo);
          
        }
        $basicinfo = DB::table('testimonials')->insert(['name'=>$request->banner_title,'image'=>$header_logo,'rating'=>1]);
         $notification = array(
         'messege' => 'App Banner Add successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
      
   }
    public function edit_banner(Request $request)
   {
       //dd($request->all());
       if($request->hasFile('edit_upload_banner')){
           
            $file = $request->file('edit_upload_banner');
            $extension = $file->getClientOriginalExtension();
            $header_logo = 'app_banner_'.time().rand().'.'.$extension;
            $file->move('assets/front/banner/', $header_logo);
          
        }else
        {
            $header_logo = $request->old_url;
        }
        $basicinfo = DB::table('testimonials')->where('id',$request->banner_id)->update(['name'=>$request->edit_banner_title,'image'=>$header_logo,'rating'=>1]);
         $notification = array(
         'messege' => 'App Banner update successfully!',
         'alert' => 'success'
         );
        return redirect()->back()->with('notification', $notification);
      
   }
   

  public function delete_banner($ln , $id)
  {
      $banner = DB::table('testimonials')->where('id',$id)->delete();
      $notification = array(
         'messege' => 'banner Delete successfully!',
         'alert' => 'success'
     );
     return redirect()->back()->with('notification', $notification);
  }
  
    public function banksettings()
    {
        $bank_details = BankSetting::where('id', '1')->first();
      
        $cb_id     = PaymentGatewey::where('id','11')->first();
        $kbz_id    = PaymentGatewey::where('id','8')->first();
        $aya_id    = PaymentGatewey::where('id','9')->first();
        $direct_id = PaymentGatewey::where('id','12')->first();
        $wave_id   = PaymentGatewey::where('id','10')->first();
        
        return view('admin.settings.bank_details',compact('bank_details', 'cb_id', 'kbz_id', 'aya_id', 'direct_id', 'wave_id'));
    }
  
    public function banksetting_store(Request $request)
    {
        $data = [
            'type' => $request->input('type'),
            'api_url' => $request->input('api_url'),
            'auth_token' => $request->input('auth_token'),
            'ecommerce_id' => $request->input('ecommerce_id'),
            'sub_mer_id' => $request->input('sub_mer_id'),
            'mer_id' => $request->input('mer_id'),
            'transaction_type' => $request->input('transaction_type'),
            'notifyurl' => $request->input('notifyurl'),
            'cb_status' => $request->input('cb_status'),
            'cb_redirect' => $request->input('cb_redirect'),
            'kbz_type' => $request->input('kbz_type'),
            'kbz_api_url' => $request->input('kbz_api_url'),
            'kbz_m_code' => $request->input('kbz_m_code'),
            'kbz_appid' => $request->input('kbz_appid'),
            'kbz_key' => $request->input('kbz_key'),
            'kbz_trade_type' => $request->input('kbz_trade_type'),
            'kbz_notifyurl' => $request->input('kbz_notifyurl'),
            'kbz_version' => $request->input('kbz_version'),
            'kbz_redirecct' => $request->input('kbz_redirecct'),
            'kbz_status' => $request->input('kbz_status'),
            'aya_paytype' => $request->input('aya_paytype'),
            'aya_api_tokenurl' => $request->input('aya_api_tokenurl'),
            'aya_consumer_key' => $request->input('aya_consumer_key'),
            'aya_consumer_secret' => $request->input('aya_consumer_secret'),
            'aya_grant_type' => $request->input('aya_grant_type'),
            'aya_api_baseurl' => $request->input('aya_api_baseurl'),
            'aya_phone' => $request->input('aya_phone'),
            'aya_password' => $request->input('aya_password'),
            'aya_enc_key' => $request->input('aya_enc_key'),
            'aya_status' => $request->input('aya_status'),
            'direct_type' => $request->input('direct_type'),
            'direct_apiurl' => $request->input('direct_apiurl'),
            'direct_mcode' => $request->input('direct_mcode'),
            'direct_key' => $request->input('direct_key'),
            'direct_status' => $request->input('direct_status'),
            'wave_live_seconds' => $request->input('wave_live_seconds'),
            'wave_merchnt_id' => $request->input('wave_merchnt_id'),
            'wave_callback_url' => $request->input('wave_callback_url'),
            'wave_secret_key' => $request->input('wave_secret_key'),
            'wave_base_url' => $request->input('wave_base_url'),
            'wave_status' => $request->input('wave_status')
        ];
        
        $rs = BankSetting::where(['id'=> '1'])->update($data);
        
        $cb_id     = PaymentGatewey::where('id','11')->update(['status'=>$request->input('cb_pay_status')]);
        $kbz_id    = PaymentGatewey::where('id','8')->update(['status'=>$request->input('kbz_pay_status')]);
        $aya_id    = PaymentGatewey::where('id','9')->update(['status'=>$request->input('aya_pay_status')]);
        $direct_id = PaymentGatewey::where('id','12')->update(['status'=>$request->input('direct_pay_status')]);
        $wave_id   = PaymentGatewey::where('id','10')->update(['status'=>$request->input('wave_pay_status')]);
        
        if($rs){
            $notification = array(
                'messege' => 'Bank Settings updated successfully!',
                'alert' => 'success'
            );
            return redirect()->back()->with('notification', $notification);
        }else{
            $notification = array(
                'messege' => 'Something went wrong!',
                'alert' => 'success'
            );
            return redirect()->back()->with('notification', $notification);
        }
    }
  
}