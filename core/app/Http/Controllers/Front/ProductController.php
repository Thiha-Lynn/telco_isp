<?php

namespace App\Http\Controllers\Front;

use App\Models\Product;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{

    public $lang_id;
    public function __construct()
    {
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }
        $this->lang_id = $currlang->id;
    }


    public function products(Request $request)
    {
        if (session()->has('lang')) {
            $currlang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currlang = Language::where('is_default', 1)->first();
        }
        $currlangid= $currlang->id;


        $search = $request->search;

       $allproduct = Product::where('language_id', $currlangid)->where('status', 1)->get();

       $data['count_product'] = count($allproduct);

        $data['products'] =
            Product::when($currlangid, function ($query, $currlangid) {
                return $query->where('language_id', $currlangid);
            })
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->where('status', 1)->paginate(8);

            
        return view('front.product', $data);
    }


    public function product_details($slug)
    {
        $data['product'] = Product::where('slug',$slug)->first();
        
        return view('front.product-details',$data);
    }


    public function cart()
    {

        if (Session::has('cart')) {
            $cart = Session::get('cart');
        } else {
            $cart = [];
        }
        return view('front.cart', compact('cart'));
    }


    // add to cart

    public function addToCart($id)
    {

        $cart = Session::get('cart');

        if (strpos($id, ',,,') == true) {
            $data = explode(',,,', $id);
            $id = $data[0];
            $qty = $data[1];

            $product = Product::findOrFail($id);

            if(!empty($cart) && array_key_exists($id, $cart)){
                if($product->stock < $cart[$id]['qty'] + $qty){
                    return response()->json(['error' => 'Product Out of Stock']);
                }
            }else{
                if($product->stock < $qty){
                    return response()->json(['error' => 'Product Out of Stock']);
                }
            }

            if (!$product) {
                abort(404);
            }
            $cart = Session::get('cart');
            // if cart is empty then this the first product
            if (!$cart) {

                $cart = [
                    $id => [
                        "name" => $product->title,
                        "qty" => $qty,
                        "price" => $product->current_price,
                        "photo" => $product->image
                    ]
                ];

                Session::put('cart', $cart);
                return response()->json(['message' => 'Product added to cart successfully!']);
            }

            // if cart not empty then check if this product exist then increment quantity
            if (isset($cart[$id])) {
                if($product->is_downloadable){
                    return response()->json(['message' => 'Product Allready Added']);
                }
                $cart[$id]['qty'] +=  $qty;
                Session::put('cart', $cart);
                return response()->json(['message' => 'Product added to cart successfully!']);
            }

            // if item not exist in cart then add to cart with quantity = 1
            $cart[$id] = [
                "name" => $product->title,
                "qty" => $qty,
                "price" => $product->current_price,
                "photo" => $product->image
            ];
        } else {

            $id = $id;
            $product = Product::findOrFail($id);
            if (!$product) {
                abort(404);
            }
            if(!empty($cart) && array_key_exists($id, $cart)){
                if($product->stock < $cart[$id]['qty'] + 1){
                    return response()->json(['error' => 'Product Out of Stock']);
                }
            }else{
                if($product->stock < 1){
                    return response()->json(['error' => 'Product Out of Stock']);
                }
            }


            $cart = Session::get('cart');
            // if cart is empty then this the first product
            if (!$cart) {

                $cart = [
                    $id => [
                        "name" => $product->title,
                        "qty" => 1,
                        "price" => $product->current_price,
                        "photo" => $product->image
                    ]
                ];

                Session::put('cart', $cart);
                return response()->json(['message' => 'Product added to cart successfully!']);
            }

            // if cart not empty then check if this product exist then increment quantity
            if (isset($cart[$id])) {
                if($product->is_downloadable){
                    return response()->json(['message' => 'Product Allready Added']);
                }
                $cart[$id]['qty']++;
                Session::put('cart', $cart);
                return response()->json(['message' => 'Product added to cart successfully!']);
            }

            // if item not exist in cart then add to cart with quantity = 1
            $cart[$id] = [
                "name" => $product->title,
                "qty" => 1,
                "price" => $product->current_price,
                "photo" => $product->image
            ];
        }

        Session::put('cart', $cart);
        return response()->json(['message' => 'Product added to cart successfully!']);
    }


    public function Prdouctcheckout(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            abort(404);
        }

        if ($request->qty) {
            $qty = $request->qty;
        } else {
            $qty = 1;
        }


        $cart = Session::get('cart');
        $id = $product->id;
        // if cart is empty then this the first product
        if (!($cart)) {
            if($product->stock <  $qty){
                Session::flash('warning','Product Out of stock');
                return back();
            }

            $cart = [
                $id => [
                    "name" => $product->title,
                    "qty" => $qty,
                    "price" => $product->current_price,
                    "photo" => $product->image
                ]
            ];

            Session::put('cart', $cart);
            if (!Auth::user()) {
                Session::put('link', url()->current());
                return redirect(route('user.login'));
            }
            return redirect(route('front.checkout'));
        }

        // if cart not empty then check if this product exist then increment quantity
        if (isset($cart[$id])) {
            if($product->is_downloadable){
                return response()->json(['message' => 'Product Allready Added']);
            }
            if($product->stock < $cart[$id]['qty'] + $qty){
                Session::flash('warning','Product Out of stock');
                return back();
            }
            $qt = $cart[$id]['qty'];
            $cart[$id]['qty'] = $qt + $qty;

            Session::put('cart', $cart);
                if (!Auth::user()) {
                Session::put('link', url()->current());
                return redirect(route('user.login'));
            }
            return redirect(route('front.checkout'));
        }

        if($product->stock <  $qty){
            Session::flash('warning','Product Out of stock');
            return back();
        }


        $cart[$id] = [
            "name" => $product->title,
            "qty" => $qty,
            "price" => $product->current_price,
            "photo" => $product->image
        ];
        Session::put('cart', $cart);

        if (!Auth::user()) {
            Session::put('link', url()->current());
            return redirect(route('user.login'));
        }
        return redirect(route('front.checkout'));
    }


    public function checkout()
    {
        if (!Session::get('cart')) {
            Session::flash('warning', 'Your cart is empty.');
            return redirect(route('front.cart'));
        }

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $user = Auth::user();

        if ($user) {
            if (Session::has('cart')) {
                $data['cart'] = Session::get('cart');
            } else {
                $data['cart'] = null;
            }
            // $data['shippings'] = ShippingCharge::where('language_id',$currentLang->id)->get();
            $data['user'] = Auth::user();
            return view('front.checkout', $data);
        } else {
            Session::put('link', url()->current());
            return redirect(route('user.login'));
        }
    }



    // header cart load

    public function headerCartLoad()
    {
        if(Session::has('cart')){
            $cart = Session::get('cart');
        }else{
            $cart = [];
        }
        return view('front.load.header_cart',compact('cart'));
    }


    // cart qty get
    public function cartQtyGet()
    {

        if(Session::has('cart')){
            $qty = count(Session::get('cart'));
            return $qty;
        }
    }


    public function cartitemremove($id)
    {
        if ($id) {
            $cart = Session::get('cart');
            if (isset($cart[$id])) {
                unset($cart[$id]);
                Session::put('cart', $cart);
            }

            $total = 0;
            $count = 0;
            foreach ($cart as $i) {
                $total += $i['price'] * $i['qty'];
                $count += $i['qty'];
            }
            $total = round($total, 2);

            return response()->json(['message' => 'Product removed successfully', 'count' => $count, 'total' => $total]);
        }
    }


    public function updatecart(Request $request)
    {

        if (Session::has('cart')) {
            $cart = Session::get('cart');
            foreach ($request->product_id as $key => $id) {
                $product = Product::findOrFail($id);
                if($product->stock < $request->qty[$key]){
                    return response()->json(['error' => $product->title .' stock not available']);
                }
                if (isset($cart[$id])) {
                    $cart[$id]['qty'] =  $request->qty[$key];
                    Session::put('cart', $cart);
                }
            }
        }
        $total = 0;
        $count = 0;
        foreach ($cart as $i) {
            $total += $i['price'] * $i['qty'];
            $count += $i['qty'];
        }

        $total = round($total, 2);

        return response()->json(['message' => 'Cart Update Successfully.', 'total' => $total, 'count' => $count]);
    }

}
