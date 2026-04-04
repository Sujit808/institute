@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div id="kanbanUndoBar" class="alert alert-warning d-none align-items-center justify-content-between" role="alert">
        <div class="small" id="kanbanUndoText">Lead moved.</div>
        <button type="button" class="btn btn-sm btn-dark" id="kanbanUndoButton">Undo</button>
    </div>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <span class="eyebrow">Admission CRM</span>
            <h1 class="h3 mb-1">Admission Leads Kanban</h1>
            <p class="text-body-secondary mb-0">Drag and drop cards across stages, or use quick stage update controls.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admission-leads.index') }}" class="btn btn-outline-secondary">Table View</a>
            <a href="{{ route('admission-leads.index') }}" class="btn btn-primary">Add / Edit Leads</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Total Leads</div><div class="h4 mb-0">{{ $kpi['total'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Active</div><div class="h4 mb-0">{{ $kpi['active'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Converted</div><div class="h4 mb-0">{{ $kpi['converted'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Conversion %</div><div class="h4 mb-0">{{ number_format((float) ($kpi['conversion_rate'] ?? 0), 2) }}%</div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Due Today</div><div class="h4 mb-0">{{ $kpi['due_today'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card app-card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary text-uppercase">Overdue</div><div class="h4 mb-0 text-danger">{{ $kpi['overdue'] ?? 0 }}</div></div></div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($stages as $stageKey => $stageLabel)
            @php
                $leads = $groupedLeads[$stageKey] ?? [];
            @endphp
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <strong>{{ $stageLabel }}</strong>
                        <span class="badge text-bg-secondary" data-stage-count="{{ $stageKey }}">{{ count($leads) }}</span>
                    </div>
                    <div class="card-body d-flex flex-column gap-3" style="min-height: 280px;" data-stage-column="{{ $stageKey }}">
                        @forelse ($leads as $lead)
                            @php
                                $isOverdue = $lead->next_follow_up_at && $lead->next_follow_up_at->copy()->startOfDay()->lt($today);
                                $isDueToday = $lead->next_follow_up_at && $lead->next_follow_up_at->copy()->startOfDay()->eq($today);
                            @endphp
                            <div class="border rounded-3 p-3 bg-light-subtle" data-kanban-card data-lead-id="{{ $lead->id }}" draggable="true">
                                <div class="fw-semibold">{{ $lead->student_name }}</div>
                                <div class="small text-body-secondary">{{ $lead->guardian_name ?: 'Guardian not set' }}</div>
                                <div class="small mt-1">{{ $lead->phone }} @if ($lead->email) | {{ $lead->email }} @endif</div>
                                <div class="small mt-2">Class: {{ $lead->academicClass?->name ?? 'Not selected' }}</div>
                                <div class="small">Counselor: {{ $lead->assignedToStaff?->full_name ?? 'Unassigned' }}</div>
                                <div class="small">
                                    Score:
                                    <span class="badge text-bg-{{ ($lead->score ?? 0) >= 70 ? 'success' : (($lead->score ?? 0) >= 45 ? 'warning' : 'secondary') }}">
                                        {{ $lead->score ?? '-' }}
                                    </span>
                                </div>
                                @if ($lead->next_follow_up_at)
                                    <div class="small mt-1">
                                        Follow-up: {{ $lead->next_follow_up_at->format('d M Y, h:i A') }}
                                        @if ($isOverdue)
                                            <span class="badge text-bg-danger">Overdue</span>
                                        @elseif ($isDueToday)
                                            <span class="badge text-bg-warning">Due Today</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($lead->convertedStudent)
                                    <div class="small mt-1 text-success fw-semibold">Converted: {{ $lead->convertedStudent->full_name }} ({{ $lead->convertedStudent->admission_no }})</div>
                                    <div class="mt-2">
                                        <a
                                            class="btn btn-sm btn-outline-success w-100"
                                            href="{{ route('students.index', ['search_field' => 'admission_no', 'search' => $lead->convertedStudent->admission_no]) }}"
                                        >Open Student Record</a>
                                    </div>
                                @endif
                                <div class="mt-2">
                                    <label class="form-label small mb-1">Move to stage</label>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" data-stage-select>
                                            @foreach ($stages as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" @selected($optValue === $lead->stage)>{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-dark" type="button" data-stage-save>Save</button>
                                    </div>
                                </div>
                                @if (! $lead->convertedStudent && $lead->stage !== 'lost')
                                    <div class="mt-2">
                                        <button
                                            class="btn btn-sm btn-success w-100"
                                            type="button"
                                            data-convert-open
                                            data-lead-id="{{ $lead->id }}"
                                            data-lead-name="{{ $lead->student_name }}"
                                            data-class-id="{{ (int) ($lead->academic_class_id ?? 0) }}"
                                        >Convert to Student</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-body-secondary small">No leads in this stage.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="leadDuplicateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title">Duplicate Student Detection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-body-secondary mb-3">Possible duplicate students found matching this lead. Choose an action:</p>
                <div id="duplicatesList" class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Name</th>
                                <th>Admission #</th>
                                <th>Phone</th>
                                <th>Match Confidence</th>
                                <th>Reasons</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="duplicatesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="forceConvertBtn">Force Convert (New Student)</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leadConvertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Convert Lead to Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-body-secondary mb-2" id="leadConvertHint"></div>
                <input type="hidden" id="convertLeadId" value="">
                <div class="mb-2">
                    <label class="form-label">Class</label>
                    <select class="form-select" id="convertClassId">
                        <option value="">Select Class</option>
                        @foreach ($classLookups as $classId => $classLabel)
                            <option value="{{ $classId }}">{{ $classLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Section</label>
                    <select class="form-select" id="convertSectionId">
                        <option value="">Select Section</option>
                        @foreach ($sectionLookups as $section)
                            <option value="{{ $section['id'] }}" data-class-id="{{ $section['academic_class_id'] }}">{{ $section['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Gender</label>
                    <select class="form-select" id="convertGender">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Conversion Reason</label>
                    <textarea class="form-control" id="convertReason" rows="2" placeholder="e.g. Parent confirmed admission with documents."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmLeadConvert">Convert</button>
            </div>
        </div>
    </div>
</div>

<script id="kanban-data" type="application/json">
    {
        "stageLabels": {!! json_encode($stages ?? []) !!},
        "stageWipLimits": {!! json_encode($stageWipLimits ?? []) !!}
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let activeDraggedCard = null;
        const kanbanData = JSON.parse(document.getElementById('kanban-data').textContent);
        const stageLabels = kanbanData.stageLabels;
        const stageWipLimits = kanbanData.stageWipLimits;
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let activeDraggedCard = null;
        const stageLabels = window.stageLabels;
        const stageWipLimits = window.stageWipLimits;

        const undoBar = document.getElementById('kanbanUndoBar');
        const undoText = document.getElementById('kanbanUndoText');
        const undoButton = document.getElementById('kanbanUndoButton');
        let undoState = null;
        let undoHideTimer = null;
        const convertModalEl = document.getElementById('leadConvertModal');
        const convertModal = convertModalEl && window.bootstrap ? new window.bootstrap.Modal(convertModalEl) : null;
        const duplicateModalEl = document.getElementById('leadDuplicateModal');
        const duplicateModal = duplicateModalEl && window.bootstrap ? new window.bootstrap.Modal(duplicateModalEl) : null;
        const forceConvertBtn = document.getElementById('forceConvertBtn');
        const duplicatesTableBody = document.getElementById('duplicatesTableBody');
        const convertLeadIdInput = document.getElementById('convertLeadId');
        const convertHint = document.getElementById('leadConvertHint');
        const convertClassId = document.getElementById('convertClassId');
        const convertSectionId = document.getElementById('convertSectionId');
        const convertGender = document.getElementById('convertGender');
        const convertReason = document.getElementById('convertReason');
        const confirmLeadConvert = document.getElementById('confirmLeadConvert');

        const updateStageCount = function (stage, delta) {
            const badge = document.querySelector(`[data-stage-count="${stage}"]`);
            if (!badge) {
                return;
            }

            const current = Number(badge.textContent || 0);
            const next = Math.max(0, current + delta);
            badge.textContent = String(next);
        };

        const currentColumnLoad = function (stage) {
            const column = document.querySelector(`[data-stage-column="${stage}"]`);
            if (!column) {
                return 0;
            }

            return column.querySelectorAll('[data-kanban-card]').length;
        };

        const maybeWarnWipLimit = function (targetStage) {
            const limit = Number(stageWipLimits[targetStage] || 0);
            if (limit <= 0) {
                return true;
            }

            const load = currentColumnLoad(targetStage);
            if (load < limit) {
                return true;
            }

            return window.confirm(`${stageLabels[targetStage] || targetStage} stage is at WIP limit (${limit}). Move anyway?`);
        };

        const hideUndoBar = function () {
            if (undoHideTimer) {
                clearTimeout(undoHideTimer);
                undoHideTimer = null;
            }
            undoState = null;
            if (undoBar) {
                undoBar.classList.add('d-none');
            }
        };

        const showUndoBar = function (state) {
            if (!undoBar || !undoText || !undoButton) {
                return;
            }

            if (undoHideTimer) {
                clearTimeout(undoHideTimer);
            }

            undoState = state;
            undoText.textContent = `Moved ${state.leadName} from ${stageLabels[state.fromStage] || state.fromStage} to ${stageLabels[state.toStage] || state.toStage}.`;
            undoBar.classList.remove('d-none');

            undoHideTimer = setTimeout(function () {
                hideUndoBar();
            }, 8000);
        };

        const moveCardInDom = function (card, fromStage, toStage) {
            const targetColumn = document.querySelector(`[data-stage-column="${toStage}"]`);
            if (!card || !targetColumn) {
                return false;
            }

            const noDataNote = targetColumn.querySelector('.text-body-secondary.small');
            if (noDataNote) {
                noDataNote.remove();
            }

            targetColumn.prepend(card);
            card.setAttribute('data-current-stage', toStage);

            const select = card.querySelector('[data-stage-select]');
            if (select) {
                select.value = toStage;
            }

            if (fromStage !== toStage) {
                updateStageCount(fromStage, -1);
                updateStageCount(toStage, 1);

                const previousColumn = document.querySelector(`[data-stage-column="${fromStage}"]`);
                if (previousColumn && previousColumn.querySelectorAll('[data-kanban-card]').length === 0) {
                    const emptyNote = document.createElement('div');
                    emptyNote.className = 'text-body-secondary small';
                    emptyNote.textContent = 'No leads in this stage.';
                    previousColumn.appendChild(emptyNote);
                }
            }

            return true;
        };

        const updateLeadStage = async function (leadId, stage, saveButton) {
            if (saveButton) {
                saveButton.disabled = true;
            }

            try {
                const response = await fetch(`/admission-leads/${leadId}/stage`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        stage: stage,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Update failed');
                }

                if (window.showToast) {
                    window.showToast('success', 'Lead stage updated');
                } else {
                    alert('Lead stage updated');
                }

                return true;
            } catch (error) {
                if (window.showToast) {
                    window.showToast('error', 'Could not update stage');
                } else {
                    alert('Could not update stage');
                }
                return false;
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                }
            }
        };

        const filterConvertSections = function () {
            if (!convertClassId || !convertSectionId) {
                return;
            }

            const classId = Number(convertClassId.value || 0);
            Array.from(convertSectionId.options).forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const optionClassId = Number(option.getAttribute('data-class-id') || 0);
                const match = classId > 0 && optionClassId === classId;
                option.hidden = !match;
                option.disabled = !match;
            });
            convertSectionId.value = '';
        };

        if (convertClassId) {
            convertClassId.addEventListener('change', filterConvertSections);
        }

        document.querySelectorAll('[data-convert-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!convertLeadIdInput || !convertHint || !convertModal) {
                    return;
                }

                const leadId = button.getAttribute('data-lead-id') || '';
                const leadName = button.getAttribute('data-lead-name') || 'Lead';
                const classId = button.getAttribute('data-class-id') || '';

                convertLeadIdInput.value = leadId;
                convertHint.textContent = `Converting: ${leadName}`;
                if (convertClassId) {
                    convertClassId.value = classId && classId !== '0' ? classId : '';
                    filterConvertSections();
                }
                if (convertGender) {
                    convertGender.value = 'male';
                }
                if (convertReason) {
                    convertReason.value = '';
                }
                convertModal.show();
            });
        });

        if (confirmLeadConvert) {
            confirmLeadConvert.addEventListener('click', async function () {
                const leadId = convertLeadIdInput ? convertLeadIdInput.value : '';
                if (!leadId || !convertClassId || !convertSectionId || !convertGender) {
                    return;
                }

                if (!convertClassId.value || !convertSectionId.value) {
                    if (window.showToast) {
                        window.showToast('error', 'Class and section are required');
                    }
                    return;
                }

        const executeConvert = async function (leadId, forceConvert, existingStudentId) {
            if (!leadId || !convertGender) {
                return false;
            }

            if (!forceConvert && !existingStudentId && (!convertClassId.value || !convertSectionId.value)) {
                if (window.showToast) {
                    window.showToast('error', 'Class and section are required for new student conversion');
                }
                return false;
            }

            const payload = {
                gender: convertGender.value,
                conversion_reason: convertReason ? String(convertReason.value || '').trim() : '',
                force_convert: forceConvert ? 1 : 0,
            };

            if (convertClassId && convertClassId.value) {
                payload.academic_class_id = Number(convertClassId.value);
            }

            if (convertSectionId && convertSectionId.value) {
                payload.section_id = Number(convertSectionId.value);
            }

            if (existingStudentId) {
                payload.existing_student_id = Number(existingStudentId);
            }

            try {
                const response = await fetch(`/admission-leads/${leadId}/convert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.status === 422) {
                    const body = await response.json();
                    const duplicates = Array.isArray(body.duplicates) ? body.duplicates : [];
                    if (duplicates.length > 0) {
                        showDuplicateModal(leadId, duplicates);
                        return false;
                    }

                    throw new Error(body.message || 'Conversion validation failed');
                }

                if (!response.ok) {
                    throw new Error('Conversion failed');
                }

                const returnPayload = await response.json();
                if (window.showToast) {
                    window.showToast('success', returnPayload.message || 'Lead converted successfully');
                }

                if (convertModal) {
                    convertModal.hide();
                }

                window.location.reload();
                return true;
            } catch (error) {
                if (window.showToast) {
                    window.showToast('error', error.message || 'Could not convert lead');
                } else {
                    alert(error.message || 'Could not convert lead');
                }
                return false;
            }
        };

        const showDuplicateModal = function (leadId, duplicates) {
            if (!duplicateModal || !duplicatesTableBody) {
                return;
            }

            duplicatesTableBody.innerHTML = '';
            duplicates.forEach(function (dup) {
                const confidence = Number(dup.confidence || 0);
                const reasons = Array.isArray(dup.reasons) ? dup.reasons.join(', ') : 'N/A';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="fw-semibold">${dup.full_name || 'N/A'}</div>
                    </td>
                    <td><code>${dup.admission_no || 'N/A'}</code></td>
                    <td>${dup.phone || 'N/A'}</td>
                    <td>
                        <span class="badge text-bg-${confidence >= 70 ? 'danger' : confidence >= 45 ? 'warning' : 'info'}">${confidence}%</span>
                    </td>
                    <td><small>${reasons}</small></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary link-student-btn" data-student-id="${dup.id}">Link</button>
                    </td>
                `;
                duplicatesTableBody.appendChild(row);
            });

            duplicatesTableBody.querySelectorAll('.link-student-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const studentId = Number(btn.getAttribute('data-student-id') || 0);
                    if (studentId > 0 && duplicateModal) {
                        duplicateModal.hide();
                        setTimeout(async function () {
                            await executeConvert(leadId, false, studentId);
                        }, 300);
                    }
                });
            });

            if (forceConvertBtn) {
                forceConvertBtn.onclick = async function () {
                    if (duplicateModal) {
                        duplicateModal.hide();
                    }
                    setTimeout(async function () {
                        await executeConvert(leadId, true, null);
                    }, 300);
                };
            }

            duplicateModal.show();
        };

                confirmLeadConvert.disabled = true;
                try {
                    await executeConvert(leadId, false, null);
                } finally {
                    confirmLeadConvert.disabled = false;
                }
            });
        }

        if (undoButton) {
            undoButton.addEventListener('click', async function () {
                if (!undoState) {
                    return;
                }

                const snapshot = undoState;
                const success = await updateLeadStage(snapshot.leadId, snapshot.fromStage, null);
                if (!success) {
                    return;
                }

                moveCardInDom(snapshot.card, snapshot.toStage, snapshot.fromStage);
                hideUndoBar();
            });
        }

        document.querySelectorAll('[data-kanban-card]').forEach(function (card) {
            const parentColumn = card.closest('[data-stage-column]');
            if (parentColumn) {
                card.setAttribute('data-current-stage', parentColumn.getAttribute('data-stage-column'));
            }

            card.addEventListener('dragstart', function () {
                activeDraggedCard = card;
                card.classList.add('opacity-50');
            });

            card.addEventListener('dragend', function () {
                card.classList.remove('opacity-50');
                activeDraggedCard = null;
            });
        });

        document.querySelectorAll('[data-stage-column]').forEach(function (column) {
            column.addEventListener('dragover', function (event) {
                event.preventDefault();
                column.classList.add('border', 'border-primary', 'rounded-3');
            });

            column.addEventListener('dragleave', function () {
                column.classList.remove('border', 'border-primary', 'rounded-3');
            });

            column.addEventListener('drop', async function (event) {
                event.preventDefault();
                column.classList.remove('border', 'border-primary', 'rounded-3');

                if (!activeDraggedCard) {
                    return;
                }

                const leadId = activeDraggedCard.getAttribute('data-lead-id');
                const targetStage = column.getAttribute('data-stage-column');
                const fromStage = activeDraggedCard.getAttribute('data-current-stage') || '';
                if (!leadId || !targetStage) {
                    return;
                }

                if (!fromStage || fromStage === targetStage) {
                    return;
                }

                if (!maybeWarnWipLimit(targetStage)) {
                    return;
                }

                const moved = moveCardInDom(activeDraggedCard, fromStage, targetStage);
                if (!moved) {
                    return;
                }

                const success = await updateLeadStage(leadId, targetStage, null);
                if (!success) {
                    moveCardInDom(activeDraggedCard, targetStage, fromStage);
                    return;
                }

                showUndoBar({
                    leadId: leadId,
                    leadName: activeDraggedCard.querySelector('.fw-semibold')?.textContent?.trim() || 'Lead',
                    fromStage: fromStage,
                    toStage: targetStage,
                    card: activeDraggedCard,
                });
            });
        });

        document.querySelectorAll('[data-kanban-card]').forEach(function (card) {
            const saveButton = card.querySelector('[data-stage-save]');
            const stageSelect = card.querySelector('[data-stage-select]');
            const leadId = card.getAttribute('data-lead-id');

            if (!saveButton || !stageSelect || !leadId) {
                return;
            }

            saveButton.addEventListener('click', async function () {
                const toStage = stageSelect.value;
                const fromStage = card.getAttribute('data-current-stage') || toStage;

                if (fromStage !== toStage && !maybeWarnWipLimit(toStage)) {
                    stageSelect.value = fromStage;
                    return;
                }

                if (fromStage !== toStage) {
                    const moved = moveCardInDom(card, fromStage, toStage);
                    if (!moved) {
                        return;
                    }
                }

                const success = await updateLeadStage(leadId, toStage, saveButton);
                if (!success) {
                    if (fromStage !== toStage) {
                        moveCardInDom(card, toStage, fromStage);
                    }
                    return;
                }

                if (fromStage !== toStage) {
                    showUndoBar({
                        leadId: leadId,
                        leadName: card.querySelector('.fw-semibold')?.textContent?.trim() || 'Lead',
                        fromStage: fromStage,
                        toStage: toStage,
                        card: card,
                    });
                }
            });
        });
    });
</script>
@endsection
