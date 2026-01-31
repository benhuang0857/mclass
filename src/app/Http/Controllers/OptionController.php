<?php

namespace App\Http\Controllers;

use App\Models\LangType;
use App\Models\LevelType;
use App\Models\ReferralSourceType;
use App\Models\GoalType;
use App\Models\PurposeType;
use App\Models\EducationType;
use App\Models\SchoolType;
use App\Models\DepartmentType;
use App\Models\CertificateType;

/**
 * @OA\Tag(
 *     name="Options",
 *     description="選項資料 API"
 * )
 */
class OptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/options/register",
     *     summary="取得註冊頁面所需的所有選項",
     *     description="一次取得語言種類、等級種類、來源種類、目標種類、目的種類、學歷種類、學校種類、系所種類、證照種類",
     *     tags={"Options"},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得所有選項",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="lang_types", type="array", description="語言種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="英文"),
     *                         @OA\Property(property="slug", type="string", example="english")
     *                     )
     *                 ),
     *                 @OA\Property(property="level_types", type="array", description="等級種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="初階")
     *                     )
     *                 ),
     *                 @OA\Property(property="referral_source_types", type="array", description="得知來源種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="網路搜尋")
     *                     )
     *                 ),
     *                 @OA\Property(property="goal_types", type="array", description="目標種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="通過檢定")
     *                     )
     *                 ),
     *                 @OA\Property(property="purpose_types", type="array", description="目的種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="工作需求")
     *                     )
     *                 ),
     *                 @OA\Property(property="education_types", type="array", description="學歷種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="大學")
     *                     )
     *                 ),
     *                 @OA\Property(property="school_types", type="array", description="學校種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="台灣大學")
     *                     )
     *                 ),
     *                 @OA\Property(property="department_types", type="array", description="系所種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="資訊工程系")
     *                     )
     *                 ),
     *                 @OA\Property(property="certificate_types", type="array", description="證照種類",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="TOEIC")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function register()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    // 語言種類（用於 known_langs 和 learning_langs）
                    'lang_types' => LangType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 等級種類
                    'level_types' => LevelType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 得知來源種類
                    'referral_source_types' => ReferralSourceType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 目標種類
                    'goal_types' => GoalType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 目的種類
                    'purpose_types' => PurposeType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 學歷種類
                    'education_types' => EducationType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 學校種類
                    'school_types' => SchoolType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 系所種類
                    'department_types' => DepartmentType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),

                    // 證照種類
                    'certificate_types' => CertificateType::where('status', true)
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'note']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve options',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
