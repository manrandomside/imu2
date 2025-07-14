<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumniApprovalController
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
        $this->checkAdminAccess();
        
        $alumni = User::where('role', 'alumni')
            ->where('is_verified', false)
            ->findOrFail($id);

        $alumni->update(['is_verified' => true]);

        return redirect()->route('admin.alumni-approval.index')
            ->with('success', "Alumni {$alumni->full_name} berhasil disetujui");
    }

    /**
     * Reject alumni
     */
    public function reject($id)
    {
        $this->checkAdminAccess();
        
        $alumni = User::where('role', 'alumni')
            ->where('is_verified', false)
            ->findOrFail($id);

        // Hapus file verification jika ada
        if ($alumni->verification_doc_path) {
            Storage::delete($alumni->verification_doc_path);
        }

        $alumni->delete();

        return redirect()->route('admin.alumni-approval.index')
            ->with('success', "Alumni {$alumni->full_name} berhasil ditolak dan dihapus");
    }

    /**
     * Download verification document
     */
    public function downloadDocument($id)
    {
        $this->checkAdminAccess();
        
        $alumni = User::where('role', 'alumni')
            ->where('is_verified', false)
            ->findOrFail($id);

        if (!$alumni->verification_doc_path || !Storage::exists($alumni->verification_doc_path)) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        return Storage::download($alumni->verification_doc_path);
    }
}