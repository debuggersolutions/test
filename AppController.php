<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Amount;
use App\Models\Categories;
use App\Models\Fonts;
use App\Models\Footer;
use App\Models\User;
use App\Models\Logo;
use App\Models\Frames;
use App\Models\LogoCate;
use App\Models\Package;
use App\Models\Payments;
use App\Models\Politician;
use App\Models\Setting;
use App\Models\Stickers;
use App\Models\Subcategory;
use App\Models\Thumbnail;
use App\Models\Userpackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AppController extends Controller
{
    public function get_all_categories()
    {
        $all_cate = Categories::active()->get(['id', 'cate_name', 'description', 'img'])->makeHidden(['updated_at']);
        if (count($all_cate)) {
            return response(['error' => false, 'data' => $all_cate], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function get_all_subcategories(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'cat_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['error' => true, 'message' => $validator->errors()], 400);
        }
        $data = Subcategory::with('templates')->where('cat_id', $req->cat_id)->active()->get(['id as sub_cate_id', 'subcate_name', 'cat_id'])->makeHidden(['updated_at']);
        // pre($data);
        // $data['templates'] = Template::where('cate_id', $req->cat_id)->active()->get(['id','temp_name','subcate_id','image'])->makeHidden(['updated_at']);

        if (count($data) > 0) {
            return response(['error' => false, 'data' => $data], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function razorypay_token(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        } else {

            $razorypay_data = Setting::where('type', 'razorypay')->first();
            $razorypay_keys = json_decode($razorypay_data['api_keys'], true);

            $input = $req->input();

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.razorpay.com/v1/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{  "amount": ' . $input['amount'] . '00,  "currency": "INR",  "receipt": "receipt#1",  "notes": {    "app": "Smart Enterprise" }}',
                CURLOPT_HTTPHEADER => array(
                    'content-type: application/json',
                    'Authorization: Basic ' . base64_encode("$razorypay_keys[key]:$razorypay_keys[secret]"),
                ),
            ));

            $response = curl_exec($curl);
            $res = json_decode($response, true);
            curl_close($curl);

            if (isset($res['error'])) {
                return response(['error' => true, 'data' => $res], 400);
            } else {
                return response(['error' => false, 'data' => $res], 200);
            }
        }
    }

    public function create_page_template()
    {
        $temp_info = Categories::with('temp_from_cate')->active()->get(['id as cat_id', 'cate_name']);

        if (count($temp_info) > 0) {
            return response(['error' => false, 'data' => $temp_info], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function checkout_pay(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'amount' => 'required',
            'payload' => 'required',
            'template_id' => 'required',
            'template_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        } else {
            $input = $req->input();
            $user = Auth()->user();
            $img_name = time() . '-' . Str::of(md5(time() . $req->file('template_name')->getClientOriginalName()))->substr(0, 50) . '_' . $user->id . '.' . $req->file('template_name')->extension();
            $path = $req->file('template_name')->storeAs('admin/img/user-template/', $img_name);
            $payments = new Payments();
            $payments->user_id = $user->id;
            $payments->amount = $input['amount'];
            $payments->template_id = $input['template_id'];
            $payments->template_name = $img_name;
            $payments->payload = $input['payload'];

            $input['template_name'] = url('admin/img/user-template/' . $img_name);
            // $input['amount'] = $input['amount'] / 100;
            unset($input['payload']);

            if ($payments->save()) {
                return response(['error' => false, 'data' => $input], 200);
            } else {
                return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
            }
        }
    }

    public function user_all_payments()
    {
        $user = Auth()->user();

        $pay_info = Payments::where('user_id', $user->id)->get();
        // dd($pay_info);

        if (count($pay_info) > 0) {
            return response(['error' => false, 'data' => $pay_info], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_image_price()
    {
        $amount = Amount::where('id', 1)->first();

        if ($amount) {
            return response(['error' => false, 'data' => $amount], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_fonts_details()
    {
        $fonts = Fonts::all();
        if (count($fonts) > 0) {
            return response(['error' => false, 'data' => $fonts], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_logo_cate()
    {
        $fonts = LogoCate::active()->get();
        if (count($fonts) > 0) {
            return response(['error' => false, 'data' => $fonts], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_thumbnails()
    {
        $thumbnail = Thumbnail::all();
        if (count($thumbnail) > 0) {
            return response(['error' => false, 'data' => $thumbnail], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_logo(Request $req)
    {
        $input = $req->input();
        if ($input['search']) {
            $logo_info = LogoCate::with('logoFromCate')->where('cate_name', 'like', '%' . $input['search'] . '%')->where('status', '1')->get();
        } else {
            $logo_info = LogoCate::with('logoFromCate')->where('status', '1')->get();
        }

        if (count($logo_info) > 0) {
            return response(['error' => false, 'data' => $logo_info], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_all_logo(Request $req)
    {
        $input = $req->input();

        $logo_info = LogoCate::where('cate_name', 'like', '%' . $input['search'] . '%')->where('status', '1')->get('id');

        if (count($logo_info) > 0) {
            foreach ($logo_info as $key => $value) {
                $id[] = $value['id'];
            }
            $all_logo = Logo::whereIn('cate_id', $id)->get();
            return response(['error' => false, 'data' => $all_logo], 200);
        } else {
            return response(['error' => false, 'message' => "No data found", 'data' => []], 200);
        }
    }

    public function get_all_politician(Request $req)
    {
        $input = $req->input();
        if ($input['search']) {
            $all_data = Politician::where('name', 'like', '%' . $input['search'] . '%')->get();
        } else {
            $all_data = Politician::all();
        }


        if (count($all_data)) {
            return response(['error' => false, 'data' => $all_data], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function get_all_frames()
    {
        $all_data = Frames::all();
        if (count($all_data)) {
            return response(['error' => false, 'data' => $all_data], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function get_all_stickers()
    {
        $all_data = Stickers::all();
        if (count($all_data)) {
            return response(['error' => false, 'data' => $all_data], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function get_footer_images()
    {
        $all_data = Footer::all();
        if (count($all_data)) {
            return response(['error' => false, 'data' => $all_data], 200);
        } else {
            return response(['error' => false, 'message' => 'No data found', 'data' => []], 200);
        }
    }

    public function buy_package(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'package_id' => 'required|exists:' . PACKAGE . ',id',
            'payload' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        } else {

            $user = Auth()->user();

            $input = $req->input();

            $pack_info = Package::where('id', $input['package_id'])->first();

            $currentDate = date('Y-m-d');

            $user_package = Userpackage::where(['user_id' => $user->id, 'package_id' => $input['package_id']])->whereDate('expiry', '>', $currentDate)->first();

            if (!$user_package) {
                $expiry = date('Y-m-d', strtotime($currentDate . ' + 12 months'));

                $payments = new Userpackage();
                $payments->user_id = $user->id;
                $payments->package_id = $pack_info->id;
                $payments->price = $pack_info->price;
                $payments->temp_count = $pack_info->temp_count;
                $payments->expiry = $expiry;
                $payments->payload = $input['payload'];

                if ($payments->save()) {
                    $user_temp_count = $pack_info->temp_count == -1 ? 0 : $pack_info->temp_count;

                    $temp_count_update = User::where('id', $user->id)->increment('temp_count', $user_temp_count);

                    if ($temp_count_update) {
                        return response(['error' => false, 'msg' => 'Payment successful'], 200);
                    } else {
                        return response(['error' => false, 'true' => "Some error occured in saving payment", 'data' => []], 200);
                    }
                } else {
                    return response(['error' => true, 'message' => "Some error occured in saving payment", 'data' => []], 200);
                }
            } else {
                return response(['error' => true, 'message' => "You already have this package", 'data' => []], 200);
            }
        }
    }

    public function get_all_package()
    {
        $pack_info = Package::all();

        $user = Auth()->user();

        $currentDate = date('Y-m-d');

        $user_package = Userpackage::where('user_id', $user->id)->whereDate('expiry', '>', $currentDate)->get();
        // return $user_package;

        foreach ($pack_info as $key => $value) {
            $pack_info[$key]['package_status'] = false;

            foreach ($user_package as $key1 => $value1) {
                if ($value->id == $value1->package_id) {
                    $pack_info[$key]['package_status']   = true;
                }
            }
        }
        // return $pack_info;
        if ($pack_info) {
            return response(['error' => false, 'msg' => 'Package data', 'data' => $pack_info], 200);
        } else {
            return response(['error' => false, 'true' => "No data found", 'data' => []], 200);
        }
    }
}
