<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Profile;
use App\Models\Contact;
use App\Models\KnownLang;
use App\Models\LearningLang;
use App\Models\Level;
use App\Models\ReferralSource;
use App\Models\Goal;
use App\Models\Purpose;
use App\Models\HighestEducation;
use App\Models\School;
use App\Models\Department;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    /**
     * @OA\Get(
     *     path="/members",
     *     summary="Get all members",
     *     description="Retrieve a list of all members with their profiles, contacts, and all related background information through pivot tables",
     *     operationId="getMembersList",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", example="john.doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", example=true),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-01-11T10:30:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-01-11T10:30:00Z"),
     *                 @OA\Property(
     *                     property="profile",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="member_id", type="integer", example=1),
     *                     @OA\Property(property="lastname", type="string", example="Doe"),
     *                     @OA\Property(property="firstname", type="string", example="John"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                     @OA\Property(property="job", type="string", example="Software Engineer")
     *                 ),
     *                 @OA\Property(
     *                     property="contact",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="city", type="string", example="Taipei"),
     *                     @OA\Property(property="region", type="string", example="Xinyi"),
     *                     @OA\Property(property="address", type="string", example="No. 123, Section 1, Xinyi Road"),
     *                     @OA\Property(property="mobile", type="string", example="0912345678"),
     *                     @OA\Property(property="mobile_valid", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="known_langs",
     *                     type="array",
     *                     description="Currently known languages",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="lang_type_id", type="integer", example=1),
     *                         @OA\Property(property="member_id", type="integer", example=1),
     *                         @OA\Property(
     *                             property="lang_type",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="English"),
     *                             @OA\Property(property="slug", type="string", example="english")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="learning_langs",
     *                     type="array",
     *                     description="Languages to learn",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="lang_type_id", type="integer", example=2),
     *                         @OA\Property(property="lang_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Japanese")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="levels",
     *                     type="array",
     *                     description="Current skill levels",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="level_type_id", type="integer", example=2),
     *                         @OA\Property(property="level_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Intermediate")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="referral_sources",
     *                     type="array",
     *                     description="How they found us",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(property="goals", type="array", description="Learning goals", @OA\Items(type="object")),
     *                 @OA\Property(property="purposes", type="array", description="Learning purposes", @OA\Items(type="object")),
     *                 @OA\Property(property="highest_educations", type="array", description="Highest education (max 1)", @OA\Items(type="object")),
     *                 @OA\Property(property="schools", type="array", description="Schools attended", @OA\Items(type="object")),
     *                 @OA\Property(property="departments", type="array", description="Departments/Majors", @OA\Items(type="object")),
     *                 @OA\Property(property="certificates", type="array", description="Certificates obtained", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $members = Member::with([
            'profile',
            'contact',
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
        ])->get();
        return response()->json($members);
    }

    /**
     * @OA\Post(
     *     path="/members",
     *     summary="Create a new member",
     *     description="Create a new member with profile, contact, and background information using pivot tables. All background fields are optional arrays of type IDs.",
     *     operationId="createMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Member data including required member and profile objects, optional contact and background relation arrays",
     *         @OA\JsonContent(
     *             required={"member", "profile"},
     *             @OA\Property(
     *                 property="member",
     *                 type="object",
     *                 required={"nickname", "account", "email", "email_valid", "password", "status"},
     *                 @OA\Property(property="nickname", type="string", maxLength=255, description="Member's display name", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", maxLength=255, description="Unique account identifier", example="john.doe"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, description="Unique email address", example="john@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", description="Email verification status", example=true),
     *                 @OA\Property(property="password", type="string", format="password", minLength=8, description="Account password (min 8 characters)", example="password123"),
     *                 @OA\Property(property="status", type="boolean", description="Account active status", example=true)
     *             ),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 required={"lastname", "firstname", "gender", "birthday", "job"},
     *                 @OA\Property(property="lastname", type="string", maxLength=255, description="Last name", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", maxLength=255, description="First name", example="John"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, description="Gender", example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", description="Date of birth (YYYY-MM-DD)", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", maxLength=255, description="Current occupation", example="Software Engineer")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 description="Contact information (optional)",
     *                 @OA\Property(property="city", type="string", maxLength=255, description="City", example="Taipei"),
     *                 @OA\Property(property="region", type="string", maxLength=255, description="Region or district", example="Xinyi"),
     *                 @OA\Property(property="address", type="string", maxLength=255, description="Detailed address", example="No. 123, Section 1, Xinyi Road"),
     *                 @OA\Property(property="mobile", type="string", maxLength=20, description="Mobile phone number (unique)", example="0912345678"),
     *                 @OA\Property(property="mobile_valid", type="boolean", description="Mobile verification status", example=true)
     *             ),
     *             @OA\Property(property="known_langs", type="array", description="Currently known language IDs (optional)", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="learning_langs", type="array", description="Languages to learn IDs (optional)", @OA\Items(type="integer"), example={3, 4}),
     *             @OA\Property(property="levels", type="array", description="Current level IDs (optional)", @OA\Items(type="integer"), example={2}),
     *             @OA\Property(property="referral_sources", type="array", description="Referral source type IDs (optional)", @OA\Items(type="integer"), example={1}),
     *             @OA\Property(property="goals", type="array", description="Goal type IDs (optional)", @OA\Items(type="integer"), example={1, 2, 3}),
     *             @OA\Property(property="purposes", type="array", description="Purpose type IDs (optional)", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="highest_educations", type="array", maxItems=1, description="Highest education type ID (optional, max 1)", @OA\Items(type="integer"), example={3}),
     *             @OA\Property(property="schools", type="array", description="School type IDs (optional)", @OA\Items(type="integer"), example={1, 2}),
     *             @OA\Property(property="departments", type="array", description="Department type IDs (optional)", @OA\Items(type="integer"), example={5}),
     *             @OA\Property(property="certificates", type="array", description="Certificate type IDs (optional)", @OA\Items(type="integer"), example={1, 3, 5})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Member created successfully with all relations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *             @OA\Property(property="account", type="string", example="john.doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="email_valid", type="boolean", example=true),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-01-12T10:30:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-01-12T10:30:00Z"),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="John"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", example="Software Engineer")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city", type="string", example="Taipei")
     *             ),
     *             @OA\Property(property="known_langs", type="array", @OA\Items(ref="#/components/schemas/PivotWithLangType")),
     *             @OA\Property(property="learning_langs", type="array", @OA\Items(ref="#/components/schemas/PivotWithLangType")),
     *             @OA\Property(property="levels", type="array", @OA\Items(ref="#/components/schemas/PivotWithLevelType")),
     *             @OA\Property(property="referral_sources", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="goals", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="purposes", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="highest_educations", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="schools", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="certificates", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="member.email", type="array", @OA\Items(type="string", example="The member.email has already been taken."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member.nickname' => 'required|string|max:255',
            'member.account' => 'required|string|max:255|unique:members,account',
            'member.email' => 'required|email|max:255|unique:members,email',
            'member.email_valid' => 'required|boolean',
            'member.password' => 'required|string|min:8',
            'member.status' => 'required|boolean',

            'profile.lastname' => 'required|string|max:255',
            'profile.firstname' => 'required|string|max:255',
            'profile.gender' => 'required|in:male,female,other',
            'profile.birthday' => 'required|date',
            'profile.job' => 'required|string|max:255',

            'contact.city' => 'sometimes|required|string|max:255',
            'contact.region' => 'sometimes|required|string|max:255',
            'contact.address' => 'sometimes|required|string|max:255',
            'contact.mobile' => 'sometimes|required|string|max:20|unique:contacts,mobile',
            'contact.mobile_valid' => 'sometimes|required|boolean',

            // 新的關聯結構
            'known_langs' => 'sometimes|array',
            'known_langs.*' => 'exists:lang_types,id',

            'learning_langs' => 'sometimes|array',
            'learning_langs.*' => 'exists:lang_types,id',

            'levels' => 'sometimes|array',
            'levels.*' => 'exists:level_types,id',

            'referral_sources' => 'sometimes|array',
            'referral_sources.*' => 'exists:referral_source_types,id',

            'goals' => 'sometimes|array',
            'goals.*' => 'exists:goal_types,id',

            'purposes' => 'sometimes|array',
            'purposes.*' => 'exists:purpose_types,id',

            'highest_educations' => 'sometimes|array|max:1',
            'highest_educations.*' => 'exists:education_types,id',

            'schools' => 'sometimes|array',
            'schools.*' => 'exists:school_types,id',

            'departments' => 'sometimes|array',
            'departments.*' => 'exists:department_types,id',

            'certificates' => 'sometimes|array',
            'certificates.*' => 'exists:certificate_types,id',
        ]);

        DB::beginTransaction();
        try {
            // Hash the password before creating the member
            if (isset($validated['member']['password'])) {
                $validated['member']['password'] = Hash::make($validated['member']['password']);
            }

            // 創建會員
            $member = Member::create($validated['member']);

            // 創建 Profile
            $member->profile()->create($validated['profile']);

            // 創建 Contact (可選)
            if (isset($validated['contact'])) {
                $member->contact()->create($validated['contact']);
            }

            // 創建關聯資料
            $this->syncMemberRelations($member, $validated);

            DB::commit();

            return response()->json($member->load([
                'profile',
                'contact',
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
            ]), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create member',
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
        if (isset($validated['known_langs'])) {
            foreach ($validated['known_langs'] as $langTypeId) {
                $member->knownLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Learning Languages
        if (isset($validated['learning_langs'])) {
            foreach ($validated['learning_langs'] as $langTypeId) {
                $member->learningLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Levels
        if (isset($validated['levels'])) {
            foreach ($validated['levels'] as $levelTypeId) {
                $member->levels()->create(['level_type_id' => $levelTypeId]);
            }
        }

        // Referral Sources
        if (isset($validated['referral_sources'])) {
            foreach ($validated['referral_sources'] as $referralSourceTypeId) {
                $member->referralSources()->create(['referral_source_type_id' => $referralSourceTypeId]);
            }
        }

        // Goals
        if (isset($validated['goals'])) {
            foreach ($validated['goals'] as $goalTypeId) {
                $member->goals()->create(['goal_type_id' => $goalTypeId]);
            }
        }

        // Purposes
        if (isset($validated['purposes'])) {
            foreach ($validated['purposes'] as $purposeTypeId) {
                $member->purposes()->create(['purpose_type_id' => $purposeTypeId]);
            }
        }

        // Highest Educations (限制最多1個)
        if (isset($validated['highest_educations'])) {
            foreach ($validated['highest_educations'] as $educationTypeId) {
                $member->highestEducations()->create(['education_type_id' => $educationTypeId]);
            }
        }

        // Schools
        if (isset($validated['schools'])) {
            foreach ($validated['schools'] as $schoolTypeId) {
                $member->schools()->create(['school_type_id' => $schoolTypeId]);
            }
        }

        // Departments
        if (isset($validated['departments'])) {
            foreach ($validated['departments'] as $departmentTypeId) {
                $member->departments()->create(['department_type_id' => $departmentTypeId]);
            }
        }

        // Certificates
        if (isset($validated['certificates'])) {
            foreach ($validated['certificates'] as $certificateTypeId) {
                $member->certificates()->create(['certificate_type_id' => $certificateTypeId]);
            }
        }
    }

    /**
     * @OA\Get(
     *     path="/members/{id}",
     *     summary="Get specific member",
     *     description="Retrieve detailed information about a specific member including profile, contact, background, and related language/level types",
     *     operationId="getMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *             @OA\Property(property="account", type="string", example="john.doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="email_valid", type="boolean", example=true),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-11T10:30:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-11T10:30:00Z"),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="John"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", example="Software Engineer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="city", type="string", example="Taipei"),
     *                 @OA\Property(property="region", type="string", example="Xinyi"),
     *                 @OA\Property(property="address", type="string", example="No. 123, Section 1, Xinyi Road"),
     *                 @OA\Property(property="mobile", type="string", example="0912345678"),
     *                 @OA\Property(property="mobile_valid", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(
     *                 property="background",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="goals", type="array", @OA\Items(type="string"), example={"Career advancement", "Skill development"}),
     *                 @OA\Property(property="purposes", type="array", @OA\Items(type="string"), example={"Professional certification", "Personal interest"}),
     *                 @OA\Property(property="highest_education", type="string", example="Master's Degree"),
     *                 @OA\Property(property="schools", type="array", @OA\Items(type="string"), example={"National Taiwan University", "MIT"}),
     *                 @OA\Property(property="departments", type="array", @OA\Items(type="string"), example={"Computer Science", "Engineering"}),
     *                 @OA\Property(property="certificates", type="array", @OA\Items(type="string"), example={"AWS Certified", "PMP"}),
     *                 @OA\Property(
     *                     property="languages",
     *                     type="array",
     *                     description="Related language types",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="English"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="levels",
     *                     type="array",
     *                     description="Related skill levels",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Beginner"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Member] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $member = Member::with([
            'profile',
            'contact',
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
        ])->findOrFail($id);
        return response()->json($member);
    }

    /**
     * @OA\Put(
     *     path="/members/{id}",
     *     summary="Update member information",
     *     description="Update an existing member's information including profile, contact, and background data. All fields are optional - only provide fields you want to update.",
     *     operationId="updateMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Member data to update (all fields optional)",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="member",
     *                 type="object",
     *                 description="Member basic information",
     *                 @OA\Property(property="nickname", type="string", maxLength=255, description="Member's display name", example="JaneDoe"),
     *                 @OA\Property(property="account", type="string", maxLength=255, description="Unique account identifier", example="jane.doe"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, description="Unique email address", example="jane@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", description="Email verification status", example=true),
     *                 @OA\Property(property="password", type="string", format="password", minLength=8, description="New password (min 8 characters)", example="newpassword123"),
     *                 @OA\Property(property="status", type="boolean", description="Account active status", example=true)
     *             ),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 description="Profile information",
     *                 @OA\Property(property="lastname", type="string", maxLength=255, description="Last name", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", maxLength=255, description="First name", example="Jane"),
     *                 @OA\Property(property="gender", type="string", description="Gender", example="female"),
     *                 @OA\Property(property="birthday", type="string", format="date", description="Date of birth (YYYY-MM-DD)", example="1992-05-15"),
     *                 @OA\Property(property="job", type="string", maxLength=255, description="Current occupation", example="Data Scientist")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 description="Contact information",
     *                 @OA\Property(property="city", type="string", maxLength=255, description="City", example="Taipei"),
     *                 @OA\Property(property="region", type="string", maxLength=255, description="Region or district", example="Daan"),
     *                 @OA\Property(property="address", type="string", maxLength=255, description="Detailed address", example="No. 456, Section 2, Roosevelt Road"),
     *                 @OA\Property(property="mobile", type="string", maxLength=20, description="Mobile phone number (unique)", example="0987654321"),
     *                 @OA\Property(property="mobile_valid", type="boolean", description="Mobile verification status", example=true)
     *             ),
     *             @OA\Property(
     *                 property="background",
     *                 type="object",
     *                 description="Educational and professional background",
     *                 @OA\Property(property="goals", type="array", description="Learning goals", @OA\Items(type="string"), example={"Leadership development", "Technical mastery"}),
     *                 @OA\Property(property="purposes", type="array", description="Learning purposes", @OA\Items(type="string"), example={"Career transition", "Continuous learning"}),
     *                 @OA\Property(property="highest_education", type="string", maxLength=255, description="Highest education level", example="PhD"),
     *                 @OA\Property(property="schools", type="array", description="Schools attended", @OA\Items(type="string"), example={"Stanford University", "Harvard"}),
     *                 @OA\Property(property="departments", type="array", description="Departments or majors", @OA\Items(type="string"), example={"Data Science", "Statistics"}),
     *                 @OA\Property(property="certificates", type="array", description="Professional certificates", @OA\Items(type="string"), example={"Google Cloud Certified", "Scrum Master"}),
     *                 @OA\Property(property="languages", type="array", description="Language IDs from lang_types table (will sync)", @OA\Items(type="integer"), example={1, 2, 3}),
     *                 @OA\Property(property="levels", type="array", description="Skill level IDs from level_types table (will sync)", @OA\Items(type="integer"), example={2, 3})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JaneDoe"),
     *             @OA\Property(property="account", type="string", example="jane.doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="email_valid", type="boolean", example=true),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="Jane"),
     *                 @OA\Property(property="gender", type="string", example="female"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1992-05-15"),
     *                 @OA\Property(property="job", type="string", example="Data Scientist")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="city", type="string", example="Taipei"),
     *                 @OA\Property(property="region", type="string", example="Daan"),
     *                 @OA\Property(property="address", type="string", example="No. 456, Section 2, Roosevelt Road"),
     *                 @OA\Property(property="mobile", type="string", example="0987654321")
     *             ),
     *             @OA\Property(
     *                 property="background",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="goals", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="purposes", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="highest_education", type="string", example="PhD"),
     *                 @OA\Property(property="schools", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="departments", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="certificates", type="array", @OA\Items(type="string")),
     *                 @OA\Property(
     *                     property="languages",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="English")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="levels",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Intermediate")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Member] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="member.email", type="array", @OA\Items(type="string", example="The member.email has already been taken."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'member.nickname' => 'sometimes|string|max:255',
            'member.account' => 'sometimes|string|max:255|unique:members,account,' . $id,
            'member.email' => 'sometimes|email|max:255|unique:members,email,' . $id,
            'member.email_valid' => 'sometimes|boolean',
            'member.password' => 'sometimes|string|min:8',
            'member.status' => 'sometimes|boolean',

            'profile.lastname' => 'sometimes|string|max:255',
            'profile.firstname' => 'sometimes|string|max:255',
            'profile.gender' => 'sometimes|string',
            'profile.birthday' => 'sometimes|date',
            'profile.job' => 'sometimes|string|max:255',

            'contact.city' => 'sometimes|string|max:255',
            'contact.region' => 'sometimes|string|max:255',
            'contact.address' => 'sometimes|string|max:255',
            'contact.mobile' => 'sometimes|string|max:20|unique:contacts,mobile,' . ($member->contact->id ?? 'NULL'),
            'contact.mobile_valid' => 'sometimes|boolean',

            // 新的關聯結構
            'known_langs' => 'sometimes|array',
            'known_langs.*' => 'exists:lang_types,id',

            'learning_langs' => 'sometimes|array',
            'learning_langs.*' => 'exists:lang_types,id',

            'levels' => 'sometimes|array',
            'levels.*' => 'exists:level_types,id',

            'referral_sources' => 'sometimes|array',
            'referral_sources.*' => 'exists:referral_source_types,id',

            'goals' => 'sometimes|array',
            'goals.*' => 'exists:goal_types,id',

            'purposes' => 'sometimes|array',
            'purposes.*' => 'exists:purpose_types,id',

            'highest_educations' => 'sometimes|array|max:1',
            'highest_educations.*' => 'exists:education_types,id',

            'schools' => 'sometimes|array',
            'schools.*' => 'exists:school_types,id',

            'departments' => 'sometimes|array',
            'departments.*' => 'exists:department_types,id',

            'certificates' => 'sometimes|array',
            'certificates.*' => 'exists:certificate_types,id',
        ]);

        DB::beginTransaction();
        try {
            // Hash the password before updating if password is provided
            if (isset($validated['member']['password'])) {
                $validated['member']['password'] = Hash::make($validated['member']['password']);
            }

            // 更新會員基本資料
            $member->update($validated['member'] ?? []);

            // 更新 Profile
            if (isset($validated['profile'])) {
                if ($member->profile) {
                    $member->profile->update($validated['profile']);
                } else {
                    $member->profile()->create($validated['profile']);
                }
            }

            // 更新 Contact
            if (isset($validated['contact'])) {
                if ($member->contact) {
                    $member->contact->update($validated['contact']);
                } else {
                    $member->contact()->create($validated['contact']);
                }
            }

            // 更新關聯資料 (先刪除舊的，再建立新的)
            $this->updateMemberRelations($member, $validated);

            DB::commit();

            return response()->json($member->load([
                'profile',
                'contact',
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
            ]));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新會員關聯資料
     */
    private function updateMemberRelations(Member $member, array $validated)
    {
        // Known Languages
        if (isset($validated['known_langs'])) {
            $member->knownLangs()->delete();
            foreach ($validated['known_langs'] as $langTypeId) {
                $member->knownLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Learning Languages
        if (isset($validated['learning_langs'])) {
            $member->learningLangs()->delete();
            foreach ($validated['learning_langs'] as $langTypeId) {
                $member->learningLangs()->create(['lang_type_id' => $langTypeId]);
            }
        }

        // Levels
        if (isset($validated['levels'])) {
            $member->levels()->delete();
            foreach ($validated['levels'] as $levelTypeId) {
                $member->levels()->create(['level_type_id' => $levelTypeId]);
            }
        }

        // Referral Sources
        if (isset($validated['referral_sources'])) {
            $member->referralSources()->delete();
            foreach ($validated['referral_sources'] as $referralSourceTypeId) {
                $member->referralSources()->create(['referral_source_type_id' => $referralSourceTypeId]);
            }
        }

        // Goals
        if (isset($validated['goals'])) {
            $member->goals()->delete();
            foreach ($validated['goals'] as $goalTypeId) {
                $member->goals()->create(['goal_type_id' => $goalTypeId]);
            }
        }

        // Purposes
        if (isset($validated['purposes'])) {
            $member->purposes()->delete();
            foreach ($validated['purposes'] as $purposeTypeId) {
                $member->purposes()->create(['purpose_type_id' => $purposeTypeId]);
            }
        }

        // Highest Educations
        if (isset($validated['highest_educations'])) {
            $member->highestEducations()->delete();
            foreach ($validated['highest_educations'] as $educationTypeId) {
                $member->highestEducations()->create(['education_type_id' => $educationTypeId]);
            }
        }

        // Schools
        if (isset($validated['schools'])) {
            $member->schools()->delete();
            foreach ($validated['schools'] as $schoolTypeId) {
                $member->schools()->create(['school_type_id' => $schoolTypeId]);
            }
        }

        // Departments
        if (isset($validated['departments'])) {
            $member->departments()->delete();
            foreach ($validated['departments'] as $departmentTypeId) {
                $member->departments()->create(['department_type_id' => $departmentTypeId]);
            }
        }

        // Certificates
        if (isset($validated['certificates'])) {
            $member->certificates()->delete();
            foreach ($validated['certificates'] as $certificateTypeId) {
                $member->certificates()->create(['certificate_type_id' => $certificateTypeId]);
            }
        }
    }    

    /**
     * @OA\Delete(
     *     path="/members/{id}",
     *     summary="Delete member",
     *     description="Delete a member and all associated profile, contact, and background information. This operation will cascade delete all related data including language and level associations.",
     *     operationId="deleteMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Member deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Member] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during deletion"
     *     )
     * )
     */
    public function destroy($id)
    {
        $member = Member::findOrFail($id);

        DB::beginTransaction();
        try {
            // 刪除所有關聯資料 (由於 migration 設定了 onDelete('cascade')，這些會自動刪除)
            // 但為了明確性，我們手動刪除
            $member->knownLangs()->delete();
            $member->learningLangs()->delete();
            $member->levels()->delete();
            $member->referralSources()->delete();
            $member->goals()->delete();
            $member->purposes()->delete();
            $member->highestEducations()->delete();
            $member->schools()->delete();
            $member->departments()->delete();
            $member->certificates()->delete();

            // 刪除 profile 和 contact
            $member->profile()->delete();
            $member->contact()->delete();

            // 刪除會員
            $member->delete();

            DB::commit();
            return response()->json(['message' => 'Member deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
