<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use App\Models\Daynamicpage;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class DynamicpageController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }
    
    public function dynamic_page(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $dynamicpages = Daynamicpage::where('language_id', $langId)->where('status', '1')->orderBy('id', 'DESC')->get();

        
        return view('admin.dynamicpage.index', compact('dynamicpages'));
    }

    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.dynamicpage.add', compact('langs', 'currentLang'));
    }

    public function store(Request $request){

        $slug = Helper::make_slug($request->title);
        $dynamicpages = Daynamicpage::select('slug')->get();
        
        $request->validate([
            'title' => [
                'required',
                'unique:daynamicpages,title',
                'max:255',
                function($attribute, $value, $fail) use ($slug, $dynamicpages){
                    foreach($dynamicpages as $dynamicpage){
                        if($dynamicpage->slug == $slug){
                            return $fail('Title already taken!');
                        }
                    }
                }
            ],
            'content' => 'required',
            'burmish' =>'required',
            'chinese'=>'required',
        ]);

        $dynamicpage = new Daynamicpage();
        $dynamicpage->language_id = $request->language_id;
        $dynamicpage->title = $request->title;
        $dynamicpage->slug = $slug;
        $dynamicpage->content = Purifier::clean($request->content);
        $dynamicpage->status = $request->status;
        $dynamicpage->save();

        $notification = array(
            'messege' => 'Daynamic Page Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function edit($ln,$id){

        $dynamicpage = Daynamicpage::find($id);
        return view('admin.dynamicpage.edit', compact('dynamicpage'));

    }

    public function update(Request $request, $ln,$id){

        $slug = Helper::make_slug($request->title);
        $dynamicpages = Daynamicpage::select('slug')->get();
        $dynamicpage = Daynamicpage::findOrFail($id);

         $request->validate([
            'title' => [
                'required',
                'max:255',
                function($attribute, $value, $fail) use ($slug, $dynamicpages, $dynamicpage){
                    foreach($dynamicpages as $blg){
                        if($dynamicpage->slug != $slug){
                            if($blg->slug == $slug){
                                return $fail('Title already taken!');
                            }
                        }
                    }
                },
                'unique:daynamicpages,title,'.$id
            ],
            'content' => 'required',
        ]);

        $dynamicpage->language_id = $request->language_id;
        $dynamicpage->title = $request->title;
        $dynamicpage->slug = $slug;
        $dynamicpage->content = $request->content;
        $dynamicpage->burmish = $request->burmish;
        $dynamicpage->chinese = $request->chinese;


        $dynamicpage->status = $request->status;
        
        $dynamicpage->save();

        $notification = array(
            'messege' => 'About language Updated successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function delete($locale, $id){

        $dynamicpage = Daynamicpage::find($id);
        $dynamicpage->delete();

        return back();
    }


}
