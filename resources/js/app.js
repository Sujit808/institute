import './bootstrap';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const toast = Swal.mixin({
	toast: true,
	position: 'top-end',
	showConfirmButton: false,
	timer: 2800,
	timerProgressBar: true,
});

const showToast = (icon, title) => {
	toast.fire({ icon, title });
};

window.showToast = showToast;

const parseJsonScript = (id) => {
	const node = document.getElementById(id);
	if (!node) {
		return null;
	}

	try {
		return JSON.parse(node.textContent || 'null');
	} catch (error) {
		return null;
	}
};

const setTheme = (theme) => {
	document.documentElement.setAttribute('data-bs-theme', theme);
	localStorage.setItem('school_theme', theme);
};

const initThemeToggle = () => {
	const initialTheme = localStorage.getItem('school_theme') || 'light';
	setTheme(initialTheme);

	const syncThemeButtons = () => {
		const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
		document.querySelectorAll('[data-theme-set]').forEach((button) => {
			const theme = button.getAttribute('data-theme-set');
			const isActive = theme === currentTheme;
			button.classList.toggle('active', isActive);
			const check = button.querySelector('[data-theme-check]');
			if (check) {
				check.classList.toggle('d-none', !isActive);
			}
		});
	};

	syncThemeButtons();

	document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
		const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
		setTheme(current === 'light' ? 'dark' : 'light');
		syncThemeButtons();
	});

	document.querySelectorAll('[data-theme-set]').forEach((button) => {
		button.addEventListener('click', () => {
			const theme = button.getAttribute('data-theme-set');
			if (theme === 'light' || theme === 'dark') {
				setTheme(theme);
				syncThemeButtons();
			}
		});
	});
};

const initLicenseExpiryPopup = () => {
	const warning = parseJsonScript('license-warning-json');
	if (!warning || !warning.show) {
		return;
	}

	const todayKey = new Date().toISOString().slice(0, 10);
	const popupKey = `license-popup-${todayKey}-${warning.expires_at}`;
	if (localStorage.getItem(popupKey) === 'shown') {
		return;
	}

	const dayWord = Number(warning.days_remaining) === 1 ? 'day' : 'days';
	const title = warning.days_remaining === 0
		? 'License Expires Today'
		: 'License Expiring Soon';

	const message = warning.days_remaining === 0
		? `Your license expires today (${warning.expires_at_label}). Please renew now.`
		: `Your license will expire in ${warning.days_remaining} ${dayWord} on ${warning.expires_at_label}.`;

	Swal.fire({
		icon: 'warning',
		title,
		html: `<div style="font-size:14px;line-height:1.5">${message}</div>`,
		showConfirmButton: true,
		confirmButtonText: 'OK',
		timer: 12000,
		timerProgressBar: true,
		allowOutsideClick: true,
	});

	localStorage.setItem(popupKey, 'shown');
};

