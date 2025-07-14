<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlumniApprovalController extends Controller
{
    /**
     * Check if user is admin (digunakan di setiap method)
     */
    private function checkAdminAccess()
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized - Admin access required');
        }
    }

    /**
     * Tampilkan daftar alumni yang menunggu approval
     */
    public function index()
    {
        $this->checkAdminAccess();
        
        $pendingAlumni = User::where('role', 'alumni')
            ->where('is_verified', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.alumni-approval.index', compact('pendingAlumni'));
    }

    /**
     * Tampilkan detail alumni untuk review
     */
    public function show($id)
    {
        $this->checkAdminAccess();
        
        $alumni = User::where('role', 'alumni')
            ->where('is_verified', false)
            ->findOrFail($id);

        return view('admin.alumni-approval.show', compact('alumni'));
    }

    /**
     * Approve alumni
     */
    public function approve($id)
    {
        try {
            $this->checkAdminAccess();
            
            $alumni = User::where('role', 'alumni')
                ->where('is_verified', false)
                ->findOrFail($id);

            // Log the action
            Log::info('Admin approving alumni', [
                'admin_id' => auth()->id(),
                'alumni_id' => $alumni->id,
                'alumni_name' => $alumni->full_name,
                'alumni_email' => $alumni->email
            ]);

            // Update alumni status
            $alumni->update(['is_verified' => true]);

            // ✅ CLEAR NAVBAR CACHE - IMPORTANT!
            $this->clearAlumniCountCache();

            // Log success
            Log::info('Alumni approved successfully', [
                'alumni_id' => $alumni->id,
                'approved_by' => auth()->id()
            ]);

            // Use URL redirect instead of route to avoid naming conflicts
            return redirect(url('/admin/alumni-approval'))
                ->with('success', "Alumni {$alumni->full_name} berhasil disetujui dan dapat mengakses sistem!");

        } catch (\Exception $e) {
            Log::error('Error approving alumni', [
                'alumni_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyetujui alumni. Silakan coba lagi.');
        }
    }

    /**
     * Reject alumni
     */
    public function reject($id)
    {
        try {
            $this->checkAdminAccess();
            
            $alumni = User::where('role', 'alumni')
                ->where('is_verified', false)
                ->findOrFail($id);

            // Log the action
            Log::info('Admin rejecting alumni', [
                'admin_id' => auth()->id(),
                'alumni_id' => $alumni->id,
                'alumni_name' => $alumni->full_name,
                'alumni_email' => $alumni->email,
                'has_document' => !empty($alumni->verification_doc_path)
            ]);

            // Store alumni data for success message
            $alumniName = $alumni->full_name;

            // Hapus file verification jika ada
            if ($alumni->verification_doc_path) {
                try {
                    // Remove 'storage/' prefix if exists for Storage::delete
                    $filePath = str_replace('storage/', 'public/', $alumni->verification_doc_path);
                    Storage::delete($filePath);
                    
                    Log::info('Alumni verification document deleted', [
                        'file_path' => $filePath,
                        'alumni_id' => $alumni->id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete alumni verification document', [
                        'file_path' => $alumni->verification_doc_path,
                        'alumni_id' => $alumni->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Delete alumni record
            $alumni->delete();

            // ✅ CLEAR NAVBAR CACHE - IMPORTANT!
            $this->clearAlumniCountCache();

            Log::info('Alumni rejected and deleted successfully', [
                'alumni_name' => $alumniName,
                'deleted_by' => auth()->id()
            ]);

            // Use URL redirect instead of route to avoid naming conflicts
            return redirect(url('/admin/alumni-approval'))
                ->with('success', "Alumni {$alumniName} berhasil ditolak dan dihapus dari sistem.");

        } catch (\Exception $e) {
            Log::error('Error rejecting alumni', [
                'alumni_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menolak alumni. Silakan coba lagi.');
        }
    }

    /**
     * Download verification document
     */
    public function downloadDocument($id)
    {
        try {
            $this->checkAdminAccess();
            
            $alumni = User::where('role', 'alumni')
                ->where('is_verified', false)
                ->findOrFail($id);

            if (!$alumni->verification_doc_path) {
                return redirect()->back()->with('error', 'Alumni tidak memiliki dokumen verifikasi');
            }

            // Convert storage path to actual file path
            $filePath = str_replace('storage/', 'public/', $alumni->verification_doc_path);

            if (!Storage::exists($filePath)) {
                Log::warning('Alumni verification document not found', [
                    'alumni_id' => $id,
                    'file_path' => $filePath,
                    'stored_path' => $alumni->verification_doc_path
                ]);

                return redirect()->back()->with('error', 'Dokumen verifikasi tidak ditemukan di server');
            }

            // Log download activity
            Log::info('Alumni verification document downloaded', [
                'alumni_id' => $id,
                'alumni_name' => $alumni->full_name,
                'downloaded_by' => auth()->id(),
                'file_path' => $filePath
            ]);

            // Generate appropriate filename for download
            $originalName = basename($alumni->verification_doc_path);
            $downloadName = "verifikasi_alumni_{$alumni->username}_{$originalName}";

            return Storage::download($filePath, $downloadName);

        } catch (\Exception $e) {
            Log::error('Error downloading alumni verification document', [
                'alumni_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mendownload dokumen. Silakan coba lagi.');
        }
    }

    /**
     * Get statistics for admin dashboard (bonus method)
     */
    public function getStats()
    {
        $this->checkAdminAccess();

        try {
            $stats = [
                'pending_alumni' => User::where('role', 'alumni')->where('is_verified', false)->count(),
                'approved_alumni' => User::where('role', 'alumni')->where('is_verified', true)->count(),
                'alumni_with_documents' => User::where('role', 'alumni')
                    ->where('is_verified', false)
                    ->whereNotNull('verification_doc_path')
                    ->count(),
                'alumni_with_unud_email' => User::where('role', 'alumni')
                    ->where('is_verified', false)
                    ->where(function($query) {
                        $query->where('email', 'like', '%@unud.ac.id')
                              ->orWhere('email', 'like', '%@student.unud.ac.id');
                    })
                    ->count(),
                'total_alumni' => User::where('role', 'alumni')->count(),
                'recent_registrations' => User::where('role', 'alumni')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting alumni approval stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * ✅ CLEAR NAVBAR CACHE METHOD
     * This clears the cached alumni count in navbar badge
     */
    private function clearAlumniCountCache()
    {
        try {
            // Clear cache for current admin
            $cacheKey = 'alumni_pending_count_' . auth()->id();
            cache()->forget($cacheKey);
            
            // Clear cache for all admins (in case multiple admins)
            $adminUsers = User::where('role', 'admin')->pluck('id');
            foreach ($adminUsers as $adminId) {
                $adminCacheKey = 'alumni_pending_count_' . $adminId;
                cache()->forget($adminCacheKey);
            }
            
            // Clear any general alumni cache
            cache()->forget('alumni_pending_count');
            cache()->forget('alumni_count_cache');
            
            Log::info('Alumni count cache cleared successfully', [
                'cleared_by' => auth()->id(),
                'admin_count' => $adminUsers->count()
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear alumni count cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ MANUAL CACHE REFRESH ENDPOINT
     * Admin can hit this to manually refresh navbar count
     */
    public function refreshCache()
    {
        $this->checkAdminAccess();
        
        try {
            $this->clearAlumniCountCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Cache refreshed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache'
            ], 500);
        }
    }
}