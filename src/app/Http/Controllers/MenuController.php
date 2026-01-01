<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    /**
     * @OA\Get(
     *     path="/menus",
     *     summary="Get all menus",
     *     description="Retrieve a list of all menus with optional filtering. If role_id is provided, is_locked will be set based on permissions.",
     *     operationId="getMenusList",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="Filter by parent ID (null for root menus)",
     *         required=false,
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Filter by role ID and set is_locked state",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dashboard"),
     *                 @OA\Property(property="icon", type="string", example="fa-home"),
     *                 @OA\Property(property="url", type="string", example="/dashboard"),
     *                 @OA\Property(property="target", type="string", example="_self"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="display_order", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="visible_to_all", type="boolean", example=false),
     *                 @OA\Property(property="is_locked", type="boolean", example=false, description="False by default, or based on role if role_id provided"),
     *                 @OA\Property(property="note", type="string", nullable=true, example="Main dashboard menu")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Menu::with(['parent', 'roles']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Filter by parent_id (including null for root menus)
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter by role
        $roleId = $request->input('role_id');
        if ($roleId) {
            $query->forRole($roleId);
        }

        $menus = $query->ordered()->get();

        // Add is_locked state if role_id is provided
        if ($roleId) {
            $menus = $menus->map(function ($menu) use ($roleId) {
                $accessState = $menu->getAccessStateForRole($roleId);
                $menu->setAttribute('is_locked', $accessState['is_locked']);
                return $menu;
            });
        }

        return response()->json($menus);
    }

    /**
     * @OA\Post(
     *     path="/menus",
     *     summary="Create a new menu",
     *     description="Create a new menu item with optional role assignments",
     *     operationId="createMenu",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Menu data",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Dashboard"),
     *             @OA\Property(property="icon", type="string", maxLength=100, example="fa-home"),
     *             @OA\Property(property="url", type="string", maxLength=500, example="/dashboard"),
     *             @OA\Property(property="target", type="string", enum={"_self", "_blank"}, example="_self"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="display_order", type="integer", example=1),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="visible_to_all", type="boolean", example=false, description="Show menu to all users (locked if no permission)"),
     *             @OA\Property(property="note", type="string", example="Main dashboard menu"),
     *             @OA\Property(
     *                 property="role_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Menu created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'url' => 'nullable|string|max:500',
            'target' => ['nullable', Rule::in(['_self', '_blank'])],
            'parent_id' => 'nullable|exists:menus,id',
            'display_order' => 'integer|min:0',
            'status' => 'boolean',
            'visible_to_all' => 'boolean',
            'note' => 'nullable|string',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $roleIds = $validated['role_ids'] ?? [];
            unset($validated['role_ids']);

            $menu = Menu::create($validated);

            if (!empty($roleIds)) {
                $menu->roles()->attach($roleIds);
            }

            $menu->load(['parent', 'roles']);
            DB::commit();

            return response()->json($menu, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/menus/{id}",
     *     summary="Get a specific menu",
     *     description="Retrieve detailed information about a specific menu",
     *     operationId="getMenuById",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu not found"
     *     )
     * )
     */
    public function show($id)
    {
        $menu = Menu::with(['parent', 'children', 'roles'])->findOrFail($id);
        return response()->json($menu);
    }

    /**
     * @OA\Put(
     *     path="/menus/{id}",
     *     summary="Update a menu",
     *     description="Update an existing menu's information",
     *     operationId="updateMenu",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Menu data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Updated Dashboard"),
     *             @OA\Property(property="icon", type="string", maxLength=100, example="fa-dashboard"),
     *             @OA\Property(property="url", type="string", maxLength=500, example="/admin/dashboard"),
     *             @OA\Property(property="target", type="string", enum={"_self", "_blank"}, example="_blank"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="display_order", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="visible_to_all", type="boolean", example=true, description="Show menu to all users (locked if no permission)"),
     *             @OA\Property(property="note", type="string", example="Updated note"),
     *             @OA\Property(
     *                 property="role_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:100',
            'url' => 'nullable|string|max:500',
            'target' => ['nullable', Rule::in(['_self', '_blank'])],
            'parent_id' => 'nullable|exists:menus,id',
            'display_order' => 'integer|min:0',
            'status' => 'boolean',
            'visible_to_all' => 'boolean',
            'note' => 'nullable|string',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Custom validation for parent_id to prevent circular references
        if (isset($validated['parent_id']) && $validated['parent_id'] != null) {
            // Cannot set self as parent
            if ($validated['parent_id'] == $id) {
                return response()->json(['error' => 'Menu cannot be its own parent'], 422);
            }

            // Check for circular references
            $potentialParent = Menu::find($validated['parent_id']);
            if ($potentialParent && !$potentialParent->canBeParentOf($menu)) {
                return response()->json(['error' => 'Invalid parent: would create circular reference'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $roleIds = $validated['role_ids'] ?? null;
            unset($validated['role_ids']);

            $menu->update($validated);

            if ($roleIds !== null) {
                $menu->roles()->sync($roleIds);
            }

            $menu->load(['parent', 'roles']);
            DB::commit();

            return response()->json($menu);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/menus/{id}",
     *     summary="Delete a menu",
     *     description="Delete a specific menu (children will be cascade deleted)",
     *     operationId="deleteMenu",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Menu deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return response()->json(['message' => 'Menu deleted successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/menus/active",
     *     summary="Get active menus",
     *     description="Retrieve only active menus",
     *     operationId="getActiveMenus",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dashboard"),
     *                 @OA\Property(property="icon", type="string", example="fa-home"),
     *                 @OA\Property(property="url", type="string", example="/dashboard")
     *             )
     *         )
     *     )
     * )
     */
    public function getActiveMenus()
    {
        $menus = Menu::active()->ordered()->with(['parent', 'roles'])->get();
        return response()->json($menus);
    }

    /**
     * @OA\Get(
     *     path="/menus/tree/all",
     *     summary="Get full menu tree",
     *     description="Retrieve hierarchical menu tree structure. If role_id is provided, is_locked will be set based on permissions.",
     *     operationId="getMenuTree",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Show only active menus",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Role ID to determine is_locked state",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="is_locked", type="boolean", description="False by default, or based on role if role_id provided"),
     *                 @OA\Property(property="children", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function getTree(Request $request)
    {
        $query = Menu::with(['children.roles', 'roles'])->rootMenus();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $menus = $query->ordered()->get();

        // Add is_locked state if role_id is provided
        $roleId = $request->input('role_id');
        if ($roleId) {
            $menus = $this->addIsLockedToTree($menus, $roleId);
        }

        return response()->json($menus);
    }

    /**
     * @OA\Get(
     *     path="/menus/tree/role/{roleId}",
     *     summary="Get menu tree for specific role",
     *     description="Retrieve hierarchical menu tree filtered by role access. Menus with 'visible_to_all' set to true will be shown to all users but marked as 'is_locked' if the user doesn't have permission.",
     *     operationId="getMenuTreeForRole",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Show only active menus",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dashboard"),
     *                 @OA\Property(property="is_locked", type="boolean", example=false, description="True if menu is visible but user doesn't have permission"),
     *                 @OA\Property(property="children", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found"
     *     )
     * )
     */
    public function getTreeForRole(Request $request, $roleId)
    {
        // Verify role exists
        Role::findOrFail($roleId);

        $query = Menu::with(['children.roles', 'roles'])->rootMenus();

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $menus = $query->ordered()->get();

        // Filter menus by role
        $filteredMenus = $this->filterMenuTreeByRole($menus, $roleId);

        return response()->json($filteredMenus);
    }

    /**
     * @OA\Post(
     *     path="/menus/{id}/roles",
     *     summary="Assign roles to menu",
     *     description="Assign one or more roles to a menu (replaces existing roles)",
     *     operationId="assignRolesToMenu",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role IDs to assign",
     *         @OA\JsonContent(
     *             required={"role_ids"},
     *             @OA\Property(
     *                 property="role_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles assigned successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function assignRoles(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $menu->roles()->sync($validated['role_ids']);
        $menu->load('roles');

        return response()->json($menu);
    }

    /**
     * @OA\Delete(
     *     path="/menus/{id}/roles",
     *     summary="Remove roles from menu",
     *     description="Remove one or more roles from a menu",
     *     operationId="removeRolesFromMenu",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role IDs to remove",
     *         @OA\JsonContent(
     *             required={"role_ids"},
     *             @OA\Property(
     *                 property="role_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Roles removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function removeRoles(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $menu->roles()->detach($validated['role_ids']);
        $menu->load('roles');

        return response()->json([
            'message' => 'Roles removed successfully',
            'menu' => $menu
        ]);
    }

    /**
     * @OA\Put(
     *     path="/menus/reorder",
     *     summary="Batch update menu display order",
     *     description="Update display_order for multiple menus at once",
     *     operationId="reorderMenus",
     *     tags={"Menus"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of menu updates",
     *         @OA\JsonContent(
     *             required={"menus"},
     *             @OA\Property(
     *                 property="menus",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id", "display_order"},
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="display_order", type="integer", example=3)
     *                 ),
     *                 example={
     *                     {"id": 1, "display_order": 3},
     *                     {"id": 2, "display_order": 1},
     *                     {"id": 3, "display_order": 2}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menus reordered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Menus reordered successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.display_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['menus'] as $menuData) {
                Menu::where('id', $menuData['id'])
                    ->update(['display_order' => $menuData['display_order']]);
            }

            DB::commit();
            return response()->json(['message' => 'Menus reordered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recursively filter menu tree by role access and add locked state
     *
     * @param \Illuminate\Support\Collection $menus
     * @param int $roleId
     * @return \Illuminate\Support\Collection
     */
    private function filterMenuTreeByRole($menus, $roleId)
    {
        return $menus->map(function ($menu) use ($roleId) {
            // Get access state for this role
            $accessState = $menu->getAccessStateForRole($roleId);

            // Skip if not visible
            if (!$accessState['visible']) {
                return null;
            }

            // Add is_locked attribute to menu
            $menu->setAttribute('is_locked', $accessState['is_locked']);

            // Recursively filter children
            if ($menu->children && $menu->children->count() > 0) {
                $filteredChildren = $this->filterMenuTreeByRole($menu->children, $roleId);
                $menu->setRelation('children', $filteredChildren);
            }

            return $menu;
        })->filter()->values();
    }

    /**
     * Recursively add is_locked state to menu tree without filtering
     *
     * @param \Illuminate\Support\Collection $menus
     * @param int $roleId
     * @return \Illuminate\Support\Collection
     */
    private function addIsLockedToTree($menus, $roleId)
    {
        return $menus->map(function ($menu) use ($roleId) {
            // Get access state for this role
            $accessState = $menu->getAccessStateForRole($roleId);

            // Add is_locked attribute to menu
            $menu->setAttribute('is_locked', $accessState['is_locked']);

            // Recursively process children
            if ($menu->children && $menu->children->count() > 0) {
                $menu->setRelation('children', $this->addIsLockedToTree($menu->children, $roleId));
            }

            return $menu;
        });
    }
}
