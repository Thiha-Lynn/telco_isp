<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Emailsetting;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class ProductOrderController extends Controller
{
    public function all(Request $request)
    {

        $data['orders'] = ProductOrder::orderBy('id', 'DESC')->get();

        return view('admin.product.order.index', $data);
    }

    public function pending(Request $request)
    {
        $data['orders'] = ProductOrder::where('order_status', 'pending')->orderBy('id', 'DESC')->get();
        return view('admin.product.order.index', $data);
    }

    public function processing(Request $request)
    {
        $search = $request->search;
        $data['orders'] = ProductOrder::where('order_status', 'processing')->orderBy('id', 'DESC')->get();
        return view('admin.product.order.index', $data);
    }

    public function completed(Request $request)
    {
        $search = $request->search;
        $data['orders'] = ProductOrder::where('order_status', 'completed')->orderBy('id', 'DESC')->get();
        return view('admin.product.order.index', $data);
    }

    public function rejected(Request $request)
    {
        $search = $request->search;
        $data['orders'] = ProductOrder::where('order_status', 'rejected')->orderBy('id', 'DESC')->get();
        return view('admin.product.order.index', $data);
    }

    public function status(Request $request)
    {

        $po = ProductOrder::find($request->order_id);
        $po->order_status = $request->order_status;
        $po->save();

        $user = User::findOrFail($po->user_id);
        $em = Emailsetting::first();
        $sub = 'Order Status Update';
         // Send Mail to Buyer
         $mail = new PHPMailer(true);

      
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

                 // Content
                 $mail->isHTML(true);
                 $mail->Subject = $sub;
                 $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your order status is '.$request->order_status.'.<br/>Thank you.';
                 $mail->send();
             } catch (Exception $e) {
                 // die($e->getMessage());
             }
         } else {
             try {

                 //Recipients
                 $mail->setFrom($em->from_mail, $em->from_name);
                 $mail->addAddress($user->email, $user->name);


                 // Content
                 $mail->isHTML(true);
                 $mail->Subject = $sub ;
                 $mail->Body    = 'Hello <strong>' . $user->name . '</strong>,<br/>Your order status is '.$request->order_status.'.<br/>Thank you.';

                 $mail->send();
             } catch (Exception $e) {
                 // die($e->getMessage());
             }
         }

         $notification = array(
            'messege' => 'Order status changed successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function details($id)
    {
        $order = ProductOrder::findOrFail($id);
        return view('admin.product.order.details',compact('order'));
    }



    public function orderDelete(Request $request)
    {
        $order = ProductOrder::findOrFail($request->order_id);
        @unlink('assets/front/invoices/product/'.$order->invoice_number);
        foreach($order->orderitems as $item){
            $item->delete();
        }
        $order->delete();

        $notification = array(
            'messege' => 'product order deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

}
