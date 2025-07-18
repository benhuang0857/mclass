<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Product;
use App\Models\ClubCourseInfo;
use App\Models\Notice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    protected $searchableModels = [
        'members' => Member::class,
        'products' => Product::class,
        'club_course_infos' => ClubCourseInfo::class,
        'notices' => Notice::class,
        'orders' => Order::class,
    ];

    protected $searchableFields = [
        'members' => ['nickname', 'account', 'email'],
        'products' => ['name', 'code'],
        'club_course_infos' => ['name', 'code', 'description', 'details'],
        'notices' => ['title', 'body'],
        'orders' => ['code', 'note'],
    ];

    protected $relationships = [
        'members' => ['profile', 'contact', 'background'],
        'products' => ['clubCourseInfo'],
        'club_course_infos' => ['schedules'],
        'notices' => ['noticeType'],
        'orders' => ['member'],
    ];

    protected $nestedSearchFields = [
        'members' => [
            'profile' => ['lastname', 'firstname', 'job'],
            'contact' => ['city', 'region', 'address', 'mobile'],
            'background' => ['highest_education'],
        ],
        'products' => [
            'clubCourseInfo' => ['name', 'description', 'details'],
        ],
        'notices' => [
            'noticeType' => ['name'],
        ],
        'orders' => [
            'member' => ['nickname', 'account'],
        ],
    ];

    public function search(array $params): array
    {
        $query = $params['query'] ?? '';
        $types = $params['types'] ?? array_keys($this->searchableModels);
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 15;
        $filters = $params['filters'] ?? [];

        $results = [];
        
        if (empty($types)) {
            $types = array_keys($this->searchableModels);
        }

        foreach ($types as $type) {
            if (!isset($this->searchableModels[$type])) {
                continue;
            }

            $modelClass = $this->searchableModels[$type];
            $queryBuilder = $modelClass::query();

            // Apply relationships
            if (isset($this->relationships[$type])) {
                $queryBuilder->with($this->relationships[$type]);
            }

            // Apply search query
            if (!empty($query)) {
                $queryBuilder->where(function (Builder $q) use ($query, $type) {
                    $this->applySearchQuery($q, $query, $type);
                });
            }

            // Apply filters
            if (!empty($filters)) {
                $this->applyFilters($queryBuilder, $filters, $type);
            }

            // Get results
            $typeResults = $queryBuilder->paginate($perPage, ['*'], $type . '_page', $page);
            
            $results[$type] = [
                'data' => $typeResults->items(),
                'pagination' => [
                    'current_page' => $typeResults->currentPage(),
                    'per_page' => $typeResults->perPage(),
                    'total' => $typeResults->total(),
                    'last_page' => $typeResults->lastPage(),
                ],
            ];
        }

        return $results;
    }

    public function globalSearch(string $query, int $limit = 10): array
    {
        $results = [];
        
        foreach ($this->searchableModels as $type => $modelClass) {
            $queryBuilder = $modelClass::query();

            // Apply relationships
            if (isset($this->relationships[$type])) {
                $queryBuilder->with($this->relationships[$type]);
            }

            // Apply search query
            $queryBuilder->where(function (Builder $q) use ($query, $type) {
                $this->applySearchQuery($q, $query, $type);
            });

            $typeResults = $queryBuilder->limit($limit)->get();
            
            if ($typeResults->isNotEmpty()) {
                $results[$type] = $typeResults->toArray();
            }
        }

        return $results;
    }

    protected function applySearchQuery(Builder $query, string $searchTerm, string $type): void
    {
        $fields = $this->searchableFields[$type] ?? [];
        
        foreach ($fields as $field) {
            $query->orWhere($field, 'LIKE', "%{$searchTerm}%");
        }

        // Search in nested relationships
        if (isset($this->nestedSearchFields[$type])) {
            foreach ($this->nestedSearchFields[$type] as $relation => $relationFields) {
                $query->orWhereHas($relation, function (Builder $q) use ($searchTerm, $relationFields) {
                    foreach ($relationFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }
    }

    protected function applyFilters(Builder $query, array $filters, string $type): void
    {
        foreach ($filters as $filter => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($filter) {
                case 'status':
                    if (in_array($type, ['products', 'club_course_infos', 'notices'])) {
                        $query->where('status', $value);
                    } elseif ($type === 'members') {
                        $query->where('status', $value === 'active' ? 1 : 0);
                    }
                    break;
                    
                case 'date_from':
                    $query->where('created_at', '>=', $value);
                    break;
                    
                case 'date_to':
                    $query->where('created_at', '<=', $value);
                    break;
                    
                case 'price_min':
                    if ($type === 'products') {
                        $query->where('regular_price', '>=', $value);
                    }
                    break;
                    
                case 'price_max':
                    if ($type === 'products') {
                        $query->where('regular_price', '<=', $value);
                    }
                    break;
                    
                case 'teaching_mode':
                    if ($type === 'club_course_infos') {
                        $query->where('teaching_mode', $value);
                    }
                    break;
                    
                case 'is_periodic':
                    if ($type === 'club_course_infos') {
                        $query->where('is_periodic', $value);
                    }
                    break;
                    
                case 'gender':
                    if ($type === 'members') {
                        $query->whereHas('profile', function (Builder $q) use ($value) {
                            $q->where('gender', $value);
                        });
                    }
                    break;
                    
                case 'city':
                    if ($type === 'members') {
                        $query->whereHas('contact', function (Builder $q) use ($value) {
                            $q->where('city', $value);
                        });
                    }
                    break;
                    
                case 'language_id':
                    if ($type === 'members') {
                        $query->whereHas('background.languages', function (Builder $q) use ($value) {
                            $q->where('lang_types.id', $value);
                        });
                    }
                    break;
                    
                case 'level_id':
                    if ($type === 'members') {
                        $query->whereHas('background.levels', function (Builder $q) use ($value) {
                            $q->where('level_types.id', $value);
                        });
                    }
                    break;
            }
        }
    }

    public function getSearchSuggestions(string $query, int $limit = 5): array
    {
        $suggestions = [];
        
        foreach ($this->searchableModels as $type => $modelClass) {
            $fields = $this->searchableFields[$type] ?? [];
            
            foreach ($fields as $field) {
                $results = $modelClass::where($field, 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->pluck($field)
                    ->unique()
                    ->values()
                    ->toArray();
                    
                if (!empty($results)) {
                    $suggestions[$type][$field] = $results;
                }
            }
        }
        
        return $suggestions;
    }

    public function getAvailableFilters(): array
    {
        return [
            'common' => ['status', 'date_from', 'date_to'],
            'products' => ['price_min', 'price_max'],
            'club_course_infos' => ['teaching_mode', 'is_periodic'],
            'members' => ['gender', 'city', 'language_id', 'level_id'],
        ];
    }
}