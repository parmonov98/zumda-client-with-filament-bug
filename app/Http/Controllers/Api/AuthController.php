<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

//    /**
//     * @OA\Post(
//     *     path="/users",
//     *     summary="Adds a new user",
//     *     @OA\RequestBody(
//     *         @OA\MediaType(
//     *             mediaType="application/json",
//     *             @OA\Schema(
//     *                 @OA\Property(
//     *                     property="id",
//     *                     type="string"
//     *                 ),
//     *                 @OA\Property(
//     *                     property="name",
//     *                     type="string"
//     *                 ),
//     *                 example={"id": 10, "name": "Jessica Smith"}
//     *             )
//     *         )
//     *     ),
//     *     @OA\Response(
//     *         response=200,
//     *         description="OK"
//     *     )
//     * )
//     */
    /**
     * @OA\Post(path="/auth/register",
     * tags={"1. Auth vs Register user"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="string"),
     *               @OA\Property(property="password_confirmation", type="string")
     *            )
     *        )
     *    ),
     *   @OA\Response(response=200,description="Successful executed"),
     *   @OA\Response(response=401, description="Token is not valid.")
     * )
     */
    /**
     * Create User
     * @param Request $request
     * @return User
     */

    public function register(Request $request){
        try {
            //Validated
            $validateUser = Validator::make($request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required'
                ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(path="/auth/login",
     * tags={"1. Auth vs Register user"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="string"),
     *            )
     *        )
     *    ),
     *   @OA\Response(response=200,description="Successful logged"),
     *   @OA\Response(response=401, description="Token is not valid.")
     * )
     */
    /**
     * Login The User
     * @param Request $request
     * @return User
     */
    public function login(Request $request)
    {
        try {
            $validateUser = Validator::make($request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if(!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
