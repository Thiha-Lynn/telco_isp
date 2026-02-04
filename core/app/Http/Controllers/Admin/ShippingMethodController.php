<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use App\Models\Shipping;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShippingMethodController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function shipping(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
        $data['methods'] = Shipping::where('language_id', $langId)->get();
        return view('admin.shipping.index', $data);
    }

    //Add Method
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.shipping.add', compact('langs', 'currentLang'));
    }

    // Store Method
    public function store(Request $request){

        $request->validate([
            'language_id' => 'required',
            'title' => 'required|unique:shippings|max:100',
            'cost' => 'required|min:0',
            'subtitle' => 'required',
        ]);

        $method = new Shipping();
        $method->language_id = $request->language_id;
        $method->title = $request->title;
        $method->subtitle = $request->subtitle;
        $method->status = $request->status;
        $method->cost = Helper::storePrice($request->cost);
        $method->save();

        $notification = array(
            'messege' => 'Shipping Method Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);

    }

    //Method Delete
    public function delete($locale, $id){
        $method = Shipping::find($id);
        $method->delete();

        return back();
    }

    //Method Delete
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $method = Shipping::find($id);
        return view('admin.shipping.edit', compact('method', 'langs', 'currentLang'));

    }

    // Method Update
    public function update(Request $request, $locale, $id){

        $request->validate([
            'language_id' => 'required',
            'title' => 'required|max:100|unique:shippings,id,'.$id,
            'subtitle' => 'required|max:100',
            'cost' => 'required|min:0',
        ]);

        $method = Shipping::findOrFail($id);
        $method->language_id = $request->language_id;
        $method->title = $request->title;
        $method->cost = Helper::storePrice($request->cost);
        $method->subtitle = $request->subtitle;
        $method->status = $request->status;
        $method->update();

        $notification = array(
            'messege' => 'Shipping Method Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.shipping.index').'?language='.$this->lang->code)->with('notification', $notification);

    }
}
