<?php

namespace App\Http\Controllers\Payment\Paybill;

use App\Models\Billpaid;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Packageorder;
use App\Models\Package;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Emailsetting;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PaymentGatewey;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PayPal\{
    Api\Item,
    Api\Payer,
    Api\Amount,
    Api\Payment,
    Api\ItemList,
    Rest\ApiContext,
    Api\Transaction,
    Api\RedirectUrls,
    Api\PaymentExecution,
    Auth\OAuthTokenCredential
};

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class PaypalController extends Controller
{

    private $_api_context;
    public function __construct()
    {
        $data = PaymentGatewey::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();
        $paypal_conf = Config::get('paypal');
        $paypal_conf['client_id'] = $paydata['client_id'];
        $paypal_conf['secret'] = $paydata['client_secret'];
        $paypal_conf['settings']['mode'] = $paydata['sandbox_check'] == 1 ? 'sandbox' : 'live';

        $this->_api_context = new ApiContext(new OAuthTokenCredential(
            $paypal_conf['client_id'],
            $paypal_conf['secret']
        ));

        $this->_api_context->setConfig($paypal_conf['settings']);
    }




    public function store(Request $request)
    {
    
      
    
        $input = $request->all();
        Session::put('package_data', $input);

        $title = \Carbon\Carbon::now()->format('M Y').", This month bill paid. Package name: ".$request->packagename;


        $order['order_number'] = Str::random(4) . time();
        $order['order_amount'] = $request->packageprice;


        $cancel_url = action('Payment\Paybill\PaypalController@paycancle');
        $notify_url = route('paybill.payment.notify');
        $total  = $request->packageprice;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName($title)
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($total);
        $item_list = new ItemList();
        $item_list->setItems(array($item_1));
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($total);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($title . ' Via Paypal');
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl($notify_url)
            ->setCancelUrl($cancel_url);
        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            $notification = array(
                'messege' => $ex->getMessage(),
                'alert' => 'error'
            );
            return redirect()->back()->with('notification', $notification);
        }
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }
        Session::put('order_data', $order);
        Session::put('paypal_payment_id', $payment->getId());
        if (isset($redirect_url)) {
            return Redirect::away($redirect_url);
        }
        $notification = array(
            'messege' => 'Unknown error occurred',
            'alert' => 'error'
        );
        return redirect()->back()->with('notification', $notification);

        if (isset($redirect_url)) {
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
        return view('front.index');
    }


    public function notify(Request $request)
    {
        
        $order_data = Session::get('order_data');
        $cancel_url = action('Payment\Paybill\PaypalController@paycancle');
        $input = $request->all();
        $payment_id = Session::get('paypal_payment_id');
        if (empty($input['PayerID']) || empty($input['token'])) {
            return redirect($cancel_url);
        }
        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($input['PayerID']);
        $result = $payment->execute($execution, $this->_api_context);
        if ($result->getState() == 'approved') {
            $resp = json_decode($payment, true);
            $package_data = Session::get('package_data');

            
            $order  = new Billpaid();

            $order['package_cost'] =  $package_data['packageprice'];
            $order['currency_code'] = 'USD';
            $order['currency_sign'] = "$";
            $order['attendance_id'] = $order_data['order_number'];
            $order['payment_status'] = "Completed";
            $order['txn_id'] = $resp['transactions'][0]['related_resources'][0]['sale']['id'];
            $order['user_id'] = Auth::user()->id;
            $order['package_id'] = $package_data['packageid'];
            $order['yearmonth'] = \Carbon\Carbon::now()->format('m-Y');
            $order['fulldate'] = \Carbon\Carbon::now()->format('M d, Y');
            $order['method'] = 'Paypal';
            $order['status'] = 0;
            $order->save();
            $order_id = $order->id;

            $package_id = $order->package_id;
            $package = Package::find($package_id);

            // sending datas to view to make invoice PDF
            $fileName = Str::random(4) . time() . '.pdf';
            $path = 'assets/front/invoices/bill/' . $fileName;
            $data['bill'] = $order;
            $data['package'] = $package;
            $data['user'] = Auth::user();
            PDF::loadView('pdf.bill', $data)->save($path);

            Billpaid::where('id', $order_id)->update([
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
                    $mail->addAttachment('assets/front/invoices/bill/' . $fileName);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "Bill Paid";
                    $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your bill was paid successfully. We have attached an invoice in this mail.<br/>Thank you.';

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
                    $mail->addAttachment('assets/front/invoices/bill/' . $fileName);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "Bill Paid";
                    $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your bill was paid successfully. We have attached an invoice in this mail.<br/>Thank you.';

                    $mail->send();
                } catch (Exception $e) {
                    // die($e->getMessage());
                }
            }

            return view('front.success.package');
        }
        return redirect($cancel_url);
    }
}
