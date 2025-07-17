<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'attachment_size',
        'status',
        'payment_id',
        'submitted_at',
        'approved_at',
        'approved_by',
        'published_at',      // ✅ ADDED
        'published_by',      // ✅ ADDED
        'rejected_at',       // ✅ ADDED
        'rejected_by',       // ✅ ADDED
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',   // ✅ ADDED
        'rejected_at' => 'datetime',    // ✅ ADDED
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(SubmissionCategory::class, 'category_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ✅ ADDED: New relationships untuk tracking
     */
    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Accessors & Mutators
     */
    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }
        
        return asset('storage/' . $this->attachment_path);
    }

    public function getFormattedFileSizeAttribute()
    {
        if (!$this->attachment_size) return null;
        
        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            'pending_payment' => 'bg-yellow-100 text-yellow-800',
            'pending_approval' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'published' => 'bg-purple-100 text-purple-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending_payment' => 'Menunggu Pembayaran',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'published' => 'Dipublikasikan',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'pending_payment' => '💰',
            'pending_approval' => '⏳',
            'approved' => '✅',
            'rejected' => '❌',
            'published' => '📢',
        ];

        return $icons[$this->status] ?? '📄';
    }

    /**
     * ✅ ADDED: Additional Accessors for File Handling
     */
    public function getAttachmentIconAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'fas fa-image';
            case 'pdf':
                return 'fas fa-file-pdf';
            case 'document':
                return 'fas fa-file-word';
            case 'spreadsheet':
                return 'fas fa-file-excel';
            default:
                return 'fas fa-file';
        }
    }

    public function getAttachmentColorAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'text-green-500';
            case 'pdf':
                return 'text-red-500';
            case 'document':
                return 'text-blue-500';
            case 'spreadsheet':
                return 'text-emerald-500';
            default:
                return 'text-gray-500';
        }
    }

    /**
     * ✅ ADDED: Status progress tracking untuk dashboard
     */
    public function getProgressPercentageAttribute()
    {
        $percentages = [
            'pending_payment' => 20,
            'pending_approval' => 40,
            'approved' => 80,
            'published' => 100,
            'rejected' => 100,
        ];

        return $percentages[$this->status] ?? 0;
    }

    public function getProgressStepsAttribute()
    {
        $currentStep = 0;
        switch ($this->status) {
            case 'pending_payment':
                $currentStep = 1;
                break;
            case 'pending_approval':
                $currentStep = 2;
                break;
            case 'approved':
                $currentStep = 3;
                break;
            case 'published':
                $currentStep = 4;
                break;
            case 'rejected':
                $currentStep = 0; // Special case
                break;
        }

        return [
            ['title' => 'Payment', 'completed' => $currentStep >= 1],
            ['title' => 'Review', 'completed' => $currentStep >= 2],
            ['title' => 'Approve', 'completed' => $currentStep >= 3],
            ['title' => 'Publish', 'completed' => $currentStep >= 4],
        ];
    }

    /**
     * Scopes
     */
    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * ✅ ADDED: Additional Scopes for Better Filtering
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending_payment', 'pending_approval']);
    }

    public function scopeApprovedAndPublished($query)
    {
        return $query->whereIn('status', ['approved', 'published']);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithAttachment($query)
    {
        return $query->whereNotNull('attachment_path');
    }

    public function scopeWithoutAttachment($query)
    {
        return $query->whereNull('attachment_path');
    }

    /**
     * Business Logic Methods
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['pending_payment', 'rejected']);
    }

    /**
     * ✅ FIXED: Updated canBePaid() method with proper payment validation
     * Submission can be paid if:
     * - Status is pending_payment AND
     * - No payment exists OR existing payment was rejected
     */
    public function canBePaid()
    {
        return $this->status === 'pending_payment' && 
               (!$this->payment || $this->payment->status === 'rejected');
    }

    /**
     * ✅ UPDATED: Enhanced canBeDeleted() method 
     */
    public function canBeDeleted()
    {
        return in_array($this->status, ['pending_payment', 'rejected']);
    }

    /**
     * ✅ ENHANCED: canBeApproved() method dengan validation yang lebih ketat
     */
    public function canBeApproved()
    {
        return $this->status === 'pending_approval' && 
               $this->payment && 
               $this->payment->status === 'confirmed';
    }

    /**
     * ✅ ENHANCED: New validation methods
     */
    public function canBeRejected()
    {
        return in_array($this->status, ['pending_approval', 'approved']);
    }

    /**
     * ✅ ENHANCED: canBePublished() method
     */
    public function canBePublished()
    {
        return $this->status === 'approved';
    }

    /**
     * ✅ NEW: Helper method to check if submission can be re-approved after rejection
     */
    public function canBeReapproved()
    {
        return $this->status === 'rejected' && 
               $this->payment && 
               $this->payment->status === 'confirmed';
    }

    public function isPending()
    {
        return in_array($this->status, ['pending_payment', 'pending_approval']);
    }

    public function isApproved()
    {
        return in_array($this->status, ['approved', 'published']);
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function requiresPayment()
    {
        return $this->status === 'pending_payment';
    }

    public function hasValidPayment()
    {
        return $this->payment && $this->payment->status === 'confirmed';
    }

    /**
     * Action Methods
     */
    public function markAsPaid($paymentId)
    {
        $this->update([
            'status' => 'pending_approval',
            'payment_id' => $paymentId,
            'submitted_at' => now(),
        ]);

        // Create notification for admin (if Notification model exists)
        if (class_exists('\App\Models\Notification')) {
            try {
                \App\Models\Notification::createSubmissionNotification($this->id, 'submission_pending_approval');
            } catch (\Exception $e) {
                Log::warning('Failed to create pending approval notification', [
                    'submission_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this;
    }

    /**
     * ✅ ENHANCED: approve() method compatible dengan controller yang sudah diupdate
     */
    public function approve($approvedBy)
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Submission cannot be approved in current status: ' . $this->status);
        }

        try {
            $this->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
                // Clear rejection data if previously rejected
                'rejection_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
            ]);

            // Clear admin cache
            if ($approvedBy) {
                Cache::forget('admin_pending_submissions_' . $approvedBy);
                Cache::forget('admin_integrated_stats_' . $approvedBy);
            }
            Cache::forget('submission_admin_stats');

            // Create notification for user
            if (class_exists('\App\Models\Notification')) {
                try {
                    \App\Models\Notification::createSubmissionNotification($this->id, 'submission_approved');
                } catch (\Exception $e) {
                    Log::warning('Failed to create approval notification', [
                        'submission_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Submission approved successfully', [
                'submission_id' => $this->id,
                'approved_by' => $approvedBy,
                'title' => $this->title
            ]);

            return $this;

        } catch (\Exception $e) {
            Log::error('Failed to approve submission', [
                'submission_id' => $this->id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to approve submission: ' . $e->getMessage());
        }
    }

    /**
     * ✅ REVISED: reject() method dengan parameter order yang konsisten dengan approve()
     */
    public function reject($rejectedBy, $reason = null)
    {
        if (!$this->canBeRejected()) {
            throw new \Exception('Submission cannot be rejected in current status: ' . $this->status);
        }

        if (empty($reason)) {
            throw new \Exception('Rejection reason is required');
        }

        try {
            $this->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
                // Clear approval data if previously approved
                'approved_at' => null,
                'approved_by' => null,
                'published_at' => null,
                'published_by' => null,
            ]);

            // Clear admin cache
            if ($rejectedBy) {
                Cache::forget('admin_pending_submissions_' . $rejectedBy);
                Cache::forget('admin_integrated_stats_' . $rejectedBy);
            }
            Cache::forget('submission_admin_stats');

            // Create notification for user
            if (class_exists('\App\Models\Notification')) {
                try {
                    \App\Models\Notification::createSubmissionNotification($this->id, 'submission_rejected');
                } catch (\Exception $e) {
                    Log::warning('Failed to create rejection notification', [
                        'submission_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Submission rejected successfully', [
                'submission_id' => $this->id,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
                'title' => $this->title
            ]);

            return $this;

        } catch (\Exception $e) {
            Log::error('Failed to reject submission', [
                'submission_id' => $this->id,
                'rejected_by' => $rejectedBy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to reject submission: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ADDED: New method to submit for approval
     */
    public function submit()
    {
        if ($this->status !== 'pending_payment' || !$this->hasValidPayment()) {
            throw new \Exception('Pembayaran harus dikonfirmasi terlebih dahulu.');
        }

        $this->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        return $this;
    }

    /**
     * ✅ ENHANCED: publish() method compatible dengan controller yang sudah diupdate
     */
    public function publish($publishedBy = null)
    {
        if (!$this->canBePublished()) {
            throw new \Exception('Submission cannot be published in current status: ' . $this->status);
        }

        try {
            // Publish to group first
            $publishResult = $this->publishToGroup();
            
            if ($publishResult) {
                $this->update([
                    'status' => 'published',
                    'published_by' => $publishedBy,
                    'published_at' => now()
                ]);

                // Clear admin cache
                if ($publishedBy) {
                    Cache::forget('admin_pending_submissions_' . $publishedBy);
                    Cache::forget('admin_integrated_stats_' . $publishedBy);
                }
                Cache::forget('submission_admin_stats');

                // Create notification for user
                if (class_exists('\App\Models\Notification')) {
                    try {
                        \App\Models\Notification::createSubmissionNotification($this->id, 'submission_published');
                    } catch (\Exception $e) {
                        Log::warning('Failed to create publication notification', [
                            'submission_id' => $this->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                Log::info('Submission published successfully', [
                    'submission_id' => $this->id,
                    'published_by' => $publishedBy,
                    'title' => $this->title
                ]);

                return $this;
            } else {
                throw new \Exception('Failed to publish content to community group');
            }

        } catch (\Exception $e) {
            Log::error('Failed to publish submission', [
                'submission_id' => $this->id,
                'published_by' => $publishedBy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to publish submission: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ENHANCED: publishToGroup() method dengan better error handling
     */
    public function publishToGroup()
    {
        try {
            // Find the corresponding chat group
            $chatGroup = null;
            if (class_exists('\App\Models\ChatGroup')) {
                $chatGroup = \App\Models\ChatGroup::where('name', 'LIKE', '%' . $this->category->name . '%')->first();
                
                if (!$chatGroup) {
                    // Create group if doesn't exist
                    $chatGroup = \App\Models\ChatGroup::create([
                        'name' => $this->category->name,
                        'description' => $this->category->description ?? 'Community group for ' . $this->category->name,
                        'creator_id' => 1, // Admin user
                        'is_approved' => true,
                    ]);
                }
            }

            // Create group message (if GroupMessage model exists)
            $message = null;
            if (class_exists('\App\Models\GroupMessage') && $chatGroup) {
                $message = \App\Models\GroupMessage::create([
                    'group_id' => $chatGroup->id,
                    'sender_id' => $this->user_id,
                    'message_content' => "📢 {$this->title}\n\n{$this->description}",
                    'attachment_path' => $this->attachment_path,
                    'attachment_type' => $this->attachment_type,
                    'attachment_name' => $this->attachment_name,
                    'attachment_size' => $this->attachment_size,
                ]);

                Log::info('Submission published to group', [
                    'submission_id' => $this->id,
                    'group_id' => $chatGroup->id,
                    'message_id' => $message->id
                ]);
            }

            return $message ?? true;

        } catch (\Exception $e) {
            Log::error('Failed to publish submission to group', [
                'submission_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * ✅ NEW: Republish submission to community
     */
    public function republish($publishedBy = null)
    {
        if ($this->status !== 'published') {
            throw new \Exception('Only published submissions can be republished');
        }

        try {
            $result = $this->publishToGroup();
            
            if ($result) {
                // Update timestamp
                $this->update([
                    'published_at' => now(),
                    'published_by' => $publishedBy ?? $this->published_by
                ]);

                Log::info('Submission republished', [
                    'submission_id' => $this->id,
                    'republished_by' => $publishedBy
                ]);

                return $this;
            } else {
                throw new \Exception('Failed to republish submission to community');
            }

        } catch (\Exception $e) {
            Log::error('Failed to republish submission', [
                'submission_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * File Management Methods
     */
    public function deleteAttachment()
    {
        if ($this->attachment_path) {
            try {
                // Fix storage path handling
                if (Storage::disk('public')->exists($this->attachment_path)) {
                    Storage::disk('public')->delete($this->attachment_path);
                }
                
                $this->update([
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'attachment_name' => null,
                    'attachment_size' => null,
                ]);

                Log::info('Submission attachment deleted', [
                    'submission_id' => $this->id,
                    'file_path' => $this->attachment_path
                ]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete submission attachment', [
                    'submission_id' => $this->id,
                    'file_path' => $this->attachment_path,
                    'error' => $e->getMessage()
                ]);
                
                return false;
            }
        }
        
        return false;
    }

    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    public function isImageAttachment()
    {
        if (!$this->attachment_type) {
            return false;
        }
        
        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($this->attachment_type, $imageTypes) || 
               (is_string($this->attachment_type) && strpos($this->attachment_type, 'image/') === 0);
    }

    /**
     * ✅ ADDED: Additional file type checks
     */
    public function isPdfAttachment()
    {
        return $this->attachment_type === 'application/pdf';
    }

    public function isDocumentAttachment()
    {
        $docTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        return in_array($this->attachment_type, $docTypes);
    }

    public function isSpreadsheetAttachment()
    {
        $spreadsheetTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        return in_array($this->attachment_type, $spreadsheetTypes);
    }

    /**
     * ✅ NEW: Helper method to get submission timeline
     */
    public function getTimelineAttribute()
    {
        $timeline = [];

        // Creation step
        $timeline[] = [
            'status' => 'created',
            'title' => 'Submission Created',
            'description' => 'Content submission created',
            'timestamp' => $this->created_at,
            'completed' => true,
            'admin' => 'System'
        ];

        // Payment step
        if ($this->payment) {
            $timeline[] = [
                'status' => 'payment',
                'title' => 'Payment Completed',
                'description' => 'Payment confirmed by admin',
                'timestamp' => $this->payment->confirmed_at ?? $this->payment->created_at,
                'completed' => $this->payment->status === 'confirmed',
                'admin' => $this->payment->confirmedBy->full_name ?? 'System'
            ];
        }

        // Submission step
        if ($this->submitted_at) {
            $timeline[] = [
                'status' => 'submitted',
                'title' => 'Content Submitted',
                'description' => 'Waiting for admin review',
                'timestamp' => $this->submitted_at,
                'completed' => true,
                'admin' => 'System'
            ];
        }

        // Approval or rejection step
        if ($this->isApproved()) {
            $timeline[] = [
                'status' => 'approved',
                'title' => 'Content Approved',
                'description' => 'Content approved and ready for publication',
                'timestamp' => $this->approved_at,
                'completed' => true,
                'admin' => $this->approvedBy->full_name ?? 'System'
            ];
        } elseif ($this->isRejected()) {
            $timeline[] = [
                'status' => 'rejected',
                'title' => 'Content Rejected',
                'description' => $this->rejection_reason,
                'timestamp' => $this->rejected_at,
                'completed' => true,
                'admin' => $this->rejectedBy->full_name ?? 'System'
            ];
        }

        if ($this->isPublished()) {
            $timeline[] = [
                'status' => 'published',
                'title' => 'Content Published',
                'description' => 'Content published to community',
                'timestamp' => $this->published_at,
                'completed' => true,
                'admin' => $this->publishedBy->full_name ?? 'System'
            ];
        }

        return $timeline;
    }

    /**
     * Static methods
     */
    public static function getStatusOptions()
    {
        return [
            'pending_payment' => 'Menunggu Pembayaran',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'published' => 'Dipublikasikan',
        ];
    }

    /**
     * ✅ ENHANCED: getUserSubmissionStats method dengan caching
     */
    public static function getUserSubmissionStats($userId)
    {
        return Cache::remember("user_submission_stats_{$userId}", 300, function() use ($userId) {
            $stats = [
                'total' => 0,
                'pending_payment' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'rejected' => 0,
                'published' => 0,
            ];

            $submissions = static::where('user_id', $userId)
                                ->select('status', DB::raw('count(*) as count'))
                                ->groupBy('status')
                                ->get();

            foreach ($submissions as $submission) {
                if (isset($stats[$submission->status])) {
                    $stats[$submission->status] = $submission->count;
                    $stats['total'] += $submission->count;
                }
            }

            return $stats;
        });
    }

    /**
     * ✅ ENHANCED: getAdminStats method dengan caching dan revenue calculation
     */
    public static function getAdminStats()
    {
        return Cache::remember('submission_admin_stats', 300, function() {
            $stats = [
                'total' => 0,
                'pending_payment' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'rejected' => 0,
                'published' => 0,
                'today' => 0,
                'this_week' => 0,
                'this_month' => 0,
                'revenue' => 0,
            ];

            try {
                // Status counts
                $submissions = static::select('status', DB::raw('count(*) as count'))
                                    ->groupBy('status')
                                    ->get();

                foreach ($submissions as $submission) {
                    if (isset($stats[$submission->status])) {
                        $stats[$submission->status] = $submission->count;
                        $stats['total'] += $submission->count;
                    }
                }

                // Time-based counts
                $stats['today'] = static::whereDate('created_at', today())->count();
                $stats['this_week'] = static::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
                $stats['this_month'] = static::whereMonth('created_at', now()->month)
                                            ->whereYear('created_at', now()->year)
                                            ->count();

                // Revenue calculation
                if (class_exists('\App\Models\Payment')) {
                    $stats['revenue'] = \App\Models\Payment::where('status', 'confirmed')->sum('amount') ?? 0;
                }

            } catch (\Exception $e) {
                Log::error('Failed to get admin submission stats', [
                    'error' => $e->getMessage()
                ]);
            }

            return $stats;
        });
    }

    /**
     * ✅ ADDED: Additional static helper methods
     */
    public static function getRecentSubmissions($limit = 10)
    {
        return static::with(['user', 'category', 'payment'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getPendingApprovalCount()
    {
        return static::pendingApproval()->count();
    }

    public static function getTotalRevenue()
    {
        if (class_exists('\App\Models\Payment')) {
            return \App\Models\Payment::where('status', 'confirmed')->sum('amount') ?? 0;
        }
        return 0;
    }

    public static function getSubmissionsByCategory()
    {
        return static::with('category')
                    ->select('category_id', DB::raw('count(*) as count'))
                    ->groupBy('category_id')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'category' => $item->category->name ?? 'Unknown',
                            'count' => $item->count
                        ];
                    });
    }

    public static function getSubmissionTrends($days = 30)
    {
        return static::selectRaw('DATE(created_at) as date, count(*) as count')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
    }

    /**
     * ✅ ADDED: Search functionality
     */
    public static function search($query)
    {
        return static::where(function($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        });
    }

    /**
     * ✅ ADDED: Validation helpers
     */
    public function validateForSubmission()
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Judul wajib diisi.';
        }

        if (empty($this->description)) {
            $errors[] = 'Deskripsi wajib diisi.';
        }

        if (strlen($this->description) < 50) {
            $errors[] = 'Deskripsi minimal 50 karakter.';
        }

        if (!$this->category_id) {
            $errors[] = 'Kategori wajib dipilih.';
        }

        return $errors;
    }

    public function getValidationErrors()
    {
        return $this->validateForSubmission();
    }

    public function isValid()
    {
        return empty($this->validateForSubmission());
    }

    /**
     * ✅ ENHANCED: Model events dengan cache management
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($submission) {
            // Clear cache when new submission is created
            Cache::forget('submission_admin_stats');
            Cache::forget("user_submission_stats_{$submission->user_id}");
            
            Log::info('Submission created', [
                'submission_id' => $submission->id,
                'user_id' => $submission->user_id,
                'title' => $submission->title
            ]);
        });

        static::updated(function ($submission) {
            // Clear cache when submission is updated
            Cache::forget('submission_admin_stats');
            Cache::forget("user_submission_stats_{$submission->user_id}");
            
            // Clear admin-specific cache if status changed
            if ($submission->isDirty('status')) {
                $adminUsers = \App\Models\User::where('role', 'admin')->pluck('id');
                foreach ($adminUsers as $adminId) {
                    Cache::forget('admin_pending_submissions_' . $adminId);
                    Cache::forget('admin_integrated_stats_' . $adminId);
                }
            }
        });

        static::deleting(function ($submission) {
            // Delete attachment file when submission is deleted
            $submission->deleteAttachment();
            
            // Clear cache
            Cache::forget('submission_admin_stats');
            Cache::forget("user_submission_stats_{$submission->user_id}");
            
            Log::info('Submission deleted', [
                'submission_id' => $submission->id,
                'user_id' => $submission->user_id
            ]);
        });
    }
}