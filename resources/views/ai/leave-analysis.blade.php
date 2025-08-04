@extends('layouts.app')

@section('title', 'AI Leave Analysis')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">🤖 AI-Powered Leave Analysis</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="refreshAnalysis()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-outline-success" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('ai.leave-analysis') }}" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Employee</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Period</label>
                            <select name="period" class="form-select">
                                <option value="week" {{ $selectedPeriod == 'week' ? 'selected' : '' }}>Last Week</option>
                                <option value="month" {{ $selectedPeriod == 'month' ? 'selected' : '' }}>Last Month</option>
                                <option value="quarter" {{ $selectedPeriod == 'quarter' ? 'selected' : '' }}>Last Quarter</option>
                                <option value="year" {{ $selectedPeriod == 'year' ? 'selected' : '' }}>Last Year</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="fas fa-search"></i> Analyze
                            </button>
                        </div>
                    </form>

                    @if(isset($analysis['summary']))
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $analysis['statistics']['total_applications'] ?? 0 }}</h3>
                                        <p class="mb-0">Total Applications</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $analysis['statistics']['approved_count'] ?? 0 }}</h3>
                                        <p class="mb-0">Approved</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $analysis['statistics']['pending_count'] ?? 0 }}</h3>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ number_format($analysis['statistics']['average_duration'] ?? 0, 1) }}</h3>
                                        <p class="mb-0">Avg Duration (days)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Insights -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-brain text-primary"></i> AI Insights</h5>
                                    </div>
                                    <div class="card-body">
                                        @if(isset($analysis['insights']) && count($analysis['insights']) > 0)
                                            @foreach($analysis['insights'] as $insight)
                                                <div class="alert alert-info">
                                                    <i class="fas fa-lightbulb"></i> {{ $insight }}
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted">No specific insights available for this period.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-exclamation-triangle text-warning"></i> Risk Alerts</h5>
                                    </div>
                                    <div class="card-body">
                                        @if(isset($analysis['risk_alerts']) && count($analysis['risk_alerts']) > 0)
                                            @foreach($analysis['risk_alerts'] as $alert)
                                                <div class="alert alert-{{ $alert['severity'] == 'high' ? 'danger' : ($alert['severity'] == 'medium' ? 'warning' : 'info') }}">
                                                    <strong>{{ ucfirst($alert['type'] ?? 'Alert') }}:</strong> 
                                                    {{ $alert['description'] ?? 'No description available' }}
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-success"><i class="fas fa-check-circle"></i> No risk alerts detected</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-recommendations text-success"></i> AI Recommendations</h5>
                                    </div>
                                    <div class="card-body">
                                        @if(isset($analysis['recommendations']) && count($analysis['recommendations']) > 0)
                                            <div class="row">
                                                @foreach($analysis['recommendations'] as $recommendation)
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card border-left-{{ $recommendation['priority'] == 'high' ? 'danger' : ($recommendation['priority'] == 'medium' ? 'warning' : 'success') }}">
                                                            <div class="card-body">
                                                                <h6 class="card-title">
                                                                    {{ ucfirst($recommendation['category'] ?? 'General') }}
                                                                    <span class="badge badge-{{ $recommendation['priority'] == 'high' ? 'danger' : ($recommendation['priority'] == 'medium' ? 'warning' : 'success') }} float-right">
                                                                        {{ ucfirst($recommendation['priority'] ?? 'Low') }}
                                                                    </span>
                                                                </h6>
                                                                <p class="card-text">{{ $recommendation['action'] ?? 'No action specified' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted">No specific recommendations available.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Predictions -->
                        @if(isset($analysis['predictions']) && count($analysis['predictions']) > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-crystal-ball text-info"></i> AI Predictions</h5>
                                        </div>
                                        <div class="card-body">
                                            @foreach($analysis['predictions'] as $prediction)
                                                <div class="alert alert-light border-left-info">
                                                    <i class="fas fa-chart-line"></i> {{ $prediction }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Analyzing leave data with AI...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshAnalysis() {
    window.location.reload();
}

function exportReport() {
    // Implement export functionality
    alert('Export functionality will be implemented in next iteration');
}
</script>

<style>
.border-left-primary { border-left: 4px solid #007bff !important; }
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-danger { border-left: 4px solid #dc3545 !important; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }
</style>
@endsection
