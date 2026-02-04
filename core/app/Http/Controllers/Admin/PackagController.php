<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Sectiontitle;
use App\Models\Language;
use Session;

class PackagController extends Controller
{
   public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function package(Request $request){
        $lang = 1;
     
        $packages = Package::where('language_id', $lang)->orderBy('id', 'DESC')->get();
        
        $saectiontitle = Sectiontitle::where('language_id', $lang)->first();
        return view('admin.package.index', compact('packages', 'saectiontitle'));
    }

    // Add slider Category
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.package.add', compact('langs', 'currentLang'));
    }

    // Store slider Category
    public function store(Request $request){
       //dd($request->all());
        $request->validate([
            'name' => 'required|max:150',
            'time' => 'required|max:150',
           // 'feature' => 'required',
            'price' => 'required|numeric',
            'status' => 'required|max:150',
        ]);

        Package::create($request->all());

        $notification = array(
            'messege' => 'Package Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // slider Category Delete
    public function delete($ln,$id){

        $Package = Package::find($id);
        $Package->delete();
        $notification = array(
            'messege' => 'Package Delete successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    // slider Category Edit
    public function edit($ln,$id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $package = Package::find($id);
     
        return view('admin.package.edit', compact('package', 'langs', 'currentLang'));

    }

    // Update slider Category
    public function update(Request $request){
  
         $request->validate([
            'name' => 'required|max:150',
            'time' => 'required|max:150',
            'bill_package_id' => 'required',
            'price' => 'required|numeric',
            'status' => 'required|max:150',
        ]);

        $package = Package::where('id',$request->package_id)->update([
            'name'          =>$request->name,
            'time'          =>$request->time,
            'feature'       =>$request->feature,
            'price'         =>$request->price,
            'status'        =>$request->status,
            'extra_days'    => $request->extra_days,
            'billing_package_id' => $request->bill_package_id,
            ]);
        
        $notification = array(
            'messege' => 'Package Updated successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

        public function plancontent(Request $request, $id){
        
        $request->validate([
            'plan_title' => 'required',
            'plan_subtitle' => 'required',
            'pricing_bg' => 'mimes:jpeg,jpg,png',
        ]);
      
        $plan_title = Sectiontitle::where('language_id', $id)->first();

        if($request->hasFile('pricing_bg')){
            @unlink('assets/front/img/'. $plan_title->pricing_bg);
            $file = $request->file('pricing_bg');
            $extension = $file->getClientOriginalExtension();
            $pricing_bg = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $pricing_bg);

            $plan_title->pricing_bg = $pricing_bg;

        }
    
        $plan_title->plan_title = $request->plan_title;
        $plan_title->plan_subtitle = $request->plan_subtitle;
        $plan_title->save();
        

        $notification = array(
            'messege' => 'Pricing Plan Content Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.package').'?language='.$this->lang->code)->with('notification', $notification);
    }

}
