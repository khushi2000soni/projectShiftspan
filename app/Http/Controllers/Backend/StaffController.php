<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\StaffDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\NotificationRequest;
use App\Http\Requests\Staff\StaffRequest;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use App\Models\Profile;
use App\Notifications\SendNotification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Hash;
use Auth;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StaffDataTable $dataTable)
    {
        abort_if(Gate::denies('staff_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try {
            return $dataTable->render('admin.staff.index');
        } catch (\Exception $e) {
            return abort(500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        abort_if(Gate::denies('staff_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if($request->ajax()) {
            try{
                if(auth()->user()->is_super_admin){
                    $subAdmins = User::whereHas('roles', function($q){ $q->where('id', config('constant.roles.sub_admin')); })->pluck('name', 'uuid');
                    $viewHTML = view('admin.staff.create', compact('subAdmins'))->render();
                    return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
                }
                $viewHTML = view('admin.staff.create')->render();
                return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
            } 
            catch (\Exception $e) {
                \Log::error($e->getMessage().' '.$e->getFile().' '.$e->getLine().' '.$e->getCode());          
                return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
            }
        }
        return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StaffRequest $request)
    {
        abort_if(Gate::denies('staff_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try {
            if ($request->ajax()){
                DB::beginTransaction();
                $input = $request->validated();

                if(!(auth()->user()->is_super_admin)){
                    $input['company_id'] = auth()->user()->id;
                } else {
                    $input['company_id'] = User::where('uuid', $request->company_id)->first()->id;
                }
                
                // $input['username'] = $request->name;
                $input['password'] = Hash::make($request->password);

                $staff = User::create($input);
                $input['user_id'] = $staff->id;
                $staff->profile()->create($input);
                $staff->roles()->sync([config('constant.roles.staff')]);
                
                if($staff && $request->hasFile('image')){
                    uploadImage($staff, $request->image, 'user/profile-images',"user_profile", 'original', 'save', null);
                }

                if($staff && $request->has('relevant_training')){
                    uploadImage($staff, $request->relevant_training, 'staff/relevant-training',"user_training_doc", 'original', 'save', null);
                }

                if($staff && $request->has('dbs_certificate')){
                    uploadImage($staff, $request->dbs_certificate, 'staff/dbs-certificate',"user_dbs_certificate", 'original', 'save', null);
                }

                if($staff && $request->has('cv_image')){
                    uploadImage($staff, $request->cv_image, 'staff/cv-image',"user_cv", 'original', 'save', null);
                }

                if($staff && $request->has('staff_budge')){

                    uploadImage($staff, $request->staff_budge, 'staff/staff-budge',"user_staff_budge", 'original', 'save', null);
                }

                if($staff && $request->has('dbs_check')){
                    uploadImage($staff, $request->dbs_check, 'staff/dbs-check',"user_dbs_check", 'original', 'save', null);
                }

                if($staff && $request->has('training_check')){
                    uploadImage($staff, $request->training_check, 'staff/training-check',"user_training_check", 'original', 'save', null);
                }

                DB::commit();

                if($staff){
                    return response()->json([
                        'success' => true,
                        'message' => trans('cruds.staff.title_singular').' '.trans('messages.crud.add_record'),
                    ]);
                }
            }

            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::info($e->getMessage().' '.$e->getFile().' '.$e->getLine());          
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        abort_if(Gate::denies('staff_view'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if($request->ajax()) {
            try{
                $user = User::where('uuid', $id)->first();
             
                $rating = getStaffRating($user->id);

                $type = $request->type;

                $viewHTML = view('admin.staff.show', compact('user','rating','type'))->render();
                return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
            } 
            catch (\Exception $e) {
                
                // dd($e->getMessage().' '.$e->getFile().' '.$e->getLine());

                return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
            }
        }
        return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        abort_if(Gate::denies('staff_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try {
            if($request->ajax()) {
                $staff = User::where('uuid', $id)->first();
                if(auth()->user()->is_super_admin){
                    $subAdmins = User::whereHas('roles', function($q){ $q->where('id', config('constant.roles.sub_admin')); })->pluck('name', 'id');
                    $viewHTML = view('admin.staff.edit', compact('staff', 'subAdmins'))->render();
                    return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
                }
                $viewHTML = view('admin.staff.edit', compact('staff'))->render();
                return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
            } 
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        } catch (\Exception $e) {
            \Log::info($e->getMessage().' '.$e->getFile().' '.$e->getLine()); 
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StaffRequest $request, string $id)
    {
        abort_if(Gate::denies('staff_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try {
            if ($request->ajax()){
                $user = User::where('uuid', $id)->first();
                DB::beginTransaction();
                $input = $request->validated();

                if(!(auth()->user()->is_super_admin)){
                    $input['company_id'] = auth()->user()->id;
                } else {
                    $input['company_id'] = User::where('id', $request->company_id)->first()->id;
                }

                $staff = $user->update($input);
                $profileData = $request->only([
                    'dob',
                    'previous_name',
                    'national_insurance_number',
                    'address',
                    'education',
                    'prev_emp_1',
                    'prev_emp_2',
                    'reference_1',
                    'reference_2',
                    'date_sign',
                    'is_criminal',
                    'is_rehabilite',
                    'is_enquire',
                    'is_health_issue',
                    'is_statement',
                ]);
                $profile = Profile::updateOrCreate(['user_id' => $user->id], $profileData);
                $user->roles()->sync([config('constant.roles.staff')]);
                
                if($user && $request->hasFile('image')){
                    $uploadImageId = $user->profileImage ? $user->profileImage->id : null;
                    uploadImage($user, $request->image, 'user/profile-images',"user_profile", 'original', $user->profileImage ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }

                if($user && $request->has('relevant_training')){
                    $uploadImageId = $user->trainingDocument ? $user->trainingDocument->id : null;
                    uploadImage($user, $request->relevant_training, 'staff/relevant-training',"user_training_doc", 'original', $user->trainingDocument ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }

                if($user && $request->has('dbs_certificate')){
                    $uploadImageId = $user->dbsCertificate ? $user->dbsCertificate->id : null;
                    uploadImage($user, $request->dbs_certificate, 'staff/dbs-certificate',"user_dbs_certificate", 'original', $user->dbsCertificate ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }
                
                if($user && $request->has('cv_image')){
                    $uploadImageId = $user->cv ? $user->cv->id : null;
                    uploadImage($user, $request->cv_image, 'staff/cv-image',"user_cv", 'original', $user->cv ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }
                
                if($user && $request->has('staff_budge')){
                    $uploadImageId = $user->staffBudge ? $user->staffBudge->id : null;
                    uploadImage($user, $request->staff_budge, 'staff/staff-budge',"user_staff_budge", 'original', $user->staffBudge ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }
                
                if ($user && $request->has('dbs_check')) {
                    $uploadImageId = $user->dbsCheck ? $user->dbsCheck->id : null;
                    uploadImage($user, $request->file('dbs_check'), 'staff/dbs-check', "user_dbs_check", 'original', $uploadImageId ? 'update' : 'save', $uploadImageId ?? null);
                }                
                
                if($user && $request->has('training_check')){
                    $uploadImageId = $user->trainingCheck ? $user->trainingCheck->id : null;
                    uploadImage($user, $request->training_check, 'staff/training-check',"user_training_check", 'original', $user->trainingCheck ? 'update' : 'save', $uploadImageId ? $uploadImageId : null);
                }

                DB::commit();

                if($user){
                    return response()->json([
                        'success' => true,
                        'message' => trans('cruds.staff.title_singular').' '.trans('messages.crud.update_record'),
                    ]);
                }
            }

            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::info($e->getMessage().' '.$e->getFile().' '.$e->getLine());          
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        abort_if(Gate::denies('staff_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $user = User::where('uuid', $id)->first();
            DB::beginTransaction();
            try {

                $groupIds = $user->groups()->pluck('id')->toArray();
                Message::whereIn('group_id', $groupIds)->delete();
                Group::whereIn('id', $groupIds)->delete();

                $user->delete();
                DB::commit();
                
                return response()->json($response = [
                    'success'    => true,
                    'message'    => trans('messages.crud.delete_record'),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();      
                // dd($e->getMessage().' '.$e->getFile().' '.$e->getLine());          
                return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
            }
        }
        return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
    }

    public function massDestroy(StaffRequest $request)
    {
        abort_if(Gate::denies('staff_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            DB::beginTransaction();
            try {
                $ids = $request->input('ids');

                $getAllUsers = User::whereIn('uuid', $ids)->get();
                foreach($getAllUsers as $user){
                    $groupIds = $user->groups()->pluck('id')->toArray();
                    Message::whereIn('group_id', $groupIds)->delete();
                    Group::whereIn('id', $groupIds)->delete();
                }

                $users = User::whereIn('uuid', $ids)->delete();
                DB::commit();
                
                if($users){
                    return response()->json($response = [
                        'success'    => true,
                        'message'    => trans('messages.crud.delete_record'),
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();      
                return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
            }
        }
        return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
    }

    public function updateStaffStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'exists:users,uuid',
        ]);

        if (!$validator->passes()) {
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }else{
            DB::beginTransaction();
            try {
                $user = User::where('uuid', $request->id)->first();

                $updateStatus = $user->is_active == 1 ? 0 : 1;
                
                $user->update(['is_active' => $updateStatus]);
                
                $sendNotificationUser = User::where('uuid', $request->id)->first();
                
                /* Send Notification */
                $key = array_search(config('constant.notification_subject.announcements'), config('constant.notification_subject'));
                if(($updateStatus == 1) && is_null($sendNotificationUser->last_login_at)){
                    $messageData = [
                        'notification_type' => array_search(config('constant.subject_notification_type.registration_completion_active'), config('constant.subject_notification_type')),
                        'section'           => $key,
                        'subject'           => trans('messages.registration_completion_subject'),
                        'message'           => trans('messages.registration_completion_message', [
                            'username'      => $user->name,
                            'listed_business' => auth()->user()->is_super_admin ? getSetting('site_title') ? getSetting('site_title') : config('app.name')  : $user->company->name,
                        ]),
                    ];
                    
                }elseif( ($updateStatus == 1) && (!is_null($sendNotificationUser->last_login_at)) ){
                    $messageData = [
                        'notification_type' => array_search(config('constant.subject_notification_type.user_account_active'), config('constant.subject_notification_type')),
                        'section'           => $key,
                        'subject'           => trans('messages.user_account_activate_subject'),
                        'message'           => trans('messages.user_account_activate_message', [
                            'username'      => $user->name,
                            'admin'         => auth()->user()->is_super_admin ? getSetting('site_title') ? getSetting('site_title') : config('app.name')  : $user->company->name,
                        ]),
                    ];
                }else{
                    $messageData = [
                        'notification_type' => array_search(config('constant.subject_notification_type.user_account_deactive'), config('constant.subject_notification_type')),
                        'section'           => $key,
                        'subject'           => trans('messages.user_account_deactivate_subject'),
                        'message'           => trans('messages.user_account_deactivate_message', [
                            'username'      => $user->name,
                            'admin'         => auth()->user()->is_super_admin ? getSetting('site_title') ? getSetting('site_title') : config('app.name')  : $user->company->name,
                        ]),
                    ];
                }
                
               
                Notification::send($sendNotificationUser, new SendNotification($messageData));
                
                DB::commit();
                $response = [
                    'success'    => true,
                    'message'    => trans('messages.crud.status_update'),
                ];
                return response()->json($response);
            } catch (\Exception $e) {
                DB::rollBack();                
                return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
            }
        }
    }

    public function createNotification(Request $request)
    {
        // abort_if(Gate::denies('staff_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try{
            if($request->ajax()) {
                $user = Auth::user();
                $staffsNotify = '';
                if($user->is_super_admin){
                    $staffsNotify = User::where('is_active', 1)->whereNotNull('company_id')->whereHas('company', function ($query) {
                            $query->where('is_active', true);
                        })
                        ->orderBy('id', 'desc')
                        ->get();
                }else{
                    $staffsNotify = User::where('is_active',1)->where('company_id', $user->id)->orderBy('id', 'desc')->get();
                }

                $viewHTML = view('admin.staff.notification.create', compact('staffsNotify'))->render();
                return response()->json(array('success' => true, 'htmlView'=>$viewHTML));
            }
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }catch (\Exception $e) {
            // dd($e->getMessage().' '.$e->getFile().' '.$e->getLine().' '.$e->getCode());
            \Log::error($e->getMessage().' '.$e->getFile().' '.$e->getLine().' '.$e->getCode());          
            return response()->json(['success' => false, 'error_type' => 'something_error', 'error' => trans('messages.error_message')], 400 );
        }
    }

    /* Notification Store */
    public function notificationStore(NotificationRequest $request)
    {
        // abort_if(Gate::denies('notification_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        try {
            $input = $request->validated();            
            $input['notification_type'] = 'send_notification';

            DB::beginTransaction();

            $users = User::whereIn('uuid', $input['staffs'])->get();

            if($input['section'] == 'help_chat'){

                foreach($users as $staff){
                    $userIds= [];
                    $group = Group::whereHas('users',function($query) use($staff){
                        $query->where('user_id',$staff->id);
                    })->where('group_name',$input['subject'])->first();

                    if(!$group){
                        $groupDetail['group_name'] = $input['subject'];
                        $groupCreated = Group::create($groupDetail);
                        if($groupCreated){
                            $group = $groupCreated;
                            $userIds[] = $staff->id;
                            $userIds[] = $staff->company->id;
                            $userIds[] = auth()->user()->is_super_admin ? auth()->user()->id : $staff->company->created_by;

                            $groupCreated->users()->attach($userIds);
                        }
                    }

                    $input['group_uuid'] = $group->uuid;
                    
                    //Start to create message
                    $messageInput['group_id'] = $group->id;
                    $messageInput['content']  = $input['message'];
                    $messageInput['type']     = 'text';
                    $messageCreated = Message::create($messageInput);
                    //End to create message

                    if(auth()->user()->company){
                        //Send notification to super admin
                        Notification::send($staff->company->createdBy, new SendNotification($input));
                    }
                    
                     Notification::send($staff, new SendNotification($input));
                   
                }
                
            }else{
                
                Notification::send($users, new SendNotification($input));
            
                
            }

           

            if($input['section'] != 'help_chat'){

                //If User is login as super admin
                if(auth()->user()->is_super_admin){
                    $companies = User::whereIn('uuid', $input['companies'])->get();
                    Notification::send($companies, new SendNotification($input));
                }

                //If User is login as company or sub admin
                if(auth()->user()->is_sub_admin){ 
                    $superAdmin = User::whereHas('roles',function($query){
                        $query->where('id',config('constant.roles.super_admin'));
                    })->first();
                    Notification::send($superAdmin, new SendNotification($input));
                }
                
            }

            DB::commit();
            return response()->json([
                'success'    => true,
                'message'    => trans('messages.crud.message_sent'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            \Log::error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            return response()->json([
                'success' => false, 
                'error_type' => 'something_error', 
                'error' => trans('messages.error_message'),
                'error_details'=>$e->getMessage().' '.$e->getFile().' '.$e->getLine(),
            ], 400 );
        }
        
    }
}
