<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required',
            'perPage' => 'required',
        ]);

        $perpage = $request->perpage;
        $data = DB::table('orders as od')
            ->select(
                'od.*',
                DB::raw('(SELECT count(*) FROM order_details WHERE order_details.order_id = od.order_id) as product_list_total'),
                'st.sale_type_name',
                'c.cus_name',
                'c.cus_tel',
                'u.name as staff_name',
                'b.branch_name'
            )
            ->leftJoin('sale_type as st', 'st.sale_type_id', '=', 'od.order_sale_type')
            ->leftJoin('customers as c', 'c.cus_id', '=', 'od.cus_id')
            ->leftJoin('users as u', 'u.id', '=', 'od.created_by')
            ->leftJoin('branchs as b', 'b.branch_id', '=', 'od.branch_id')
            ->limit($perpage)
            ->paginate(
                $request->perPage, // per page (may be get it from request)
                ['*'], // columns to select from table (default *, means all fields)
                'page', // page name that holds the page number in the query string
                $request->page // current page, default 1
            );
        if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff') {
            // $res = [];
            foreach ($data as $dt) {
                // print($dt->order_id);
                $dt->details = DB::table('order_details')->where('order_id', $dt->order_id)->get();
            }
            return response()->json(['data' => $data], 200);
        } else {
            abort(403);
        }
    }
    public function store(Request $request)
    {

        $request->validate([
            // 'cus_id' => 'integer|',
            'branch_id' => 'required|integer',
            'order_cost_price' =>  'required|between:0,99.99',
            'order_discount_price' =>  'required|between:0,99.99',
            'order_price' =>  'required|between:0,99.99',
            'order_total_price' =>  'required|between:0,99.99',
            'order_revenue_price' =>  'required|between:0,99.99',
            'order_sale_type' =>  'required|integer',
            'status' => 'required|string',
            'get_money' =>  'required|between:0,99.99',
            'change_money' =>  'required|between:0,99.99',
            'product_amount' =>  'required|integer',
            'order_details' => 'required',


        ]);
        if (auth()->user()->role == 'admin') {
            $store =  DB::table('orders')->insertGetId(
                [
                    'cus_id' => $request->cus_id,
                    'branch_id' => $request->branch_id,
                    'order_cost_price' => $request->order_cost_price,
                    'order_discount_price' => $request->order_discount_price,
                    'order_price' => $request->order_price,
                    'order_total_price' => $request->order_total_price,
                    'order_revenue_price' => $request->order_revenue_price,
                    'order_sale_type' => $request->order_sale_type,
                    'status' => $request->status,
                    'get_money' => $request->get_money,
                    'change_money' => $request->change_money,
                    'product_amount' => $request->product_amount,
                    'created_by' => auth()->user()->id
                ]
            );
            if ($store) {
                $order_id = $store;

                foreach ($request->order_details as $pd) {
                    DB::table('order_details')->insert([
                        "order_id" => $order_id,
                        "cus_id" => $pd['cus_id'],
                        "branch_id" => $pd['branch_id'],
                        "product_id" => $pd['product_id'],
                        "product_sale_price" => $pd['product_sale_price'],
                        "product_cost_price" => $pd['product_cost_price'],
                        "order_detail_product_sale_type" => $pd['order_detail_product_sale_type'],
                        "amount" => $pd['amount'],
                        "order_detail_total_price" => $pd['order_detail_total_price'],
                        "order_detail_revenue" => $pd['order_detail_revenue'],
                        "order_detail_cost_price" => $pd['order_detail_cost_price'],
                        "created_by" => auth()->user()->id
                    ]);

                    $before_stock = DB::table('products')->select('stock')->where('product_id', $pd['product_id'])->get();
                    // print($before_stock[0]->stock . '\n');
                    DB::table('products')
                        ->where('product_id', $pd['product_id'])
                        ->update(
                            [
                                'stock' => ($before_stock[0]->stock - $pd['amount'])
                            ]
                        );
                }

                return response()->json(['message' => 'บันทึกข้อมูลสำเร็จ', 'order_id' => $order_id], 200);
            } else {
                return response()->json(['message' => 'Error !', 'error message ' => $store], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }


        // return response()->json(['message' => 'บันทึกข้อมูลสำเร็จ'], 200);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
        ]);
        if (auth()->user()->role == 'admin') {
            $del = DB::table('products')
                ->where('product_id', $request->product_id)
                ->limit(1)
                ->update(
                    [
                        'is_active' => false,
                    ]
                );

            if ($del) {
                return response()->json(['message' => 'ลบข้อมูลสำเร็จ', 'product_id' => $request->product_id], 200);
            } else {
                return response()->json(['message' => 'รายการนี้ถูกลบไปแล้ว'], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }
        // return response()->json(['message' => 'Delete Successfully'], 200);
    }

    // public function edit(Request $request)
    // {

    //     $request->validate([
    //         'product_id' => 'required',
    //     ]);
    //     if (auth()->user()->role == 'admin') {
    //         $master = DB::table('products as p')
    //             ->select('p.*', 'u.name as created_name')
    //             ->where('p.product_id', $request->product_id)
    //             ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
    //             ->first();
    //         if ($master) {
    //             return response()->json(['data' => $master], 200);
    //         } else {
    //             return response()->json(['message' => 'Error !', 'error message ' => $master], 400);
    //         }
    //     } else {
    //         return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
    //     }
    // }

    // public function update(Request $request)
    // {
    //     $request->validate([
    //         'product_name' => 'required|string',
    //         'product_code' => 'required|string',
    //         'cost_price' => 'required|between:0,99.99',
    //         'wholesale_price' => 'required|between:0,99.99',
    //         'normal_price' => 'required|between:0,99.99',
    //         'stock' => 'required|integer|',
    //         'product_id' => 'required'
    //     ]);
    //     if (auth()->user()->role == 'admin') {
    //         $update = DB::table('products')
    //             ->where('product_id', $request->product_id)
    //             ->limit(1)
    //             ->update(
    //                 [
    //                     'product_code' => $request->product_code,
    //                     'product_name' => $request->product_name,
    //                     'cost_price' => $request->cost_price,
    //                     'wholesale_price' => $request->wholesale_price,
    //                     'normal_price' => $request->normal_price,
    //                     'stock' => $request->stock,
    //                     'description' => $request->description,
    //                     'updated_by' => auth()->user()->id,
    //                 ]
    //             );
    //         if ($update) {
    //             return response()->json(['message' => 'แก้ไขข้อมูลสำเร็จ'], 200);
    //         } else {
    //             return response()->json(['message' => 'Error !', 'error message ' => $update], 400);
    //         }
    //     } else {
    //         return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
    //     }
    // }



    //! Master
    public function master_product_find_one(Request $request)
    {

        $request->validate([
            'product_code' => 'required',
        ]);
        if (auth()->user()->role == 'admin' || auth()->user()->role == "staff") {
            $master = DB::table('products as p')
                ->select('product_id', 'p.product_code', 'p.product_name', 'p.cost_price', 'p.wholesale_price', 'p.normal_price', 'p.stock')
                ->where('p.product_code', $request->product_code)
                ->where('p.is_active', true)
                ->first();
            if ($master) {
                return response()->json(['data' => $master], 200);
            } else {
                return response()->json(['message' => 'Error !', 'error message ' => $master], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }
    }

    public function master_product_check_price(Request $request)
    {

        $request->validate([
            'product_code' => 'required',
        ]);
        if (auth()->user()->role == 'admin' || auth()->user()->role == "staff") {
            $master = DB::table('products as p')
                ->select('p.product_code', 'p.product_name', 'p.cost_price', 'p.wholesale_price', 'p.normal_price', 'p.stock')
                ->where('p.product_code', 'like', '%' . $request->product_code . '%')
                ->where('p.is_active', true)
                ->get();
            if ($master) {
                return response()->json(['data' => $master], 200);
            } else {
                return response()->json(['message' => 'Error !', 'error message ' => $master], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }
    }

    public function master_customer_find_one(Request $request)
    {
        $request->validate([
            'cus_tel' => 'required',
        ]);
        if (auth()->user()->role == 'admin' || auth()->user()->role == "staff") {
            $master = DB::table('customers as c')
                ->select('c.cus_name', 'c.cus_tel', 'c.cus_id')
                ->where('c.cus_tel', $request->cus_tel)
                ->where('c.is_active', true)
                ->first();
            if ($master) {
                return response()->json(['data' => $master], 200);
            } else {
                return response()->json(['message' => 'Error !', 'error message ' => $master], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }
    }
}
