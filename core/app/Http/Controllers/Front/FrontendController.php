<?php

namespace App\Http\Controllers\Front;
use App\Models\Faq;
use Session;
use App\Models\Blog;
use App\Models\Team;
use App\Models\About;
use App\Models\Branch;
use App\Models\Slider;
use App\Models\Social;
use App\Models\Package;
use App\Models\Service;
use App\Models\Billpaid;
use App\Models\Language;
use App\Models\Bcategory;
use App\Models\Daynamicpage;
use App\Models\Mediazone;
use App\Models\Emailsetting;
use App\Models\Offerprovide;
use App\Models\Packageorder;
use App\Models\Sectiontitle;
use App\Models\Entertainment;
use App\Models\Funfact;
use App\Models\PaymentGatewey;
use App\Helpers\MailSend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\PaymentNew;
use App\Models\Setting;
use App\Models\User;
use App\Models\PendingPayment;

class FrontendController extends Controller
{
    const INIT_VECTOR_LENGTH = 16;
    const CIPHER = 'AES-128-CBC';

    public function RedirectUrl()
    {
        print_r($_GET);
    }
    // Home Page Funtions
    public function index(){
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }
       
        $data['sliders'] = Slider::where('status',1)->where('language_id', $currlang->id)->get();
        $data['abouts'] = About::where('status',1)->where('language_id', $currlang->id)->get();
        $data['sectionInfo'] = Sectiontitle::where('language_id', $currlang->id)->first();
        $data['plans'] = Package::where('status',1)->where('language_id', $currlang->id)->get();
        $data['offers'] = Offerprovide::where('status',1)->where('language_id', $currlang->id)->get();
        $data['services'] = Service::where('status',1)->where('language_id', $currlang->id)->limit(6)->get();
        $data['blogs'] = Blog::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->limit(3)->get();
        $data['testimonials'] = Testimonial::where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();
        $data['funfacts'] = Funfact::where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();
        
        
        return view('front.index', $data);
    }

   // Email Sends  Funtions
    public function sendmail(Request $request) {

        $request->validate([
          'name' => 'required',
          'email' => 'required|email',
          'subject' => 'required',
          'message' => 'required'
        ]);

        $about =  About::first();
        $from = $request->email;
        $to = $about->mail;
        $subject = $request->subject;
        $message = $request->message;

        $headers = "From: $request->name <$from> \r\n";
        $headers .= "Reply-To: $request->name <$from> \r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        @mail($to, $subject, $message, $headers);

        $notification = array(
            'messege' => 'Email sent successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);

    }



    //Faq page
    public function faq() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $faqs = Faq::where('status', 1)->where('language_id', $currlang->id)->get();
        return view('front.faq', compact('faqs'));
    }


   //notify page
    public function notify() {
        return view('front.notify');
    }

    //About page
    public function about() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['abouts'] = About::where('status',1)->where('language_id', $currlang->id)->get();
        $data['sectionInfo'] = Sectiontitle::where('language_id', $currlang->id)->first();
        $data['offers'] = Offerprovide::where('status',1)->where('language_id', $currlang->id)->get();
        $data['funfacts'] = Funfact::where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();

        return view('front.about', $data);
    }

    //service page
    public function service() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['services'] = Service::where('status',1)->where('language_id', $currlang->id)->get();

        return view('front.service', $data);
    }

    public function service_details($slug){
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['service'] = Service::where('slug', $slug)->where('language_id', $currlang->id)->firstOrFail();
        $data['all_services'] = Service::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();

        return view('front.service-details', $data);
    }

    //package page
    public function package() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['plans'] = Package::where('status',1)->where('language_id', $currlang->id)->get();

        return view('front.package', $data);
    }

    //packagecheckout page
    public function packagecheckout($id){
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['packagedetails'] = Package::where('id', $id)->where('language_id', $currlang->id)->first();
        $data['gateways'] = PaymentGatewey::where('status',1)->get();
        $data['already_purchased'] = Packageorder::where('user_id', Auth::user()->id)->first();

        return view('front.packagecheckout', $data);
    }

    //media page
    public function media() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['entertainments'] = Entertainment::where('status',1)->where('language_id', $currlang->id)->get();
        $data['mediazones'] = Mediazone::where('status',1)->where('language_id', $currlang->id)->get();
        $data['sectionInfo'] = Sectiontitle::where('language_id', $currlang->id)->first();

        return view('front.media', $data);
    }

    //branch page
    public function branch() {
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['branches'] = Branch::where('language_id', $currlang->id)->where('status',1)->get();

        return view('front.branch', $data);
    }

    //team page
    public function team() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['teams'] = Team::where('language_id', $currlang->id)->where('status',1)->orderBy('id', 'DESC')->paginate(6);

        return view('front.team', $data);
    }

    //contact page
    public function contact() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $data['sectionInfo'] = Sectiontitle::where('language_id', $currlang->id)->first();
        $data['socials'] = Social::all();

        return view('front.contact', $data);
    }
    public function contactSubmit(Request $request){
         
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'message' => 'required|string',
        ]);
       
        // Login Section
        $name = $request->name;
        $fromemail = $request->email;
        $number = $request->phone;
        $mail = new PHPMailer(true);
        $em = Emailsetting::first();
        if ($em->is_smtp == 1) {
            try {
                $mail->isSMTP();
                $mail->Host       = $em->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $em->smtp_user;
                $mail->Password   = $em->smtp_pass;
                $mail->SMTPSecure = $em->email_encryption;
                $mail->Port       = $em->smtp_port;

                //Recipients
                $mail->setFrom($fromemail, $name);
                $mail->addAddress($em->from_email, $em->from_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "User message from contact page";
                $mail->Body    = "Name: ".$name."</br>Email: ".$fromemail."</br>Phone: ".$number."</br>Message: ".$request->message;

                $mail->send();
            } catch (Exception $e) {
                // die($e->getMessage());
            }
        } else {
            try {
                //Recipients
                $mail->setFrom($fromemail, $name);
                $mail->addAddress($em->from_email, $em->from_name);


                // Content
                $mail->isHTML(true);
                $mail->Subject = "User message from contact page";
                $mail->Body    = "Name: ".$name."</br>Email: ".$fromemail."</br>Phone: ".$number."</br>Message: ".$request->message;

                $mail->send();
            } catch (Exception $e) {
                // die($e->getMessage());
            }
        }


         $notification = array(
            'messege' => 'Mail send successfully',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);

    }
    //billpay page
    public function billpay() {
         if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $user = Auth::user();
        
        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('user.login')->with('error', 'Please login to access bill pay.');
        }
        
        $data['packagedetails'] = $user->activepackage ? Package::where('id', $user->activepackage)->first() : null;
        $data['gateways'] = PaymentGatewey::where('status',1)->get();
        $data['billpayed'] = Billpaid::where('user_id', $user->id)->where('yearmonth', \Carbon\Carbon::now()->format('m-Y'))->first();

        return view('front.billpay', $data);
        
    }
 



    // Blog Page  Funtion
    public function blogs(Request $request){

        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        
        $category = $request->category;
        $catid = null;
        if (!empty($category)) {
            $data['category'] = Bcategory::where('slug', $category)->firstOrFail();
            $catid = $data['category']->id;
        }

        $term = $request->term;
        $month = $request->month;
        $year = $request->year;
        $bcategories = Bcategory::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();

        $latestblogs = Blog::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->limit(4)->get();

        $blogs = Blog::where('status', 1)->where('language_id', $currlang->id)
                        ->when($catid, function ($query, $catid) {
                            return $query->where('bcategory_id', $catid);
                        })
                        ->when($term, function ($query, $term) {
                            return $query->where('title', 'like', '%'.$term.'%');
                        })
                        ->orderBy('id', 'DESC')->paginate(6);

        return view('front.blogs', compact('blogs', 'bcategories', 'latestblogs'));
    }

    // Blog Details  Funtion
    public function blogdetails($slug) {

        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }

        $blog = Blog::where('slug', $slug)->where('language_id', $currlang->id)->firstOrFail();
        $latestblogs = Blog::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->limit(4)->get();
        $bcategories = Bcategory::where('status', 1)->where('language_id', $currlang->id)->orderBy('id', 'DESC')->get();
       
        return view('front.blogdetails', compact('blog', 'bcategories', 'latestblogs'));
    }

    // Front Daynamic Page Function
    public function front_dynamic_page($slug){
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }
        
        $front_daynamic_page = Daynamicpage::where('slug', $slug)->where('language_id', $currlang->id)->firstOrFail();

        return view('front.daynamicpage', compact('front_daynamic_page'));
    }

    // Speed Test
    public function speed(){
        return view('front.speed');
    }

    // Change Language
    public function changeLanguage($lang)
    {
      //  dd($lang);
        session()->put('lang', $lang);
        app()->setLocale($lang);

        return redirect()->route('front.index');
    }
    
    public function kbzmobile(){
        return view('front.kbzmobile');
    }
    
    public function kbzmobile_pay(Request $request)
    {
        $api_url          = 'https://mirror.kbzbank.com/B001/directpay?&encdata=';
        
        $fldClientCode    = $request->fldClientCode;
        $fldMerchCode     = $request->fldMerchCode;
        $fldTxnCurr       = $request->fldTxnCurr;
        $fldTxnAmt        = $request->fldTxnAmt;
        $fldTxnScAmt      = $request->fldTxnScAmt;
        $fldMerchRefNbr   = $request->fldMerchRefNbr;
        $fldSucStatFlg    = $request->fldSucStatFlg;
        $fldFailStatFlg   = $request->fldFailStatFlg;
        $fldDatTimeTxn    = $request->fldDatTimeTxn;
        
        $string = 'fldClientCode='.$fldClientCode.'|fldMerchCode='.$fldMerchCode.'|fldTxnCurr='.$fldTxnCurr.'|fldTxnAmt='.$fldTxnAmt.'|fldTxnScAmt='.$fldTxnScAmt.'|fldMerchRefNbr='.$fldMerchRefNbr.'|fldSucStatFlg='.$fldSucStatFlg.'|fldFailStatFlg='.$fldFailStatFlg.'|fldDatTimeTxn='.$fldDatTimeTxn;
        $md5_enc = md5($string);
        
        $text = 'fldClientCode='.$fldClientCode.'|fldMerchCode='.$fldMerchCode.'|fldTxnCurr='.$fldTxnCurr.'|fldTxnAmt='.$fldTxnAmt.'|fldTxnScAmt='.$fldTxnScAmt.'|fldMerchRefNbr='.$fldMerchRefNbr.'|fldSucStatFlg='.$fldSucStatFlg.'|fldFailStatFlg='.$fldFailStatFlg.'|fldDatTimeTxn='.$fldDatTimeTxn.'|checkSum='.$md5_enc;
        
        $secretKey = 'S%Y#N@';
        
        $initVector = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc')));
        $dec_inv = base64_decode($initVector);

        $raw = base64_encode(openssl_encrypt($text, static::CIPHER, $secretKey, OPENSSL_RAW_DATA, $dec_inv));
        
        $result = base64_encode($initVector . $raw);
        //dd($result);
        
        $pay_url = $api_url.$raw;
        //dd($pay_url);
        
        echo '<script>window.location.href = "'.$pay_url.'";</script>';
      
    }
    
    public function invoice($id)
    {
        $payment = PaymentNew::where('id', $id)->first();
        $pending_pay = PendingPayment::where('number', $payment->invoice_no)->first();
        $settings = Setting::where('id', '1')->first();
        
        $url = $settings->ip_address."api/v1/auth/get-access-token"; 
        $headerArray =array("Content-type:application/json","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15*60);
        $output = curl_exec($ch);
        if ($output === false)
        {
            echo 'Curl error: ' . curl_error($ch);
        }
        else
        {
            echo PHP_EOL;  
            echo "\n";
            $decode_token = json_decode($output,true);
            $access_token = $decode_token['data']['access_token'];
        };
        curl_close($ch);
        
        $user_name    = $payment->payment_user_name;
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $Currurl             = "api/v1/user/view?".$data; 
        $mainUrl = $settings->ip_address.$Currurl;
        $headerArray =array("Content-type:application/json","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $mainUrl);
        curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        $result = curl_exec($ch);
        curl_close($ch);
    
        $CurrDecode = json_decode($result,true);
        
        if(!empty($CurrDecode['data']['Installation_cost'])){
            if($CurrDecode['data']['Installation_cost'] == 'FREE'){
                $installation_cost = 0;
            }elseif($CurrDecode['data']['Installation_cost'] == 'Free'){
                $installation_cost = 0;
            }else{
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }
        }else{
            if($CurrDecode['data']['Installation_cost'] == '0'){
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }else{
                $installation_cost = '0';
            }
        }
        
        $user = $CurrDecode['data'];
        
        $Currurl1          = "api/v1/package/users-packages?".$data;  
        $mainUrl = $settings->ip_address.$Currurl1;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $mainUrl);
        curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        $result1 = curl_exec($ch);
        curl_close($ch);
    
        $CurrDecode1 = json_decode($result1,true);
        
        $payment_rec = $CurrDecode1['data'][0];
        
        $amount4 = $pending_pay->amount - $pending_pay->installation_cost;
        $amount5 = number_format($amount4 * (1 + $pending_pay->commercial_tax/100), 0, '.', '');
        $amount6 = number_format($amount5 * (1 + $pending_pay->discount/100), 0, '.', '');
        $act_months = number_format($amount6/$payment_rec['checkout_amount'], 0, '.', '');
        
        return view('front.invoice', compact('payment', 'installation_cost', 'user', 'payment_rec', 'pending_pay', 'act_months'));
    }

}