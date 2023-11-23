<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
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

    public function dashboard_today(Request $request)
    {
        $request->validate([
            'date' => 'required',
        ]);

        $data = DB::table('orders as od')->select(
            DB::raw('count(order_id) as today_total_order'),
            DB::raw('sum(product_amount) as today_total_product'),
            DB::raw('sum(order_total_price) as today_total_price'),
            DB::raw('sum(order_revenue_price) as today_total_revenue'),
            DB::raw('(SELECT count(product_id) FROM products WHERE stock < 10) as out_of_stock_products')
            
        )
            ->whereDate('od.created_at', $request->date)
            ->get();
        
        $set_dept = DB::table('orders as od')->select(
            DB::raw('sum(order_total_price) as total_debt'), 
            DB::raw('count(order_id) as total_debt_bill'))
            
            ->where('od.status', 'เปิดบิล')
            ->get();
        if (auth()->user()->role == 'admin') {
            return response()->json(['data' => $data, 'dept'=> $set_dept], 200);
        } else {
            abort(403);
        }
    }

    public function dashboard_fdate_tdate(Request $request)
    {
        $request->validate([
            'from' => 'required',
            'to' => 'required',
        ]);

        $data = DB::table('orders as od')->select(
            DB::raw('count(order_id) as today_total_order'),
            DB::raw('sum(product_amount) as today_total_product'),
            DB::raw('sum(order_total_price) as today_total_price'),
            DB::raw('sum(order_revenue_price) as today_total_revenue'),
        )
            ->whereDate('od.created_at', ">=", $request->from)
            ->whereDate('od.created_at', "<=", $request->to)

            ->get();

        if (auth()->user()->role == 'admin') {
            return response()->json(['data' => $data], 200);
        } else {
            abort(403);
        }
    }
}
