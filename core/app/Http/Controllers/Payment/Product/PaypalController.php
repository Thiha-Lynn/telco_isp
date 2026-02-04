<?php

namespace App\Http\Controllers\Payment\Product;

use App\Models\Product;
use App\Models\Language;
use App\Models\Shipping;
use App\Models\OrderItem;
use PayPal\Api\Item;
use App\Models\Emailsetting;
use App\Models\ProductOrder;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use App\Models\PaymentGatewey;
use PayPal\Api\Payment;
use PayPal\Api\ItemList;
use Illuminate\Support\Str;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use Illuminate\Http\Request;
use PayPal\Api\RedirectUrls;
use Illuminate\Support\Carbon;
use PayPal\Api\PaymentExecution;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use PayPal\Auth\OAuthTokenCredential;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class PaypalController extends Controller
{
    private $_api_context;
    public function __construct()
    {
        $data = PaymentGatewey::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();
        $paypal_conf = \Config::get('paypal');
        $paypal_conf['client_id'] = $paydata['client_id'];
        $paypal_conf['secret'] = $paydata['client_secret'];
        $paypal_conf['settings']['mode'] = $paydata['sandbox_check'] == 1 ? 'sandbox' : 'live';
        $this->_api_context = new ApiContext(
            new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret']
            )
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
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
                    'messege' =>  $product->title . ' stock not available',
                    'alert' => 'error'
                );
                return redirect()->back()->with('notification', $notification);
            }
            $total  += $product->current_price * $item['qty'];
        }

     

        if ($request->shipping_charge != 0) {
            $shipping = Shipping::where('cost', $request->shipping_charge)->first();
            $shipping_charge = $shipping->cost;
        } else {
            $shipping_charge = 0;
        }

        $total = round($total + $shipping_charge, 2);

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
            ]);
        }

        $input = $request->all();

   
        $title = 'Product Checkout';

        $order['order_number'] = Str::random(4) . time();

        $order['order_amount'] = round($total, 2);
        $cancel_url = action('Payment\Product\PaypalController@paycancle');
        $notify_url = route('product.payment.notify');
        $success_url = action('Payment\Product\PaypalController@payreturn');


        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName($title)
            /** item name **/
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice(round($total, 2));
        /** unit price **/
        $item_list = new ItemList();
        $item_list->setItems(array($item_1));
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal(round($total, 2));
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($title . ' Via Paypal');
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl($notify_url)
            /** Specify return URL **/
            ->setCancelUrl($cancel_url);
        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            return redirect()->back()->with('unsuccess', $ex->getMessage());
        }
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }
        /** add payment ID to session **/
        Session::put('paypal_data', $input);
        Session::put('order_data', $order);
        Session::put('paypal_payment_id', $payment->getId());
        if (isset($redirect_url)) {
            /** redirect to paypal **/
            return Redirect::away($redirect_url);
        }
        $notification = array(
            'messege' => 'Unknown error occurred',
            'alert' => 'error'
        );
        return redirect()->back()->with('notification', $notification);

        if (isset($redirect_url)) {
            /** redirect to paypal **/
            return Redirect::away($redirect_url);
        }
        $notification = array(
            'messege' => 'Unknown error occurred',
            'alert' => 'error'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function paycancle()
    {
        $notification = array(
            'messege' => 'Payment Cancelled.',
            'alert' => 'error'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function payreturn()
    {
        return view('front.success.product');
    }


    public function notify(Request $request)
    {


        $success_url = action('Payment\Product\PaypalController@payreturn');
        $cancel_url = action('Payment\Product\PaypalController@paycancle');


        $paypal_data = Session::get('paypal_data');


        $order_data = Session::get('order_data');
        $payment_id = Session::get('paypal_payment_id');

        $input = $request->all();
        /** Get the payment ID before session clear **/
        /** clear the session payment ID **/
        if (empty($input['PayerID']) || empty($input['token'])) {
            return redirect($cancel_url);
        }
        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($input['PayerID']);
        /**Execute the payment **/
        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() == 'approved') {



            $resp = json_decode($payment, true);

            $cart = Session::get('cart');

            $total = 0;
            foreach ($cart as $id => $item) {
                $product = Product::findOrFail($id);
                if ($product->stock < $item['qty']) {
                    $notification = array(
                        'messege' =>  $product->title . ' stock not available',
                        'alert' => 'error'
                    );
                    return redirect()->back()->with('notification', $notification);
                }
                $total  += $product->current_price * $item['qty'];
            }

            if ($paypal_data['shipping_charge'] != 0) {
                $shipping = Shipping::where('cost', $paypal_data['shipping_charge'])->first();
                $shipping_charge = $shipping->cost;
            } else {
                $shipping_charge = 0;
            }

            $total = round($total + $shipping_charge, 2);


         

            $order = new ProductOrder;


            $order->billing_name = $paypal_data['billing_name'];
            $order->billing_email = $paypal_data['billing_email'];
            $order->billing_address = $paypal_data['billing_address'];
            $order->billing_city = $paypal_data['billing_city'];
            $order->billing_country = $paypal_data['billing_country'];
            $order->billing_number = $paypal_data['billing_number'];
            $order->billing_zip = $paypal_data['billing_zip'];

            $order->shipping_name = $paypal_data['shipping_name'];
            $order->shipping_email = $paypal_data['shipping_email'];
            $order->shipping_address = $paypal_data['shipping_address'];
            $order->shipping_city = $paypal_data['shipping_city'];
            $order->shipping_country = $paypal_data['shipping_country'];
            $order->shipping_number = $paypal_data['shipping_number'];
            $order->shipping_zip = $paypal_data['shipping_zip'];


            $order->total = round($order_data['order_amount'], 2);
            $order->shipping_charge = round($shipping_charge, 2);
            $order->method = 'Paypal';
            $order->currency_code = 'USD';
            $order['order_number'] = $order_data['order_number'];
            $order['payment_status'] = "Completed";
            $order['txnid'] = $resp['transactions'][0]['related_resources'][0]['sale']['id'];
            $order['charge_id'] = $request->paymentId;
            $order['user_id'] = Auth::user()->id;
            $order['method'] = 'Paypal';

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

            $fileName = Str::random(4) . time() . '.pdf';
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


            Session::forget('paypal_data');
            Session::forget('order_data');
            Session::forget('paypal_payment_id');
            Session::forget('cart');

            return redirect($success_url);
        }
        return redirect($cancel_url);
    }
}
