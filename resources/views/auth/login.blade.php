@extends('layouts.app')

@section('content')
@php
    $org = $organizationContext['organization'] ?? null;
    $orgName = $org?->name ?: config('app.name', 'SchoolSphere');
    $orgTypeLabel = ucfirst((string) ($org?->type ?: 'school'));
    $brandShort = strtoupper(substr((string) ($org?->short_name ?: $orgName), 0, 2));
    $activeRole = old('role_hint', 'super-admin');
@endphp

<style>
    @keyframes loginFloatIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.985);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes loginPulseGlow {
        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(17, 103, 177, 0.18);
        }

        50% {
            box-shadow: 0 0 0 10px rgba(17, 103, 177, 0);
        }
    }

    @keyframes loginCardReveal {
        from {
            opacity: 0;
            transform: translateY(18px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes loginGradientShift {
        0% {
            transform: translateX(-22%);
            opacity: 0.24;
        }

        50% {
            transform: translateX(22%);
            opacity: 0.38;
        }

        100% {
            transform: translateX(-22%);
            opacity: 0.24;
        }
    }

    @keyframes loginOrbDrift {
        0% {
            transform: translate3d(0, 0, 0);
        }

        50% {
            transform: translate3d(-6px, -8px, 0);
        }

        100% {
            transform: translate3d(0, 0, 0);
        }
    }

    @keyframes loginWindowFlicker {
        0%,
        100% {
            opacity: 0.88;
        }

        48% {
            opacity: 0.78;
        }

        54% {
            opacity: 1;
        }
    }

    .login-page-shell {
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .login-page-shell::before,
    .login-page-shell::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        filter: blur(12px);
        opacity: 0.55;
        pointer-events: none;
    }

    .login-page-shell::before {
        width: 280px;
        height: 280px;
        top: 2rem;
        left: -6rem;
        background: rgba(17, 103, 177, 0.16);
    }

    .login-page-shell::after {
        width: 240px;
        height: 240px;
        right: -4rem;
        bottom: 3rem;
        background: rgba(15, 118, 110, 0.16);
    }

    .login-page-grid {
        position: relative;
        z-index: 1;
    }

    .login-backdrop-grid {
        position: absolute;
        inset: 1.5rem 1rem auto 1rem;
        height: 420px;
        border-radius: 2rem;
        background-image:
            linear-gradient(rgba(17, 103, 177, 0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(17, 103, 177, 0.06) 1px, transparent 1px);
        background-size: 28px 28px;
        mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), transparent 90%);
        pointer-events: none;
        z-index: 0;
    }

    .login-brand-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        margin-bottom: 1.5rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--role-soft), rgba(15, 118, 110, 0.1));
        border: 1px solid color-mix(in srgb, var(--role-accent) 28%, transparent);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.42);
        animation: loginFloatIn 0.6s ease-out;
    }

    .login-brand-logo {
        width: 60px;
        height: 60px;
        border-radius: 1rem;
        object-fit: cover;
        border: 1px solid rgba(17, 103, 177, 0.18);
        background: #fff;
        flex-shrink: 0;
        animation: loginPulseGlow 2.8s ease-in-out infinite;
    }

    .login-brand-mark {
        width: 60px;
        height: 60px;
        border-radius: 1rem;
        display: grid;
        place-items: center;
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #145da0, #0f766e);
        flex-shrink: 0;
        animation: loginPulseGlow 2.8s ease-in-out infinite;
    }

    .login-card-shell {
        --role-accent: #145da0;
        --role-soft: rgba(17, 103, 177, 0.09);
        --role-strong: #0f766e;
        border: 1px solid rgba(17, 103, 177, 0.14);
        box-shadow: 0 22px 38px -30px rgba(17, 103, 177, 0.62);
        animation: loginFloatIn 0.72s ease-out;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .login-card-shell[data-role-theme='super-admin'] {
        --role-accent: #d97706;
        --role-soft: rgba(245, 158, 11, 0.12);
        --role-strong: #b45309;
        border-color: rgba(245, 158, 11, 0.28);
        box-shadow: 0 26px 44px -34px rgba(245, 158, 11, 0.62);
    }

    .login-card-shell[data-role-theme='staff'] {
        --role-accent: #0f766e;
        --role-soft: rgba(20, 184, 166, 0.12);
        --role-strong: #0f766e;
        border-color: rgba(20, 184, 166, 0.28);
        box-shadow: 0 26px 44px -34px rgba(20, 184, 166, 0.62);
    }

    .login-card-shell[data-role-theme='student'] {
        --role-accent: #1d4ed8;
        --role-soft: rgba(99, 102, 241, 0.12);
        --role-strong: #4338ca;
        border-color: rgba(99, 102, 241, 0.28);
        box-shadow: 0 26px 44px -34px rgba(99, 102, 241, 0.62);
    }

    .login-helper-panel {
        position: sticky;
        top: 5.75rem;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: loginFloatIn 0.82s ease-out;
    }

    .login-helper-panel::before {
        content: '';
        position: absolute;
        inset: auto -10% -20% auto;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.22), transparent 68%);
        pointer-events: none;
    }

    .login-panel-inner {
        position: relative;
        z-index: 1;
    }

    .login-hero-visual {
        position: relative;
        min-height: 165px;
        margin: 1.4rem 0 1.15rem;
        border-radius: 1.35rem;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 28%, rgba(255, 255, 255, 0.22), transparent 20%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.04));
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .login-hero-visual::before {
        content: '';
        position: absolute;
        inset: -40% -25%;
        background: linear-gradient(115deg, transparent 28%, rgba(255, 255, 255, 0.22), transparent 68%);
        animation: loginGradientShift 8.5s ease-in-out infinite;
        pointer-events: none;
    }

    .login-hero-orb,
    .login-hero-orb-two {
        position: absolute;
        border-radius: 999px;
        filter: blur(2px);
        animation: loginOrbDrift 6.2s ease-in-out infinite;
    }

    .login-hero-orb {
        width: 120px;
        height: 120px;
        top: -1.8rem;
        right: -1rem;
        background: rgba(255, 255, 255, 0.18);
    }

    .login-hero-orb-two {
        width: 88px;
        height: 88px;
        bottom: -1.25rem;
        left: 1rem;
        background: rgba(255, 209, 102, 0.24);
        animation-duration: 7.4s;
    }

    .login-campus-line {
        position: absolute;
        inset: auto 0 0 0;
        height: 58%;
        background: linear-gradient(180deg, rgba(11, 19, 43, 0), rgba(11, 19, 43, 0.22) 28%, rgba(11, 19, 43, 0.42) 100%);
    }

    .login-campus-building,
    .login-campus-tower,
    .login-campus-wing {
        position: absolute;
        bottom: 0;
        background: rgba(10, 19, 42, 0.82);
        border-top-left-radius: 0.8rem;
        border-top-right-radius: 0.8rem;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
    }

    .login-campus-building {
        left: 17%;
        width: 32%;
        height: 52%;
    }

    .login-campus-wing {
        left: 47%;
        width: 26%;
        height: 38%;
    }

    .login-campus-tower {
        right: 13%;
        width: 14%;
        height: 66%;
    }

    .login-campus-building::before,
    .login-campus-wing::before,
    .login-campus-tower::before {
        content: '';
        position: absolute;
        inset: 14% 14% auto 14%;
        height: 8px;
        background-image: radial-gradient(circle, rgba(255, 224, 102, 0.86) 0 2px, transparent 2.6px);
        background-size: 18px 8px;
        background-repeat: repeat-x;
        animation: loginWindowFlicker 4.4s ease-in-out infinite;
    }

    .login-campus-building::after,
    .login-campus-wing::after,
    .login-campus-tower::after {
        content: '';
        position: absolute;
        inset: 30% 14% auto 14%;
        height: 8px;
        background-image: radial-gradient(circle, rgba(255, 224, 102, 0.82) 0 2px, transparent 2.6px);
        background-size: 18px 8px;
        background-repeat: repeat-x;
        animation: loginWindowFlicker 4.8s ease-in-out infinite;
    }

    .login-campus-caption {
        position: absolute;
        left: 1.1rem;
        top: 1rem;
        max-width: 13rem;
        font-size: 0.86rem;
        color: rgba(255, 255, 255, 0.92);
        z-index: 9;
    }

    .login-role-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 0.7rem;
    }

    .login-role-list {
        display: grid;
        gap: 1rem;
        margin-top: 1rem;
    }

    .login-role-card {
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
        opacity: 0.72;
        transform: scale(0.985);
        transition: transform 0.28s ease, opacity 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
    }

    .login-role-card.active {
        opacity: 1;
        transform: scale(1);
        border-color: rgba(255, 255, 255, 0.34);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 18px 40px -28px rgba(15, 23, 42, 0.85);
    }

    .login-role-card:hover {
        transform: translateY(-2px) scale(1.006);
    }

    .login-role-card.super-admin-card {
        background: linear-gradient(135deg, rgba(255, 221, 87, 0.2), rgba(245, 158, 11, 0.16));
    }

    .login-role-card.staff-card {
        background: linear-gradient(135deg, rgba(74, 222, 128, 0.18), rgba(20, 184, 166, 0.14));
    }

    .login-role-card.student-card {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.18), rgba(99, 102, 241, 0.14));
    }

    .login-role-icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 0.8rem;
        display: inline-grid;
        place-items: center;
        margin-bottom: 0.75rem;
        background: rgba(255, 255, 255, 0.14);
        font-size: 1rem;
    }

    .login-role-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.73rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.16);
        white-space: nowrap;
    }

    .login-role-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .login-role-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.88);
        font-weight: 700;
        font-size: 0.82rem;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
    }

    .login-role-tab:hover {
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 0.12);
    }

    .login-role-tab.active {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.28);
    }

    .login-brand-card {
        flex-wrap: wrap;
        row-gap: 0.9rem;
    }

    .login-brand-card .login-role-tabs {
        flex: 1 1 calc(100% - 76px);
        margin-top: 0;
        justify-content: flex-start;
    }

    .login-brand-card .login-role-tab {
        border-color: color-mix(in srgb, var(--role-accent) 22%, transparent);
        background: color-mix(in srgb, var(--role-soft) 86%, #ffffff);
        color: color-mix(in srgb, var(--role-accent) 78%, #0f172a);
        font-weight: 800;
    }

    .login-brand-card .login-role-tab:hover {
        background: color-mix(in srgb, var(--role-soft) 100%, #ffffff);
        color: var(--role-accent);
        border-color: color-mix(in srgb, var(--role-accent) 35%, transparent);
    }

    .login-brand-card .login-role-tab.active {
        background: linear-gradient(135deg, var(--role-accent), var(--role-strong));
        color: #fff;
        border-color: transparent;
        box-shadow: 0 12px 24px -20px color-mix(in srgb, var(--role-accent) 80%, transparent);
    }

    .login-credential-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.95rem;
    }

    .login-credential-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 0.72rem;
        border-radius: 0.8rem;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.96);
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .login-credential-badge strong {
        color: #fff;
    }

    .login-quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 0.9rem;
    }

    .login-quick-link {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.58rem 0.82rem;
        border-radius: 0.9rem;
        text-decoration: none;
        color: #fff;
        font-weight: 700;
        font-size: 0.84rem;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        transition: transform 0.18s ease, background 0.18s ease;
    }

    .login-quick-link:hover {
        color: #fff;
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 0.18);
    }

    .login-step-list {
        display: grid;
        gap: 0.55rem;
        margin: 0.85rem 0 0;
        padding: 0;
        list-style: none;
    }

    .login-step-item {
        display: flex;
        gap: 0.7rem;
        align-items: flex-start;
        color: rgba(255, 255, 255, 0.92);
    }

    .login-step-number {
        width: 1.55rem;
        height: 1.55rem;
        border-radius: 999px;
        display: inline-grid;
        place-items: center;
        font-size: 0.78rem;
        font-weight: 800;
        color: #0f172a;
        background: rgba(255, 255, 255, 0.88);
        flex-shrink: 0;
        margin-top: 0.05rem;
    }

    .login-submit-btn {
        min-height: 3rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .login-password-wrap {
        position: relative;
    }

    .login-login-wrap {
        position: relative;
    }

    .login-input-icon {
        position: absolute;
        left: 0.62rem;
        top: 50%;
        transform: translateY(-50%);
        width: 2rem;
        height: 2rem;
        border-radius: 0.65rem;
        display: inline-grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, var(--role-accent), var(--role-strong));
        box-shadow: 0 10px 20px -16px color-mix(in srgb, var(--role-accent) 70%, transparent);
        pointer-events: none;
    }

    .login-input-field {
        padding-left: 3rem;
    }

    .login-password-input {
        padding-right: 3.15rem;
    }

    .login-password-toggle {
        position: absolute;
        right: 0.55rem;
        top: 50%;
        transform: translateY(-50%);
        width: 2.2rem;
        height: 2.2rem;
        border: 0;
        border-radius: 0.75rem;
        background: color-mix(in srgb, var(--role-soft) 72%, #ffffff);
        color: var(--role-accent);
        display: inline-grid;
        place-items: center;
    }

    .login-password-toggle:hover {
        background: color-mix(in srgb, var(--role-soft) 92%, #ffffff);
    }

    .login-submit-btn {
        background: linear-gradient(135deg, var(--role-accent), var(--role-strong));
        border: 0;
        box-shadow: 0 16px 28px -22px color-mix(in srgb, var(--role-accent) 72%, transparent);
    }

    .login-submit-btn:hover {
        background: linear-gradient(135deg, color-mix(in srgb, var(--role-accent) 86%, #000000), color-mix(in srgb, var(--role-strong) 88%, #000000));
    }

    .login-form-quick-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .login-form-chip {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding: 0.85rem 0.95rem;
        border-radius: 1rem;
        text-decoration: none;
        color: var(--app-text);
        background: linear-gradient(135deg, color-mix(in srgb, var(--role-soft) 78%, #ffffff), rgba(15, 118, 110, 0.06));
        border: 1px solid color-mix(in srgb, var(--role-accent) 24%, transparent);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .login-form-chip:hover {
        color: var(--app-primary);
        transform: translateY(-1px);
        border-color: rgba(17, 103, 177, 0.22);
        box-shadow: 0 14px 28px -24px rgba(17, 103, 177, 0.7);
    }

    .login-form-chip-icon {
        width: 2.3rem;
        height: 2.3rem;
        display: inline-grid;
        place-items: center;
        border-radius: 0.8rem;
        background: #fff;
        color: var(--role-accent);
        border: 1px solid color-mix(in srgb, var(--role-accent) 22%, transparent);
        flex-shrink: 0;
    }

    .login-demo-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .login-demo-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.42rem;
        padding: 0.42rem 0.7rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--role-accent);
        background: color-mix(in srgb, var(--role-soft) 76%, #ffffff);
        border: 1px solid color-mix(in srgb, var(--role-accent) 22%, transparent);
    }

    .login-role-preview {
        position: relative;
        margin: 1rem 0 1.1rem;
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid color-mix(in srgb, var(--role-accent) 22%, transparent);
        background: linear-gradient(135deg, color-mix(in srgb, var(--role-soft) 78%, #ffffff), rgba(15, 118, 110, 0.06));
        overflow: hidden;
        animation: loginFloatIn 0.85s ease-out;
    }

    .login-role-preview::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        right: -2rem;
        top: -2.25rem;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.32), transparent 70%);
        pointer-events: none;
    }

    .login-role-preview-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.8rem;
    }

    .login-role-preview-title {
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .login-role-preview-icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 0.85rem;
        display: inline-grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, #145da0, #0f766e);
        box-shadow: 0 14px 28px -22px rgba(17, 103, 177, 0.75);
    }

    .login-role-preview-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.34rem 0.68rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        color: var(--role-accent);
        background: rgba(255, 255, 255, 0.76);
        border: 1px solid color-mix(in srgb, var(--role-accent) 18%, transparent);
    }

    .login-role-preview-copy {
        margin: 0;
        color: var(--app-muted);
        font-size: 0.92rem;
    }

    .login-role-preview-meta {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.9rem;
    }

    .login-role-preview-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.65rem 0.78rem;
        border-radius: 0.9rem;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid color-mix(in srgb, var(--role-accent) 15%, transparent);
        font-size: 0.84rem;
        color: var(--app-text);
    }

    .login-role-preview-item strong {
        min-width: 5.1rem;
    }

    .login-role-preview-item i {
        color: var(--role-accent);
    }

    @media (max-width: 991.98px) {
        .login-helper-panel {
            position: static;
            top: auto;
        }

        .login-page-shell::before,
        .login-page-shell::after {
            display: none;
        }

        .login-backdrop-grid {
            display: none;
        }

        .login-hero-visual {
            min-height: 145px;
        }

        .login-form-quick-actions {
            grid-template-columns: 1fr;
        }

        .login-role-preview-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .login-brand-card .login-role-tabs {
            flex-basis: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .login-role-header {
            flex-direction: column;
        }

        .login-role-chip {
            align-self: flex-start;
        }
    }
</style>

<div class="container-fluid px-4 py-4 py-lg-5 login-page-shell">
    <div class="login-backdrop-grid"></div>
    <div class="row justify-content-center align-items-start g-4 g-xl-5 login-page-grid">
        <div class="col-lg-6 col-xl-6">
            <div class="card app-card border-0 shadow-sm login-card-shell" id="loginCardShell" data-role-theme="{{ $activeRole }}">
                <div class="card-body p-4 p-lg-5">
                    <div class="login-brand-card">
                        @if (! empty($org?->logo_path))
                            <img src="{{ asset('storage/'.$org->logo_path) }}" alt="{{ $orgName }}" class="login-brand-logo">
                        @else
                            <span class="login-brand-mark">{{ $brandShort }}</span>
                        @endif
                        <!-- <div>
                            <span class="eyebrow">{{ $orgTypeLabel }} Login Portal</span>
                            <h1 class="h4 mb-1">{{ $orgName }}</h1>
                            <p class="text-body-secondary mb-0">Logo aur school name yahin visible rahenge, chahe page scroll ho.</p>
                        </div> -->
                        <div class="login-role-tabs" role="tablist" aria-label="Login role selection">
                            <button type="button" class="login-role-tab {{ $activeRole === 'super-admin' ? 'active' : '' }}" data-role-tab="super-admin" role="tab" aria-selected="{{ $activeRole === 'super-admin' ? 'true' : 'false' }}"><i class="bi bi-shield-lock"></i> Super Admin</button>
                            <button type="button" class="login-role-tab {{ $activeRole === 'staff' ? 'active' : '' }}" data-role-tab="staff" role="tab" aria-selected="{{ $activeRole === 'staff' ? 'true' : 'false' }}"><i class="bi bi-person-badge"></i> Staff</button>
                            <button type="button" class="login-role-tab {{ $activeRole === 'student' ? 'active' : '' }}" data-role-tab="student" role="tab" aria-selected="{{ $activeRole === 'student' ? 'true' : 'false' }}"><i class="bi bi-mortarboard"></i> Student</button>
                        </div>
                    </div>

                    <span class="eyebrow">Authentication</span>
                    <h2 class="h3 mb-1">Sign in to your account</h2>
                    <p class="text-body-secondary mb-3">Email ya mobile number se login karein. First login ke baad password update karna better rahega.</p>

                    <!-- <div class="login-demo-strip">
                        <span class="login-demo-pill"><i class="bi bi-shield-lock"></i> Super Admin Ready</span>
                        <span class="login-demo-pill"><i class="bi bi-person-badge"></i> Staff Access Enabled</span>
                        <span class="login-demo-pill"><i class="bi bi-mortarboard"></i> Student Portal Live</span>
                    </div> -->

                    <div class="login-role-preview" id="rolePreviewBox">
                        <div class="login-role-preview-head">
                            <div class="login-role-preview-title">
                                <span class="login-role-preview-icon" id="rolePreviewIcon"><i class="bi bi-shield-lock"></i></span>
                                <div>
                                    <strong class="d-block" id="rolePreviewTitle">Super Admin Access</strong>
                                    <p class="login-role-preview-copy" id="rolePreviewCopy">Initial setup, institute configuration aur full control ke liye default super admin account use hota hai.</p>
                                </div>
                            </div>
                            <span class="login-role-preview-chip" id="rolePreviewChip"><i class="bi bi-stars"></i> Priority Access</span>
                        </div>
                        <div class="login-role-preview-meta">
                            <div class="login-role-preview-item"><i class="bi bi-person-lines-fill"></i><strong>Login Input</strong><span id="rolePreviewLogin">Use superadmin email</span></div>
                            <div class="login-role-preview-item"><i class="bi bi-key"></i><strong>Demo Access</strong><span id="rolePreviewCredential">superadmin@school.com / Admin@123</span></div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <input type="hidden" name="role_hint" id="role_hint" value="{{ $activeRole }}">

                        <div class="mb-3">
                            <label for="login" class="form-label">Email Address or Mobile Number</label>
                            <div class="login-login-wrap">
                                <span class="login-input-icon" id="loginFieldIcon"><i class="bi bi-shield-lock"></i></span>
                                <input id="login" type="text" class="form-control login-input-field @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" placeholder="superadmin@school.com" required autocomplete="username" autofocus>
                            </div>
                            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="login-password-wrap">
                                <input id="password" type="password" class="form-control login-password-input @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                <button type="button" class="login-password-toggle" id="passwordToggle" aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                </button>
                            </div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Remember Me</label>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                @if (Route::has('password.request'))
                                    <a class="small" href="{{ route('password.request') }}">Staff/Admin forgot password?</a>
                                @endif
                                <a class="small" href="{{ route('student.password.reset.form') }}">Student reset password</a>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 login-submit-btn">Login</button>
                    </form>

                    <div class="login-form-quick-actions">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="login-form-chip">
                                <span class="login-form-chip-icon"><i class="bi bi-key"></i></span>
                                <span>
                                    <strong class="d-block">Staff/Admin Help</strong>
                                    <small class="text-body-secondary">Forgot password support</small>
                                </span>
                            </a>
                        @endif
                        <a href="{{ route('student.password.reset.form') }}" class="login-form-chip">
                            <span class="login-form-chip-icon"><i class="bi bi-person-vcard"></i></span>
                            <span>
                                <strong class="d-block">Student Reset</strong>
                                <small class="text-body-secondary">Roll no + guardian verify</small>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-6">
            <div class="hero-panel p-4 p-lg-5 login-helper-panel">
                <div class="login-panel-inner">
                    <span class="eyebrow text-white-50">Login Guide</span>
                    <h2 class="h4 text-white mb-2">Premium access panel with role-wise steps</h2>
                    <p class="text-white-50 mb-0">Super admin, staff, aur student sab ke liye same form use hota hai, lekin credentials aur recovery flow alag dikhaya gaya hai.</p>

                    <!-- <div class="login-role-tabs" role="tablist" aria-label="Login role selection">
                        <button type="button" class="login-role-tab {{ $activeRole === 'super-admin' ? 'active' : '' }}" data-role-tab="super-admin" role="tab" aria-selected="{{ $activeRole === 'super-admin' ? 'true' : 'false' }}"><i class="bi bi-shield-lock"></i> Super Admin</button>
                        <button type="button" class="login-role-tab {{ $activeRole === 'staff' ? 'active' : '' }}" data-role-tab="staff" role="tab" aria-selected="{{ $activeRole === 'staff' ? 'true' : 'false' }}"><i class="bi bi-person-badge"></i> Staff</button>
                        <button type="button" class="login-role-tab {{ $activeRole === 'student' ? 'active' : '' }}" data-role-tab="student" role="tab" aria-selected="{{ $activeRole === 'student' ? 'true' : 'false' }}"><i class="bi bi-mortarboard"></i> Student</button>
                    </div> -->

                    <div class="login-hero-visual" aria-hidden="true">
                        <div class="login-hero-orb"></div>
                        <div class="login-hero-orb-two"></div>
                        <div class="login-campus-caption">
                            <strong class="d-block mb-1">Digital campus access</strong>
                            Attendance, academics, fee, exam aur student portal ek hi secure login se.
                        </div>
                        <div class="login-campus-line"></div>
                        <div class="login-campus-building"></div>
                        <div class="login-campus-wing"></div>
                        <div class="login-campus-tower"></div>
                    </div>

                    <div class="login-role-list">
                        <section class="login-role-card super-admin-card {{ $activeRole === 'super-admin' ? 'active' : '' }}" data-role-card="super-admin" style="animation: loginCardReveal 0.55s ease-out 0.04s both;">
                            <div class="login-role-header">
                                <div>
                                    <span class="login-role-icon"><i class="bi bi-shield-lock"></i></span>
                                    <h3 class="h6 text-white mb-1">Super Admin Login Step</h3>
                                    <p class="small text-white-50 mb-0">Initial setup ke baad yahi account system activate aur configure karta hai.</p>
                                </div>
                                <span class="login-role-chip"><i class="bi bi-stars"></i> Control Access</span>
                            </div>
                            <ul class="login-step-list">
                                <li class="login-step-item"><span class="login-step-number">1</span><span><strong>Email:</strong> superadmin@school.com</span></li>
                                <li class="login-step-item"><span class="login-step-number">2</span><span><strong>Password:</strong> Admin@123</span></li>
                                <li class="login-step-item"><span class="login-step-number">3</span><span>First login ke baad password change karein aur institute setup complete karein.</span></li>
                            </ul>
                            <div class="login-credential-badges">
                                <span class="login-credential-badge"><i class="bi bi-envelope"></i> <strong>Login:</strong> superadmin@school.com</span>
                                <span class="login-credential-badge"><i class="bi bi-key"></i> <strong>Pass:</strong> Admin@123</span>
                            </div>
                        </section>

                        <section class="login-role-card staff-card {{ $activeRole === 'staff' ? 'active' : '' }}" data-role-card="staff" style="animation: loginCardReveal 0.55s ease-out 0.14s both;">
                            <div class="login-role-header">
                                <div>
                                    <span class="login-role-icon"><i class="bi bi-person-badge"></i></span>
                                    <h3 class="h6 text-white mb-1">Staff Login Step</h3>
                                    <p class="small text-white-50 mb-0">Teachers, admin staff, HR ya office staff apna assigned account use karenge.</p>
                                </div>
                                <span class="login-role-chip"><i class="bi bi-briefcase"></i> Staff Console</span>
                            </div>
                            <ul class="login-step-list">
                                <li class="login-step-item"><span class="login-step-number">1</span><span>Login field me staff ka <strong>email</strong> ya <strong>mobile number</strong> dalein.</span></li>
                                <li class="login-step-item"><span class="login-step-number">2</span><span>Most demo staff accounts ka default password <strong>Password@123</strong> hai, lekin legacy teacher account <strong>teacher@school.com</strong> ka password <strong>ChangeMe@123</strong> hai.</span></li>
                                <li class="login-step-item"><span class="login-step-number">3</span><span>Agar password yaad na ho to admin se reset karwayein ya forgot-password link use karein.</span></li>
                            </ul>
                            <div class="login-credential-badges">
                                <span class="login-credential-badge"><i class="bi bi-person-lines-fill"></i> <strong>Use:</strong> email ya mobile</span>
                                <span class="login-credential-badge"><i class="bi bi-shield-check"></i> <strong>Demo:</strong> Password@123</span>
                            </div>
                            @if (Route::has('password.request'))
                                <div class="login-quick-links">
                                    <a href="{{ route('password.request') }}" class="login-quick-link"><i class="bi bi-arrow-up-right-circle"></i> Staff forgot password</a>
                                </div>
                            @endif
                        </section>

                        <section class="login-role-card student-card {{ $activeRole === 'student' ? 'active' : '' }}" data-role-card="student" style="animation: loginCardReveal 0.55s ease-out 0.24s both;">
                            <div class="login-role-header">
                                <div>
                                    <span class="login-role-icon"><i class="bi bi-mortarboard"></i></span>
                                    <h3 class="h6 text-white mb-1">Student Login Step</h3>
                                    <p class="small text-white-50 mb-0">Student portal ke liye student ke own login details use honge.</p>
                                </div>
                                <span class="login-role-chip"><i class="bi bi-journal-bookmark"></i> Portal Entry</span>
                            </div>
                            <ul class="login-step-list">
                                <li class="login-step-item"><span class="login-step-number">1</span><span>Student apna <strong>email</strong> ya <strong>mobile number</strong> login box me dale.</span></li>
                                <li class="login-step-item"><span class="login-step-number">2</span><span>Seeded student accounts ka default password student ka <strong>roll number</strong> hota hai. Example: <strong>10A001</strong>.</span></li>
                                <li class="login-step-item"><span class="login-step-number">3</span><span>Password issue ho to <strong>Student reset password</strong> link se reset karein.</span></li>
                            </ul>
                            <div class="login-credential-badges">
                                <span class="login-credential-badge"><i class="bi bi-phone"></i> <strong>Use:</strong> email ya mobile</span>
                                <span class="login-credential-badge"><i class="bi bi-hash"></i> <strong>Default:</strong> roll number</span>
                            </div>
                            <div class="login-quick-links">
                                <a href="{{ route('student.password.reset.form') }}" class="login-quick-link"><i class="bi bi-arrow-up-right-circle"></i> Student reset password</a>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var roleConfig = {
            'super-admin': {
                placeholder: 'superadmin@school.com',
                title: 'Super Admin Access',
                copy: 'Initial setup, institute configuration aur full control ke liye default super admin account use hota hai.',
                chip: '<i class="bi bi-stars"></i> Priority Access',
                icon: 'bi bi-shield-lock',
                loginIcon: 'bi bi-shield-lock',
                theme: 'super-admin',
                login: 'Use superadmin email',
                credential: 'superadmin@school.com / Admin@123'
            },
            'staff': {
                placeholder: 'Staff email ya mobile number',
                title: 'Staff Dashboard Access',
                copy: 'Teacher, office, HR aur admin staff same form se apna assigned email ya mobile use karke login karte hain.',
                chip: '<i class="bi bi-briefcase"></i> Staff Console',
                icon: 'bi bi-person-badge',
                loginIcon: 'bi bi-person-badge',
                theme: 'staff',
                login: 'Use staff email ya mobile',
                credential: 'Most staff: Password@123 | teacher@school.com: ChangeMe@123'
            },
            'student': {
                placeholder: 'Student email ya mobile number',
                title: 'Student Portal Access',
                copy: 'Student portal login ke liye student ka email ya mobile use hota hai, aur default password roll number ho sakta hai.',
                chip: '<i class="bi bi-journal-bookmark"></i> Portal Entry',
                icon: 'bi bi-mortarboard',
                loginIcon: 'bi bi-mortarboard',
                theme: 'student',
                login: 'Use student email ya mobile',
                credential: 'Default password: student roll number, e.g. 10A001'
            }
        };
        var passwordInput = document.getElementById('password');
        var passwordToggle = document.getElementById('passwordToggle');
        var passwordToggleIcon = document.getElementById('passwordToggleIcon');
        var loginInput = document.getElementById('login');
        var loginFieldIcon = document.getElementById('loginFieldIcon');
        var loginCardShell = document.getElementById('loginCardShell');
        var roleHintInput = document.getElementById('role_hint');
        var roleTabs = Array.prototype.slice.call(document.querySelectorAll('[data-role-tab]'));
        var roleCards = Array.prototype.slice.call(document.querySelectorAll('[data-role-card]'));
        var rolePreviewTitle = document.getElementById('rolePreviewTitle');
        var rolePreviewCopy = document.getElementById('rolePreviewCopy');
        var rolePreviewChip = document.getElementById('rolePreviewChip');
        var rolePreviewIcon = document.getElementById('rolePreviewIcon');
        var rolePreviewLogin = document.getElementById('rolePreviewLogin');
        var rolePreviewCredential = document.getElementById('rolePreviewCredential');

        if (passwordInput && passwordToggle && passwordToggleIcon) {
            passwordToggle.addEventListener('click', function () {
                var isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                passwordToggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                passwordToggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        if (roleTabs.length && roleCards.length) {
            var activateRole = function (role) {
                var config = roleConfig[role] || roleConfig['super-admin'];

                roleTabs.forEach(function (tab) {
                    var isActive = tab.getAttribute('data-role-tab') === role;
                    tab.classList.toggle('active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                roleCards.forEach(function (card) {
                    card.classList.toggle('active', card.getAttribute('data-role-card') === role);
                });

                if (roleHintInput) {
                    roleHintInput.value = role;
                }

                if (loginInput) {
                    loginInput.setAttribute('placeholder', config.placeholder);
                }

                if (loginFieldIcon) {
                    loginFieldIcon.innerHTML = '<i class="' + config.loginIcon + '"></i>';
                }

                if (loginCardShell) {
                    loginCardShell.setAttribute('data-role-theme', config.theme);
                }

                if (rolePreviewTitle) {
                    rolePreviewTitle.textContent = config.title;
                }

                if (rolePreviewCopy) {
                    rolePreviewCopy.textContent = config.copy;
                }

                if (rolePreviewChip) {
                    rolePreviewChip.innerHTML = config.chip;
                }

                if (rolePreviewIcon) {
                    rolePreviewIcon.innerHTML = '<i class="' + config.icon + '"></i>';
                }

                if (rolePreviewLogin) {
                    rolePreviewLogin.textContent = config.login;
                }

                if (rolePreviewCredential) {
                    rolePreviewCredential.textContent = config.credential;
                }
            };

            roleTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activateRole(tab.getAttribute('data-role-tab'));
                });
            });

            activateRole(roleHintInput ? roleHintInput.value : 'super-admin');
        }
    })();
</script>
@endpush
