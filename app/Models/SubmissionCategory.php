<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubmissionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'is_active',
        'allowed_file_types',
        'max_file_size',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_file_types' => 'array',
        'price' => 'decimal:2',
        'max_file_size' => 'integer',
    ];

    /**
     * Relationships
     */
    public function submissions()
    {
        return $this->hasMany(ContentSubmission::class, 'category_id');
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedMaxFileSizeAttribute()
    {
        $bytes = $this->max_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getAllowedFileTypesStringAttribute()
    {
        if (!$this->allowed_file_types || !is_array($this->allowed_file_types)) {
            return 'Semua file';
        }
        
        return strtoupper(implode(', ', $this->allowed_file_types));
    }

    public function getIconAttribute()
    {
        $icons = [
            'lomba-kompetisi' => '🏆',
            'lowongan-kerja' => '💼',
            'seminar-workshop' => '🎤',
            'event-acara' => '🎪',
        ];

        return $icons[$this->slug] ?? '📄';
    }

    public function getColorClassAttribute()
    {
        $colors = [
            'lomba-kompetisi' => 'bg-yellow-500',
            'lowongan-kerja' => 'bg-blue-500',
            'seminar-workshop' => 'bg-purple-500',
            'event-acara' => 'bg-green-500',
        ];

        return $colors[$this->slug] ?? 'bg-gray-500';
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Business Logic Methods
     */
    public function isFileTypeAllowed($fileType)
    {
        if (!$this->allowed_file_types || !is_array($this->allowed_file_types)) {
            return true; // Allow all if not specified
        }
        
        return in_array(strtolower($fileType), array_map('strtolower', $this->allowed_file_types));
    }

    public function isFileSizeAllowed($fileSize)
    {
        return $fileSize <= $this->max_file_size;
    }

    public function validateFile($file)
    {
        $errors = [];
        
        // Check file type
        $extension = $file->getClientOriginalExtension();
        if (!$this->isFileTypeAllowed($extension)) {
            $errors[] = "Tipe file {$extension} tidak diizinkan. Gunakan: " . $this->allowed_file_types_string;
        }
        
        // Check file size
        if (!$this->isFileSizeAllowed($file->getSize())) {
            $errors[] = "Ukuran file terlalu besar. Maksimal: " . $this->formatted_max_file_size;
        }
        
        return $errors;
    }

    /**
     * Static methods
     */
    public static function getActiveCategories()
    {
        return static::active()->orderBy('name')->get();
    }

    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }

    public static function getCategoryStats($categoryId)
    {
        $category = static::find($categoryId);
        
        if (!$category) {
            return null;
        }
        
        return [
            'total_submissions' => $category->submissions()->count(),
            'pending_approval' => $category->submissions()->pendingApproval()->count(),
            'approved' => $category->submissions()->approved()->count(),
            'rejected' => $category->submissions()->rejected()->count(),
            'published' => $category->submissions()->published()->count(),
            'revenue' => Payment::whereHas('submission', function($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })->confirmed()->sum('amount'),
        ];
    }
}