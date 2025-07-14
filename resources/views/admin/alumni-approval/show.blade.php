{{-- resources/views/admin/alumni-approval/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>👤 Detail Alumni - {{ $alumni->full_name }}</h4>
                        <a href="{{ route('admin.alumni-approval.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="row">
                        {{-- Informasi Dasar --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>📋 Informasi Dasar</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Nama Lengkap:</strong></td>
                                            <td>{{ $alumni->full_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Username:</strong></td>
                                            <td><span class="badge badge-secondary">{{ $alumni->username }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>
                                                @if($alumni->email)
                                                    <span class="text-success">
                                                        <i class="fas fa-envelope"></i>
                                                        {{ $alumni->email }}
                                                    </span>
                                                    @if(str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id'))
                                                        <br><span class="badge badge-success mt-1">Email UNUD Valid</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Tidak ada email</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Prodi:</strong></td>
                                            <td>{{ $alumni->prodi ?? 'Tidak diisi' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fakultas:</strong></td>
                                            <td>{{ $alumni->fakultas ?? 'Tidak diisi' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Gender:</strong></td>
                                            <td>{{ $alumni->gender ?? 'Tidak diisi' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Daftar:</strong></td>
                                            <td>{{ $alumni->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Dokumen Verifikasi --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>📄 Dokumen Verifikasi</h5>
                                </div>
                                <div class="card-body">
                                    @if($alumni->verification_doc_path)
                                        <div class="alert alert-info">
                                            <i class="fas fa-file-pdf"></i>
                                            <strong>Ada dokumen verifikasi</strong>
                                            <br>
                                            <small>Dokumen PKKMB/KTM telah diupload</small>
                                        </div>
                                        <a href="{{ route('admin.alumni-approval.download', $alumni->id) }}" 
                                           class="btn btn-primary">
                                            <i class="fas fa-download"></i>
                                            Download Dokumen
                                        </a>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Tidak ada dokumen</strong>
                                            <br>
                                            <small>Alumni tidak mengupload dokumen verifikasi</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Verifikasi --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>✅ Status Verifikasi</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    @php
                                        $hasUnudEmail = $alumni->email && (str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id'));
                                        $hasDocument = $alumni->verification_doc_path;
                                        $isValid = $hasUnudEmail || $hasDocument;
                                    @endphp

                                    @if($hasUnudEmail)
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i>
                                            <strong>Email UNUD terverifikasi</strong>
                                            <br>
                                            Alumni memiliki email UNUD yang valid, tidak perlu dokumen tambahan.
                                        </div>
                                    @elseif($hasDocument)
                                        <div class="alert alert-info">
                                            <i class="fas fa-file-check"></i>
                                            <strong>Dokumen verifikasi tersedia</strong>
                                            <br>
                                            Alumni mengupload dokumen PKKMB/KTM karena tidak memiliki email UNUD.
                                        </div>
                                    @else
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle"></i>
                                            <strong>Tidak ada verifikasi</strong>
                                            <br>
                                            Alumni tidak memiliki email UNUD dan tidak mengupload dokumen.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Aksi --}}
                    <div class="card">
                        <div class="card-header">
                            <h5>⚡ Aksi</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form method="POST" action="{{ route('admin.alumni-approval.approve', $alumni->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-lg btn-block" 
                                                onclick="return confirm('Yakin ingin menyetujui alumni ini?')">
                                            <i class="fas fa-check"></i>
                                            Setujui Alumni
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form method="POST" action="{{ route('admin.alumni-approval.reject', $alumni->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-lg btn-block" 
                                                onclick="return confirm('Yakin ingin menolak dan menghapus alumni ini?')">
                                            <i class="fas fa-times"></i>
                                            Tolak & Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection