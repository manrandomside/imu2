<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
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
     * ✅ UPDATED: Enhanced canBeApproved() method with payment validation
     */
    public function canBeApproved()
    {
        return $this->status === 'pending_approval' && 
               $this->payment && 
               $this->payment->status === 'confirmed';
    }

    /**
     * ✅ ADDED: New validation methods
     */
    public function canBeRejected()
    {
        return in_array($this->status, ['pending_approval', 'approved']);
    }

    public function canBePublished()
    {
        return $this->status === 'approved';
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
            \App\Models\Notification::createForUser(
                1, // Admin user ID - you might want to get this dynamically
                'Submission Pending Approval',
                "New submission '{$this->title}' is pending approval.",
                'submission_pending_approval',
                ['submission_id' => $this->id]
            );
        }

        return $this;
    }

    /**
     * ✅ UPDATED: Enhanced approve method with better notification handling
     */
    public function approve($adminId, $shouldPublish = false)
    {
        $status = $shouldPublish ? 'published' : 'approved';
        
        $this->update([
            'status' => $status,
            'approved_at' => now(),
            'approved_by' => $adminId,
            'rejection_reason' => null,
        ]);

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            $message = $shouldPublish 
                ? "Konten '{$this->title}' telah disetujui dan dipublikasikan."
                : "Konten '{$this->title}' telah disetujui.";
                
            \App\Models\Notification::createForUser(
                $this->user_id,
                'Konten Disetujui',
                $message,
                $shouldPublish ? 'submission_published' : 'submission_approved',
                ['submission_id' => $this->id]
            );
        }

        // If published, create the actual group message
        if ($shouldPublish) {
            $this->publishToGroup();
        }

        return $this;
    }

    /**
     * ✅ UPDATED: Enhanced reject method
     */
    public function reject($reason, $adminId = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => null,
            'rejection_reason' => $reason,
        ]);

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createForUser(
                $this->user_id,
                'Konten Ditolak',
                "Konten '{$this->title}' ditolak. Alasan: {$reason}",
                'submission_rejected',
                ['submission_id' => $this->id]
            );
        }

        return $this;
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
     * ✅ ADDED: New method to publish content
     */
    public function publish()
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Konten harus disetujui terlebih dahulu sebelum dipublikasikan.');
        }

        $this->update(['status' => 'published']);

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createForUser(
                $this->user_id,
                'Konten Dipublikasikan',
                "Konten '{$this->title}' telah dipublikasikan di komunitas.",
                'submission_published',
                ['submission_id' => $this->id]
            );
        }

        return $this;
    }

    public function publishToGroup()
    {
        if ($this->status !== 'approved' && $this->status !== 'published') {
            return false;
        }

        // Find the corresponding chat group
        $chatGroup = \App\Models\ChatGroup::where('name', 'LIKE', '%' . $this->category->name . '%')->first();
        
        if (!$chatGroup) {
            // Create group if doesn't exist
            $chatGroup = \App\Models\ChatGroup::create([
                'name' => $this->category->name,
                'description' => $this->category->description,
                'creator_id' => 1, // Admin user
                'is_approved' => true,
            ]);
        }

        // Create group message (if GroupMessage model exists)
        $message = null;
        if (class_exists('\App\Models\GroupMessage')) {
            $message = \App\Models\GroupMessage::create([
                'group_id' => $chatGroup->id,
                'sender_id' => $this->user_id,
                'message_content' => "📢 {$this->title}\n\n{$this->description}",
                'attachment_path' => $this->attachment_path,
                'attachment_type' => $this->attachment_type,
                'attachment_name' => $this->attachment_name,
                'attachment_size' => $this->attachment_size,
            ]);
        }

        // Update status to published
        $this->update(['status' => 'published']);

        return $message ?? true;
    }

    /**
     * File Management Methods
     */
    public function deleteAttachment()
    {
        if ($this->attachment_path) {
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
            
            return true;
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
     * ✅ UPDATED: Enhanced getUserSubmissionStats method
     */
    public static function getUserSubmissionStats($userId)
    {
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
    }

    /**
     * ✅ UPDATED: Enhanced getAdminStats method
     */
    public static function getAdminStats()
    {
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

        return $stats;
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
}