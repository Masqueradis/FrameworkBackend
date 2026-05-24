@extends('layouts.admin')

@section('title', 'Reports Management')

@section('content')
    <div>

        <div class="d-flex justify-content-end align-items-center mb-4">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Refresh Page
            </a>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="mb-0 fw-bold text-uppercase text-secondary">Request New Report</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">

                        <div class="col-md-4">
                            <label for="type" class="form-label text-muted small">Report Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="sales">Sales (Orders)</option>
                                <option value="inventory">Inventory (Products)</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="date_from" class="form-label text-muted small">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control"
                                   value="{{ old('date_from', now()->subMonth()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label for="date_to" class="form-label text-muted small">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control"
                                   value="{{ old('date_to', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Generate
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
                <h6 class="mb-0 fw-bold text-uppercase text-secondary">Reports History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="ps-4 text-muted small text-uppercase">ID</th>
                            <th class="text-muted small text-uppercase">Type</th>
                            <th class="text-muted small text-uppercase">Period</th>
                            <th class="text-muted small text-uppercase">Requested At</th>
                            <th class="text-muted small text-uppercase">Status</th>
                            <th class="text-end pe-4 text-muted small text-uppercase">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#{{ $report->id }}</td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $report->type === 'sales' ? 'Sales' : 'Inventory' }}</span>
                                </td>
                                <td>
                                    @if(!empty($report->filters['date_from']) && !empty($report->filters['date_to']))
                                        {{ \Carbon\Carbon::parse($report->filters['date_from'])->format('d.m.Y') }}
                                        &mdash;
                                    {{ \Carbon\Carbon::parse($report->filters['date_to'])->format('d.m.Y') }}
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $report->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($report->status === \App\Enums\ReportStatus::Pending)
                                        <span class="badge bg-secondary bg-opacity-75">Pending</span>
                                    @elseif($report->status === \App\Enums\ReportStatus::Processing)
                                        <span class="badge bg-primary bg-opacity-75">Processing...</span>
                                    @elseif($report->status === \App\Enums\ReportStatus::Completed)
                                        <span class="badge bg-success bg-opacity-75">Completed</span>
                                    @elseif($report->status === \App\Enums\ReportStatus::Failed)
                                        <span class="badge bg-danger bg-opacity-75">Failed</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($report->status === \App\Enums\ReportStatus::Completed && $report->file_path)
                                        <a href="{{ route('admin.reports.download', $report) }}" class="btn btn-sm btn-outline-success">
                                            Download CSV
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary border-0" disabled>Unavailable</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    You haven't requested any reports yet. Fill out the form above to start.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        setTimeout(function(){
            window.location.reload();
        }, 30000);
    </script>
@endpush
