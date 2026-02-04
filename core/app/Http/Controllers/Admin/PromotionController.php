<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Language;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use App\Models\ExtraMonth;

class PromotionController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
    }

    public function index(Request $request)
    {
        $promotions = Promotion::orderBy('id', 'DESC')->get();
        
        return view('admin.promotions.index', compact('promotions'));
    }

    public function add(){
        return view('admin.promotions.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $promotion = new Promotion();
        $promotion->status = $request->status;
        $promotion->promotion_type = $request->promotion_type;
        $promotion->title = $request->title;
        $promotion->description = $request->description;
        $promotion->chinese = $request->chinese;
        $promotion->myanmar = $request->myanmar;
        $promotion->duration = $request->duration;
        $promotion->extra_month = $request->extra_month;
        $promotion->extra_days = $request->extra_days;
        $promotion->save();
       
        $notification = array(
            'messege' => 'Promotion Added successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.promotion',app()->getLocale()))->with('notification', $notification);
    }

    public function delete($ln,$id)
    {
        $promotion = Promotion::find($id);
        $promotion->delete();
        
        $notification = array(
            'messege' => 'Promotion Deleted successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }

    public function edit($ln,$id)
    {
        $promotion = Promotion::find($id);
        return view('admin.promotions.edit', compact('promotion'));
    }

    public function update(Request $request, $locale, $id){

        $id = $request->id;
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $promotion = Promotion::find($id);
        $promotion->status = $request->status;
        $promotion->promotion_type = $request->promotion_type;
        $promotion->title = $request->title;
        $promotion->description = $request->description;
        $promotion->chinese = $request->chinese;
        $promotion->myanmar = $request->myanmar;
        $promotion->duration = $request->duration;
        $promotion->extra_month = $request->extra_month;
        $promotion->extra_days = $request->extra_days;
        $promotion->save();

        $notification = array(
            'messege' => 'Promotion Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.promotion',app()->getLocale()))->with('notification', $notification);
    }
    
    public function extra_months(Request $request)
    {
        $extramonths = ExtraMonth::orderBy('id', 'DESC')->get();
        
        return view('admin.promotions.extramonth', compact('extramonths'));
    }
    
    public function extra_months_edit($ln,$id)
    {
        $extramonth = ExtraMonth::find($id);
        return view('admin.promotions.extramonthedit', compact('extramonth'));
    }

    public function extra_months_update(Request $request, $id){

        $id = $request->id;
        $request->validate([
            'duration' => 'required',
            'extra_month' => 'required',
        ]);

        $extramonth = ExtraMonth::find($id);
        $extramonth->status = '1';
        $extramonth->duration = $request->duration;
        $extramonth->extra_month = $request->extra_month;
        $extramonth->save();

        $notification = array(
            'messege' => 'Extra Month Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.extra.months',app()->getLocale()))->with('notification', $notification);
    }



}