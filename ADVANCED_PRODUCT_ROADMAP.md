# Advanced Product Roadmap (School SaaS)

Date: 2026-03-29
Scope: Existing Laravel school management platform with Master Control and SaaS plans.

## 1) 30-Day MVP Plan (High Impact, Fast Delivery)

### Objective
Ship paid-SaaS ready capabilities without destabilizing core school operations.

### Workstream A: Billing and Subscription Automation
- Integrate recurring plans (Starter, Professional, Enterprise).
- Add subscription states: trialing, active, past_due, canceled, grace_period.
- Auto-sync billing status with Master Control (`license_configs`).
- Add plan upgrade/downgrade path with proration rules.

Deliverables
- Billing provider integration layer.
- Subscription webhook endpoint + signature verification.
- Admin billing page: current plan, renewal date, invoices.
- Job queue retries for failed webhook processing.

### Workstream B: Generic Approval Engine (v1)
- Introduce reusable approval workflow table design.
- First modules: leave request, fee discount, result publish.
- Support approver role chain and status timeline.

Deliverables
- Workflow configuration UI (super admin).
- Approval inbox for approvers.
- Audit trail for approve/reject/escalate actions.

### Workstream C: Communication Automation (v1)
- Notification templates (email + WhatsApp + in-app).
- Trigger rules for fee due, low attendance, leave status.
- Delivery log + retry states.

Deliverables
- Template editor with variables.
- Trigger rule CRUD.
- Delivery tracking dashboard.

### Workstream D: Analytics Dashboard v2 (Management View)
- KPI cards: collection rate, absent risk, exam performance trend, admissions trend.
- Branch and class level filters.

Deliverables
- Aggregation queries + cached metrics.
- Visual dashboard with trend comparison.

## 2) 90-Day Scale Plan (Enterprise Readiness)

### Phase 2 (Day 31-60): Platform Hardening
- Multi-tenant isolation strategy (tenant_id everywhere or tenant DB).
- RBAC v2 with action-level permissions (`view/create/edit/delete/approve/export`).
- Data governance: retention policies, PII masking in exports, immutable audit events.
- Incident controls: backup verification + restore drill scripts.

### Phase 3 (Day 61-90): Product Differentiation
- Parent portal APIs + parent login experience.
- Examination suite upgrade: question blueprinting, moderation workflow, OMR import.
- Attendance intelligence: anomaly detection and alerting.
- AI assistant (reports + risk insights) for admins.

## 3) Suggested Architecture Changes

### New Core Domains
- Billing domain (`subscriptions`, `invoices`, `transactions`, `webhook_events`).
- Workflow domain (`approval_workflows`, `approval_instances`, `approval_actions`).
- Messaging domain (`message_templates`, `message_triggers`, `message_deliveries`).
- Analytics domain (`metric_snapshots`, optional pre-aggregates).

### Infrastructure
- Queue-first processing for webhook, notifications, analytics refresh.
- Cache layer for dashboard metrics.
- Scheduled jobs for reminders, retries, and risk scoring.

## 4) Database Change Map (Initial)

### Billing
- `subscriptions`: tenant/license ref, provider id, plan, status, trial_ends_at, renews_at.
- `invoices`: number, amount, currency, period_start/end, status, due_date.
- `transactions`: invoice_id, provider_txn_id, amount, status, paid_at.
- `webhook_events`: provider_event_id, type, payload_json, processed_at, status.

### Approval Engine
- `approval_workflows`: module_key, trigger_action, active, version.
- `approval_workflow_steps`: workflow_id, sequence, approver_role, sla_hours.
- `approval_instances`: workflow_id, resource_type, resource_id, status, current_step.
- `approval_actions`: instance_id, actor_id, action, comment, acted_at.

### Messaging
- `message_templates`: channel, key, subject, body, variables_json, status.
- `message_triggers`: module_key, event_key, channel, template_id, active.
- `message_deliveries`: trigger_id, recipient, payload_json, status, retries, sent_at.

### Analytics
- `metric_snapshots`: metric_key, dimension_json, value, snapshot_date.

## 5) Effort Estimate (T-Shirt + Person-Weeks)

- Billing and subscriptions: L (3-4 pw)
- Approval engine v1: L (3-4 pw)
- Communication automation v1: M/L (2-3 pw)
- Analytics dashboard v2: M (2 pw)
- RBAC v2: L (3 pw)
- Tenant isolation uplift: XL (4-6 pw)
- Parent API and portal foundation: L (3 pw)

Recommended team for 90 days
- 2 backend engineers
- 1 frontend engineer
- 1 QA engineer
- 0.5 DevOps support

## 6) Rollout Risk Map

### High Risk
- Tenant isolation migration on existing shared data.
- Billing webhook correctness and idempotency.
- Approval workflow misconfiguration causing blocked operations.

Mitigation
- Feature flags per module.
- Backfill scripts + dry-run mode.
- Idempotency keys and replay-safe webhook handling.
- Shadow mode for approvals before enforcement.

### Medium Risk
- Notification fatigue due to aggressive triggers.
- Analytics query load spikes.

Mitigation
- Rate limits and quiet hours.
- Precomputed metrics with cache invalidation.

### Low Risk
- UI training overhead for admins.

Mitigation
- Inline help + one-page SOP per module.

## 7) Acceptance Criteria (Release Gate)

30-day MVP release is ready only if:
- Billing lifecycle updates plan limits in Master Control automatically.
- Approval flow works end-to-end for at least 3 module actions.
- Triggered notifications have delivery logs and retry outcomes.
- Dashboard KPIs load under target response time.
- Test suite green and new feature tests added for each workstream.

## 8) Execution Sequence (Recommended)

1. Billing foundations + webhook idempotency.
2. Approval engine core tables and APIs.
3. Message trigger + delivery pipeline.
4. Analytics snapshot jobs and dashboard UI.
5. Hardening: RBAC v2 + tenant strategy decision.
6. Parent APIs and exam/attendance intelligence.

## 9) Immediate Next Sprint (First 7 Days)

1. Freeze billing provider choice and finalize webhook contract.
2. Create migration set for billing and approval tables.
3. Implement subscription sync service to `license_configs`.
4. Scaffold approval inbox and first workflow (leave approvals).
5. Add CI jobs for migration dry-run, tests, and changed-file style checks.
