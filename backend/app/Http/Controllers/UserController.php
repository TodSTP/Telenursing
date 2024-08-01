<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = new User();
    }


    /*** list รายชื่อทั้งหมด.*/
    public function indexuser()
    {
        $data = $this->user->all();
        if ($data->isEmpty()) {
            return response()->json(['message' => 'ไม่มีข้อมูล'], 200);
        }
        return $data;
    }

    /** * list ชื่อตาม id */
    public function showuserwithid(string $id)
    {
        $user = $this->user->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return $user;
    }
    


    /** * ลบ */
    public function destroyuser(string $id)
    {
        $user = $this->user->find($id);
        if (!$user) {
            throw ValidationException::withMessages(['message' => 'Mysugar not found']);
        }
        $user->delete();
        return response()->json(['message' => 'Mysugar deleted successfully']);
    }


    /* edituser */
    public function updateuser(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
                        'dob' => 'nullable|date|before_or_equal:' . Carbon::now()->format('Y-m-d'),
                        'phone' => 'nullable|string|max:10',
                        'address' => 'nullable|string|max:500',
                        'status' => 'nullable|boolean',
                    ],[],[
                        'address' => 'ที่อยู่',
                    ]);
                
                    if ($validator->fails()) {
                        return response()->json(['message' => $validator->errors()->first()], 400);
                    }
                
                    try {
            
                        $User = User::find($id); // ใช้ find() เพื่อค้นหาข้อมูลผู้ใช้ที่ต้องการอัปเดต
                        $User->fname=$request->fname;
                        $User->lname=$request->lname;
                        $User->idcard=$request->idcard;
                        $User->dob=$request->dob;
                        $User->phone=$request->phone;
                        $User->address=$request->address;
                        $User->status=$request->status;
                        // $User->user_id=$request->user_id; // ลบส่วนนี้ออก
                        $User->save();
            
                
                        return response()->json(['message' => 'Information updated successfully', 'user' => $User], 201);
                    } catch (\Exception $e) {
                        return response()->json(['message' => 'Failed to create Information', 'error' => $e->getMessage()], 500);
                    }
    }
}
