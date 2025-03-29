<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JobFilterService
{
    protected $query;
    protected $filterString;
    protected $sort;

    /**
     * Constructor for JobFilterService.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param string|null $filterString The filter string from the request.
     * @param string|null $sort The sort string from the request (e.g., "salary_min:asc").
     */
    public function __construct(Builder $query, ?string $filterString = null, ?string $sort = null)
    {
        $this->query = $query;
        $this->filterString = $filterString;
        $this->sort = $sort;
    }

    /**
     * Apply the filters and sorting to the query.
     *
     * @return Builder
     */


    public function apply(): Builder
    {
        if ($this->filterString) {
            $conditions = $this->parseFilterString($this->filterString);
            $this->applyConditions($this->query, $conditions);
        }

        if ($this->sort) {
            $this->applySorting();
        }

        return $this->query;
    }


    /**
     * Parse the filter string into a structured array of conditions.
     *
     * @param string $filterString
     * @return array
     */

    protected function parseFilterString(string $filterString): array
    {
        $filterString = trim(preg_replace('/\s+/', ' ', $filterString));

        if (Str::startsWith($filterString, '(') && Str::endsWith($filterString, ')')) {
            $innerContent = substr($filterString, 1, -1);
            return [
                'type' => 'group',
                'conditions' => $this->parseConditions($innerContent),
                'operator' => 'AND',
            ];
        }

        $conditions = $this->parseConditions($filterString);
        // Ensure the result is a single group if it's not already
        if (!isset($conditions[0]['type'])) {
            return [
                'type' => 'group',
                'conditions' => $conditions,
                'operator' => 'AND',
            ];
        }

        return $conditions;
    }

    /**
     * Parse conditions separated by AND/OR operators.
     *
     * @param string $conditionString
     * @return array
     */
    protected function parseConditions(string $conditionString): array
    {
        $conditions = [];
        $currentCondition = '';
        $parenthesesLevel = 0;

        for ($i = 0; $i < strlen($conditionString); $i++) {
            $char = $conditionString[$i];

            if ($char === '(') {
                $parenthesesLevel++;
            } elseif ($char === ')') {
                $parenthesesLevel--;
            }

            if ($parenthesesLevel === 0 && $i > 0) {
                $nextFive = substr($conditionString, $i, 5);
                $nextThree = substr($conditionString, $i, 3);

                if (strtoupper($nextFive) === ' AND ') {
                    $conditions[] = $this->parseFilterString(trim($currentCondition));
                    $conditions[] = ['type' => 'operator', 'value' => 'AND'];
                    $currentCondition = '';
                    $i += 4;
                    continue;
                } elseif (strtoupper($nextThree) === ' OR ') {
                    $conditions[] = $this->parseFilterString(trim($currentCondition));
                    $conditions[] = ['type' => 'operator', 'value' => 'OR'];
                    $currentCondition = '';
                    $i += 2;
                    continue;
                }
            }

            $currentCondition .= $char;
        }

        if (!empty($currentCondition)) {
            $conditions[] = $this->parseSingleCondition(trim($currentCondition));
        }

        return $this->combineConditions($conditions);
    }

    /**
     * Combine conditions with their operators.
     *
     * @param array $conditions
     * @return array
     */

    protected function combineConditions(array $conditions): array
    {
        $result = [];
        $currentGroup = ['type' => 'group', 'conditions' => [], 'operator' => 'AND'];

        foreach ($conditions as $index => $condition) {
            if (isset($condition['type']) && $condition['type'] === 'operator') {
                $currentGroup['operator'] = $condition['value'];
            } else {
                $currentGroup['conditions'][] = $condition;
                if (!isset($conditions[$index + 1]) || (isset($conditions[$index + 1]['type']) && $conditions[$index + 1]['type'] === 'operator')) {
                    if (!empty($currentGroup['conditions'])) {
                        $result[] = $currentGroup;
                    }
                    $currentGroup = ['type' => 'group', 'conditions' => [], 'operator' => 'AND'];
                }
            }
        }

        return $result;
    }

    /**
     * Parse a single condition (e.g., job_type=full-time).
     *
     * @param string $condition
     * @return array
     */
    protected function parseSingleCondition(string $condition): array
    {
        if (preg_match('/(\w+)\s+(HAS_ANY|IS_ANY|EXISTS)\s*\(([^)]+)\)/i', $condition, $matches)) {
            return [
                'type' => 'relationship',
                'field' => $matches[1],
                'operator' => strtoupper($matches[2]),
                'value' => array_map('trim', explode(',', $matches[3])),
            ];
        }

        if (preg_match('/(\w+)(:|=|!=|>|>=|<|<=|LIKE|IN)\s*([^\s]+)/i', $condition, $matches)) {
            $field = $matches[1];
            $operator = strtoupper($matches[2] === ':' ? '=' : $matches[2]);
            $value = $matches[3];

            if ($operator === 'IN' && Str::startsWith($value, '(') && Str::endsWith($value, ')')) {
                $value = array_map('trim', explode(',', substr($value, 1, -1)));
            }

            return [
                'type' => 'basic',
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        throw new \InvalidArgumentException("Invalid filter condition: $condition");
    }

    /**
     * Apply the parsed conditions to the query.
     *
     * @param array $conditionGroups
     * @return Builder
     */

     protected function flattenConditions(array $conditions): array
     {
         $flattened = [];

         foreach ($conditions as $condition) {
             if (!isset($condition['type']) || ($condition['type'] !== 'group' && $condition['type'] !== 'operator')) {
                 $flattened[] = $condition;
             } elseif ($condition['type'] === 'group') {
                 $nestedConditions = $this->flattenConditions($condition['conditions']);
                 $flattened = array_merge($flattened, $nestedConditions);
             }
         }

         return $flattened;
     }

     protected function applyConditions(Builder $query, array $conditionGroups): void
     {
         foreach ($conditionGroups as $group) {
             if (!isset($group['type']) || ($group['type'] !== 'group' && $group['type'] !== 'operator')) {
                 $query->where(function ($subQuery) use ($group) {
                     $this->applyFilterToQuery($subQuery, $group);
                 });
                 continue;
             }

             if ($group['type'] === 'group') {
                 $operator = $group['operator'] ?? 'AND';
                 $flattenedConditions = $this->flattenConditions($group['conditions']);
                 $query->where(function ($subQuery) use ($flattenedConditions, $operator) {
                     $firstCondition = true;
                     foreach ($flattenedConditions as $condition) {
                         if ($firstCondition) {
                             $this->applyFilterToQuery($subQuery, $condition);
                             $firstCondition = false;
                         } else {
                             if ($operator === 'AND') {
                                 $subQuery->where(function ($nestedQuery) use ($condition) {
                                     $this->applyFilterToQuery($nestedQuery, $condition);
                                 });
                             } else {
                                 $subQuery->orWhere(function ($nestedQuery) use ($condition) {
                                     $this->applyFilterToQuery($nestedQuery, $condition);
                                 });
                             }
                         }
                     }
                 });
             }
         }
     }

    /**
     * Apply a single filter condition to the query.
     *
     * @param array $condition
     * @return void
     */

    protected function applyFilterToQuery(Builder $query, array $condition): void
    {
        $requiredKeys = ['field', 'operator', 'value'];
        $missingKeys = array_diff($requiredKeys, array_keys($condition));
        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Invalid condition structure. Missing keys: ' . implode(', ', $missingKeys) . '. Condition: ' . json_encode($condition));
        }

        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        if (Str::startsWith($field, 'attribute:')) {
            $this->applyEavFilter($query, substr($field, 10), $operator, $value);
        } elseif (in_array($field, ['languages', 'locations', 'categories'])) {
            $this->applyRelationshipFilter($query, $field, $operator, $value);
        } else {
            $this->applyBasicFilter($query, $field, $operator, $value);
        }
    }

    /**
     * Apply basic field filtering using query scopes.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return void
     */

    protected function applyBasicFilter(Builder $query, string $field, string $operator, $value): void
    {
        $jobFields = (new Job())->getFillable();
        $jobFields[] = 'is_remote';
        $jobFields[] = 'job_type';
        $jobFields[] = 'status';
        $jobFields[] = 'published_at';
        $jobFields[] = 'created_at';
        $jobFields[] = 'updated_at';

        if (!in_array($field, $jobFields)) {
            throw new \InvalidArgumentException("Invalid field: $field");
        }

        if ($field === 'job_type') {
            $query->scopeJobType($operator, $value);
        } elseif ($field === 'status') {
            $query->scopeStatus($operator, $value);
        } elseif ($field === 'is_remote') {
            $query->scopeIsRemote($operator, filter_var($value, FILTER_VALIDATE_BOOLEAN));
        } else {
            if (in_array($field, ['salary_min', 'salary_max'])) {
                $value = (float) $value;
            } elseif (in_array($field, ['published_at', 'created_at', 'updated_at'])) {
                $value = \Carbon\Carbon::parse($value);
            }

            if ($operator === 'LIKE') {
                $query->where($field, 'LIKE', "%$value%");
            } elseif ($operator === 'IN') {
                $query->whereIn($field, (array) $value);
            } else {
                $query->where($field, $operator, $value);
            }
        }
    }

    /**
     * Apply relationship filtering using joins for better performance.
     *
     * @param string $relationship
     * @param string $operator
     * @param mixed $value
     * @return void
     */

    protected function applyRelationshipFilter(Builder $query, string $relationship, string $operator, $value): void
    {
        $relationshipMap = [
            'languages' => [
                'model' => \App\Models\Language::class,
                'pivot' => 'job_language',
                'field' => 'name',
            ],
            'locations' => [
                'model' => \App\Models\Location::class,
                'pivot' => 'job_location',
                'field' => 'city',
            ],
            'categories' => [
                'model' => \App\Models\Category::class,
                'pivot' => 'category_job',
                'field' => 'name',
            ],
        ];

        if (!isset($relationshipMap[$relationship])) {
            throw new \InvalidArgumentException("Invalid relationship: $relationship");
        }

        $config = $relationshipMap[$relationship];
        $pivotTable = $config['pivot'];
        $relatedTable = (new $config['model'])->getTable();
        $field = $config['field'];

        if ($operator === 'EXISTS') {
            $query->join($pivotTable, 'jobs.id', '=', "$pivotTable.job_id");
        } elseif ($operator === 'HAS_ANY' || $operator === 'IS_ANY') {
            $query->join($pivotTable, 'jobs.id', '=', "$pivotTable.job_id")
                ->join($relatedTable, "$relatedTable.id", '=', "$pivotTable.{$relationship}_id")
                ->whereIn("$relatedTable.$field", (array) $value)
                ->select('jobs.*')
                ->distinct();
        } else {
            throw new \InvalidArgumentException("Unsupported relationship operator: $operator");
        }
    }

    /**
     * Apply EAV attribute filtering using joins for better performance.
     *
     * @param string $attributeName
     * @param string $operator
     * @param mixed $value
     * @return void
     */

    protected function applyEavFilter(Builder $query, string $attributeName, string $operator, $value): void
    {
        $attribute = Cache::remember("attribute:$attributeName", 3600, function () use ($attributeName) {
            return Attribute::where('name', $attributeName)->first();
        });

        if (!$attribute) {
            throw new \InvalidArgumentException("Attribute not found: $attributeName");
        }

        $alias = "job_attribute_values_{$attribute->id}";

        $query->join('job_attribute_values as ' . $alias, function ($join) use ($alias, $attribute) {
            $join->on('jobs.id', '=', "$alias.job_id")
                ->where("$alias.attribute_id", $attribute->id);
        });

        switch ($attribute->type) {
            case 'number':
                $value = (float) $value;
                break;
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'date':
                $value = \Carbon\Carbon::parse($value);
                break;
        }

        if ($operator === 'LIKE') {
            $query->where("$alias.value", 'LIKE', "%$value%");
        } elseif ($operator === 'IN') {
            $query->whereIn("$alias.value", (array) $value);
        } else {
            $query->where("$alias.value", $operator, $value);
        }
    }

    /**
     * Apply sorting to the query.
     *
     * @return void
     */
    protected function applySorting(): void
    {
        [$field, $direction] = explode(':', $this->sort) + [1 => 'asc'];
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        $sortableFields = (new Job())->getFillable();
        $sortableFields[] = 'created_at';
        $sortableFields[] = 'updated_at';

        if (!in_array($field, $sortableFields)) {
            throw new \InvalidArgumentException("Invalid sort field: $field");
        }

        $this->query->orderBy($field, $direction);
    }
}
