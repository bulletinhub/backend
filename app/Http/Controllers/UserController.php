<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store']]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // validate fields
            $validateUser = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required'
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation Error',
                    'errors' => $validateUser->errors()
                ], 422);
            }

            // create
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            // generate jwt token
            $authController = new AuthController();
            $tokenInfo = $authController->loginOnlyToken();

            return response()->json([
                'status' => 'success',
                'message' => 'User Created Successfully',
                'data' => [
                    'user' => $user,
                    'token_data' => $tokenInfo->original
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'User Retrieved Successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $currentUserCredentials = [
                'email' => $request->current_email,
                'password' => $request->oldPassword
            ];
            $newUserData = [
                'name' => empty($request->name) ? $request->current_name : $request->name,
                'email' => empty($request->email) ? $request->current_email : $request->email,
                'password' => empty($request->password) ? $request->oldPassword : $request->password
            ];

            $authController = new AuthController();
            $areCredentialsValid = $authController->checkCredentials($currentUserCredentials);
            if (!$areCredentialsValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid old credentials',
                    'error' => 'Unauthorized'
                ], 401);
            }

            // validate fields
            $validateUser = Validator::make($newUserData, [
                'name' => 'required',
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation Error',
                    'errors' => $validateUser->errors()
                ], 422);
            }

            // update
            $user = User::findOrFail($id);
            $user->name = $newUserData['name'];
            $user->email = $newUserData['email'];
            $user->password = Hash::make($newUserData['password']);
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'User Updated Successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            User::destroy($id);

            return response()->json([
                'status' => 'success',
                'message' => 'User Deleted Successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
