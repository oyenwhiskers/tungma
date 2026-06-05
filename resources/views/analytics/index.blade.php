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

    @if($showStaffBreakdown)
    <div class="row mt-4 mb-4">
        <!-- Staff Performance Charts Card -->
        <div class="col-lg-5 mb-4">
            <div class="tm-card h-100">
                <div class="tm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold"><i class="bi bi-bar-chart-line-fill me-2" style="color:var(--tm-primary);"></i> Staff Sales Analysis</span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Staff chart switch">
                        <button type="button" class="btn btn-outline-secondary active py-1 px-2" id="btn-chart-share" onclick="switchStaffChart('share')">Revenue Share</button>
                        <button type="button" class="btn btn-outline-secondary py-1 px-2" id="btn-chart-methods" onclick="switchStaffChart('methods')">Payment Methods</button>
                    </div>
                </div>
                <div class="tm-card-body d-flex flex-column justify-content-center align-items-center" style="min-height: 320px;">
                    <div id="container-chart-share" class="w-100" style="max-width: 280px; margin: 0 auto; height: 280px;">
                        <canvas id="staffRevenueChart" height="280"></canvas>
                    </div>
                    <div id="container-chart-methods" class="w-100 d-none" style="margin: 0 auto; height: 280px;">
                        <canvas id="staffPaymentMethodsChart" height="280"></canvas>
                    </div>
                    @if(empty($staffChartData))
                        <div class="tm-empty-state mt-3">
                            <i class="bi bi-graph-up-arrow"></i>
                            <div class="title">No sales data</div>
                            <div class="text-muted">No active revenue generated by staff.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Staff breakdown table -->
        <div class="col-lg-7 mb-4">
            <div class="tm-card h-100">
                <div class="tm-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-primary" style="font-size: 20px;"></i>
                        <span class="fs-5 fw-bold">Staff Performance Metrics</span>
                    </div>
                    
                    <form method="GET" action="{{ route('analytics.index') }}" class="d-flex align-items-center flex-wrap gap-2">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        @if($selectedYear)<input type="hidden" name="year" value="{{ $selectedYear }}">@endif
                        @if($selectedMonth)<input type="hidden" name="month" value="{{ $selectedMonth }}">@endif
                        @if($selectedDay)<input type="hidden" name="day" value="{{ $selectedDay }}">@endif

                        @if(auth()->user()->role === 'super_admin')
                        <div class="d-flex align-items-center gap-1">
                            <select name="company_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Companies</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" {{ $selected_company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="d-flex align-items-center gap-1">
                            <input type="text" name="search_staff" class="form-control form-control-sm" style="width: 120px;" placeholder="Search..." value="{{ $search_staff }}">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary py-1 px-2">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="{{ route('analytics.index', ['filter' => $filter]) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </form>
                </div>
                
                <div class="tm-card-body p-0">
                    @if(empty($staffStats))
                        <div class="tm-empty-state">
                            <i class="bi bi-people"></i>
                            <div class="title">No staff found</div>
                            <div class="text-muted">No staff members matched your query.</div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="padding: 12px 15px;">Staff Name</th>
                                        <th class="text-center" style="padding: 12px 10px;">Bills</th>
                                        <th class="text-end" style="padding: 12px 10px;">Total Sales</th>
                                        <th class="text-end" style="padding: 12px 10px;">Avg Bill</th>
                                        <th class="text-center" style="padding: 12px 10px;">Voids</th>
                                        <th class="text-center" style="padding: 12px 10px;">Void Rate</th>
                                        <th class="text-end" style="padding: 12px 15px;">Rev Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($staffStats as $stat)
                                        @php
                                            $avgBill = $stat['total_bills'] > 0 ? $stat['total_sales'] / $stat['total_bills'] : 0.0;
                                            $totalBillsCreated = $stat['total_bills'] + $stat['void_count'];
                                            $voidRate = $totalBillsCreated > 0 ? ($stat['void_count'] / $totalBillsCreated) * 100 : 0.0;
                                            $revShare = $staff_total_sales > 0 ? ($stat['total_sales'] / $staff_total_sales) * 100 : 0.0;
                                        @endphp
                                        <tr class="align-middle">
                                            <td style="padding: 12px 15px;">
                                                <div class="fw-semibold text-dark">{{ $stat['staff']->name }}</div>
                                                <small class="text-muted" style="font-size: 11px;">{{ $stat['staff']->email ?? $stat['staff']->username }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $stat['total_bills'] }}</span>
                                            </td>
                                            <td class="text-end text-nowrap fw-semibold text-dark">
                                                RM {{ number_format($stat['total_sales'], 2) }}
                                            </td>
                                            <td class="text-end text-nowrap text-muted">
                                                RM {{ number_format($avgBill, 2) }}
                                            </td>
                                            <td class="text-center">
                                                @if($stat['void_count'] > 0)
                                                    <span class="badge bg-danger-subtle text-danger fw-bold border border-danger-subtle">{{ $stat['void_count'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($voidRate > 0)
                                                    <span class="text-danger fw-semibold">{{ number_format($voidRate, 1) }}%</span>
                                                @else
                                                    <span class="text-muted">0%</span>
                                                @endif
                                            </td>
                                            <td class="text-end" style="padding: 12px 15px;">
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <span class="fw-bold text-primary">{{ number_format($revShare, 1) }}%</span>
                                                    <div class="progress" style="width: 50px; height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $revShare }}%" aria-valuenow="{{ $revShare }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
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
    @endif
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

        // Switch between staff analytics charts
        window.switchStaffChart = function(type) {
            const shareBtn = document.getElementById('btn-chart-share');
            const methodsBtn = document.getElementById('btn-chart-methods');
            const shareContainer = document.getElementById('container-chart-share');
            const methodsContainer = document.getElementById('container-chart-methods');
            
            if (!shareBtn || !methodsBtn || !shareContainer || !methodsContainer) return;
            
            if (type === 'share') {
                shareBtn.classList.add('active');
                methodsBtn.classList.remove('active');
                shareContainer.classList.remove('d-none');
                methodsContainer.classList.add('d-none');
            } else {
                methodsBtn.classList.add('active');
                shareBtn.classList.remove('active');
                methodsContainer.classList.remove('d-none');
                shareContainer.classList.add('d-none');
            }
        };

        // Staff revenue share doughnut chart
        const staffRevenueCtx = document.getElementById('staffRevenueChart');
        if (staffRevenueCtx && @json(!empty($staffChartData) ? true : false)) {
            new Chart(staffRevenueCtx, {
                type: 'doughnut',
                data: {
                    labels: @json(collect($staffChartData)->pluck('name')),
                    datasets: [{
                        data: @json(collect($staffChartData)->pluck('sales')),
                        backgroundColor: ['#b32020', '#ff8c1a', '#10b981', '#0ea5e9', '#6366f1', '#f59e0b', '#ec4899'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
                        tooltip: { callbacks: { label: ctx => `${ctx.label}: RM ${ctx.parsed.toFixed(2)}` } }
                    },
                    cutout: '60%'
                }
            });
        }

        // Staff payment methods stacked bar chart
        const staffPaymentMethodsCtx = document.getElementById('staffPaymentMethodsChart');
        if (staffPaymentMethodsCtx && @json(!empty($staffStats) ? true : false)) {
            const staffStatsData = @json($staffPaymentMethodsChartData);
            
            const labels = staffStatsData.map(item => item.name);
            const cashData = staffStatsData.map(item => item.cash);
            const codData = staffStatsData.map(item => item.cod);
            const qrData = staffStatsData.map(item => item.qr);
            const transferData = staffStatsData.map(item => item.transfer);
            
            new Chart(staffPaymentMethodsCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Cash',
                            data: cashData,
                            backgroundColor: '#b32020',
                        },
                        {
                            label: 'COD',
                            data: codData,
                            backgroundColor: '#ff8c1a',
                        },
                        {
                            label: 'QR',
                            data: qrData,
                            backgroundColor: '#0ea5e9',
                        },
                        {
                            label: 'Transfer',
                            data: transferData,
                            backgroundColor: '#10b981',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
                        tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: RM ${ctx.parsed.y.toFixed(2)}` } }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false }
                        },
                        y: {
                            stacked: true,
                            ticks: { callback: (val) => 'RM ' + val },
                            grid: { color: '#f3f4f6' }
                        }
                    }
                }
            });
        }
    </script>
@endpush