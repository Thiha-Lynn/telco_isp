<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Blog;
use App\Models\Language;
use App\Models\Bcategory;
use App\Models\Sectiontitle;
use App\Helpers\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;

class BlogController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default', 1)->first();
    }

    public function blog(Request $request){
        $langCode = $request->language ?? $this->lang->code;
        $lang = Language::where('code', $langCode)->first();
        if (!$lang) {
            $lang = $this->lang;
        }
        $langId = $lang->id;

        $blogs = Blog::where('language_id', $langId)->orderBy('id', 'DESC')->get();

        $saectiontitle = Sectiontitle::where('language_id', $langId)->first();
        if (!$saectiontitle) {
            $saectiontitle = Sectiontitle::first() ?? new Sectiontitle(['language_id' => $langId]);
        }
      
        return view('admin.blog.index', compact('blogs', 'saectiontitle'));
    }

    // Add Blog 
    public function add(){
        $langs = Language::all();
        $currentLang = $this->lang;
        return view('admin.blog.add', compact('langs', 'currentLang'));
    }

    public function blog_get_category($id){

        $bcategories = Bcategory::where('status', 1)->where('language_id', $id)->get();
        $output = '';

        foreach($bcategories as $bcategory){
            $output .= '<option value="'.$bcategory->id.'">'.$bcategory->name.'</option>';
        }
        return $output;
    }

    // Store Blog 
    public function store(Request $request){

        $slug = Helper::make_slug($request->title);
        $blogs = Blog::select('slug')->get();


        $request->validate([
            'main_image' => 'required|mimes:jpeg,jpg,png',
            'title' => [
                'required',
                'unique:blogs,title',
                'max:255',
                function($attribute, $value, $fail) use ($slug, $blogs){
                    foreach($blogs as $blog){
                        if($blog->slug == $slug){
                            return $fail('Title already taken!');
                        }
                    }
                }
            ],
            'status' => 'required',
            'content' => 'required',
            'bcategory_id' => 'required',
            'language_id' => 'required',
        ]);


        $blog = new Blog();

        if($request->hasFile('main_image')){

            $file = $request->file('main_image');
            $extension = $file->getClientOriginalExtension();
            $main_image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $main_image);

            $blog->main_image = $main_image;
        }


        $blog->title = $request->title;
        $blog->language_id = $request->language_id;
        $blog->status = $request->status;
        $blog->content = Purifier::clean($request->content);
        $blog->slug = $slug;
        $blog->bcategory_id = $request->bcategory_id;
        $blog->meta_keywords = $request->meta_keywords;
        $blog->meta_description = $request->meta_description;
        $blog->save();

        $notification = array(
            'messege' => 'Blog Added successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);

    }

    // Blog  Delete
    public function delete($locale, $id){

        $blog = Blog::find($id);
        @unlink('assets/front/img/'. $blog->main_image);
        $blog->delete();

    }

    // Blog  Edit
    public function edit($locale, $id){
        $langs = Language::all();
        $currentLang = $this->lang;
        $blog = Blog::findOrFail($id);
        $blog_lan = $blog->language_id;
       
        $bcategories = Bcategory::where('status', 1)->where('language_id', $blog_lan)->get();
        
        return view('admin.blog.edit', compact('bcategories', 'blog', 'langs', 'currentLang'));
    }

    // Blog Update
    public function update(Request $request, $locale, $id){

        $slug = Helper::make_slug($request->title);
        $blogs = Blog::select('slug')->get();
        $blog = Blog::findOrFail($id);

        $request->validate([
            'main_image' => 'mimes:jpeg,jpg,png',
            'title' => [
                'required',
                'max:255',
                function($attribute, $value, $fail) use ($slug, $blogs, $blog){
                    foreach($blogs as $blg){
                        if($blog->slug != $slug){
                            if($blg->slug == $slug){
                                return $fail('Title already taken!');
                            }
                        }
                    }
                },
                'unique:blogs,title,'.$id
            ],
            'status' => 'required',
            'content' => 'required',
            'bcategory_id' => 'required',
            'language_id' => 'required',

        ]);

        if($request->hasFile('main_image')){
            @unlink('assets/front/img/'. $blog->main_image);

            $file = $request->file('main_image');
            $extension = $file->getClientOriginalExtension();
            $main_image = time().rand().'.'.$extension;
            $file->move('assets/front/img/', $main_image);

            $blog->main_image = $main_image;
            
        }

        $blog->title = $request->title;
        $blog->language_id = $request->language_id;
        $blog->status = $request->status;
        $blog->content = Purifier::clean($request->content);
        $blog->slug = $slug;
        $blog->bcategory_id = $request->bcategory_id;
        $blog->meta_keywords = $request->meta_keywords;
        $blog->meta_description = $request->meta_description;

        $blog->save();

        $notification = array(
            'messege' => 'Blog Updated successfully!',
            'alert' => 'success'
        );

        return redirect(route('admin.blog').'?language='.$this->lang->code)->with('notification', $notification);

    }

    public function blogcontent(Request $request, $id){
        
        $request->validate([
            'blog_title' => 'required',
            'blog_subtitle' => 'required'
        ]);
        // dd($request->all());
        $blog_title = Sectiontitle::where('language_id', $id)->first();


        $blog_title->blog_title = $request->blog_title;
        $blog_title->blog_subtitle = $request->blog_subtitle;
        $blog_title->save();

        $notification = array(
            'messege' => 'Blog Content Updated successfully!',
            'alert' => 'success'
        );
        return redirect(route('admin.blog').'?language='.$this->lang->code)->with('notification', $notification);
    }
}
