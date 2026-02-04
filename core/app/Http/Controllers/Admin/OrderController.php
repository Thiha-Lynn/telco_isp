<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Billpaid;
use App\Models\Packageorder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Package;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Emailsetting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function package_allorder(){
        $package_allorders = Packageorder::orderBy('id', 'DESC')->get();
        return view('admin.package.order.allorder', compact('package_allorders'));
    }

    public function view_order($id){

        $data['order'] = Packageorder::with('user', 'package')->where('id', $id)->first();
        return response()->json($data);
    }

    public function order_edit_status($id){

        $data['order'] = Packageorder::where('id', $id)->first();
        return response()->json($data);
    }

    public function order_update_status(Request $request){
    
        $id = $request->status_orderid;
        $data['order'] = Packageorder::where('id', $id)->first();
        $data['order']->status = $request->status;
        $data['order']->save();

        $notification = array(
            'messege' => 'Order status updated successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
        
    }

    public function delete_order($id){
        $order = Packageorder::where('id', $id)->first();

        $user = User::where('id', $order->user_id)->first();
        $user->activepackage = null;
        $user->save();

        $billpay = Billpaid::where('user_id', $order->user_id)->get();
        foreach($billpay as $bill){
            $bill->delete();
        }
        $order->delete();

        $notification = array(
            'messege' => 'Order deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function package_pendingorder(){
        $package_pendingorders = Packageorder::where('status', 0)->orderBy('id', 'DESC')->paginate(10);
        return view('admin.package.order.pendingorder', compact('package_pendingorders'));
    }

    public function package_inprogress_order(){
        $package_inprogress_orders = Packageorder::where('status', 1)->orderBy('id', 'DESC')->paginate(10);
        return view('admin.package.order.inprogress', compact('package_inprogress_orders'));
    }

    public function package_compleated_order(){
        $package_compleated_orders = Packageorder::where('status', 2)->orderBy('id', 'DESC')->paginate(10);
        return view('admin.package.order.compleated', compact('package_compleated_orders'));
    }

    public function bill_pay(){
        
        $bills = Billpaid::with('user', 'package')->orderBy('id', 'DESC')->paginate(10);
        
        return view('admin.bill.index', compact('bills'));
    }

    public function billpay_view($id){
        $data['bill'] = Billpaid::with('user','package')->where('id', $id)->first();
        return response()->json($data);
    }

    public function billpay_delete($id){
        $bill = Billpaid::where('id', $id);
        $bill->delete();

        $notification = array(
            'messege' => 'Bill deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function bill_add(){
        $users = User::whereNotNull('activepackage')->get();
        $packages = Package::where('status', 1)->get();

        return view('admin.bill.add', compact('users', 'packages'));
    }


    public function bill_save(Request $request){
 
        $month_year = str_replace('/', '-', $request->month);

        $order  = new Billpaid();
        $package = Package::where('id', $request->package_id)->first();

        if($package->discount_price == null){
            $price = $package->price;
        }else{
            $price = $package->discount_price;
        }

       
        
        $order['package_cost'] =  $price;
        $order['currency_code'] = 'USD';
        $order['currency_sign'] = "$";
        $order['attendance_id'] = Str::random(4).time();
        $order['payment_status'] = "Completed";
        $order['user_id'] = $request->user_id;
        $order['package_id'] = $request->package_id;
        $order['yearmonth'] = $month_year;
        $order['fulldate'] = $month_year;
        $order['method'] = 'Cash Payment';
        $order->save();
        $order_id = $order->id;

        
        $package_id = $order->package_id;
        $package = Package::find($package_id);

        // sending datas to view to make invoice PDF
        $fileName = Str::random(4) . time() . '.pdf';
        $path = 'assets/front/invoices/bill/' . $fileName;
        $data['bill'] = $order;
        $data['package'] = $package;
        
        $data['user'] = User::find($request->user_id);
        
        PDF::loadView('pdf.bill', $data)->save($path);
        // echo("hello"); die;
        Billpaid::where('id', $order_id)->update([
            'invoice_number' => $fileName
        ]);

            // Send Mail to Buyer
        $mail = new PHPMailer(true);
        $user = User::find($request->user_id);

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


        $notification = array(
            'messege' => 'Custom bill Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

}
