<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Payment;
use App\Models\ContentSubmission;
use App\Models\SubmissionCategory;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the integrated admin dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check admin/moderator access
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        // Get filter parameters
        $statusFilter = $request->get('status', 'all');
        $categoryFilter = $request->get('category', 'all');
        
        // Build submissions query with related data
        $submissionsQuery = ContentSubmission::with([
            'user:id,full_name,prodi,email',
            'category:id,name,icon,price',
            'payment:id,status,amount,created_at,updated_at,content_submission_id'
        ]);

        // Apply filters
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'payment_pending') {
                $submissionsQuery->where(function($q) {
                    $q->whereDoesntHave('payment')
                      ->orWhereHas('payment', function($pq) {
                          $pq->where('status', 'pending');
                      });
                });
            } else {
                $submissionsQuery->where('status', $statusFilter);
            }
        }

        if ($categoryFilter !== 'all') {
            $submissionsQuery->where('category_id', $categoryFilter);
        }

        // Order by priority: pending payments first, then by creation date
        $submissions = $submissionsQuery
            ->orderByRaw("
                CASE 
                    WHEN status = 'pending_approval' THEN 1
                    WHEN status = 'approved' THEN 2
                    WHEN status = 'draft' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = $this->getIntegratedStats();
        
        // Get categories for filter
        $categories = SubmissionCategory::getActiveCategories();

        return view('admin.dashboard.index', compact(
            'submissions',
            'stats', 
            'categories',
            'statusFilter',
            'categoryFilter'
        ));
    }

    /**
     * Get integrated statistics for dashboard
     */
    private function getIntegratedStats()
    {
        // Cache stats for 5 minutes to improve performance
        return Cache::remember('admin_integrated_stats_' . Auth::id(), 300, function() {
            
            // Payment statistics
            $pendingPayments = Payment::where('status', 'pending')->count();
            $confirmedPayments = Payment::where('status', 'confirmed')->count();
            $rejectedPayments = Payment::where('status', 'rejected')->count();
            $totalPayments = Payment::count();
            
            // Submission statistics  
            $pendingReviews = ContentSubmission::where('status', 'pending_approval')->count();
            $approvedSubmissions = ContentSubmission::where('status', 'approved')->count();
            $publishedSubmissions = ContentSubmission::where('status', 'published')->count();
            $readyToPublish = ContentSubmission::where('status', 'approved')->count();
            
            // Revenue statistics
            $todayRevenue = Payment::where('status', 'confirmed')
                ->whereDate('updated_at', today())
                ->sum('amount');
                
            $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');
            
            $todayPayments = Payment::where('status', 'confirmed')
                ->whereDate('updated_at', today())
                ->count();

            // Weekly trends
            $weeklyPayments = Payment::where('status', 'confirmed')
                ->where('updated_at', '>=', now()->subDays(7))
                ->count();
                
            $weeklyRevenue = Payment::where('status', 'confirmed')
                ->where('updated_at', '>=', now()->subDays(7))
                ->sum('amount');

            return [
                // Payment stats
                'pending_payments' => $pendingPayments,
                'confirmed_payments' => $confirmedPayments,
                'rejected_payments' => $rejectedPayments,
                'total_payments' => $totalPayments,
                
                // Submission stats
                'pending_reviews' => $pendingReviews,
                'approved_submissions' => $approvedSubmissions,
                'published_submissions' => $publishedSubmissions,
                'ready_to_publish' => $readyToPublish,
                
                // Revenue stats
                'today_revenue' => $todayRevenue,
                'total_revenue' => $totalRevenue,
                'today_payments' => $todayPayments,
                'weekly_payments' => $weeklyPayments,
                'weekly_revenue' => $weeklyRevenue,
                
                // Trend calculations
                'revenue_trend' => $this->calculateRevenueTrend(),
                'payment_trend' => $this->calculatePaymentTrend(),
            ];
        });
    }

    /**
     * Calculate revenue trend (percentage change from last week)
     */
    private function calculateRevenueTrend()
    {
        $thisWeekRevenue = Payment::where('status', 'confirmed')
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');
            
        $lastWeekRevenue = Payment::where('status', 'confirmed')
            ->whereBetween('updated_at', [
                now()->subWeek()->startOfWeek(), 
                now()->subWeek()->endOfWeek()
            ])
            ->sum('amount');

        if ($lastWeekRevenue == 0) {
            return $thisWeekRevenue > 0 ? 100 : 0;
        }

        return round((($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1);
    }

    /**
     * Calculate payment trend (percentage change from last week)
     */
    private function calculatePaymentTrend()
    {
        $thisWeekPayments = Payment::where('status', 'confirmed')
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        $lastWeekPayments = Payment::where('status', 'confirmed')
            ->whereBetween('updated_at', [
                now()->subWeek()->startOfWeek(), 
                now()->subWeek()->endOfWeek()
            ])
            ->count();

        if ($lastWeekPayments == 0) {
            return $thisWeekPayments > 0 ? 100 : 0;
        }

        return round((($thisWeekPayments - $lastWeekPayments) / $lastWeekPayments) * 100, 1);
    }

    /**
     * API endpoint for real-time stats (for dashboard refresh)
     */
    public function getStats()
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Clear cache and get fresh stats
        Cache::forget('admin_integrated_stats_' . Auth::id());
        $stats = $this->getIntegratedStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Handle bulk actions for submissions/payments
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'action' => 'required|in:approve_submissions,publish_submissions,confirm_payments',
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        $processed = 0;
        $errors = [];

        try {
            switch ($action) {
                case 'approve_submissions':
                    $submissions = ContentSubmission::whereIn('id', $ids)
                        ->where('status', 'pending_approval')
                        ->get();
                    
                    foreach ($submissions as $submission) {
                        if ($submission->approve($user->id)) {
                            $processed++;
                        } else {
                            $errors[] = "Gagal menyetujui: {$submission->title}";
                        }
                    }
                    break;

                case 'publish_submissions':
                    $submissions = ContentSubmission::whereIn('id', $ids)
                        ->where('status', 'approved')
                        ->get();
                    
                    foreach ($submissions as $submission) {
                        if ($submission->publish($user->id)) {
                            $processed++;
                        } else {
                            $errors[] = "Gagal mempublikasi: {$submission->title}";
                        }
                    }
                    break;

                case 'confirm_payments':
                    $payments = Payment::whereIn('id', $ids)
                        ->where('status', 'pending')
                        ->get();
                    
                    foreach ($payments as $payment) {
                        if ($payment->confirm($user->id)) {
                            $processed++;
                        } else {
                            $errors[] = "Gagal mengkonfirmasi pembayaran ID: {$payment->id}";
                        }
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil memproses {$processed} item.",
                'processed' => $processed,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses bulk action.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export dashboard data to CSV
     */
    public function exportData(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $submissions = ContentSubmission::with(['user', 'category', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'admin_dashboard_export_' . now()->format('Y-m-d_H-i') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($submissions) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Category', 'User', 'Status', 
                'Payment Status', 'Payment Amount', 'Created At', 'Updated At'
            ]);

            // CSV data
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->id,
                    $submission->title,
                    $submission->category->name,
                    $submission->user->full_name,
                    $submission->status,
                    $submission->payment ? $submission->payment->status : 'No Payment',
                    $submission->payment ? $submission->payment->amount : 0,
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $submission->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}