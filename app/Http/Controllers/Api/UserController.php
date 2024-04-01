<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($flag)
    {
        $query = User::select('email','name');
        if($flag==1){
           $query->where('status',1);
        }else if($flag==0){
            $query->where('status',0);
        }else{
            return response()->json(['message' => 'invalid flag value', 'status'=>0],400);
        }
        $user = $query->get();
        if(count($user)>0){
            $response = [
                'message' =>count($user). 'user found',
                'status'=>1,
                'data'=>$user
            ];
            //return response()->json($response,200);

        }else{
            $response = [
                'message' =>count($user). 'user found',
                'status'=>0,
            ];
        }
        return response()->json($response,200);
        p($user);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=>['required'],
            'email'=>['required','email','unique:users,email'],
            'password'=>['required','min:8','confirmed'],
            'password_confirmation'=>['required']
        ]);
        if($validator->fails()){
            return response()->json(
                $validator->messages(),
                400,
            );
        }else{
            DB::beginTransaction();
            $data = [
                'name'=>$request->name,
                'email'=>$request->email,
                'password'=>Hash::make($request->password)
            ];
            try{
                $user = User::create($data);
                DB::commit();
            }catch(\Exception $e){
                DB::rollBack();
                p($e->getMessage());
                $user = null;

            }
        }
        if($user!=null){
            return response()->json(
                [
                    'message' => 'user registered successfully',

                ],200);
        }else{
            return response()->json(['message'=>'insertnal server error'],500);
        }
        p($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' =>'user not found',
                'status'=>0,
            
            ];
        }else{
            $response = [
                'message' =>'user found',
                'status'=>1,
                'data'=>$user
            ];
        }
        return response()->json($response,200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' =>'user does not exist',
                'status'=>0,
            ];
            $resCode = 404;
        }else{
            DB::beginTransaction();
            try{
                // Retrieve JSON data from the request
                $jsonData = $request->json()->all();

                $user->name = $jsonData['name'];
                $user->email = $jsonData['email'];
                $user->contact = $jsonData['contact'];
                $user->pincode = $jsonData['pincode'];
                $user->address = $jsonData['address'];
                $user->save();
                DB::commit();

            }catch(\Exception $e){
                DB::rollBack();
                $user=null;
            }
            if(is_null($user)){
                $response = [
                    'message' =>'internal server error',
                    'error_msg'=>$e->getMessage(),
                    'status'=>0,
                ];
                $resCode = 500;
            }else{
                $response = [
                    'message' =>'user data updated successfully',
                    'status'=>1,
                ];
                $resCode = 200;
            }
        }
        return response()->json(
            $response,
            $resCode
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' =>'user does not exist',
                'status'=>0,
            ];
            $resCode = 400;
        }else{
            DB::beginTransaction();
            try{
               $user->delete();
               DB::commit();
               $response = [
                'message' =>'user deleted successfully',
                'status'=>1,
            ];
            $resCode = 200;
            }catch(\Exception $e){
                DB::rollBack();
                $response = [
                    'message' =>'internal server error',
                    'status'=>0,
                ];
                $resCode = 500;
            }
        }
        return response()->json(
            $response,
            $resCode
        );
    }


    public function changePassword(Request $request, $id){
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' =>'user does not exist',
                'status'=>0,
            ];
            $resCode = 400;
        }else{
            $jsonData = $request->json()->all();
            if(Hash::check($jsonData['old_password'], $user->password)){

                if($jsonData['new_password']==$jsonData['confirm_password']){

                    DB::beginTransaction();
                    try{
                        $user->password = Hash::make($jsonData['new_password']);
                        $user->save();
                        DB::commit();
                    }catch(\Exception $e){
                        $user = null;
                        DB::rollBack();
                    }
                    if(is_null($user)){
                        $response = [
                            'message' =>'internal server error',
                            'error_msg'=>$e->getMessage(),
                            'status'=>0,
                        ];
                        $resCode = 500;
                    }else{
                        $response = [
                            'message' =>'password updated successfully',
                            'status'=>1,
                        ];
                        $resCode = 200;
                    }

                }else{
                    $response = [
                        'message' =>'new password and confirm password should be match',
                        'status'=>0,
                    ];
                    $resCode = 400;
                }

            }else{
                $response = [
                    'message' =>'old password does not match',
                    'status'=>0,
                ];
                $resCode = 400;
            }

        }

        return response()->json(
            $response,
            $resCode
        );
    }
}
