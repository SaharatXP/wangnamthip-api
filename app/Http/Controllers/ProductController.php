<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required',
            'perPage' => 'required',
        ]);

        $perpage = $request->perpage;


        $datax = DB::table('products')
            ->where('is_active', true)->limit($perpage);;
        if ($request->search) {

            $datax->where(function ($q) use ($request) {
                $q->where('product_name', 'like', '%' . $request->search . '%')->orWhere('product_code', 'like', '%' . $request->search . '%');
            });
        }


        $data =  $datax->paginate(
            $request->perPage, // per page (may be get it from request)
            ['*'], // columns to select from table (default *, means all fields)
            'page', // page name that holds the page number in the query string
            $request->page // current page, default 1
        );
        if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff') {
            return response()->json(['data' => $data], 200);
        } else {
            abort(403);
        }
    }
    public function store(Request $request)
    {

        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:products,product_code',
            'cost_price' => 'required|between:0,99.99',
            'wholesale_price' => 'required|between:0,99.99',
            'normal_price' => 'required|between:0,99.99',
            'stock' => 'required|integer|'

        ]);
        if (auth()->user()->role == 'admin') {
            $store =  DB::table('products')->insert(
                [
                    'product_code' => $request->product_code,
                    'product_name' => $request->product_name,
                    'cost_price' => $request->cost_price,
                    'wholesale_price' => $request->wholesale_price,
                    'normal_price' => $request->normal_price,
                    'stock' => $request->stock,
                    'description' => $request->description,
                    'created_by' => auth()->user()->id,
                    'is_active' => true,
                ]
            );
            if ($store) {
                return response()->json(['message' => 'บันทึกข้อมูลสำเร็จ'], 200);
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

    public function edit(Request $request)
    {

        $request->validate([
            'product_id' => 'required',
        ]);
        if (auth()->user()->role == 'admin') {
            $master = DB::table('products as p')
                ->select('p.*', 'u.name as created_name')
                ->where('p.product_id', $request->product_id)
                ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
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

    public function update(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string',
            'cost_price' => 'required|between:0,99.99',
            'wholesale_price' => 'required|between:0,99.99',
            'normal_price' => 'required|between:0,99.99',
            'stock' => 'required|integer|',
            'product_id' => 'required'
        ]);
        if (auth()->user()->role == 'admin') {
            $update = DB::table('products')
                ->where('product_id', $request->product_id)
                ->limit(1)
                ->update(
                    [
                        'product_code' => $request->product_code,
                        'product_name' => $request->product_name,
                        'cost_price' => $request->cost_price,
                        'wholesale_price' => $request->wholesale_price,
                        'normal_price' => $request->normal_price,
                        'stock' => $request->stock,
                        'description' => $request->description,
                        'updated_by' => auth()->user()->id,
                    ]
                );
            if ($update) {
                return response()->json(['message' => 'แก้ไขข้อมูลสำเร็จ'], 200);
            } else {
                return response()->json(['message' => 'Error !', 'error message ' => $update], 400);
            }
        } else {
            return response()->json(['message' => 'คุณไม่มีสิทธ์เข้าถึง'], 403);
        }
    }
}
