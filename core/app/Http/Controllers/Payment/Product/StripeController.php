<?php

namespace App\Http\Controllers\Payment\Product;


use App\Models\Product;
use App\Models\Shipping;
use App\Models\OrderItem;
use Carbon\Carbon;
use App\Models\Emailsetting;
use App\Models\ProductOrder;
use App\Helpers\Helper;
use App\Models\PaymentGatewey;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\Token;
use Stripe\Charge;
use Stripe\Exception\CardException;

class StripeController extends Controller
{
    protected string $stripeSecret;

    public function __construct()
    {
        //Set Stripe Keys
        $data = PaymentGatewey::whereKeyword('stripe')->first();
        if ($data) {
            $paydata = $data->convertAutoData();
            $this->stripeSecret = $paydata['secret'] ?? '';
            Config::set('services.stripe.key', $paydata['key'] ?? '');
            Config::set('services.stripe.secret', $this->stripeSecret);
            Stripe::setApiKey($this->stripeSecret);
        }
    }


    public function store(Request $request)
    {


        if (!Session::has('cart')) {
            return view('errors.404');
        }

        $cart = Session::get('cart');

        $total = 0;
        foreach ($cart as $id => $item) {
            $product = Product::findOrFail($id);
            if ($product->stock < $item['qty']) {
                $notification = array(
                    'messege' => $product->title . ' stock not available',
                    'alert' => 'error'
                );
                return redirect()->back()->with('notification', $notification);
            }
            $total  += $product->current_price * $item['qty'];
        }

        if ($request->shipping_charge != 0) {
            $shipping = Shipping::where('cost', $request->shipping_charge)->first();
            $shippig_charge = $shipping->cost;
        } else {
            $shippig_charge = 0;
        }


        $total = round($total + $shippig_charge, 2);
        
        $title = 'Product Checkout';

        $success_url = action('Payment\Product\PaypalController@payreturn');

        if(isset($request->is_ship)){
            $request->validate([
                'shipping_name' => 'required',
                'shipping_email' => 'required',
                'shipping_number' => 'required',
                'shipping_address' => 'required',
                'shipping_country' => 'required',
                'shipping_city' => 'required',
                'shipping_zip' => 'required',
                'billing_name' => 'required',
                'billing_email' => 'required',
                'billing_number' => 'required',
                'billing_address' => 'required',
                'billing_country' => 'required',
                'billing_city' => 'required',
                'billing_zip' => 'required',
                'fullname' => 'required',
                'card_number' => 'required',
                'cvc' => 'required',
                'month' => 'required',
                'year' => 'required',
            ]);
        }else{
            $request->validate([
                'billing_name' => 'required',
                'billing_email' => 'required',
                'billing_number' => 'required',
                'billing_address' => 'required',
                'billing_country' => 'required',
                'billing_city' => 'required',
                'billing_zip' => 'required',
                'fullname' => 'required',
                'card_number' => 'required',
                'cvc' => 'required',
                'month' => 'required',
                'year' => 'required',
            ]);
        }


        try {

            $token = Token::create([
                'card' => [
                    'name' => $request->fullname,
                    'number' => $request->card_number,
                    'exp_month' => $request->month,
                    'exp_year' => $request->year,
                    'cvc' => $request->cvc,
                ],
            ]);

            if (!isset($token->id)) {
                $notification = array(
                    'messege' => 'Token Problem With Your Token.',
                    'alert' => 'error'
                );
                return redirect()->back()->with('notification', $notification);
            }

            $charge = Charge::create([
                'source' => $token->id,
                'currency' => strtolower(Helper::showCurrencyCode()),
                'amount' => (int)($total * 100),
                'description' => $title,
            ]);



            if ($charge->status == 'succeeded') {

                $order = new ProductOrder;
                
                $order->user_id = Auth::user()->id;

                $order->billing_name = $request->billing_name;
                $order->billing_email = $request->billing_email;
                $order->billing_address = $request->billing_address;
                $order->billing_city = $request->billing_city;
                $order->billing_country = $request->billing_country;
                $order->billing_number = $request->billing_number;
                $order->billing_zip = $request->billing_zip;

                $order->shipping_name = $request->shipping_name;
                $order->shipping_email = $request->shipping_email;
                $order->shipping_address = $request->shipping_address;
                $order->shipping_city = $request->shipping_city;
                $order->shipping_country = $request->shipping_country;
                $order->shipping_number = $request->shipping_number;
                $order->shipping_zip = $request->shipping_zip;

                $order->total = $total;
                $order->shipping_charge = round($shippig_charge, 2);
                $order->method = 'Stripe';
                $order->currency_code = 'USD';
                $order->order_number = Str::random(4). time();
                $order->payment_status = 'Completed';
                $order['txnid'] = $charge->balance_transaction;
                $order['charge_id'] = $charge->id;

                $order->save();
                $order_id = $order->id;

                $carts = Session::get('cart');
                $products = [];
                $qty = [];
                foreach ($carts as $id => $item) {
                    $qty[] = $item['qty'];
                    $products[] = Product::findOrFail($id);
                }


                foreach ($products as $key => $product) {
          
                    OrderItem::insert([
                        'product_order_id' => $order_id,
                        'product_id' => $product->id,
                        'user_id' => Auth::user()->id,
                        'title' => $product->title,
                        'qty' => $qty[$key],
                        'price' => $product->current_price,
                        'previous_price' => $product->previous_price,
                        'image' => $product->image,
                        'short_description' => $product->short_description,
                        'description' => $product->description,
                        'created_at' => Carbon::now()
                    ]);
                }


                foreach ($cart as $id => $item) {
                    $product = Product::findOrFail($id);
                    $stock = $product->stock - $item['qty'];
                    Product::where('id', $id)->update([
                        'stock' => $stock
                    ]);
                }


                $fileName = Str::random(4).time() . '.pdf';
                $path = 'assets/front/invoices/product/' . $fileName;
                $data['order']  = $order;
                $pdf = PDF::loadView('pdf.product', $data)->save($path);

                ProductOrder::where('id', $order_id)->update([
                    'invoice_number' => $fileName
                ]);


                // Send Mail to Buyer
                $mail = new PHPMailer(true);
                $user = Auth::user();

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
                        $mail->setFrom($em->from_email, $em->from_name);
                        $mail->addAddress($user->email, $user->name);
    
                        // Attachments
                        $mail->addAttachment('assets/front/invoices/product/' . $fileName);
    
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = "Order placed for Product";
                        $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your order has been placed successfully. We have attached an invoice in this mail.<br/>Thank you.';
    
                        $mail->send();
                    } catch (Exception $e) {
                        // die($e->getMessage());
                    }
                } else {
                    try {
                        //Recipients
                        $mail->setFrom($em->from_mail, $em->from_name);
                        $mail->addAddress($user->email, $user->name);
    
                        // Attachments
                        $mail->addAttachment('assets/front/invoices/product/' . $fileName);
    
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = "Order placed for Product";
                        $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your order has been placed successfully. We have attached an invoice in this mail.<br/>Thank you.';
    
                        $mail->send();
                    } catch (Exception $e) {
                        // die($e->getMessage());
                    }
                }

                Session::forget('cart');

                return redirect($success_url);
            }
        } catch (CardException $e) {
            $notification = array(
                'messege' => $e->getMessage(),
                'alert' => 'warning'
            );
            return redirect()->back()->with('notification', $notification);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $notification = array(
                'messege' => $e->getMessage(),
                'alert' => 'warning'
            );
            return redirect()->back()->with('notification', $notification);
        } catch (Exception $e) {
            $notification = array(
                'messege' => 'Please Enter Valid Credit Card Informations.',
                'alert' => 'warning'
            );
            return redirect()->back()->with('notification', $notification);
        }

        // return back()->with('unsuccess', 'Please Enter Valid Credit Card Informations.');
    }
}
