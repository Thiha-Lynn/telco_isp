<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Language;
use App\Models\Offerprovide;
use App\Models\Sectiontitle;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class OfferController extends Controller
{
     public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function offer(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $offers = Offerprovide::where('language_id', $langId)->orderBy('id', 'DESC')->get();
        
        $saectiontitle = Sectiontitle::where('language_id', $langId)->first();
        if (!$saectiontitle) {
            $saectiontitle = Sectiontitle::first() ?? new Sectiontitle(['language_id' => $langId]);
        }
        
        return view('admin.offer.index', compact('offers', 'saectiontitle'));
    }

    // Add slider Category
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.offer.add', compact('langs', 'currentLang'));
    }

    // Store slider Category
    public function store(Request $request){

        $request->validate([
            'offer' => 'required|max:150',
        ]);

        $offer = new Offerprovide();
        $offer->language_id =  $request->language_id;
        $offer->status =  $request->status;
        $offer->offer =  Purifier::clean($request->offer);
        $offer->save();

        $notification = array(
            'messege' => 'Offer Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // slider Category Delete
    public function delete($locale, $id){

        $offer = Offerprovide::find($id);
        $offer->delete();

        return back();
    }

    // slider Category Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $offer = Offerprovide::find($id);
        return view('admin.offer.edit', compact('offer', 'langs', 'currentLang'));
    }

    // Update slider Category
    public function update(Request $request, $locale, $id){

        $id = $request->id;
         $request->validate([
            'offer' => 'required|max:150',
        ]);

        $offer = Offerprovide::find($id);

        $offer->language_id =  $request->language_id;
        $offer->status =  $request->status;
        $offer->offer =  Purifier::clean($request->offer);
        $offer->save();

        $notification = array(
            'messege' => 'Offer Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.offer').'?language='.$this->lang->code)->with('notification', $notification);
    }

    public function offercontent(Request $request, $id){
        
        $request->validate([
            'offer_title' => 'required',
            'offer_subtitle' => 'required',
            'offer_image' => 'mimes:jpeg,jpg,png',
        ]);
        // dd($request->all());
        $offer_title = Sectiontitle::where('language_id', $id)->first();

        if($request->hasFile('offer_image')){
            @unlink('assets/front/img/'. $offer_title->offer_image);
            $file = $request->file('offer_image');
            $extension = $file->getClientOriginalExtension();
            $offer_image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $offer_image);

            $offer_title->offer_image = $offer_image;
        }

        $offer_title->offer_title = $request->offer_title;
        $offer_title->offer_subtitle = $request->offer_subtitle;
        $offer_title->save();

        $notification = array(
            'messege' => 'Offer Content Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.offer').'?language='.$this->lang->code)->with('notification', $notification);
    }

}