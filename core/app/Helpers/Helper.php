<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\Currency;
use App\Models\StatusDescription;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class Helper
{

    public static function get_status()
    {
        $status = StatusDescription::where('is_active',1)->get();
        return json_encode($status);
    }
    public static function get_first_and_last_date($table)
    {
        if($table == 'payment_query')
        {
            $data['start'] = DB::table($table)->orderBy('id', 'asc')->first()->pack_expiery_date??'';
            $data['end']   = DB::table($table)->orderBy('id', 'desc')->first()->pack_expiery_date??'';
        }else
        {
            $data['start'] = DB::table($table)->orderBy('apply_id', 'desc')->first()->created_at??'';
            $data['end']   = DB::table($table)->orderBy('apply_id', 'asc')->first()->created_at??'';
        }
        //dd($data['start']);
        return json_encode($data);
    }
    public static function get_permission()
    {
        return $user = Auth::user();
    }
    public static function make_slug($string) {
        $slug = preg_replace('/\s+/u', '-', trim($string));
        $slug = str_replace("/","",$slug);
        $slug = str_replace("?","",$slug);
        return $slug;
    }

    public static function convertUtf8($value){
        return mb_detect_encoding($value, mb_detect_order(), true) === 'UTF-8' ? $value : mb_convert_encoding($value, 'UTF-8');
    }
    
    public static function showCurrencyPrice($price) {
        $curr = Currency::where('is_default','=',1)->first();
        $price = round($price * $curr->value,2);
        if (Session::has('currency')){
            $curr = Currency::find(Session::get('currency'));
        }
        else
        {
            $curr = Currency::where('is_default','=',1)->first();
        }


            return $curr->sign.$price;


    }


    public static function showAdminCurrencyPrice($price) {
        $curr = Currency::where('is_default','=',1)->first();
        $price = round($price * $curr->value,2);
        return $curr->sign.$price;
    }


      public static function storePrice($price) {
        $curr = Currency::where('is_default','=',1)->first();
        $price = ($price / $curr->value);
        return $price;
    }


    public static function showCurrency()
    {
        $curr = Currency::where('is_default','=',1)->first();
        return $curr->sign;
    }

    public static function showCurrencyCode()
    {
        $curr = Currency::where('is_default','=',1)->first();
        return $curr->name;
    }

    public static function showCurrencyValue()
    {
        $curr = Currency::where('is_default','=',1)->first();
        return $curr->value;
    }


    public static function showPrice($price) {
        $curr = Currency::where('is_default','=',1)->first();
        $price = $price * $curr->value;
        return round($price,2);

    }

    public static function Total()
    {
        if(Session::has('cart')){
            $cart_data = Session::get('cart');
            $cartTotal = 0;
            if($cart_data){
                foreach($cart_data as $product){
                    $cartTotal += (double)$product['price'] * (int)$product['qty'];
                }
            }
            return $cartTotal;

        }else{
            return 0;
        }
        
    }
    
    public static function  SendFirebasePush($userdevices,$message) {
        // print_r($userdevices);
        $fcmUrl          = 'https://fcm.googleapis.com/fcm/send';
        $headers         = [
            'Authorization: key=AAAA7Ezz3aM:APA91bEGn9WwTBBupVB0Zc4g9Fv26i1UYKaHn49zu0ETqTQQsboQN82Eu2AzbKyvlvflCuvo4T3MQgZqKidQtjGxdY1HjEokKURXYs9M86grlJfoCAJrn-I_NFMo3wnws0Gm7AhU1QI4',
            'Content-Type: application/json'
        ];  
        $fcmNotification = [
            'to'        => $userdevices,
            'collapse_key'=> "type_a",
            'notification'     => [
                'title' => 'MBT promotion message',
                'body'  => $message,
                'image' => "https://telco.mbt.com.mm/assets/front/img/header_logo_162426094536756743.png",
                'sound' => TRUE,
                'priority' => "high",
                'android_channel_id'=>"xpay-notification-channel"
            ],
            'data'             => [
                 "type"    => "Typoe",
            ]
        ];
        $ch              = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);
        // dd($result);
        return $result;
    }
    
     public static function  SendFirebasembtnotification($userdevices,$message) {
        $fcmUrl          = 'https://fcm.googleapis.com/fcm/send';
        $headers         = [
            'Authorization: key=AAAA7Ezz3aM:APA91bEGn9WwTBBupVB0Zc4g9Fv26i1UYKaHn49zu0ETqTQQsboQN82Eu2AzbKyvlvflCuvo4T3MQgZqKidQtjGxdY1HjEokKURXYs9M86grlJfoCAJrn-I_NFMo3wnws0Gm7AhU1QI4',
            'Content-Type: application/json'
        ];  
        $fcmNotification = [
            'to'        => $userdevices,
            'collapse_key'=> "type_a",
            'notification'     => [
                'title' => 'MBT Notification',
                'body'  => $message,
                'image' => "https://telco.mbt.com.mm/assets/front/img/header_logo_162426094536756743.png",
                'sound' => TRUE,
                'priority' => "high",
                'android_channel_id'=>"xpay-notification-channel"
            ],
            'data'             => [
                 "type"    => "Typoe",
            ]
        ];
        $ch              = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);
        //dd($result);
        return $result;
    }
    
    

}


