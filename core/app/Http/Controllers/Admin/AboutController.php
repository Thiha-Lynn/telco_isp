<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\About;
use App\Models\Language;
use App\Models\Sectiontitle;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class AboutController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function about(Request $request){
        
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $abouts = About::where('language_id', $langId)->orderBy('id', 'DESC')->get();
        $saectiontitle = Sectiontitle::where('language_id', $langId)->first();
        
        return view('admin.about.index', compact('abouts', 'saectiontitle'));
    }

    // Add slider Category
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.about.add', compact('langs', 'currentLang'));
    }

    // Store slider Category
    public function store(Request $request){

        $request->validate([
            'feature' => 'required|max:150',
        ]);

        About::create($request->all());

        $notification = array(
            'messege' => 'About Feature Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // slider Category Delete
    public function delete($locale, $id){

        $about = About::find($id);
        $about->delete();

        return back();
    }

    // slider Category Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $about = About::find($id);
        if (!$about) {
            $notification = array(
                'messege' => 'About record not found!',
                'alert' => 'warning'
            );
            return redirect()->route('admin.about', app()->getLocale())->with('notification', $notification);
        }
        return view('admin.about.edit', compact('about', 'langs', 'currentLang'));

    }

    // Update slider Category
    public function update(Request $request, $locale, $id){

        $id = $request->id;
        $request->validate([
            'feature' => 'required|max:150',
        ]);

        $about = About::find($id);
        $about->update($request->all());

        $notification = array(
            'messege' => 'About Feature Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.about').'?language='.$this->lang->code)->with('notification', $notification);
    }

    public function contact_info(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
        
        $setting = \App\Models\Setting::where('language_id', $langId)->first();
        if (!$setting) {
            $setting = \App\Models\Setting::first();
        }
        
        return view('admin.about.contact-info', compact('setting'));
    }

    public function contact_info_update(Request $request){
        $request->validate([
            'number' => 'required|max:250',
            'email' => 'required|max:250',
            'contactemail' => 'required|max:250',
            'address' => 'required',
        ]);

        $setting = \App\Models\Setting::first();
        $setting->number = $request->number;
        $setting->email = $request->email;
        $setting->contactemail = $request->contactemail;
        $setting->address = $request->address;
        $setting->save();

        $notification = array(
            'messege' => 'Contact Info Updated successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function aboutcontent(Request $request, $id){
        
        $request->validate([
            'about_title' => 'required',
            'about_subtitle' => 'required',
            'about_image' => 'mimes:jpeg,jpg,png',
        ]);
        $about_title = Sectiontitle::where('language_id', $id)->first();

         if($request->hasFile('about_image')){
            @unlink('assets/front/img/'. $about_title->about_image);
            $file = $request->file('about_image');
            $extension = $file->getClientOriginalExtension();
            $about_image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $about_image);

            $about_title->about_image = $about_image;
        }

        $about_title->about_title = $request->about_title;
        $about_title->about_subtitle = Purifier::clean($request->about_subtitle);
        $about_title->save();

        $notification = array(
            'messege' => 'About Content Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.about').'?language='.$this->lang->code)->with('notification', $notification);
    }
}