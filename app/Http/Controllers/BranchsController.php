<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BranchsController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required',
            'perPage' => 'required',
        ]);

        $perpage = $request->perpage;

        $data = DB::table('branchs')
            ->where('is_active', true)
            ->limit($perpage)
            ->paginate(
                $request->perPage, // per page (may be get it from request)
                ['*'], // columns to select from table (default *, means all fields)
                'page', // page name that holds the page number in the query string
                $request->page // current page, default 1
            );
        if (auth()->user()->role == 'admin') {
            return response()->json(['data' => $data], 200);
        } else {
            abort(403);
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'branch_name' => 'required|string',
        ]);
        if (auth()->user()->role == 'admin') {
            $store =  DB::table('branchs')->insert(
                [
                    'branch_name' => $request->branch_name,
                    'created_by' => auth()->user()->id
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
            'branch_id' => 'required',
        ]);
        if (auth()->user()->role == 'admin') {
            $del = DB::table('branchs')
                ->where('branch_id', $request->branch_id)
                ->limit(1)
                ->update(
                    [
                        'is_active' => false,
                    ]
                );

            if ($del) {
                return response()->json(['message' => 'ลบข้อมูลสำเร็จ', 'branch_id' => $request->branch_id], 200);
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
            'branch_id' => 'required',
        ]);
        if (auth()->user()->role == 'admin') {
            $master = DB::table('branchs as p')
                ->select('p.*', 'u.name as created_name')
                ->where('p.branch_id', $request->branch_id)
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
            'branch_name' => 'required|string',
            'branch_id' => 'required'
        ]);
        if (auth()->user()->role == 'admin') {
            $update = DB::table('branchs')
                ->where('branch_id', $request->branch_id)
                ->limit(1)
                ->update(
                    [
                        'branch_name' => $request->branch_name,
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
