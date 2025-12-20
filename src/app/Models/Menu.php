<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'url',
        'target',
        'parent_id',
        'display_order',
        'status',
        'note',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'display_order' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Parent menu
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Child menus (with recursive loading)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->with('children')
                    ->orderBy('display_order', 'asc');
    }

    /**
     * All descendants (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->with('descendants')
                    ->orderBy('display_order', 'asc');
    }

    /**
     * Roles that can access this menu
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role')
                    ->withTimestamps();
    }

    /**
     * Query scope: Active menus only
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Query scope: Root menus (no parent)
     */
    public function scopeRootMenus($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Query scope: Ordered by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Query scope: Filter by role
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->whereHas('roles', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        });
    }

    /**
     * Query scope: Filter by multiple roles
     */
    public function scopeForRoles($query, array $roleIds)
    {
        return $query->whereHas('roles', function ($q) use ($roleIds) {
            $q->whereIn('role_id', $roleIds);
        });
    }

    /**
     * Check if this is a root menu
     */
    public function isRootMenu(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this is a submenu
     */
    public function isSubmenu(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get menu depth level (0 for root, 1 for child, etc.)
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Get the root menu (traverse to top level)
     */
    public function getRootMenu(): Menu
    {
        if ($this->isRootMenu()) {
            return $this;
        }

        $parent = $this->parent;
        while ($parent && $parent->parent) {
            $parent = $parent->parent;
        }

        return $parent ?: $this;
    }

    /**
     * Check if menu has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if menu is accessible by a specific role
     * If no roles are assigned, menu is public (accessible to all)
     */
    public function isAccessibleByRole($roleId): bool
    {
        // If no roles assigned, menu is public
        if ($this->roles()->count() === 0) {
            return true;
        }

        return $this->roles()->where('role_id', $roleId)->exists();
    }

    /**
     * Check if a menu can be set as parent (prevent circular references)
     */
    public function canBeParentOf(Menu $menu): bool
    {
        // Cannot be parent of itself
        if ($this->id === $menu->id) {
            return false;
        }

        // Check if the proposed parent is a descendant
        $ancestorIds = $menu->getAncestorIds();

        return !in_array($this->id, $ancestorIds);
    }

    /**
     * Get array of ancestor IDs (for breadcrumbs and circular reference prevention)
     */
    public function getAncestorIds(): array
    {
        $ancestorIds = [];
        $parent = $this->parent;

        while ($parent) {
            $ancestorIds[] = $parent->id;
            $parent = $parent->parent;
        }

        return $ancestorIds;
    }
}
