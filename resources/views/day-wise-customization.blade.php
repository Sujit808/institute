@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div style="background: linear-gradient(120deg, #0f172a, #1e293b); color: white; padding: 2rem; border-radius: 12px;">
                <p class="mb-1 opacity-75">Academic Calendar</p>
                <h1 class="mb-1">Day-wise Customization</h1>
                <p class="text-secondary mb-0">Manage holidays, weekoffs, events and special days for the entire academic year</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📅 Add Day-wise Entries</h5>
                        <small class="text-muted">Academic Year: {{ now()->year }} - {{ now()->month >= 4 ? now()->year + 1 : now()->year }}</small>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('day-wise-customization.save') }}" id="dayWiseForm">
                        @csrf

                        <!-- Summary Stats -->
                        <div class="row mb-4">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="p-3 border rounded" style="background: #f0f7ff;">
                                    <div class="text-muted small">Total Entries</div>
                                    <div class="h4 mb-0"><span id="totalCount">0</span></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="p-3 border rounded" style="background: #fff0f0;">
                                    <div class="text-muted small">Holidays</div>
                                    <div class="h4 mb-0"><span id="holidayCount">0</span></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="p-3 border rounded" style="background: #f5f0ff;">
                                    <div class="text-muted small">Weekoffs</div>
                                    <div class="h4 mb-0"><span id="weekoffCount">0</span></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="p-3 border rounded" style="background: #f0fff5;">
                                    <div class="text-muted small">Events</div>
                                    <div class="h4 mb-0"><span id="eventCount">0</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Entries Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-hover mb-0" id="entriesTable">
                                <thead class="table-light sticky-top" style="background: #f8fafc;">
                                    <tr>
                                        <th style="width: 5%;" class="text-center">#</th>
                                        <th style="width: 12%;">From Date</th>
                                        <th style="width: 12%;">To Date</th>
                                        <th style="width: 15%;">Type</th>
                                        <th style="width: 25%;">Title / Description</th>
                                        <th style="width: 15%;">Apply To</th>
                                        <th style="width: 16%;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="entriesBody">
                                    <!-- Rows will be added here -->
                                </tbody>
                            </table>
                            <div id="emptyState" class="text-center py-4 text-muted">
                                <p class="mb-0">No entries added yet. Click "Add Entry" to get started.</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 justify-content-between flex-wrap">
                            <button type="button" class="btn btn-primary" id="addEntryBtn">
                                <i class="bi bi-plus-lg"></i> Add Entry
                            </button>
                            <div class="d-flex gap-2">
                                <button type="reset" class="btn btn-outline-secondary">Clear Form</button>
                                <button type="submit" class="btn btn-success" id="saveBtn">
                                    <i class="bi bi-check-lg"></i> Save All Entries
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Input Container -->
                        <input type="hidden" id="entriesData" name="entries" value="[]">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Entries Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">📋 Existing Entries</h5>
                </div>
                <div class="card-body">
                    @if ($holidays->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Apply To</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($holidays as $holiday)
                                        <tr>
                                            <td><small>{{ $holiday->start_date->format('d M Y') }}</small></td>
                                            <td><small>{{ $holiday->end_date ? $holiday->end_date->format('d M Y') : 'N/A' }}</small></td>
                                            <td>
                                                <span class="badge" data-entry-type="{{ $holiday->entry_type }}" style="background: #fee2e2; color: #991b1b;">
                                                    {{ ucfirst($holiday->entry_type) }}
                                                </span>
                                            </td>
                                            <td>{{ $holiday->title }}</td>
                                            <td>
                                                @if ($holiday->class_id)
                                                    <small class="badge bg-info">Class {{ $holiday->class_id }}</small>
                                                @else
                                                    <small class="badge bg-secondary">Organization-wide</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <form method="POST" action="{{ route('day-wise-customization.delete', $holiday->id) }}" style="display: inline;" onsubmit="return confirm('Delete this entry?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <p class="mb-0">No entries found. Create one to get started.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Entry Details -->
<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    <div class="mb-3">
                        <label class="form-label">From Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fromDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date (Optional)</label>
                        <input type="date" class="form-control" id="toDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="entryType" required>
                            <option value="">-- Select Type --</option>
                            <option value="holiday">🎉 Holiday</option>
                            <option value="weekoff">📅 Weekoff / Rest Day</option>
                            <option value="event">⭐ Special Event</option>
                            <option value="exam">✏️ Exam Day</option>
                            <option value="sports">🏃 Sports Day</option>
                            <option value="fest">🎪 Fest / Celebration</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title / Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="entryTitle" placeholder="e.g., Holi, Summer Vacation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="entryNotes" rows="3" placeholder="Add any additional notes..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apply To</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="applyTo" id="applyOrg" value="org" checked>
                                <label class="form-check-label" for="applyOrg">
                                    Organization-wide
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="applyTo" id="applyClass" value="class">
                                <label class="form-check-label" for="applyClass">
                                    Specific Class Only
                                </label>
                            </div>
                        </div>
                        <div id="classSelect" class="mt-2" style="display: none;">
                            <select class="form-select" id="selectedClass">
                                <option value="">-- Select Class --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEntryBtn">Save Entry</button>
            </div>
        </div>
    </div>
</div>

<script>
    let entries = [];
    let currentEditIndex = -1;
    let entryModal;

    document.addEventListener('DOMContentLoaded', function () {
        entryModal = new bootstrap.Modal(document.getElementById('entryModal'));
        setupEventListeners();
        updateCounts();
    });

    function setupEventListeners() {
        document.getElementById('addEntryBtn').addEventListener('click', openEntryModal);
        document.getElementById('saveEntryBtn').addEventListener('click', saveEntry);
        document.getElementById('dayWiseForm').addEventListener('submit', submitForm);

        document.querySelectorAll('input[name="applyTo"]').forEach(radio => {
            radio.addEventListener('change', function () {
                document.getElementById('classSelect').style.display =
                    this.value === 'class' ? 'block' : 'none';
            });
        });
    }

    function openEntryModal(editIndex = -1) {
        currentEditIndex = editIndex;
        
        if (editIndex >= 0) {
            const entry = entries[editIndex];
            document.getElementById('fromDate').value = entry.from_date;
            document.getElementById('toDate').value = entry.to_date || '';
            document.getElementById('entryType').value = entry.type;
            document.getElementById('entryTitle').value = entry.title;
            document.getElementById('entryNotes').value = entry.notes || '';
            
            if (entry.class_id) {
                document.getElementById('applyClass').checked = true;
                document.getElementById('selectedClass').value = entry.class_id;
                document.getElementById('classSelect').style.display = 'block';
            } else {
                document.getElementById('applyOrg').checked = true;
                document.getElementById('classSelect').style.display = 'none';
            }
        } else {
            document.getElementById('entryForm').reset();
            document.getElementById('applyOrg').checked = true;
            document.getElementById('classSelect').style.display = 'none';
        }

        entryModal.show();
    }

    function saveEntry() {
        if (!document.getElementById('entryForm').checkValidity()) {
            document.getElementById('entryForm').reportValidity();
            return;
        }

        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const type = document.getElementById('entryType').value;
        const title = document.getElementById('entryTitle').value;
        const notes = document.getElementById('entryNotes').value;
        const classId = document.getElementById('applyClass').checked ? document.getElementById('selectedClass').value : null;

        if (toDate && new Date(toDate) < new Date(fromDate)) {
            alert('To Date must be after From Date');
            return;
        }

        const entry = {
            from_date: fromDate,
            to_date: toDate,
            type: type,
            title: title,
            notes: notes,
            class_id: classId ? parseInt(classId) : null
        };

        if (currentEditIndex >= 0) {
            entries[currentEditIndex] = entry;
        } else {
            entries.push(entry);
        }

        updateEntriesTable();
        updateCounts();
        entryModal.hide();
    }

    function updateEntriesTable() {
        const tbody = document.getElementById('entriesBody');
        const emptyState = document.getElementById('emptyState');
        
        tbody.innerHTML = '';

        if (entries.length === 0) {
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';

        entries.forEach((entry, index) => {
            const toDateText = entry.to_date ? new Date(entry.to_date).toLocaleDateString('en-IN') : '—';
            const fromDateText = new Date(entry.from_date).toLocaleDateString('en-IN');
            const typeIcon = getTypeIcon(entry.type);
            const applyToText = entry.class_id ? `Class ${entry.class_id}` : 'All Classes';
            
            const row = `
                <tr>
                    <td class="text-center text-muted small">${index + 1}</td>
                    <td><small>${fromDateText}</small></td>
                    <td><small>${toDateText}</small></td>
                    <td><small>${typeIcon} ${entry.type}</small></td>
                    <td><small><strong>${entry.title}</strong></small></td>
                    <td><small>${applyToText}</small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEntryModal(${index})">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(${index})">Remove</button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function deleteEntry(index) {
        if (confirm('Remove this entry?')) {
            entries.splice(index, 1);
            updateEntriesTable();
            updateCounts();
        }
    }

    function updateCounts() {
        document.getElementById('totalCount').textContent = entries.length;
        document.getElementById('holidayCount').textContent = entries.filter(e => e.type === 'holiday').length;
        document.getElementById('weekoffCount').textContent = entries.filter(e => e.type === 'weekoff').length;
        document.getElementById('eventCount').textContent = entries.filter(e => ['event', 'exam', 'sports', 'fest'].includes(e.type)).length;
    }

    function getTypeIcon(type) {
        const icons = {
            'holiday': '🎉',
            'weekoff': '📅',
            'event': '⭐',
            'exam': '✏️',
            'sports': '🏃',
            'fest': '🎪'
        };
        return icons[type] || '📌';
    }

    function submitForm(e) {
        e.preventDefault();

        if (entries.length === 0) {
            alert('Please add at least one entry');
            return;
        }

        document.getElementById('entriesData').value = JSON.stringify(entries);
        this.submit();
    }
</script>

<style>
    .table-responsive {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .badge[data-entry-type="holiday"] {
        background: #fee2e2 !important;
        color: #991b1b !important;
    }
    .badge[data-entry-type="weekoff"] {
        background: #fef3c7 !important;
        color: #b45309 !important;
    }
    .badge[data-entry-type="event"],
    .badge[data-entry-type="exam"],
    .badge[data-entry-type="sports"],
    .badge[data-entry-type="fest"] {
        background: #dbeafe !important;
        color: #0c4a6e !important;
    }
</style>
@endsection
