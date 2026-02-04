<?php

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use Session;
use App\Models\Language;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function faq(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $faqs = Faq::where('language_id', $langId)->orderBy('id', 'DESC')->get();
        
        return view('admin.faq.index', compact('faqs'));
    }

    // Add Faq
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.faq.add', compact('langs', 'currentLang'));
    }

    // Store Faq
    public function store(Request $request){

        $request->validate([
            'title' => 'required|max:150',
            'content' => 'required',
        ]);

        $faq = new Faq();
        $faq->language_id = $request->language_id;
        $faq->status = $request->status;
        $faq->title = $request->title;
        $faq->content = Purifier::clean($request->content);
        $faq->save();
       
        $notification = array(
            'messege' => 'Faq Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // Faq Delete
    public function delete($locale, $id){

        $faq = Faq::find($id);
        $faq->delete();

        return back();
    }

    // Faq Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $faq = Faq::find($id);
        
        if (!$faq) {
            $notification = array(
                'messege' => 'FAQ not found!',
                'alert' => 'error'
            );
            return redirect(route('admin.faq'))->with('notification', $notification);
        }
        
        return view('admin.faq.edit', compact('faq', 'langs', 'currentLang'));
    }

    // Update Faq
    public function update(Request $request, $locale, $id){

        $id = $request->id;
         $request->validate([
            'title' => 'required|max:150',
            'content' => 'required',
        ]);

        $faq = Faq::find($id);
        $faq->language_id = $request->language_id;
        $faq->status = $request->status;
        $faq->title = $request->title;
        $faq->content = Purifier::clean($request->content);
        $faq->save();

        $notification = array(
            'messege' => 'Faq Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.faq').'?language='.$this->lang->code)->with('notification', $notification);
    }



}