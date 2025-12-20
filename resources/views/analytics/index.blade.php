@extends('layouts.app')

@section('content')
    <style>
        .tm-segmented-control {
            background: rgba(0, 0, 0, 0.04);
            padding: 4px;
            border-radius: 12px;
            display: inline-flex;
            gap: 4px;
        }

        .tm-segmented-item {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--tm-muted);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            line-height: 1;
            border: 1px solid transparent;
        }

        .tm-segmented-item i {
            font-size: 15px;
            margin-bottom: 2px;
            opacity: 0.7;
        }

        .tm-segmented-item:hover {
            color: var(--tm-text);
            background: rgba(255, 255, 255, 0.5);
        }

        .tm-segmented-item.active {
            background: white;
            color: var(--tm-primary);
            box-shadow: 0 2px 6px rgba(179, 32, 32, 0.08);
            font-weight: 600;
            border-color: rgba(0, 0, 0, 0.02);
        }

        .tm-segmented-item.active i {
            opacity: 1;
        }

        /* Filter Styles */
        .tm-filter-container {
            background: rgba(0, 0, 0, 0.04);
            padding: 4px;
            border-radius: 12px;
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }

        .tm-filter-select {
            appearance: none;
            border: 1px solid transparent;
            background-color: transparent;
            padding: 8px 32px 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--tm-muted);
            cursor: pointer;
            outline: none;
            transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);

            /* Chevron */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 10px 10px;
        }

        .tm-filter-select:hover {
            color: var(--tm-text);
            background-color: rgba(255, 255, 255, 0.5);
        }

        .tm-filter-select:focus {
            background-color: white;
            color: var(--tm-text);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .tm-filter-select.active {
            background-color: white;
            color: var(--tm-primary);
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(179, 32, 32, 0.08);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23b32020' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }
    </style>
    <div class="tm-header">
        <div>
            <h2>Analytics</h2>
            <div class="text-muted">Visual overview of revenue, staff and billing performance.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>

    @php
        $totalStaff = $staffDistribution->sum('total');
        $totalBills = $billSummaries->sum('bills');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="tm-card h-100">
                <div class="tm-card-body tm-kpi">
                    <span class="label">Total Revenue</span>
                    <span class="value">RM {{ number_format($totalRevenue, 2) }}</span>
                    <span class="text-muted">All-time bill revenue</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tm-card h-100">
                <div class="tm-card-body tm-kpi">
                    <span class="label">Total Staff</span>
                    <span class="value">{{ $totalStaff }}</span>
                    <span class="text-muted">Across all companies</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tm-card h-100">
                <div class="tm-card-body tm-kpi">
                    <span class="label">Total Bills</span>
                    <span class="value">{{ $totalBills }}</span>
                    <span class="text-muted">All recorded bills</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div class="tm-segmented-control">
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'daily']) }}"
                class="tm-segmented-item {{ $filter === 'daily' || !in_array($filter, ['monthly', 'yearly']) ? 'active' : '' }}">
                <i class="bi bi-calendar-date"></i> Daily
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'monthly']) }}"
                class="tm-segmented-item {{ $filter === 'monthly' ? 'active' : '' }}">
                <i class="bi bi-calendar-month"></i> Monthly
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'yearly']) }}"
                class="tm-segmented-item {{ $filter === 'yearly' ? 'active' : '' }}">
                <i class="bi bi-calendar3"></i> Yearly
            </a>
        </div>

        <form action="{{ route('analytics.index') }}" method="GET" class="d-flex align-items-center gap-3">
            <input type="hidden" name="filter" value="{{ $filter }}">

            <div class="tm-filter-container">
                <select name="year" class="tm-filter-select {{ $selectedYear ? 'active' : '' }}"
                    onchange="this.form.submit()">
                    <option value="">Year</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <div style="width: 1px; height: 16px; background: rgba(0,0,0,0.1);"></div>

                <select name="month" class="tm-filter-select {{ $selectedMonth ? 'active' : '' }}"
                    onchange="this.form.submit()">
                    <option value="">Month</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endforeach
                </select>

                <div style="width: 1px; height: 16px; background: rgba(0,0,0,0.1);"></div>

                <select name="day" class="tm-filter-select {{ $selectedDay ? 'active' : '' }}"
                    onchange="this.form.submit()">
                    <option value="">Day</option>
                    @foreach(range(1, 31) as $d)
                        <option value="{{ $d }}" {{ $selectedDay == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            @if($selectedYear || $selectedMonth || $selectedDay)
                <a href="{{ route('analytics.index', ['filter' => $filter]) }}"
                    class="btn btn-sm btn-link text-danger text-decoration-none px-0">
                    <i class="bi bi-x-circle"></i> Clear filters
                </a>
            @endif
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="tm-card h-100">
                <div class="tm-card-header">Revenue Trend</div>
                <div class="tm-card-body">
                    <canvas id="revenueTrendChart" height="120"></canvas>
                    @if($revenueTrend->isEmpty())
                        <div class="tm-empty-state mt-3">
                            <i class="bi bi-graph-up"></i>
                            <div class="title">No revenue data yet</div>
                            <div class="text-muted">Create bills to see the trend.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="tm-card h-100">
                <div class="tm-card-header">Staff Distribution</div>
                <div class="tm-card-body">
                    <canvas id="staffChart" height="220"></canvas>
                    @if($staffDistribution->isEmpty())
                        <div class="tm-empty-state mt-3">
                            <i class="bi bi-people"></i>
                            <div class="title">No staff yet</div>
                            <div class="text-muted">Add staff to companies to populate.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="tm-card h-100 tm-table">
                <div class="tm-card-header d-flex justify-content-between align-items-center">
                    <span>Bill Revenue by Company</span>
                    <small class="text-muted">RM</small>
                </div>
                <div class="tm-card-body">
                    <canvas id="revenueByCompanyChart" height="220"></canvas>
                    @if($billSummaries->isEmpty())
                        <div class="tm-empty-state mt-3">
                            <i class="bi bi-building"></i>
                            <div class="title">No bills recorded</div>
                            <div class="text-muted">Add bills to see company revenue.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="tm-card h-100 tm-table">
                <div class="tm-card-header">Bill Counts by Company</div>
                <div class="tm-card-body">
                    <canvas id="billCountChart" height="220"></canvas>
                    @if($billSummaries->isEmpty())
                        <div class="tm-empty-state mt-3">
                            <i class="bi bi-receipt"></i>
                            <div class="title">No bills recorded</div>
                            <div class="text-muted">Add bills to view distribution.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const palette = {
            primary: '#b32020',
            primaryLight: '#fef2f2',
            accent: '#ff8c1a',
            neutral: '#6b7280'
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
        };

        // Revenue trend line
        const revenueTrendCtx = document.getElementById('revenueTrendChart');
        if (revenueTrendCtx && {{ $revenueTrend->isNotEmpty() ? 'true' : 'false' }}) {
            new Chart(revenueTrendCtx, {
                type: 'line',
                data: {
                    labels: @json($revenueTrend->pluck('label')),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($revenueTrend->pluck('revenue')),
                        borderColor: palette.primary,
                        backgroundColor: 'rgba(179, 32, 32, 0.08)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: palette.primary,
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => formatCurrency(ctx.parsed.y) } }
                    },
                    scales: {
                        y: {
                            ticks: { callback: (val) => formatCurrency(val) },
                            grid: { color: '#f3f4f6' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Staff per company donut
        const staffCtx = document.getElementById('staffChart');
        if (staffCtx && {{ $staffDistribution->isNotEmpty() ? 'true' : 'false' }}) {
            new Chart(staffCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($staffDistribution->pluck('company')),
                    datasets: [{
                        data: @json($staffDistribution->pluck('total')),
                        backgroundColor: ['#b32020', '#ff8c1a', '#10b981', '#0ea5e9', '#6366f1', '#f59e0b', '#ec4899'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed}` } }
                    },
                    cutout: '60%'
                }
            });
        }

        // Revenue by company bar
        const revenueCompanyCtx = document.getElementById('revenueByCompanyChart');
        if (revenueCompanyCtx && {{ $billSummaries->isNotEmpty() ? 'true' : 'false' }}) {
            new Chart(revenueCompanyCtx, {
                type: 'bar',
                data: {
                    labels: @json($billSummaries->pluck('company')),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($billSummaries->pluck('revenue')),
                        backgroundColor: palette.primary,
                        borderRadius: 6
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => formatCurrency(ctx.parsed.y) } }
                    },
                    scales: {
                        y: {
                            ticks: { callback: (val) => formatCurrency(val) },
                            grid: { color: '#f3f4f6' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Bill count by company bar
        const billCountCtx = document.getElementById('billCountChart');
        if (billCountCtx && {{ $billSummaries->isNotEmpty() ? 'true' : 'false' }}) {
            new Chart(billCountCtx, {
                type: 'bar',
                data: {
                    labels: @json($billSummaries->pluck('company')),
                    datasets: [{
                        label: 'Bills',
                        data: @json($billSummaries->pluck('bills')),
                        backgroundColor: palette.accent,
                        borderRadius: 6
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            ticks: { stepSize: 1 },
                            grid: { color: '#f3f4f6' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    </script>
@endpush