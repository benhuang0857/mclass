<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VerificationService;

/**
 * @OA\Tag(
 *     name="Verification",
 *     description="驗證碼相關 API"
 * )
 */
class VerificationController extends Controller
{
    protected VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * @OA\Post(
     *     path="/verification/email/send",
     *     summary="發送 Email 驗證碼",
     *     tags={"Verification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="驗證碼發送成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="驗證碼已發送"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_in", type="integer", example=600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="驗證錯誤"),
     *     @OA\Response(response=429, description="請求過於頻繁")
     * )
     */
    public function sendEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->verificationService->sendEmailCode($request->email);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => [
                    'wait_seconds' => $result['wait_seconds'] ?? 0,
                ],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'expires_in' => $result['expires_in'],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/verification/email/verify",
     *     summary="驗證 Email 驗證碼",
     *     tags={"Verification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="驗證成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="驗證成功"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="verified", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="驗證失敗"),
     *     @OA\Response(response=422, description="驗證錯誤")
     * )
     */
    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $result = $this->verificationService->verifyCode(
            $request->email,
            'email',
            $request->code
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'verified' => $result['verified'],
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ],
        ], $statusCode);
    }

    /**
     * @OA\Post(
     *     path="/verification/mobile/send",
     *     summary="發送手機驗證碼",
     *     tags={"Verification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(property="mobile", type="string", example="0912345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="驗證碼發送成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="驗證碼已發送"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_in", type="integer", example=600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="驗證錯誤"),
     *     @OA\Response(response=429, description="請求過於頻繁")
     * )
     */
    public function sendMobileCode(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09\d{8}$/',
        ], [
            'mobile.regex' => '手機號碼格式不正確，請使用 09 開頭的 10 位數字',
        ]);

        $result = $this->verificationService->sendMobileCode($request->mobile);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => [
                    'wait_seconds' => $result['wait_seconds'] ?? 0,
                ],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'expires_in' => $result['expires_in'],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/verification/mobile/verify",
     *     summary="驗證手機驗證碼",
     *     tags={"Verification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile", "code"},
     *             @OA\Property(property="mobile", type="string", example="0912345678"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="驗證成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="驗證成功"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="verified", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="驗證失敗"),
     *     @OA\Response(response=422, description="驗證錯誤")
     * )
     */
    public function verifyMobileCode(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09\d{8}$/',
            'code' => 'required|string|size:6',
        ], [
            'mobile.regex' => '手機號碼格式不正確',
        ]);

        $result = $this->verificationService->verifyCode(
            $request->mobile,
            'mobile',
            $request->code
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'verified' => $result['verified'],
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ],
        ], $statusCode);
    }

    /**
     * @OA\Get(
     *     path="/verification/code/{type}",
     *     summary="取得驗證碼（僅 APP_DEBUG=true 時可用）",
     *     tags={"Verification"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="驗證類型 (email 或 mobile)",
     *         @OA\Schema(type="string", enum={"email", "mobile"})
     *     ),
     *     @OA\Parameter(
     *         name="target",
     *         in="query",
     *         required=true,
     *         description="Email 或手機號碼",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得驗證碼",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="code", type="string", example="123456"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="此 API 僅在開發模式可用"),
     *     @OA\Response(response=404, description="找不到驗證碼")
     * )
     */
    public function getCode(Request $request, string $type)
    {
        // Only available in debug mode
        if (!config('app.debug')) {
            return response()->json([
                'success' => false,
                'message' => '此 API 僅在開發模式可用',
            ], 403);
        }

        // Validate type
        if (!in_array($type, ['email', 'mobile'])) {
            return response()->json([
                'success' => false,
                'message' => '無效的驗證類型',
            ], 400);
        }

        $request->validate([
            'target' => 'required|string',
        ]);

        $codeData = $this->verificationService->getCode($request->target, $type);

        if (!$codeData) {
            return response()->json([
                'success' => false,
                'message' => '找不到有效的驗證碼',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $codeData,
        ]);
    }
}
