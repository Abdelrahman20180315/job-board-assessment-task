<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'company_name',
        'salary_min',
        'salary_max',
        'is_remote',
        'job_type',
        'status',
        'published_at',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'job_language')->withTimestamps();
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'job_location')->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_job')->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->hasMany(JobAttributeValue::class);
    }

    public function getEavAttributeValue(string $attributeName)
    {
        $attributeValue = $this->attributeValues()
            ->whereHas('attribute', function ($query) use ($attributeName) {
                $query->where('name', $attributeName);
            })
            ->first();

        if (!$attributeValue) {
            return null;
        }

        $attribute = $attributeValue->attribute;
        $value = $attributeValue->value;

        switch ($attribute->type) {
            case 'number':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'date':
                return $value ? \Carbon\Carbon::parse($value) : null;
            case 'text':
            case 'select':
            default:
                return $value;
        }
    }

    // Query Scopes
    public function scopeJobType($query, $operator, $value)
    {
        if ($operator === 'IN') {
            return $query->whereIn('job_type', (array) $value);
        }
        return $query->where('job_type', $operator, $value);
    }

    public function scopeStatus($query, $operator, $value)
    {
        if ($operator === 'IN') {
            return $query->whereIn('status', (array) $value);
        }
        return $query->where('status', $operator, $value);
    }

    public function scopeIsRemote($query, $operator, $value)
    {
        return $query->where('is_remote', $operator, $value);
    }
}
