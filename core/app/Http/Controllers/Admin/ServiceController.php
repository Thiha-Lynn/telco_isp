<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Service;
use App\Models\Language;
use App\Models\Sectiontitle;
use App\Helpers\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function service(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;
     
        $services = Service::where('language_id', $langId)->orderBy('id', 'DESC')->get();
        
        $saectiontitle = Sectiontitle::where('language_id', $langId)->first();
        return view('admin.service.index', compact('services', 'saectiontitle'));
    }

    // Add Service
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.service.add', compact('langs', 'currentLang'));
    }

    // Store Service
    public function store(Request $request){

      
       
        $slug = Helper::make_slug($request->name);
        $services = Service::select('slug')->get();
      
        $request->validate([
            'name' => [
              'required',
              'unique:services,name',
              'max:150',
              function($attribute, $value, $fail) use ($slug, $services) {
                  foreach($services as $service) {
                    if ($service->slug == $slug) {
                      return $fail('Name already taken!');
                    }
                  }
                }
            ],
            'icon' => 'required|mimes:jpeg,jpg,png',
            'image' => 'required|mimes:jpeg,jpg,png',
            'content' => 'required'
        ]);

        $service = new Service();

        if($request->hasFile('icon')){
            $file = $request->file('icon');
            $extension = $file->getClientOriginalExtension();
            $icon = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $icon);

            $service->icon = $icon;
        }

        if($request->hasFile('image')){
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $image);

            $service->image = $image;
        }
        

        $service->name = $request->name;
        $service->slug = $slug;
        $service->content = Purifier::clean($request->content);
        $service->language_id = $request->language_id;
        $service->status = $request->status;
        $service->save();

        $notification = array(
            'messege' => 'Service Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // Service Delete
    public function delete($locale, $id){

        $service = Service::find($id);
        @unlink('assets/front/img/'. $service->icon);
        $service->delete();

        return back();
    }

    // Service Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $service = Service::find($id);
        return view('admin.service.edit', compact('service', 'langs', 'currentLang'));

    }

    // Update Service
    public function update(Request $request, $locale, $id){

        $slug = Helper::make_slug($request->name);
        $services = Service::select('slug')->get();
        $service = Service::findOrFail($id);

         $request->validate([
            'name' => [
              'required',
              'max:150',
              function($attribute, $value, $fail) use ($slug, $services, $service) {
                  foreach($services as $serv) {
                    if ($service->slug != $slug) {
                      if ($serv->slug == $slug) {
                        return $fail('Title already taken!');
                      }
                    }
                  }
                },
                'unique:services,name,'.$id
            ],
            'icon' => 'mimes:jpeg,jpg,png',
            'content' => 'required'
        ]);

        if($request->hasFile('icon')){
            @unlink('assets/front/img/'. $service->icon);
            $file = $request->file('icon');
            $extension = $file->getClientOriginalExtension();
            $icon = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $icon);

            $service->icon = $icon;
        }

        if($request->hasFile('image')){
            @unlink('assets/front/img/'. $service->image);
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $image);

            $service->image = $image;
        }

        $service->name = $request->name;
        $service->slug = $slug;
        $service->content = Purifier::clean($request->content);
        $service->language_id = $request->language_id;
        $service->status = $request->status;
        $service->save();

        $notification = array(
            'messege' => 'Service Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.service').'?language='.$this->lang->code)->with('notification', $notification);
    }

    public function servicecontent(Request $request, $id){
        
        $request->validate([
            'service_title' => 'required',
            'service_subtitle' => 'required',
        ]);

        $service_title = Sectiontitle::where('language_id', $id)->first();

        $service_title->update($request->all());

        $notification = array(
            'messege' => 'Service Content Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.service').'?language='.$this->lang->code)->with('notification', $notification);
    }
}
