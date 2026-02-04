<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Slider;
use Session;

class SliderController extends Controller
{
   public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function slider(Request $request){
        
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $sliders = Slider::where('language_id', $langId)->orderBy('id', 'DESC')->get();
        return view('admin.slider.index', compact('sliders'));
    }

    // Add slider Category
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.slider.add', compact('langs', 'currentLang'));
    }

    // Store slider Category
    public function store(Request $request){

        $request->validate([
            'name' => 'required|max:150',
            'language_id' => 'required',
            'offer' => 'required|max:150',
            'desc' => 'required',
            'image' => 'required|mimes:jpeg,jpg,png',
        ]);

        $slider = new Slider();
        if($request->hasFile('image')){
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $image);

            $slider->image = $image;
        }
        $slider->language_id = $request->language_id;
        $slider->status = $request->status;
        $slider->name = $request->name;
        $slider->offer = $request->offer;
        $slider->desc = $request->desc;
        $slider->save();
        

        $notification = array(
            'messege' => 'Slider Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // slider Category Delete
    public function delete($locale, $id){

        $slider = Slider::find($id);
        @unlink('assets/front/img/'. $slider->image);
        $slider->delete();

        return back();
    }

    // slider Category Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $slider = Slider::find($id);
        return view('admin.slider.edit', compact('slider', 'langs', 'currentLang'));
    }

    // Update slider Category
    public function update(Request $request, $locale, $id){

        $id = $request->id;
        $request->validate([
            'name' => 'required|max:150',
            'language_id' => 'required',
            'offer' => 'required|max:150',
            'desc' => 'required',
            'image' => 'mimes:jpeg,jpg,png',
        ]);

        $slider = Slider::find($id);

        if($request->hasFile('image')){
            @unlink('assets/front/img/'. $slider->image);
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $image);

            $slider->image = $image;
        }
        $slider->language_id = $request->language_id;
        $slider->status = $request->status;
        $slider->name = $request->name;
        $slider->offer = $request->offer;
        $slider->desc = $request->desc;
        $slider->save();

        $notification = array(
            'messege' => 'Slider Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.slider').'?language='.$this->lang->code)->with('notification', $notification);;
    }
}