<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Member;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="認證相關 API"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="會員註冊",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account","password","email"},
     *             @OA\Property(property="account", type="string", example="user123"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="nickname", type="string", example="使用者暱稱")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="註冊成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="member", type="object"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=422, description="驗證錯誤")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'account' => 'required|string|unique:members',
            'password' => 'required|string|min:6',
            'email' => 'required|email|unique:members',
            'nickname' => 'nullable|string',
        ]);

        $member = Member::create([
            'account' => $request->account,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'nickname' => $request->nickname ?? $request->account,
            'status' => 1,
            'email_valid' => 0,
        ]);

        $token = Auth::guard('api')->login($member);

        return response()->json([
            'message' => 'User registered successfully',
            'member' => $member,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="會員登入",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account","password"},
     *             @OA\Property(property="account", type="string", example="user123"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="登入成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="member", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="認證失敗")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('account', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="取得當前使用者資訊",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得使用者資訊",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="account", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="nickname", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="未認證")
     * )
     */
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="會員登出",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="登出成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="刷新 Token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token 刷新成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     )
     * )
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'member' => Auth::guard('api')->user()
        ]);
    }
}
