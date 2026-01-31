<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Services\VerificationService;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="認證相關 API"
 * )
 */
class AuthController extends Controller
{
    protected VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="會員註冊（需先完成 Email 驗證）",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="會員註冊資料，包含必填的 member 和 profile，以及可選的 contact 和關聯資料",
     *         @OA\JsonContent(
     *             required={"member", "profile"},
     *             @OA\Property(
     *                 property="member",
     *                 type="object",
     *                 required={"nickname", "account", "email", "password"},
     *                 @OA\Property(property="nickname", type="string", maxLength=255, description="會員暱稱", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", maxLength=255, description="帳號（唯一）", example="john.doe"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, description="Email（唯一，需先驗證）", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", minLength=8, description="密碼（至少8字元）", example="password123")
     *             ),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 required={"lastname", "firstname", "gender", "birthday", "job"},
     *                 @OA\Property(property="lastname", type="string", maxLength=255, description="姓", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", maxLength=255, description="名", example="John"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, description="性別", example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", description="生日 (YYYY-MM-DD)", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", maxLength=255, description="職業", example="Software Engineer")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 description="聯絡資訊（可選，若填寫 mobile 則需先完成 SMS 驗證）",
     *                 @OA\Property(property="city", type="string", maxLength=255, description="城市", example="Taipei"),
     *                 @OA\Property(property="region", type="string", maxLength=255, description="地區", example="Xinyi"),
     *                 @OA\Property(property="address", type="string", maxLength=255, description="地址", example="No. 123, Section 1, Xinyi Road"),
     *                 @OA\Property(property="mobile", type="string", maxLength=20, description="手機號碼（唯一，需先完成 SMS 驗證）", example="0912345678")
     *             ),
     *             @OA\Property(property="known_langs", type="array", description="所具備的語言專長 IDs（可選）", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="learning_langs", type="array", description="欲學習的語言別 IDs（可選）", @OA\Items(type="integer"), example={3, 4}),
     *             @OA\Property(property="levels", type="array", description="目前程度 IDs（可選）", @OA\Items(type="integer"), example={2}),
     *             @OA\Property(property="referral_sources", type="array", description="得知來源 IDs（可選）", @OA\Items(type="integer"), example={1}),
     *             @OA\Property(property="goals", type="array", description="學習目標 IDs（可選）", @OA\Items(type="integer"), example={1, 2, 3}),
     *             @OA\Property(property="purposes", type="array", description="學習目的 IDs（可選）", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="highest_educations", type="array", maxItems=1, description="最高學歷 ID（可選，最多1個）", @OA\Items(type="integer"), example={3}),
     *             @OA\Property(property="schools", type="array", description="就讀學校 IDs（可選）", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="departments", type="array", description="就讀科系 IDs（可選）", @OA\Items(type="integer"), example={5}),
     *             @OA\Property(property="certificates", type="array", description="語言證照 IDs（可選）", @OA\Items(type="integer"), example={1, 3, 5}),
     *             @OA\Property(property="roles", type="array", description="角色 IDs（必填，至少選擇一個）", @OA\Items(type="integer"), example={1})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="註冊成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object", description="會員資料含所有關聯"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Email 或手機尚未驗證"),
     *     @OA\Response(response=422, description="驗證錯誤"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // Member 必填欄位
            'member.nickname' => 'required|string|max:255',
            'member.account' => 'required|string|max:255|unique:members,account',
            'member.email' => 'required|email|max:255|unique:members,email',
            'member.password' => 'required|string|min:8',

            // Profile 必填欄位
            'profile.lastname' => 'required|string|max:255',
            'profile.firstname' => 'required|string|max:255',
            'profile.gender' => 'required|in:male,female,other',
            'profile.birthday' => 'required|date',
            'profile.job' => 'required|string|max:255',

            // Contact 可選欄位（若填寫 mobile 需先完成 SMS 驗證）
            'contact.city' => 'nullable|string|max:255',
            'contact.region' => 'nullable|string|max:255',
            'contact.address' => 'nullable|string|max:255',
            'contact.mobile' => 'nullable|string|max:20|regex:/^09\d{8}$/|unique:contacts,mobile',

            // 關聯資料（全部可選）
            'known_langs' => 'nullable|array',
            'known_langs.*' => 'exists:lang_types,id',
            'learning_langs' => 'nullable|array',
            'learning_langs.*' => 'exists:lang_types,id',
            'levels' => 'nullable|array',
            'levels.*' => 'exists:level_types,id',
            'referral_sources' => 'nullable|array',
            'referral_sources.*' => 'exists:referral_source_types,id',
            'goals' => 'nullable|array',
            'goals.*' => 'exists:goal_types,id',
            'purposes' => 'nullable|array',
            'purposes.*' => 'exists:purpose_types,id',
            'highest_educations' => 'nullable|array|max:1',
            'highest_educations.*' => 'exists:education_types,id',
            'schools' => 'nullable|array',
            'schools.*' => 'exists:school_types,id',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:department_types,id',
            'certificates' => 'nullable|array',
            'certificates.*' => 'exists:certificate_types,id',

            // 角色（必填，至少選擇一個）
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        // Check if email has been verified (within 30 minutes)
        if (!$this->verificationService->isTargetVerified($validated['member']['email'], 'email')) {
            return response()->json([
                'success' => false,
                'message' => '請先完成 Email 驗證',
                'error' => 'email_not_verified',
            ], 400);
        }

        // Check if mobile has been verified (within 30 minutes) - only if mobile is provided
        $mobileVerified = false;
        if (!empty($validated['contact']['mobile'])) {
            if (!$this->verificationService->isTargetVerified($validated['contact']['mobile'], 'mobile')) {
                return response()->json([
                    'success' => false,
                    'message' => '請先完成手機驗證',
                    'error' => 'mobile_not_verified',
                ], 400);
            }
            $mobileVerified = true;
        }

        DB::beginTransaction();
        try {
            // Hash password
            $validated['member']['password'] = Hash::make($validated['member']['password']);
            $validated['member']['status'] = true;
            $validated['member']['email_valid'] = true; // Already verified

            // 創建會員
            $member = Member::create($validated['member']);

            // 創建 Profile（必填）
            $member->profile()->create($validated['profile']);

            // 創建 Contact（可選，有任何欄位就創建）
            if (!empty($validated['contact'])) {
                $contactData = $validated['contact'];
                // 如果手機已驗證，自動設置 mobile_valid = true
                if ($mobileVerified) {
                    $contactData['mobile_valid'] = true;
                }
                $member->contact()->create($contactData);
            }

            // 創建關聯資料
            $this->syncMemberRelations($member, $validated);

            // 綁定角色
            $member->roles()->attach($validated['roles']);

            DB::commit();

            $token = Auth::guard('api')->login($member);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $member->load([
                    'profile',
                    'contact',
                    'roles',
                    'knownLangs.langType',
                    'learningLangs.langType',
                    'levels.levelType',
                    'referralSources.referralSourceType',
                    'goals.goalType',
                    'purposes.purposeType',
                    'highestEducations.educationType',
                    'schools.schoolType',
                    'departments.departmentType',
                    'certificates.certificateType',
                ]),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to register member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 同步會員關聯資料
     */
    private function syncMemberRelations(Member $member, array $validated)
    {
        // Known Languages
        if (!empty($validated['known_langs'])) {
            foreach ($validated['known_langs'] as $langTypeId) {
                $member->knownLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Learning Languages
        if (!empty($validated['learning_langs'])) {
            foreach ($validated['learning_langs'] as $langTypeId) {
                $member->learningLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Levels
        if (!empty($validated['levels'])) {
            foreach ($validated['levels'] as $levelTypeId) {
                $member->levels()->create(['level_type_id' => $levelTypeId]);
            }
        }

        // Referral Sources
        if (!empty($validated['referral_sources'])) {
            foreach ($validated['referral_sources'] as $referralSourceTypeId) {
                $member->referralSources()->create(['referral_source_type_id' => $referralSourceTypeId]);
            }
        }

        // Goals
        if (!empty($validated['goals'])) {
            foreach ($validated['goals'] as $goalTypeId) {
                $member->goals()->create(['goal_type_id' => $goalTypeId]);
            }
        }

        // Purposes
        if (!empty($validated['purposes'])) {
            foreach ($validated['purposes'] as $purposeTypeId) {
                $member->purposes()->create(['purpose_type_id' => $purposeTypeId]);
            }
        }

        // Highest Educations
        if (!empty($validated['highest_educations'])) {
            foreach ($validated['highest_educations'] as $educationTypeId) {
                $member->highestEducations()->create(['education_type_id' => $educationTypeId]);
            }
        }

        // Schools
        if (!empty($validated['schools'])) {
            foreach ($validated['schools'] as $schoolTypeId) {
                $member->schools()->create(['school_type_id' => $schoolTypeId]);
            }
        }

        // Departments
        if (!empty($validated['departments'])) {
            foreach ($validated['departments'] as $departmentTypeId) {
                $member->departments()->create(['department_type_id' => $departmentTypeId]);
            }
        }

        // Certificates
        if (!empty($validated['certificates'])) {
            foreach ($validated['certificates'] as $certificateTypeId) {
                $member->certificates()->create(['certificate_type_id' => $certificateTypeId]);
            }
        }
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
     *     summary="取得當前使用者完整資訊",
     *     description="取得當前登入會員的完整資料，包含個人資料、聯絡資訊、角色權限、選單、以及所有關聯資料",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得使用者資訊",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", example="john.doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", example=true),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="profile", type="object", description="個人資料"),
     *                 @OA\Property(property="contact", type="object", description="聯絡資訊"),
     *                 @OA\Property(property="roles", type="array", description="角色列表", @OA\Items(type="object")),
     *                 @OA\Property(property="menus", type="array", description="可存取的選單", @OA\Items(type="object")),
     *                 @OA\Property(property="known_langs", type="array", description="熟悉語言", @OA\Items(type="object")),
     *                 @OA\Property(property="learning_langs", type="array", description="學習語言", @OA\Items(type="object")),
     *                 @OA\Property(property="levels", type="array", description="目前程度", @OA\Items(type="object")),
     *                 @OA\Property(property="referral_sources", type="array", description="得知來源", @OA\Items(type="object")),
     *                 @OA\Property(property="goals", type="array", description="學習目標", @OA\Items(type="object")),
     *                 @OA\Property(property="purposes", type="array", description="學習目的", @OA\Items(type="object")),
     *                 @OA\Property(property="highest_educations", type="array", description="最高學歷", @OA\Items(type="object")),
     *                 @OA\Property(property="schools", type="array", description="就讀學校", @OA\Items(type="object")),
     *                 @OA\Property(property="departments", type="array", description="就讀科系", @OA\Items(type="object")),
     *                 @OA\Property(property="certificates", type="array", description="語言證照", @OA\Items(type="object")),
     *                 @OA\Property(property="notification_preferences", type="array", description="通知偏好設定", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="未認證")
     * )
     */
    public function me()
    {
        $member = Auth::guard('api')->user();

        // 載入所有關聯資料
        $member->load([
            'profile',
            'contact',
            'roles.menus' => function ($query) {
                $query->where('status', true)->orderBy('display_order');
            },
            'knownLangs.langType',
            'learningLangs.langType',
            'levels.levelType',
            'referralSources.referralSourceType',
            'goals.goalType',
            'purposes.purposeType',
            'highestEducations.educationType',
            'schools.schoolType',
            'departments.departmentType',
            'certificates.certificateType',
            'notificationPreferences',
        ]);

        // 整理可存取的選單（根據角色合併並去重）
        $menus = collect();
        foreach ($member->roles as $role) {
            $menus = $menus->merge($role->menus);
        }
        $menus = $menus->unique('id')->sortBy('display_order')->values();

        // 組合回傳資料
        $data = $member->toArray();
        $data['menus'] = $menus;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
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
