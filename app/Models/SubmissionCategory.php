<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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
     * ✅ ADDED: Additional relationships for better data access
     */
    public function pendingSubmissions()
    {
        return $this->hasMany(ContentSubmission::class, 'category_id')
                   ->whereIn('status', ['pending_payment', 'pending_approval']);
    }

    public function approvedSubmissions()
    {
        return $this->hasMany(ContentSubmission::class, 'category_id')
                   ->whereIn('status', ['approved', 'published']);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, ContentSubmission::class, 'category_id', 'submission_id');
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
            'beasiswa-grant' => '🎓',
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
            'beasiswa-grant' => 'bg-indigo-500',
        ];

        return $colors[$this->slug] ?? 'bg-gray-500';
    }

    /**
     * ✅ ADDED: Additional accessors for better UI support
     */
    public function getBadgeColorAttribute()
    {
        $colors = [
            'lomba-kompetisi' => 'bg-yellow-100 text-yellow-800',
            'lowongan-kerja' => 'bg-blue-100 text-blue-800',
            'seminar-workshop' => 'bg-purple-100 text-purple-800',
            'event-acara' => 'bg-green-100 text-green-800',
            'beasiswa-grant' => 'bg-indigo-100 text-indigo-800',
        ];

        return $colors[$this->slug] ?? 'bg-gray-100 text-gray-800';
    }

    public function getSubmissionCountAttribute()
    {
        return $this->submissions()->count();
    }

    public function getPendingApprovalCountAttribute()
    {
        return $this->submissions()->where('status', 'pending_approval')->count();
    }

    public function getTotalRevenueAttribute()
    {
        if (!class_exists('\App\Models\Payment')) {
            return 0;
        }
        
        return \App\Models\Payment::whereHas('submission', function($query) {
            $query->where('category_id', $this->id);
        })->where('status', 'confirmed')->sum('amount') ?? 0;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ ADDED: Additional scopes for better querying
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByPrice($query, $price)
    {
        return $query->where('price', $price);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeAllowingFileType($query, $fileType)
    {
        return $query->whereJsonContains('allowed_file_types', strtolower($fileType))
                    ->orWhereNull('allowed_file_types');
    }

    public function scopeWithSubmissions($query)
    {
        return $query->has('submissions');
    }

    public function scopeWithoutSubmissions($query)
    {
        return $query->doesntHave('submissions');
    }

    public function scopePopular($query, $limit = 5)
    {
        return $query->withCount('submissions')
                    ->orderBy('submissions_count', 'desc')
                    ->limit($limit);
    }

    public function scopeOrderByPrice($query, $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
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

    /**
     * ✅ ENHANCED: Improved validateFile method with better error handling
     */
    public function validateFile(UploadedFile $file)
    {
        $errors = [];

        // Check if file is valid
        if (!$file->isValid()) {
            $errors[] = 'File tidak valid atau rusak.';
            return $errors;
        }

        // Check file size
        if (!$this->isFileSizeAllowed($file->getSize())) {
            $errors[] = "Ukuran file terlalu besar. Maksimal: {$this->formatted_max_file_size}.";
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!$this->isFileTypeAllowed($extension)) {
            $errors[] = "Tipe file '{$extension}' tidak diizinkan. Yang diizinkan: {$this->allowed_file_types_string}.";
        }

        // Additional MIME type validation for security
        $mimeType = $file->getMimeType();
        $dangerousMimes = [
            'application/x-php',
            'application/x-httpd-php',
            'text/x-php',
            'application/x-executable',
            'application/x-msdownload',
        ];

        if (in_array($mimeType, $dangerousMimes)) {
            $errors[] = 'Tipe file ini tidak diizinkan karena alasan keamanan.';
        }

        // Check for common image types if allowed
        if ($this->isFileTypeAllowed($extension)) {
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
            
            if (in_array($extension, $imageExtensions)) {
                // Additional validation for images
                $imageInfo = getimagesize($file->getPathname());
                if (!$imageInfo) {
                    $errors[] = 'File gambar tidak valid atau rusak.';
                }
            }
        }

        return $errors;
    }

    /**
     * ✅ ADDED: New file validation methods
     */
    public function acceptsFileType($extension)
    {
        if (!$this->allowed_file_types || !is_array($this->allowed_file_types)) {
            return true; // Accept all if no restrictions
        }

        return in_array(strtolower($extension), array_map('strtolower', $this->allowed_file_types));
    }

    public function acceptsFileSize($sizeInBytes)
    {
        return $sizeInBytes <= $this->max_file_size;
    }

    public function getFileTypeValidationRule()
    {
        if (!$this->allowed_file_types || !is_array($this->allowed_file_types)) {
            return 'file';
        }

        $extensions = implode(',', $this->allowed_file_types);
        return "file|mimes:{$extensions}";
    }

    public function getFileSizeValidationRule()
    {
        $maxSizeKB = ceil($this->max_file_size / 1024);
        return "max:{$maxSizeKB}";
    }

    /**
     * ✅ ADDED: Category management methods
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function updatePrice($newPrice)
    {
        $this->update(['price' => $newPrice]);
        return $this;
    }

    public function updateFileRestrictions(array $allowedTypes, int $maxSize)
    {
        $this->update([
            'allowed_file_types' => $allowedTypes,
            'max_file_size' => $maxSize
        ]);
        return $this;
    }

    /**
     * ✅ ADDED: Statistics and analytics methods
     */
    public function getSubmissionStats()
    {
        $stats = [
            'total' => 0,
            'pending_payment' => 0,
            'pending_approval' => 0,
            'approved' => 0,
            'rejected' => 0,
            'published' => 0,
        ];

        $submissions = $this->submissions()
                           ->selectRaw('status, count(*) as count')
                           ->groupBy('status')
                           ->get();

        foreach ($submissions as $submission) {
            if (isset($stats[$submission->status])) {
                $stats[$submission->status] = $submission->count;
                $stats['total'] += $submission->count;
            }
        }

        return $stats;
    }

    public function getRevenueStats()
    {
        if (!class_exists('\App\Models\Payment')) {
            return [
                'total_revenue' => 0,
                'confirmed_payments' => 0,
                'pending_payments' => 0,
                'average_payment' => 0
            ];
        }

        $payments = \App\Models\Payment::whereHas('submission', function($query) {
            $query->where('category_id', $this->id);
        });

        $confirmedPayments = $payments->where('status', 'confirmed');
        $totalRevenue = $confirmedPayments->sum('amount') ?? 0;
        $confirmedCount = $confirmedPayments->count();

        return [
            'total_revenue' => $totalRevenue,
            'confirmed_payments' => $confirmedCount,
            'pending_payments' => $payments->where('status', 'pending')->count(),
            'average_payment' => $confirmedCount > 0 ? ($totalRevenue / $confirmedCount) : 0
        ];
    }

    public function getMonthlyStats($year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        return [
            'submissions' => $this->submissions()
                                 ->whereYear('created_at', $year)
                                 ->whereMonth('created_at', $month)
                                 ->count(),
            'approved' => $this->submissions()
                              ->whereYear('approved_at', $year)
                              ->whereMonth('approved_at', $month)
                              ->whereIn('status', ['approved', 'published'])
                              ->count(),
        ];
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

    /**
     * ✅ ENHANCED: Improved getCategoryStats method
     */
    public static function getCategoryStats($categoryId)
    {
        $category = static::find($categoryId);
        
        if (!$category) {
            return null;
        }
        
        $stats = [
            'total_submissions' => $category->submissions()->count(),
            'pending_payment' => $category->submissions()->where('status', 'pending_payment')->count(),
            'pending_approval' => $category->submissions()->where('status', 'pending_approval')->count(),
            'approved' => $category->submissions()->where('status', 'approved')->count(),
            'rejected' => $category->submissions()->where('status', 'rejected')->count(),
            'published' => $category->submissions()->where('status', 'published')->count(),
            'revenue' => 0,
        ];

        // Calculate revenue if Payment model exists
        if (class_exists('\App\Models\Payment')) {
            $stats['revenue'] = \App\Models\Payment::whereHas('submission', function($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })->where('status', 'confirmed')->sum('amount') ?? 0;
        }

        return $stats;
    }

    /**
     * ✅ ADDED: Additional static helper methods
     */
    public static function getPopularCategories($limit = 5)
    {
        return static::withCount('submissions')
                    ->where('is_active', true)
                    ->orderBy('submissions_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getCheapestCategories($limit = 5)
    {
        return static::where('is_active', true)
                    ->orderBy('price', 'asc')
                    ->limit($limit)
                    ->get();
    }

    public static function getMostExpensiveCategories($limit = 5)
    {
        return static::where('is_active', true)
                    ->orderBy('price', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getTotalRevenue()
    {
        if (!class_exists('\App\Models\Payment')) {
            return 0;
        }

        return \App\Models\Payment::whereHas('submission.category')
                                 ->where('status', 'confirmed')
                                 ->sum('amount') ?? 0;
    }

    public static function getCategoriesWithSubmissionCount()
    {
        return static::withCount('submissions')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
    }

    public static function searchCategories($query)
    {
        return static::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->where('is_active', true)
                    ->get();
    }

    public static function getCategoryByFileType($fileExtension)
    {
        return static::active()
                    ->get()
                    ->filter(function($category) use ($fileExtension) {
                        return $category->acceptsFileType($fileExtension);
                    });
    }

    /**
     * ✅ ADDED: Validation and business logic helpers
     */
    public function canAcceptNewSubmissions()
    {
        return $this->is_active;
    }

    public function hasSubmissions()
    {
        return $this->submissions()->exists();
    }

    public function hasPendingSubmissions()
    {
        return $this->submissions()
                   ->whereIn('status', ['pending_payment', 'pending_approval'])
                   ->exists();
    }

    public function getAverageApprovalTime()
    {
        $approvedSubmissions = $this->submissions()
                                   ->whereNotNull('approved_at')
                                   ->whereNotNull('submitted_at')
                                   ->get();

        if ($approvedSubmissions->isEmpty()) {
            return null;
        }

        $totalMinutes = $approvedSubmissions->sum(function($submission) {
            return $submission->submitted_at->diffInMinutes($submission->approved_at);
        });

        return $totalMinutes / $approvedSubmissions->count();
    }

    public function getApprovalRate()
    {
        $totalSubmissions = $this->submissions()
                                ->whereNotIn('status', ['pending_payment', 'pending_approval'])
                                ->count();

        if ($totalSubmissions === 0) {
            return 0;
        }

        $approvedSubmissions = $this->submissions()
                                   ->whereIn('status', ['approved', 'published'])
                                   ->count();

        return ($approvedSubmissions / $totalSubmissions) * 100;
    }

    /**
     * ✅ ADDED: Boot method for automatic slug generation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * ✅ ADDED: Export and import helpers
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed attributes for exports
        $array['formatted_price'] = $this->formatted_price;
        $array['formatted_max_file_size'] = $this->formatted_max_file_size;
        $array['submission_count'] = $this->submission_count;
        $array['total_revenue'] = $this->total_revenue;
        
        return $array;
    }

    public function getExportData()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'is_active' => $this->is_active,
            'max_file_size' => $this->max_file_size,
            'formatted_max_file_size' => $this->formatted_max_file_size,
            'allowed_file_types' => $this->allowed_file_types_string,
            'submissions_count' => $this->submission_count,
            'total_revenue' => $this->total_revenue,
            'approval_rate' => $this->getApprovalRate(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}