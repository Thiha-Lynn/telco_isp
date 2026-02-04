<?php

namespace App\Http\Controllers\Payment\Paybill;

use App\Models\Billpaid;
use App\Helpers\Helper;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Token;
use Stripe\Charge;
use Stripe\Exception\CardException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Emailsetting;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PaymentGatewey;
use App\Models\Setting;
use App\Models\Packageorder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class StripeController extends Controller
{
    protected string $stripeSecret;

    public function __construct()
    {
        $data = PaymentGatewey::whereKeyword('stripe')->first();
        if ($data) {
            $paydata = $data->convertAutoData();
            $this->stripeSecret = $paydata['secret'] ?? '';
            Config::set('services.stripe.key', $paydata['key'] ?? '');
            Config::set('services.stripe.secret', $this->stripeSecret);
            Stripe::setApiKey($this->stripeSecret);
        }
    }


    public function store(Request $request){

          
            $request->validate([
                'card_number' => 'required',
                'fullname' => 'required',
                'cvc' => 'required',
                'month' => 'required',
                'year' => 'required',
            ]);

            try{
              
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
                    'amount' => (int)($request->packageprice * 100), // Stripe expects cents
                    'description' => \Carbon\Carbon::now()->format('M Y').", This month bill paid. Package name: ".$request->packagename,
                ]);

               
                if ($charge->status == 'succeeded') {

                    
                    $order  = new Billpaid();

                    $order['package_cost'] =  $request->packageprice;
                    $order['currency_code'] = 'USD';
                    $order['currency_sign'] = "$";
                    $order['attendance_id'] = Str::random(4).time();
                    $order['payment_status'] = "Completed";
                    $order['txn_id'] = $charge->balance_transaction;
                    $order['user_id'] = Auth::user()->id;
                    $order['package_id'] = $request->packageid;
                    $order['yearmonth'] = \Carbon\Carbon::now()->format('m-Y');
                    $order['fulldate'] = \Carbon\Carbon::now()->format('M d, Y');
                    $order['method'] = 'Stripe';
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

            }catch (CardException $e){
                $notification = array(
                    'messege' => $e->getMessage(),
                    'alert' => 'warning'
                );
                return redirect()->back()->with('notification', $notification);
            }catch (\Stripe\Exception\ApiErrorException $e){
                $notification = array(
                    'messege' => $e->getMessage(),
                    'alert' => 'warning'
                );
                return redirect()->back()->with('notification', $notification);
            }catch (Exception $e){
                $notification = array(
                    'messege' => $e->getMessage(),
                    'alert' => 'warning'
                );
                return redirect()->back()->with('notification', $notification);
            }
        $notification = array(
            'messege' => 'Please Enter Valid Credit Card Informations.',
            'alert' => 'warning'
        );
        return redirect()->back()->with('notification', $notification);
    }


    public function payreturn(){
        return view('front.success.package');
     }


}