<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class PaymentGatewey extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'details', 'status', 'subtitle', 'name', 'type', 'information', 'currency_id'];
    public $timestamps = false;
    protected $primaryKey = 'id';

    public function currency()
    {
        return $this->belongsTo(Currency::class)->withDefault(function ($data) {
            foreach ($data->getFillable() as $dt) {
                $data[$dt] = '0';
            }
        });
    }

    public function checkCurrency(): bool
    {
        if (Session::has('currency')) {
            $curr = Currency::find(Session::get('currency'));
        } else {
            $curr = Currency::where('is_default', '=', 1)->first();
        }

        if ($this->currency_id == 0 || $this->currency->name == '0' || $this->currency_id == $curr->id) {
            return false;
        }
        return true;
    }

    public function convertAutoData(): ?array
    {
        return json_decode($this->information, true);
    }

    public function getAutoDataText(): mixed
    {
        $text = $this->convertAutoData();
        return end($text);
    }

    public function showKeyword(): string
    {
        return $this->keyword ?? 'other';
    }

    public function showCheckoutLink(): string
    {
        $link = '';
        $data = $this->keyword ?? 'other';
        
        $routes = [
            'paypal' => 'front.paypal.submit',
            'stripe' => 'front.stripe.submit',
            'instamojo' => 'front.instamojo.submit',
            'paystack' => 'front.paystack.submit',
            'paytm' => 'front.paytm.submit',
            'mollie' => 'front.molly.submit',
            'razorpay' => 'front.razorpay.submit',
            'authorize.net' => 'front.authorize.submit',
            'mercadopago' => 'front.mercadopago.submit',
            'flutterwave' => 'front.flutter.submit',
            '2checkout' => 'front.twocheckout.submit',
            'sslcommerz' => 'front.ssl.submit',
            'voguepay' => 'front.voguepay.submit',
            'cod' => 'front.cod.submit',
        ];

        if (isset($routes[$data])) {
            $link = route($routes[$data]);
        } else {
            $link = route('front.manual.submit');
        }
        return $link;
    }

    public function showSubscriptionLink(): string
    {
        $link = '';
        $data = $this->keyword;
        
        $routes = [
            'paypal' => 'user.paypal.submit',
            'stripe' => 'user.stripe.submit',
            'instamojo' => 'user.instamojo.submit',
            'paystack' => 'user.paystack.submit',
            'paytm' => 'user.paytm.submit',
            'mollie' => 'user.molly.submit',
            'razorpay' => 'user.razorpay.submit',
            'authorize.net' => 'user.authorize.submit',
            'mercadopago' => 'user.mercadopago.submit',
            'flutterwave' => 'user.flutter.submit',
            '2checkout' => 'user.twocheckout.submit',
            'sslcommerz' => 'user.ssl.submit',
            'voguepay' => 'user.voguepay.submit',
        ];

        if (isset($routes[$data])) {
            $link = route($routes[$data]);
        } elseif ($data === null) {
            $link = route('user.manual.submit');
        }
        return $link;
    }

    public function showDepositLink(): string
    {
        $link = '';
        $data = $this->keyword;
        
        $routes = [
            'paypal' => 'deposit.paypal.submit',
            'stripe' => 'deposit.stripe.submit',
            'instamojo' => 'deposit.instamojo.submit',
            'paystack' => 'deposit.paystack.submit',
            'paytm' => 'deposit.paytm.submit',
            'mollie' => 'deposit.molly.submit',
            'razorpay' => 'deposit.razorpay.submit',
            'authorize.net' => 'deposit.authorize.submit',
            'mercadopago' => 'deposit.mercadopago.submit',
            'flutterwave' => 'deposit.flutter.submit',
            '2checkout' => 'deposit.twocheckout.submit',
            'sslcommerz' => 'deposit.ssl.submit',
            'voguepay' => 'deposit.voguepay.submit',
        ];

        if (isset($routes[$data])) {
            $link = route($routes[$data]);
        } elseif ($data === null) {
            $link = route('deposit.manual.submit');
        }
        return $link;
    }

    public function showForm(): string
    {
        $data = $this->keyword ?? 'other';
        $noFormRequired = ['cod', 'voguepay', 'sslcommerz', 'flutterwave', 'razorpay', 'mollie', 'paytm', 'paystack', 'paypal', 'instamojo'];
        
        return in_array($data, $noFormRequired) ? 'no' : 'yes';
    }
}
