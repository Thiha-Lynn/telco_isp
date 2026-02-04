<?php

namespace App\Http\Controllers\Payment\Package;

use App\Models\User;
use App\Models\Package;
use App\Models\Billpaid;
use Carbon\Carbon;
use App\Models\Emailsetting;
use App\Models\Packageorder;
use App\Helpers\Helper;
use App\Models\PaymentGatewey;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Stripe\Token;
use Stripe\Charge;
use Stripe\Exception\CardException;

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
                    'amount' => (int)($request->packageprice * 100),
                    'description' => "Package name: ".$request->packagename." & ".\Carbon\Carbon::now()->format('M Y').", this month bill has paid.",
                ]);

               
                if ($charge->status == 'succeeded') {

                    
                    $already_purchased = Packageorder::where('user_id', Auth::user()->id)->first();
                    if($already_purchased){
                        $order = $already_purchased;
                    }else{
                        $order  = new Packageorder();
                    }

                    $order['package_cost'] =  $request->packageprice;
                    $order['currency_code'] = 'USD';
                    $order['currency_sign'] = "$";
                    $order['attendance_id'] = Str::random(4).time();
                    $order['payment_status'] = "Completed";
                    $order['txn_id'] = $charge['balance_transaction'];
                    $order['user_id'] = Auth::user()->id;
                    $order['package_id'] = $request->packageid;
                    $order['method'] = 'Stripe';
                    $order['status'] = 0;
                    $order->save();
                    $order_id = $order->id;
                    $package_id = $order->package_id;
                    $package = Package::find($package_id);

                    $paybill  = new Billpaid();

                    $paybill['package_cost'] =  $request->packageprice;
                    $paybill['currency_code'] = 'USD';
                    $paybill['currency_sign'] = "$";
                    $paybill['attendance_id'] = Str::random(4).time();
                    $paybill['payment_status'] = "Completed";
                    $paybill['txn_id'] = $charge['balance_transaction'];
                    $paybill['user_id'] = Auth::user()->id;
                    $paybill['package_id'] = $request->packageid;
                    $paybill['yearmonth'] = \Carbon\Carbon::now()->format('m-Y');
                    $paybill['fulldate'] = \Carbon\Carbon::now()->format('M d, Y');
                    $paybill['method'] = 'Stripe';
                    $paybill['status'] = 0;
                    $paybill->save();
                    $bill_id = $paybill->id;

                    $user = User::where('id', Auth::user()->id)->first();
                    $user->activepackage = $request->packageid;
                    $user->save();



                    // sending datas to view to make invoice PDF
                    $fileName = Str::random(4) . time() . '.pdf';
                    $path = 'assets/front/invoices/package/' . $fileName;
                    $data['order'] = $order;
                    $data['bill'] = $paybill;
                    $data['package'] = $package;
                    $data['user'] = Auth::user();
                    PDF::loadView('pdf.package', $data)->save($path);

                    Packageorder::where('id', $order_id)->update([
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
                            $mail->addAttachment('assets/front/invoices/package/' . $fileName);
        
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = "Order placed for Package";
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
                            $mail->addAttachment('assets/front/invoices/package/' . $fileName);
        
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = "Order placed for Package";
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