const initDashboardCharts = () => {
	const dashboardData = parseJsonScript('dashboard-chart-data');
	if (!dashboardData) {
		return;
	}

	const chartOptions = {
		responsive: true,
		maintainAspectRatio: false,
		plugins: { legend: { display: true, position: 'bottom' } },
	};

	const attendanceCanvas = document.getElementById('attendanceChart');
	if (attendanceCanvas) {
		attendanceCanvas.style.height = '230px';
		const presentValues = dashboardData.attendance.present || dashboardData.attendance.values || [];
		const absentValues = dashboardData.attendance.absent || presentValues.map((value) => Math.max(0, 1 - Number(value || 0)));
		new Chart(attendanceCanvas, {
			type: 'line',
			data: {
				labels: dashboardData.attendance.labels,
				datasets: [
					{ label: 'Present', data: presentValues, borderColor: '#16a34a', tension: 0.35 },
					{ label: 'Absent', data: absentValues, borderColor: '#dc2626', tension: 0.35 },
				],
			},
			options: chartOptions,
		});
	}

	const feesCanvas = document.getElementById('feesChart');
	if (feesCanvas) {
		feesCanvas.style.height = '230px';
		new Chart(feesCanvas, {
			type: 'bar',
			data: {
				labels: dashboardData.fees.labels,
				datasets: [{ label: 'Fees Collected', data: dashboardData.fees.values, backgroundColor: '#1167b1' }],
			},
			options: chartOptions,
		});
	}

	const resultsCanvas = document.getElementById('resultsChart');
	if (resultsCanvas) {
		resultsCanvas.style.height = '230px';
		new Chart(resultsCanvas, {
			type: 'radar',
			data: {
				labels: dashboardData.results.labels,
				datasets: [{ label: 'Avg Result', data: dashboardData.results.values, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.2)' }],
			},
			options: chartOptions,
		});
	}
};

const clearValidationErrors = (form) => {
	form.querySelectorAll('[data-error-for]').forEach((node) => {
		node.textContent = '';
	});
};

const renderFilePreview = (form, field, value = null) => {
	const slot = form.querySelector(`[data-file-preview="${field.name}"]`);
	if (!slot) {
		return;
	}

	slot.innerHTML = '';
	const input = form.querySelector(`[name="${field.name}"]`) || form.querySelector(`[name="${field.name}[]"]`);
	const files = input?.files ? Array.from(input.files) : [];

	if (files.length > 0) {
		files.forEach((file) => {
			const item = document.createElement('div');
			item.className = 'file-preview-item';
			item.textContent = file.name;
			slot.appendChild(item);
		});
		return;
	}

	const values = Array.isArray(value) ? value : (value ? [value] : []);
	values.forEach((path) => {
		const wrapper = document.createElement('div');
		wrapper.className = 'file-preview-item';

		const fileName = (path || '').split('/').pop();
		const ext = fileName?.split('.').pop()?.toLowerCase();
		const url = `/storage/${path}`;

		if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext || '')) {
			const img = document.createElement('img');
			img.src = url;
			img.alt = fileName || 'Preview';
			img.className = 'file-preview-thumb';
			wrapper.appendChild(img);
		}

		const link = document.createElement('a');
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		link.textContent = fileName || 'Open file';
		wrapper.appendChild(link);
		slot.appendChild(wrapper);
	});
};

const bindFilePreviewListeners = (form, fields) => {
	fields.filter((field) => field.type === 'file').forEach((field) => {
		const input = form.querySelector(`[name="${field.name}"]`) || form.querySelector(`[name="${field.name}[]"]`);
		if (!input || input.dataset.previewBound === 'true') {
			return;
		}

		input.dataset.previewBound = 'true';
		input.addEventListener('change', () => renderFilePreview(form, field));
	});
};

const fillForm = (form, fields, record) => {
	fields.forEach((field) => {
		const name = field.name;
		const value = record[name];

		if (field.type === 'checkboxes') {
			form.querySelectorAll(`input[name="${name}[]"]`).forEach((checkbox) => {
				checkbox.checked = Array.isArray(value) && value.includes(checkbox.value);
			});
			return;
		}

		const input = form.querySelector(`[name="${name}"]`) || form.querySelector(`[name="${name}[]"]`);
		if (!input) {
			return;
		}

		if (input.type === 'file') {
			renderFilePreview(form, field, value);
			return;
		}

		if (input.multiple && Array.isArray(value)) {
			Array.from(input.options).forEach((option) => {
				option.selected = value.includes(option.value);
			});
			return;
		}

		input.value = value ?? '';
	});
};

const serializeForm = (form, fields) => {
	const formData = new FormData(form);
	fields
		.filter((field) => field.type === 'checkboxes')
		.forEach((field) => {
			formData.delete(field.name);
			formData.delete(`${field.name}[]`);
			form.querySelectorAll(`input[name="${field.name}[]"]:checked`).forEach((checkbox) => {
				formData.append(`${field.name}[]`, checkbox.value);
			});
		});

	return formData;
};

const showAlert = (container, type, message) => {
	container.className = `alert alert-${type}`;
	container.textContent = message;
	container.classList.remove('d-none');
	showToast(type === 'danger' ? 'error' : 'success', message);
};

const submitSearchForm = (moduleRoot, moduleKey) => {
	const searchForm = moduleRoot.querySelector('#module-search-form');
	if (!searchForm) {
		return;
	}

	searchForm.addEventListener('submit', async (event) => {
		event.preventDefault();

		const formData = new FormData(searchForm);
		const params = new URLSearchParams(formData);

		try {
			const response = await fetch(`/${moduleKey}?${params.toString()}`, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'text/html',
				},
			});

			if (response.ok) {
				const html = await response.text();
				moduleRoot.querySelector('[data-module-table-wrapper]').innerHTML = html;
			}
		} catch (error) {
			console.error('Search error:', error);
		}
	});

	searchForm.addEventListener('reset', async () => {
		setTimeout(() => {
			try {
				fetch(`/${moduleKey}?per_page=25`, {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'text/html',
					},
				}).then((res) => res.text()).then((html) => {
					moduleRoot.querySelector('[data-module-table-wrapper]').innerHTML = html;
				});
			} catch (error) {
				console.error('Reset error:', error);
			}
		}, 0);
	});
};

const initPagination = (moduleRoot, moduleKey) => {
	moduleRoot.addEventListener('click', async (event) => {
		const paginationLink = event.target.closest('[data-pagination-link]');
		if (!paginationLink) {
			return;
		}

		event.preventDefault();
		const page = paginationLink.getAttribute('data-page');
		const searchForm = moduleRoot.querySelector('#module-search-form');
		const formData = new FormData(searchForm || new FormData());

		if (!searchForm) {
			formData.append('per_page', 25);
		}

		const params = new URLSearchParams(formData);
		params.append('page', page);

		try {
			const response = await fetch(`/${moduleKey}?${params.toString()}`, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'text/html',
				},
			});

			if (response.ok) {
				const html = await response.text();
				moduleRoot.querySelector('[data-module-table-wrapper]').innerHTML = html;
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		} catch (error) {
			console.error('Pagination error:', error);
		}
	});
};

const restrictTeacherFields = (form, fields, moduleConfig) => {
	const isTeacher = document.body.classList.contains('is-teacher');
	if (!isTeacher) {
		return;
	}

	fields.forEach((field) => {
		if (field.teacher_restricted) {
			const container = form.querySelector(`[data-teacher-restricted]`);
			if (container && container.querySelector(`[name="${field.name}"]`)) {
				container.style.display = 'none';
			}
		}
	});
};

const setFieldVisibility = (form, fieldName, shouldShow, shouldRequire = false) => {
	const wrapper = form.querySelector(`[data-field="${fieldName}"]`);
	if (!wrapper) {
		return;
	}

	wrapper.style.display = shouldShow ? '' : 'none';
	const input = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`[name="${fieldName}[]"]`);
	if (input) {
		input.required = shouldRequire;
		if (!shouldShow && input.tagName === 'SELECT') {
			input.value = '';
		}
	}
};

const syncAttendanceFieldState = (moduleKey, form) => {
	if (moduleKey !== 'attendance') {
		return;
	}

	const attendanceFor = form.querySelector('[name="attendance_for"]')?.value || 'student';
	const isStudentMode = attendanceFor === 'student';

	setFieldVisibility(form, 'academic_class_id', isStudentMode, isStudentMode);
	setFieldVisibility(form, 'section_id', isStudentMode, isStudentMode);
	setFieldVisibility(form, 'student_id', isStudentMode, isStudentMode);
	setFieldVisibility(form, 'staff_attendance_id', !isStudentMode, !isStudentMode);
};

const syncStaffPermissionPresetState = (moduleKey, form, lookups, options = {}) => {
	if (moduleKey !== 'staff') {
		return;
	}

	const roleInput = form.querySelector('[name="role_type"]');
	const presetPanel = form.querySelector('[data-permission-preset-panel]');
	const presetSummary = form.querySelector('[data-permission-preset-summary]');
	const role = roleInput?.value || 'staff';
	const presets = lookups?.permission_presets || {};
	const permissions = Array.isArray(presets[role]) ? presets[role] : [];
	const shouldApply = options.apply === true;
	const shouldNotify = options.notify === true;

	if (!presetPanel || !roleInput) {
		return;
	}

	presetPanel.classList.toggle('d-none', role === 'staff');

	if (presetSummary) {
		presetSummary.textContent = permissions.length
			? `Recommended for ${role.toUpperCase()}: ${permissions.join(', ')}`
			: 'No default permission bundle for this role.';
	}

	if (!shouldApply) {
		return;
	}

	form.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
		checkbox.checked = permissions.includes(checkbox.value);
	});

	if (shouldNotify && role !== 'staff') {
		showToast('info', `Applied recommended ${role.toUpperCase()} permissions.`);
	}
};

