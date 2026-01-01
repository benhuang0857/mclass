<?php

namespace App\Services;

use App\Models\ClubCourseInfo;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    protected $searchableModels = [
        'courses' => ClubCourseInfo::class,
        'discussions' => Comment::class,
    ];

    protected $searchableFields = [
        'courses' => ['name'],
        'discussions' => ['title'],
    ];

    protected $relationships = [
        'courses' => ['languages'],
        'discussions' => ['languages', 'author'],
    ];

    protected $nestedSearchFields = [
        // 暫時不需要嵌套搜索
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

            // 討論區只搜索根評論（主題帖），不搜索回覆
            if ($type === 'discussions') {
                $queryBuilder->whereNull('parent_id');
            }

            // Apply filters
            if (!empty($filters)) {
                $this->applyFilters($queryBuilder, $filters, $type);
            }

            // Get results
            $typeResults = $queryBuilder->paginate($perPage, ['*'], $type . '_page', $page);

            // 添加 tag 和 type 標識
            $items = collect($typeResults->items())->map(function ($item) use ($type) {
                $item->type = $type;
                $item->tag = $this->getTagForType($type, $item);
                return $item;
            });

            $results[$type] = [
                'data' => $items,
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

            // 討論區只搜索根評論（主題帖），不搜索回覆
            if ($type === 'discussions') {
                $queryBuilder->whereNull('parent_id');
            }

            $typeResults = $queryBuilder->limit($limit)->get();

            if ($typeResults->isNotEmpty()) {
                // 添加 tag 和 type 標識
                $items = $typeResults->map(function ($item) use ($type) {
                    $item->type = $type;
                    $item->tag = $this->getTagForType($type, $item);
                    return $item;
                });
                $results[$type] = $items->toArray();
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
            if (empty($value) && $value !== 0 && $value !== false) {
                continue;
            }

            switch ($filter) {
                case 'status':
                    if (in_array($type, ['courses', 'discussions'])) {
                        $query->where('status', $value);
                    }
                    break;

                case 'date_from':
                    $query->where('created_at', '>=', $value);
                    break;

                case 'date_to':
                    $query->where('created_at', '<=', $value);
                    break;

                case 'teaching_mode':
                    if ($type === 'courses') {
                        $query->where('teaching_mode', $value);
                    }
                    break;

                case 'is_periodic':
                    if ($type === 'courses') {
                        $query->where('is_periodic', $value);
                    }
                    break;

                case 'language_id':
                    $query->whereHas('languages', function (Builder $q) use ($value) {
                        $q->where('lang_types.id', $value);
                    });
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
            'common' => ['status', 'date_from', 'date_to', 'language_id'],
            'courses' => ['teaching_mode', 'is_periodic'],
            'discussions' => [],
        ];
    }

    /**
     * 根據類型和項目生成 tag
     *
     * @param string $type
     * @param mixed $item
     * @return string
     */
    protected function getTagForType(string $type, $item): string
    {
        // 獲取語言名稱
        $languageName = '';
        if ($item->languages && $item->languages->isNotEmpty()) {
            $languageName = $item->languages->first()->name;
        }

        // 根據類型返回不同的 tag
        switch ($type) {
            case 'courses':
                return $languageName ? "{$languageName}俱樂部" : '俱樂部';
            case 'discussions':
                return $languageName ? "{$languageName}討論區" : '討論區';
            default:
                return '';
        }
    }
}