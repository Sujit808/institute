# School ERP Go-To-Market Gap Matrix

Date: 2026-03-29
Product: MeerahR School Management Platform
Goal: Launch demand ko fastest tareeke se spike karna (admissions + fee collection + parent trust)

## 1) Current Capability Snapshot (What you already have)

| Area | Status | Current Strength |
|------|--------|------------------|
| Academic Core (students/classes/sections/subjects) | Available | Solid operational base |
| Attendance (manual + biometric) | Available | Daily workflow ready |
| Exams + Papers + Attempts | Available | Strong student exam lifecycle |
| Fees + Payments | Available | Basic collection flow present |
| Leave + Approvals | Available | Internal control flow available |
| Notifications/Announcements | Available | School communication foundation |
| Master Control (plan/module/limits) | Available | SaaS governance differentiator |
| Billing Webhooks + License Sync | Available | Strong platform monetization base |

## 2) Demand Booster Gap Matrix (Existing vs Missing)

Scale:
- Revenue Impact: High / Medium / Low
- Build Effort: S (small), M (medium), L (large)
- Time to Market: 2-3 weeks, 4-6 weeks, 6-10 weeks

| Capability | Current State | Why Market Cares | Revenue Impact | Build Effort | Time to Market | Priority |
|------------|---------------|------------------|----------------|--------------|----------------|----------|
| Parent Mobile App (Android first) | Missing | Parents daily engage, school stickiness high | High | L | 6-10 weeks | P1 |
| WhatsApp Automation (attendance/fees/homework alerts) | Partial (notifications exist) | Instant visible value for school owners | High | M | 2-4 weeks | P1 |
| Admission CRM Funnel | Missing | Direct admission growth = direct ROI | High | M | 3-5 weeks | P1 |
| Fee Collection Intelligence (autoreminders + defaulter scoring) | Partial | Collection % increase is strongest sales pitch | High | M | 3-5 weeks | P1 |
| Transport + Live Tracking | Missing | Parent trust and premium differentiation | High | L | 6-10 weeks | P2 |
| Staff Payroll (attendance-linked) | Missing | Back-office workload reduction, strong retention | High | M | 4-6 weeks | P2 |
| Learning Outcome Analytics | Partial (results exist) | Better exam outcomes improve decision-maker buy-in | High | M | 4-6 weeks | P2 |
| Timetable Auto-Substitution | Missing | Daily operational painkiller for schools | Medium | M | 3-5 weeks | P2 |
| Compliance Report Pack (board/admin ready) | Partial | Fast audits and trust for larger schools | Medium | M | 3-4 weeks | P2 |
| Library + Inventory + Procurement | Missing | ERP depth and upsell path | Medium | M | 4-6 weeks | P3 |
| White-label + Multi-school Branding | Partial (org/branch exists) | Franchise/chain deals unlock | High | M | 3-5 weeks | P2 |
| Integrations Marketplace | Partial (billing adapters exist) | Vendor lock-in and ecosystem moat | Medium | L | 6-10 weeks | P3 |

## 3) Fastest Revenue Narrative (Launch Messaging)

Use this 3-line pitch in sales/demo:
1. Admissions badhao: built-in inquiry-to-admission CRM.
2. Fee collection badhao: automated reminders + smart defaulter tracking.
3. Parent trust badhao: real-time alerts + transport visibility + exam progress insights.

If you can show these 3 outcomes in demo, demand jumps faster than feature-heavy generic ERP positioning.

## 4) 30-60-90 Day Execution Plan

## Day 0-30 (P1 only, fastest demand)
1. WhatsApp automation workflows
   - Attendance absent alert
   - Fee due reminder (T-7, T-3, T+1)
   - Homework/exam alert
2. Admission CRM v1
   - Lead source, stage pipeline, follow-up tasks, counselor assignment
3. Fee intelligence v1
   - Defaulter list, risk score, class-wise due dashboard

Target KPI:
- +15% follow-up conversion visibility
- +10% on-time fee collection behavior

## Day 31-60 (P2 operational moat)
1. Payroll v1 (attendance + leave linked)
2. Timetable substitution engine
3. Learning analytics dashboard (top weak chapters/classes)
4. Compliance pack v1 (monthly export templates)

Target KPI:
- 25-35% admin time reduction in monthly operations

## Day 61-90 (P2/P3 expansion)
1. Transport tracking v1
2. White-label controls for multi-school deployments
3. Integration connectors (priority: accounting + communication)

Target KPI:
- Higher ACV (annual contract value) and chain-school deal readiness

## 5) Suggested Pricing Packaging (Demand-led)

| Plan | Price Logic | Core Hook |
|------|-------------|-----------|
| Starter | Low entry | Attendance + basic fees + announcements |
| Growth | Mid | CRM + fee intelligence + WhatsApp automation |
| Premium | High | Payroll + transport + analytics + white-label |

Important:
- Add setup/onboarding fee for data migration + training.
- Offer 14-day assisted pilot with measurable KPI report.

## 6) Technical Implementation Order (based on existing architecture)

1. Build Admission CRM as a module in current module registry/controller pattern.
2. Extend notifications pipeline into template-based WhatsApp queue.
3. Enhance fee module with risk scoring and reminder scheduler.
4. Add analytics aggregate tables/jobs for principal dashboard.
5. Add payroll entities and approval workflows using existing role/license controls.

This order reuses current architecture, minimizes rewrite risk, and gives fastest market-facing outcomes.

## 7) Pre-Launch Readiness Checklist (Must-have)

1. Security baseline: file upload validation, role hardening, audit completeness.
2. Reliability baseline: queue monitoring, webhook retry visibility, dead-letter handling.
3. Demo baseline: one-click demo reset with realistic school dataset.
4. Success baseline: owner dashboard with 3 KPIs (admissions, collections, attendance).

---

Conclusion:
Agar demand launch ke saath spike karni hai, to next build should be outcome-first:
- Admission growth
- Fee collection improvement
- Parent trust visibility

Feature breadth se zyada outcome proof bikta hai.