const syncLeaveApprovalState = (moduleKey, form, moduleRoot, options = {}) => {
	if (moduleKey !== 'leaves') {
		return;
	}

	const approvalRequired = moduleRoot?.getAttribute('data-leave-approval-required') === '1';
	const statusWrapper = form.querySelector('[data-field="status"]');
	const statusInput = form.querySelector('[name="status"]');
	if (!statusWrapper || !statusInput) {
		return;
	}

	let mirrorInput = form.querySelector('[data-leave-status-mirror]');
	if (!mirrorInput) {
		mirrorInput = document.createElement('input');
		mirrorInput.type = 'hidden';
		mirrorInput.name = 'status';
		mirrorInput.setAttribute('data-leave-status-mirror', 'true');
		form.appendChild(mirrorInput);
	}

	let note = statusWrapper.querySelector('[data-leave-approval-note]');
	if (!note) {
		note = document.createElement('div');
		note.className = 'form-text';
		note.setAttribute('data-leave-approval-note', 'true');
		statusWrapper.appendChild(note);
	}

	if (!approvalRequired) {
		statusWrapper.style.display = '';
		statusInput.disabled = false;
		statusInput.required = true;
		mirrorInput.disabled = true;
		mirrorInput.value = '';
		note.textContent = 'Status can be managed directly because strict approval mode is off.';
		return;
	}

	statusWrapper.style.display = options.isEditing ? 'none' : '';
	statusInput.value = options.isEditing && statusInput.value ? statusInput.value : 'pending';
	statusInput.disabled = true;
	statusInput.required = false;
	mirrorInput.disabled = false;
	mirrorInput.value = statusInput.value || 'pending';
	note.textContent = options.isEditing
		? 'Status changes are locked here. Use the quick approve or reject action from the leaves list.'
		: 'New leave requests are forced to Pending. Final approval happens from the leaves list.';
};

const initModulePageToasts = () => {
	const moduleRoot = document.querySelector('[data-module-page]');
	if (!moduleRoot) {
		return;
	}

	const type = moduleRoot.getAttribute('data-module-toast-type');
	const message = moduleRoot.getAttribute('data-module-toast-message');
	if (!type || !message) {
		return;
	}

	showToast(type, message);
};

const parseNumericValue = (value) => {
	const parsed = Number.parseFloat(String(value ?? '').trim());
	return Number.isFinite(parsed) ? parsed : 0;
};

