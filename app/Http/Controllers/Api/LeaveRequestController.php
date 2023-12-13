<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\LeaveRequest;


class LeaveRequestController extends Controller
{
    public function index(){
        $leaverequest = DB::table('leave_requests')
                    ->join('users','users.id','=','leave_requests.id_user')
                    ->select('leave_requests.*','users.name')
                    ->get();

        if(count($leaverequest) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $leaverequest
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }


    public function show($id){
        $leaverequest = LeaveRequest::find($id);

        if(!is_null($leaverequest)){
            return response([
                'message' => 'Retrieve Leave Request Success',
                'data' => $leaverequest
            ],200);
        }

        return response([
            'message' => 'Leave Request Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_user'                   => 'required',
            'start_date'                => 'required|date_format:Y-m-d',
            'end_date'                  => 'required|date_format:Y-m-d',
            'total_date'                => 'nullable',
            'request_type'              => 'required',
            'day_time'                  => 'required',
            'overall_status'            => 'nullable',
            'approval_status'           => 'nullable',
            'document'                  => 'required|file|mimes:pdf',
            
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

            if (!is_null($request->file('document'))) {
                $file          = $request->file('document');
                $nama_file     = time() . "_" . $file->getClientOriginalName();
                $tujuan_upload = 'document';
                $file->move($tujuan_upload, $nama_file);
            } else {
                $nama_file = 'No Document';
            }

        $leaverequest = new LeaveRequest();
        
        $leaverequest->id_user                = $storeData['id_user'];
        $leaverequest->start_date             = $storeData['start_date'];
        $leaverequest->end_date               = $storeData['end_date'];
        $leaverequest->request_type           = $storeData['request_type'];
        $leaverequest->day_time               = $storeData['day_time'];
        $leaverequest->approval_status        = 0;
        $leaverequest->overall_status         = 'Up Coming';
        $leaverequest->document               = $nama_file;
            
        $leaverequest->save();
        //$leaverequest = LeaveRequest::create($storeData);

        $leaverequest->approval_status = 0;

        $leaverequest->overall_status = 'Up Coming';
         
        return response([
            'message' => 'Add Leave Request Success',
            'data' => $leaverequest,
        ],200);
    }

    public function destroy($id){
        $leaverequest = LeaveRequest::find($id);

        if(is_null($leaverequest)){
            return response([
                'message' => 'Leave Request Not Found',
                'data' => null
            ],404);
        }

        if($leaverequest->delete()){
            return response([
                'message' => 'Delete Leave Request Success',
                'data' => $leaverequest,
            ],200);
        }
        
        return response([
            'message' => 'Delete Leave Request Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $leaverequest = LeaveRequest::find($id);
        if(is_null($leaverequest)){
            return response([
                'message' => 'Leave Request Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_user'                   => 'required',
            'start_date'                => 'required|date_format:Y-m-d',
            'end_date'                  => 'required|date_format:Y-m-d',
            'total_date'                => 'nullable',
            'request_type'              => 'required',
            'day_time'                  => 'required',
            'overall_status'            => 'nullable',
            'approval_status'           => 'nullable',
            'document'                  => 'required|file|mimes:pdf',
            
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $leaverequest->id_user                   = $updateData['id_user'];
        $leaverequest->start_date                = $updateData['start_date'];
        $leaverequest->end_date                  = $updateData['end_date'];
        $leaverequest->request_type              = $updateData['request_type'];
        $leaverequest->day_time                  = $updateData['day_time'];
        $leaverequest->document                  = $updateData['document'];

        if (!is_null($request->file('document'))) {
            $file          = $request->file('document');
            $nama_file     = time() . "_" . $file->getClientOriginalName();
            $tujuan_upload = 'document';
            $file->move($tujuan_upload, $nama_file);
    
            $leaverequest->document     = $nama_file;
        }

        $leaverequest->approval_status = 0;

        $leaverequest->overall_status = 'Up Coming';
        
        if($leaverequest->save()){
            return response([
                'message' => 'Update Leave Request Success',
                'data' => $leaverequest,
            ],200);
        }

        return response([
            'message' => 'Update Leave Request Failed',
            'data' => null
        ],400);
    }

    public function updateStatusApproved(Request $request, $id){
        $leaverequest = LeaveRequest::find($id);
        if(is_null($leaverequest)){
            return response([
                'message' => 'Leave Request Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'approval_status'           => 'nullable',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $leaverequest->approval_status           = 1;
        
        if($leaverequest->save()){
            return response([
                'message' => 'Update Leave Request Success',
                'data' => $leaverequest,
            ],200);
        }

        return response([
            'message' => 'Update Leave Request Failed',
            'data' => null
        ],400);
    }

    //harus send API di postman, ini dibuat di frontend
    public function updateOverallStatus(){
        $now = Carbon::now()->format('Y-m-d');

        $lessorGreaterDate = DB::table('leave_requests')
                                    ->select('leave_requests.start_date')
                                    ->where('leave_requests.start_date','<=',$now)
                                    ->where('leave_requests.end_date','>=',$now)
                                    ->get();
        
        $greaterEndDate = DB::table('leave_requests')
                                    ->select('leave_requests.end_date')
                                    ->where('leave_requests.end_date','<=',$now)
                                    ->get();

        if($lessorGreaterDate != null){
            $updateoverallstatus = DB::table('leave_requests')
                                   ->where('leave_requests.approval_status','=',1)
                                   ->where('leave_requests.start_date','<=',$now)
                                   ->where('leave_requests.end_date','>=',$now)
                                   ->update(['leave_requests.overall_status' => 'In Progress']);               
        } 
        
        if ($greaterEndDate != null){
            $updateoverallstatus = DB::table('leave_requests')
                                   ->where('leave_requests.approval_status','=',1)
                                   ->where('leave_requests.end_date','<=',$now)
                                   ->update(['leave_requests.overall_status' => 'Complete']);
        }

        // if(count($lessEndDate) > 0){
        //     return response([
        //         'message' => 'Retrieve All Success',
        //         'data' => $lessEndDate
        //     ],200);
        // }
                        
        return response([
            'message' => 'Update Success',
            'data' => $updateoverallstatus
        ],200);
                      
        // return response([
        //     'message' => 'Empty',
        //     'data' => null
        // ],400);
    }
    
}
