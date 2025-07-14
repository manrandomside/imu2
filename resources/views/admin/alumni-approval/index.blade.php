{{-- resources/views/admin/alumni-approval/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>📋 Approval Alumni - {{ $pendingAlumni->count() }} menunggu</h4>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($pendingAlumni->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Tidak ada alumni yang menunggu approval saat ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Prodi</th>
                                        <th>Status Verifikasi</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingAlumni as $index => $alumni)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $alumni->full_name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $alumni->username }}</span>
                                            </td>
                                            <td>
                                                @if($alumni->email)
                                                    <span class="text-success">
                                                        <i class="fas fa-envelope"></i>
                                                        {{ $alumni->email }}
                                                    </span>
                                                    @if(str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id'))
                                                        <span class="badge badge-success">Email UNUD</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Tidak ada email</span>
                                                @endif
                                            </td>
                                            <td>{{ $alumni->prodi ?? 'Tidak diisi' }}</td>
                                            <td>
                                                @if($alumni->verification_doc_path)
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-file-pdf"></i>
                                                        Ada Dokumen
                                                    </span>
                                                @endif
                                                
                                                @if($alumni->email && (str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id')))
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-check"></i>
                                                        Email Valid
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $alumni->created_at->format('d M Y H:i') }}</td>
                                            <td>
                                                <a href="{{ route('admin.alumni-approval.show', $alumni->id) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection