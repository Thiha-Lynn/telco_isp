<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Language;
use App\Models\PaymentProcess;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use App\Models\ExtraMonth;

class PaymentController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function index(Request $request)
    {
        $payments = PaymentProcess::orderBy('id', 'DESC')->get();
        
        return view('admin.payments.index', compact('payments'));
    }

    public function add(){
        return view('admin.payments.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bind_id' => 'required',
            'description' => 'required',
        ]);

        $payment = new PaymentProcess();
        $payment->bind_id = $request->bind_id;
        $payment->description = $request->description;
        $payment->status = $request->status;
        $payment->save();
       
        $notification = array(
            'messege' => 'Payment Processes Added successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.payment.process',app()->getLocale()))->with('notification', $notification);
    }

    public function delete($ln,$id)
    {
        $payment = PaymentProcess::find($id);
        $payment->delete();
        
        $notification = array(
            'messege' => 'Payment Process Deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function edit($ln,$id)
    {
        $payment = PaymentProcess::find($id);
        return view('admin.payments.edit', compact('payment'));
    }

    public function update(Request $request, $locale, $id){

        $id = $request->id;
        $request->validate([
            'bind_id' => 'required',
            'description' => 'required',
        ]);

        $payment = PaymentProcess::find($id);
        $payment->bind_id = $request->bind_id;
        $payment->description = $request->description;
        $payment->status = $request->status;
        $payment->save();

        $notification = array(
            'messege' => 'Payment Processes Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.payment.process',app()->getLocale()))->with('notification', $notification);
    }
}