const rupeeFormatter = new Intl.NumberFormat('en-IN', {
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

const formatRupees = (amount) => `Rs ${rupeeFormatter.format(Math.max(0, Number(amount) || 0))}`;

const initFeeFormLiveState = (moduleKey, form) => {
	if (moduleKey !== 'fees') {
		return {
			refresh: () => {},
			resetForCreate: () => {},
			setFromRecord: () => {},
		};
	}

	const totalSlot = form.querySelector('[data-fee-form-total]');
	const paidSlot = form.querySelector('[data-fee-form-paid]');
	const dueSlot = form.querySelector('[data-fee-form-due]');
	const amountInput = form.querySelector('[name="amount"]');
	const paidInput = form.querySelector('[name="paid_amount"]');
	const installmentInput = form.querySelector('[name="installment_amount"]');
	const installmentDateInput = form.querySelector('[name="installment_date"]');

	if (!totalSlot || !paidSlot || !dueSlot) {
		return {
			refresh: () => {},
			resetForCreate: () => {},
			setFromRecord: () => {},
		};
	}

	let baselinePaid = 0;

	const setDefaultInstallmentDate = () => {
		if (installmentDateInput && !installmentDateInput.value) {
			installmentDateInput.value = new Date().toISOString().slice(0, 10);
		}
	};

	const refresh = () => {
		const totalAmount = Math.max(0, parseNumericValue(amountInput?.value));
		const paidAmount = paidInput
			? Math.max(0, parseNumericValue(paidInput.value))
			: baselinePaid;
		const installmentAmount = Math.max(0, parseNumericValue(installmentInput?.value));
		const projectedDue = Math.max(0, totalAmount - (paidAmount + installmentAmount));

		totalSlot.textContent = formatRupees(totalAmount);
		paidSlot.textContent = formatRupees(paidAmount);
		dueSlot.textContent = formatRupees(projectedDue);
	};

	const setFromRecord = (record = {}) => {
		const amount = Math.max(0, parseNumericValue(record.amount));
		const paymentSum = Array.isArray(record.payments)
			? record.payments.reduce((sum, payment) => sum + parseNumericValue(payment?.amount), 0)
			: 0;
		const paid = Math.max(0, paymentSum > 0 ? paymentSum : parseNumericValue(record.paid_amount));

		baselinePaid = paid;
		if (amountInput && !amountInput.value) {
			amountInput.value = amount;
		}
		if (paidInput) {
			paidInput.value = paid;
		}
		if (installmentInput) {
			installmentInput.value = '';
		}
		setDefaultInstallmentDate();
		refresh();
	};

	const resetForCreate = () => {
		baselinePaid = 0;
		if (installmentInput) {
			installmentInput.value = '';
		}
		setDefaultInstallmentDate();
		refresh();
	};

	[amountInput, paidInput, installmentInput].forEach((input) => {
		if (!input || input.dataset.feeLiveBound === 'true') {
			return;
		}

		input.dataset.feeLiveBound = 'true';
		input.addEventListener('input', refresh);
		input.addEventListener('change', refresh);
	});

	refresh();

	return {
		refresh,
		resetForCreate,
		setFromRecord,
	};
};

const initModuleCrud = () => {
	const moduleRoot = document.querySelector('[data-module-page]');
	if (!moduleRoot) {
		return;
	}

	const moduleKey = moduleRoot.getAttribute('data-module');
	const moduleConfig = parseJsonScript('module-config-json');
	const lookups = parseJsonScript('module-lookups-json') || {};
	if (!moduleConfig || moduleConfig.readonly) {
		return;
	}

	const fields = moduleConfig.fields || [];
	const modalEl = document.getElementById('moduleCrudModal');
	const modal = new window.bootstrap.Modal(modalEl);
	const form = modalEl.querySelector('[data-module-form]');
	const methodInput = modalEl.querySelector('[data-form-method]');
	const alertBox = modalEl.querySelector('[data-form-alert]');
	const modalTitle = modalEl.querySelector('[data-modal-title]');
	const tableWrapper = moduleRoot.querySelector('[data-module-table-wrapper]');
	const feeFormLiveState = initFeeFormLiveState(moduleKey, form);
	let editId = null;

	const openCreate = () => {
		editId = null;
		form.reset();
		feeFormLiveState.resetForCreate();
		syncStaffPermissionPresetState(moduleKey, form, lookups, { apply: true });
		syncLeaveApprovalState(moduleKey, form, moduleRoot, { isEditing: false });
		if (moduleKey === 'attendance') {
			const attendanceForInput = form.querySelector('[name="attendance_for"]');
			const attendanceMethodInput = form.querySelector('[name="attendance_method"]');
			const syncStatusInput = form.querySelector('[name="sync_status"]');
			if (attendanceForInput) {
				attendanceForInput.value = 'student';
			}
			if (attendanceMethodInput) {
				attendanceMethodInput.value = 'manual';
			}
			if (syncStatusInput) {
				syncStatusInput.value = 'synced';
			}
			syncAttendanceFieldState(moduleKey, form);
		}
		fields.filter((field) => field.type === 'file').forEach((field) => renderFilePreview(form, field));
		clearValidationErrors(form);
		alertBox.classList.add('d-none');
		methodInput.value = 'POST';
		modalTitle.textContent = `Add ${moduleConfig.singular}`;
		restrictTeacherFields(form, fields, moduleConfig);
		modal.show();
	};

	moduleRoot.querySelector('[data-open-create-modal]')?.addEventListener('click', openCreate);
	form.querySelector('[name="attendance_for"]')?.addEventListener('change', () => syncAttendanceFieldState(moduleKey, form));
	form.querySelector('[name="role_type"]')?.addEventListener('change', () => {
		syncStaffPermissionPresetState(moduleKey, form, lookups, { apply: true, notify: true });
	});
	form.querySelector('[data-apply-permission-preset]')?.addEventListener('click', () => {
		syncStaffPermissionPresetState(moduleKey, form, lookups, { apply: true, notify: true });
	});
	bindFilePreviewListeners(form, fields);

	moduleRoot.addEventListener('click', async (event) => {
		const leaveQuickBtn = event.target.closest('[data-leave-quick-action]');
		if (leaveQuickBtn) {
			const leaveId = leaveQuickBtn.getAttribute('data-id');
			const status = leaveQuickBtn.getAttribute('data-status');

			if (!/^\d+$/.test(String(leaveId || '')) || !['approved', 'rejected'].includes(String(status))) {
				showToast('error', 'Invalid leave action.');
				return;
			}

			const confirmResult = await Swal.fire({
				title: status === 'approved' ? 'Approve leave request?' : 'Reject leave request?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: status === 'approved' ? 'Approve' : 'Reject',
				cancelButtonText: 'Cancel',
				reverseButtons: true,
			});

			if (!confirmResult.isConfirmed) {
				return;
			}

			const formData = new FormData();
			formData.append('status', String(status));

			const response = await fetch(`/leaves/${leaveId}/quick-status`, {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': csrfToken,
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json',
				},
				body: formData,
			});

			const payload = await response.json();

			if (payload.html) {
				tableWrapper.innerHTML = payload.html;
			}

			const pendingBadge = moduleRoot.querySelector('[data-leave-pending-badge]');
			if (pendingBadge && typeof payload.pending_count !== 'undefined') {
				pendingBadge.textContent = `Pending: ${payload.pending_count}`;
			}

			if (response.ok) {
				showToast('success', payload.message || 'Leave status updated.');
			} else {
				showToast('error', payload.message || 'Unable to update leave status.');
			}

			return;
		}

		const editBtn = event.target.closest('[data-edit-record]');
		if (editBtn) {
			editId = editBtn.getAttribute('data-id');
			if (!/^\d+$/.test(String(editId || ''))) {
				showAlert(alertBox, 'danger', `Invalid ${moduleConfig.singular} ID.`);
				modal.show();
				return;
			}
			form.reset();
			clearValidationErrors(form);
			alertBox.classList.add('d-none');
			methodInput.value = 'PUT';
			modalTitle.textContent = `Edit ${moduleConfig.singular}`;

			try {
				const response = await fetch(`/${moduleKey}/${editId}`, {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
				});

				const payload = await response.json();
				const record = payload?.record;

				if (!response.ok || !record || Object.keys(record).length === 0) {
					showAlert(alertBox, 'danger', payload?.message || `Unable to load ${moduleConfig.singular} data.`);
					modal.show();
					return;
				}

				fillForm(form, fields, record);
				feeFormLiveState.setFromRecord(record);
				syncAttendanceFieldState(moduleKey, form);
				syncStaffPermissionPresetState(moduleKey, form, lookups);
				syncLeaveApprovalState(moduleKey, form, moduleRoot, { isEditing: true });
				restrictTeacherFields(form, fields, moduleConfig);
				modal.show();
			} catch (error) {
				showAlert(alertBox, 'danger', `Unable to load ${moduleConfig.singular} data.`);
				modal.show();
			}
			return;
		}

		const deleteBtn = event.target.closest('[data-delete-record]');
		if (deleteBtn) {
			const deleteId = deleteBtn.getAttribute('data-id');
			if (!/^\d+$/.test(String(deleteId || ''))) {
				showToast('error', `Invalid ${moduleConfig.singular} ID.`);
				return;
			}

			const confirmResult = await Swal.fire({
				title: 'Are you sure?',
				text: `You are about to delete this ${moduleConfig.singular.toLowerCase()}.`,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Yes, delete it',
				cancelButtonText: 'Cancel',
				reverseButtons: true,
			});

			if (!confirmResult.isConfirmed) {
				return;
			}

			const response = await fetch(`/${moduleKey}/${deleteId}`, {
				method: 'DELETE',
				headers: {
					'X-CSRF-TOKEN': csrfToken,
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			const payload = await response.json();
			if (payload.html) {
				tableWrapper.innerHTML = payload.html;
			}
			if (response.ok) {
				showToast('success', payload.message || `${moduleConfig.singular} deleted successfully.`);
			} else {
				showToast('error', payload.message || 'Delete failed.');
			}
		}
	});

	modalEl.querySelector('[data-submit-module-form]')?.addEventListener('click', async () => {
		clearValidationErrors(form);
		alertBox.classList.add('d-none');

		const formData = serializeForm(form, fields);
		if (editId) {
			formData.append('_method', 'PUT');
		}

		const endpoint = editId ? `/${moduleKey}/${editId}` : `/${moduleKey}`;
		const response = await fetch(endpoint, {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': csrfToken,
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: formData,
		});

		const payload = await response.json();

		if (!response.ok) {
			if (payload.errors) {
				Object.entries(payload.errors).forEach(([field, messages]) => {
					const slot = form.querySelector(`[data-error-for="${field.replace('.*', '')}"]`);
					if (slot) {
						slot.textContent = messages.join(', ');
					}
				});
				showAlert(alertBox, 'danger', 'Please fix the highlighted fields.');
			} else {
				showAlert(alertBox, 'danger', payload.message || 'Request failed.');
			}
			return;
		}

		if (payload.html) {
			tableWrapper.innerHTML = payload.html;
		}

		showToast('success', payload.message || `${moduleConfig.singular} saved successfully.`);

		modal.hide();
	});

	// Add search form and pagination handlers
	submitSearchForm(moduleRoot, moduleKey);
	initPagination(moduleRoot, moduleKey);
};

document.addEventListener('DOMContentLoaded', () => {
	initThemeToggle();
	initLicenseExpiryPopup();
	initDashboardCharts();
	initModulePageToasts();
	initModuleCrud();
});
