<?php

namespace App\Http\Controllers\Seller;

use Throwable;
use App\Http\Controllers\Controller;
use App\Events\SellerToUser;
use App\Models\BannerImage;
use App\Models\Message;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;

class SellerMessageContoller extends Controller
{

    public function __construct(){
        $this->middleware('auth:api');
    }

    public function index(){
        $auth = Auth::guard('api')->user();
        $defaultProfile = BannerImage::whereId('15')->first();
        $customers = Message::with('customer')->where(['seller_id' => $auth->id])->select('customer_id')->groupBy('customer_id')->orderBy('id','desc')->get();

        //return view('seller.chat_list', compact('customers','defaultProfile','auth'));
        return response()->json(['customers' => $customers, 'defaultProfile' => $defaultProfile, 'auth' => $auth]);
    }

    public function sendMessage(Request $request){

        $request->validate([
            'customer_id' => ['required'],
            'message' => ['required_without:product_id'],
        ]);

        $auth = Auth::guard('api')->user();
        $message = new Message();
        $message->customer_id = $request->customer_id;
        $message->seller_id = $auth->id;
        $message->message = $request->message;
        $message->customer_read_msg = 0;
        $message->seller_read_msg = 1;
        $message->send_by = 'seller';
        $message->product_id = $request->product_id ?: 0;
        $message->save();

        $updateMessage = Message::with('product')->find($message->id);
        $user = User::find($request->customer_id);
        try {
            event(new SellerToUser($updateMessage, $user));
        } catch (Throwable $exception) {
            report($exception);
        }
        $customer = User::find($request->customer_id);
        $id = $request->customer_id;
        $messages = Message::with('product')->where(['seller_id' => $auth->id, 'customer_id'=>$id])->get();
        $defaultProfile = BannerImage::whereId('15')->first();

        //return view('seller.chat__message_list', compact('customer','auth','messages','defaultProfile'));
        return response()->json([
            'customer' => $customer,
            'auth' => $auth,
            'message' => $updateMessage,
            'messages' => $messages,
            'defaultProfile' => $defaultProfile,
        ]);
    }

    public function loadNewMessage($id){
        $auth = Auth::guard('api')->user();
        $customer = User::find($id);
        $unRead = Message::where(['seller_id' => $auth->id, 'customer_id' => $customer->id])->update(['seller_read_msg' => 1]);
        $messages = Message::with('product')->where(['seller_id' => $auth->id, 'customer_id'=>$id])->get();
        $defaultProfile = BannerImage::whereId('15')->first();

        //return view('seller.chat__message_list', compact('customer','auth','messages','defaultProfile'));
        return response()->json(['customer' => $customer, 'auth' => $auth, 'messages' => $messages]);
    }



    public function loadChatBox($id){
        $customer = User::find($id);
        $auth = Auth::guard('api')->user();
        $unRead = Message::where(['seller_id' => $auth->id, 'customer_id' => $customer->id])->update(['seller_read_msg' => 1]);
        $messages = Message::with('product')->where(['seller_id' => $auth->id, 'customer_id'=>$id])->get();
        $defaultProfile = BannerImage::whereId('15')->first();

        //return view('seller.chat_box', compact('customer','auth','messages','defaultProfile'));

        return response()->json(['customer' => $customer, 'auth' => $auth, 'messages' => $messages, 'defaultProfile' => $defaultProfile]);
    }
}
