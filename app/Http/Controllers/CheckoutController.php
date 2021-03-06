<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use DB;
use Cart;
use Illuminate\Support\Facades\Redirect;
session_start();

class CheckoutController extends Controller
{
    // This Function will take us to the login page if we want to checkout without signing in or if we wish to login by our own will
    public function login_check()
    {
      Session::put('page', 'login');
      $categories = DB::table('categories')->where('pub_stat', 1)->get();
      $manufactures = DB::table('manufactures')->where('pub_stat', 1)->get();
      return view('pages.login', compact('categories', 'manufactures'));
    }

    //User registration function. If user click the sign up button this function will be triggered
    public function user_reg(Request $request)
    {
      $data = array();
      $data['name'] = $request->name;
      $data['email'] = $request->email;
      $data['password'] = md5($request->password);
      $data['mobile'] = $request->mobile;

      $user_id = DB::table('users')
                                ->insertGetId($data);
      // the insertGetId() function inserts the values into the database and at the same time it gets the id of that current table
      // Create session for the current user
      if ($user_id != null) {
        Session::put('user_id', $user_id);
        Session::put('user_name', $request->name);
        Session::put('user_email', $request->email);
        Session::put('user_mobile', $request->mobile);
      } else {
          return Redirect::to('/checkout')->with('message', 'registration Failed.');
      }

      return Redirect::to('/checkout')->with('message', 'Registration Failed successful.');
    }

    //This is our checkout function when we press checkout in our add to cart page this function will be triggered
    public function checkout()
    {
      $this->UserAuthCheck();
      if (Cart::count() <= 0) {
        //If there is no item in the cart we will return to our homepage with a warning message
        return Redirect::to('/')
                          ->with('message', 'You have nothing in your cart. Please buy something at first.');
      }
      // This 'page' session variable is created so that we can keep track of out current page
      Session::put('page', 'checkout');
      $categories = DB::table('categories')->where('pub_stat', 1)->get();
      $manufactures = DB::table('manufactures')->where('pub_stat', 1)->get();
      return view('pages.checkout', compact('categories', 'manufactures'));
    }

    public function save_shipping_details(Request $request)
    {
      // UserAuthCheck() function is used to check if the user is logged in or not
      $this->UserAuthCheck();
      $data = array();
      $data['shipping_email'] = $request->shipping_email;
      $data['shipping_first_name'] = $request->shipping_first_name;
      $data['shipping_last_name'] = $request->shipping_last_name;
      $data['shipping_address'] = $request->shipping_address;
      $data['shipping_city'] = $request->shipping_city;
      $data['mobile_number'] = $request->shipping_mobile;

      $sipping_id = DB::table('shippings')
                      ->insertGetId($data);
      Session::put('shipping_id', $sipping_id);
      return Redirect::to('/payment');
    }

    //This is the logout function
    public function logout()
    {
      $this->UserAuthCheck();
      // the flush() method in the Session class destroys all the session in that browser
      Session::flush();
      return Redirect::to('/');
    }

    // This is the user login function
    public function user_login(Request $request)
    {
      $user_email = $request->user_email;
      // md5() is an encryption method . Now more advanced ones are deployed like the argon() hashing
      $user_pwd = md5($request->user_pwd);

      $result = DB::table('users')
                                ->where('email', $user_email)
                                ->where('password', $user_pwd)
                                ->first();

      if ($result) {
        //This is session variables are created for the customer to fulfil his task
        Session::put('user_id', $result->uid);
        Session::put('user_name', $result->name);
        Session::put('user_email', $result->email);
        Session::put('user_mobile', $result->mobile);
        Session::put('user_image', $result->user_image);
        $sid = Session::get('shipping_id');
        if($sid == null)
          return Redirect::to('/');

        return Redirect::to('/checkout');
      } else {
        Session::put('message', 'Email or Passord Invalid');
        return Redirect::to('/login_check');
      }
    }

    // THis is the funtion that determines the payment method of the user
    public function payment()
    {
      $this->UserAuthCheck();
      $sid = Session::get('shipping_id');
      // We see if the customer has already given his shipping details or not. If not we will send him back to the homepage with a warning message
      if(!$sid) {
          return Redirect::to('/')->with('message', 'You must checkout at first');
      }
      $categories = DB::table('categories')->where('pub_stat', 1)->get();
      $manufactures = DB::table('manufactures')->where('pub_stat', 1)->get();
      return view('pages.payment', compact('categories', 'manufactures'));
    }

    public function payment_insert(Request $request)
    {
      $this->UserAuthCheck();

      if($request->payment_gateway == 'bkash') {
        dd('bkash');
      } else if($request->payment_gateway == 'ppal') {
        dd('pay_pal');
      } else if($request->payment_gateway == 'hcash') {
        $this->store_order($request);
      }

      $contents = Cart::content();
            

      if ($request->payment_gateway == 'hcash') {
          $message = 'hcash';
          Cart::destroy();
      } elseif ($request->payment_gateway == 'bkash') {
          $message = 'bkash';
          Cart::destroy();
      } elseif ($request->payment_gateway == 'ppal') {
          $message = 'ppal';
          Cart::destroy();
      }

      Session::forget('shipping_id');

      return Redirect::to('/')
                        ->with('message', $message);
    }

    public function store_order($request)
    {
      $payment_gateway = $request->payment_gateway;

      $pdata = array();
      $pdata['payment_method'] = $payment_gateway;
      $pdata['payment_status'] = 'pending';

      $payment_id = DB::table('payments')
                          ->insertGetId($pdata);


      $odata = array();
      $odata['uid'] = Session::get('user_id');
      $odata['shipping_id'] = Session::get('shipping_id');
      $odata['payment_id'] = $payment_id;
      $odata['order_total'] = Cart::total();
      $odata['order_status'] = 'pending';

      $order_id = DB::table('orders')
                        ->insertGetId($odata);

      $odetails = array();

      $contents = Cart::content();

      foreach ($contents as $content) {
        $odetails['order_id'] = $order_id;
        $odetails['product_id'] = $content->id;
        $odetails['product_name'] = $content->name;
        $odetails['product_price'] = $content->price;
        $odetails['product_sales_quantity'] = $content->qty;

        DB::table('orderdetails')
                                ->insert($odetails);
      }
    }

    // The function you see below was created for the purpose to see if the user is logged in or not
    public function UserAuthCheck()
    {
      $userId = Session::get('user_id');

      if ($userId) {
        return;
      } else {
        return Redirect::to('/login_check')->send();
      }
    }
}
