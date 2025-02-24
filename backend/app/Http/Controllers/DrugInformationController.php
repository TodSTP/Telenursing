<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DrugInformation;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class DrugInformationController extends Controller
{
    protected $drug_informations;
   
    public function __construct()
    {
        $this->drugInformation = new DrugInformation();
    }
    
    /* สร้างข้อมูลยา */
    public function createDrug(Request $request)
    {
    $validator = Validator::make($request->data, [
        'allergic_drug' => 'required|string|max:500',
        'my_drug' => 'required|string|max:500',
        'user_id' => 'required',
    ],[],[
        'allergic_drug' => 'ยาที่แพ้',
        'my_drug' => 'ยาที่ใช้ประจำ',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first()], 400);
    }

    try {
        $drugInformation = DrugInformation::create([
            'allergic_drug' => $request->data['allergic_drug'],
            'my_drug' => $request->data['my_drug'],
            'user_id' => $request->data['user_id'],
        ]);
        

        return response()->json(['message' => 'Drug Information created successfully', 'drugInformation' => $drugInformation], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to create Drug Information', 'error' => $e->getMessage()], 500);
    }
}


/* แสดงข้อมูลยา */
    /* ------------------------------------------------------ */
   
    /** * Display ตาม id */
    public function showDrug(string $id)
    {
        $drugInformation = DrugInformation::where('user_id', $id)->get();
        if ($drugInformation->isEmpty()) {
            return response()->json(['message' => 'Drug Information not found'], 404);
        }
        return $drugInformation;
    }
    

/* แก้ไขข้อมูลยา */
public function updateDrug(Request $request)
{
    $validator = Validator::make($request->all(), [
        'allergic_drug' => 'required|string|max:500',
        'my_drug' => 'required|string|max:500',
        'userId' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first()], 400);
    }

    try {
        $drugInformation = DrugInformation::where('user_id', $request->userId)->firstOrFail();

        $drugInformation->fill($request->all())->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Drug Information updated successfully',
            'drugInformation' => $drugInformation,
        ],201);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Drug Information not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to update Drug Information', 'error' => $e->getMessage()], 500);
    }
}


// ตรวจสอบว่ามีข้อมูลยารึยัง
public function checkDrugInformation(string $id)
    {
        // เช็คว่ามีข้อมูลในตารางหรือไม่สำหรับ userId ที่กำหนด
        $hasData = DrugInformation::where('user_id', $id)
                                  ->whereNotNull('allergic_drug')
                                  ->whereNotNull('my_drug')
                                  ->exists();

        return response()->json(['hasData' => $hasData]);
    }

}
