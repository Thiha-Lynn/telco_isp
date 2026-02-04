<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PaymentGatewayController extends Controller
{
   public function index()
    {
   
        $data = PaymentGatewey::get();
        return view('admin.payment_gateway.index',compact('data'));
    }


    public function edit($locale, $id)
    {
        $data = PaymentGatewey::findOrFail($id);
        return view('admin.payment_gateway.edit',compact('data'));

    }


    public function Update(Request $request , $id)
    {
        $data = PaymentGatewey::findOrFail($id);
        $prev = '';
        if($data->type == "automatic"){

           $request->validate([
            'name' => 'required|unique:payment_gateweys,name,'.$id,
            'status' => 'required'
           ]);

            $input = $request->all();

            $info_data = $input['pkey'];

            if($data->keyword == 'mollie'){
                $paydata = $data->convertAutoData();
                $prev = $paydata['key'];
            }

            if (array_key_exists("sandbox_check",$info_data)){
                $info_data['sandbox_check'] = 1;
            }else{
                if (strpos($data->information, 'sandbox_check') !== false) {
                    $info_data['sandbox_check'] = 0;
                    $text =  $info_data['text'];
                    unset($info_data['text']);
                    $info_data['text'] = $text;
                }
            }
            $input['information'] = json_encode($info_data);
            $data->update($input);

        }
        else{

            $request->validate([
                'title' => 'required|unique:payment_gateweys,title,'.$id
            ]);
            $input = $request->all();
            $data->update($input);
        }

        $notification = array(
            'messege' => 'Payment Gateway Updated Successfully.',
            'alert' => 'success'
          );
          return redirect()->route('admin.payment.index')->with('notification', $notification);
      

    }
}