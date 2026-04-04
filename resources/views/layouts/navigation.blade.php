@php
    $announcementsCount = $headerAnnouncements->count();
    $notificationsCount = $headerNotificationsUnseenCount ?? 0;
    $messagesCount = $headerMessagesUnseenCount ?? 0;

    $firstThread = ($headerMessages ?? collect())->first();
    $firstThreadUser = $firstThread
        ? ($firstThread->sender_id === auth()->id() ? $firstThread->receiver : $firstThread->sender)
        : null;
@endphp

<style>
    :root,
    [data-bs-theme="light"] {
        --mx-font: "Vazirmatn", "IRANSansX", "Yekan Bakh", Tahoma, sans-serif;

        --mx-bg: #f8fafc;
        --mx-surface: #ffffff;
        --mx-surface-2: #f1f5f9;
        --mx-surface-3: #e2e8f0;

        --mx-text: #0f172a;
        --mx-text-soft: #334155;
        --mx-text-muted: #64748b;

        --mx-border: #e2e8f0;
        --mx-border-strong: #cbd5e1;

        --mx-primary: #2563eb;
        --mx-primary-2: #3b82f6;
        --mx-success: #16a34a;
        --mx-danger: #dc2626;
        --mx-warning: #d97706;

        --mx-shadow-sm: 0 8px 24px rgba(15, 23, 42, .06);
        --mx-shadow-md: 0 18px 38px rgba(15, 23, 42, .08);
        --mx-shadow-lg: 0 26px 70px rgba(15, 23, 42, .14);

        --mx-header-bg: rgba(255,255,255,.88);
        --mx-header-btn: rgba(255,255,255,.92);
        --mx-header-btn-hover: #ffffff;

        --mx-modal-bg: #ffffff;
        --mx-modal-header-bg: #ffffff;
        --mx-modal-body-bg: #f8fafc;
        --mx-modal-footer-bg: #ffffff;
        --mx-modal-backdrop: rgba(15, 23, 42, .42);

        --mx-chat-list-bg: #ffffff;
        --mx-chat-pane-bg: #ffffff;
        --mx-chat-scroll-bg: #f8fafc;
        --mx-chat-mine: #dbeafe;
        --mx-chat-mine-border: #bfdbfe;
        --mx-chat-theirs: #ffffff;
        --mx-chat-theirs-border: #e2e8f0;

        --mx-badge-surface: #ffffff;
        --mx-empty-border: #cbd5e1;
        --mx-highlight-bg: #eff6ff;
        --mx-highlight-border: #bfdbfe;
        --mx-new-bg: rgba(239, 68, 68, .12);
        --mx-new-border: rgba(239, 68, 68, .18);
    }

    [data-bs-theme="dark"] {
        --mx-bg: #0b1220;
        --mx-surface: #0f172a;
        --mx-surface-2: #111827;
        --mx-surface-3: #1e293b;

        --mx-text: #e5eefc;
        --mx-text-soft: #cbd5e1;
        --mx-text-muted: #94a3b8;

        --mx-border: rgba(148, 163, 184, .18);
        --mx-border-strong: rgba(148, 163, 184, .30);

        --mx-primary: #60a5fa;
        --mx-primary-2: #3b82f6;
        --mx-success: #22c55e;
        --mx-danger: #f87171;
        --mx-warning: #f59e0b;

        --mx-shadow-sm: 0 8px 24px rgba(0,0,0,.24);
        --mx-shadow-md: 0 18px 38px rgba(0,0,0,.30);
        --mx-shadow-lg: 0 26px 70px rgba(0,0,0,.42);

        --mx-header-bg: rgba(11,18,32,.88);
        --mx-header-btn: rgba(17,24,39,.92);
        --mx-header-btn-hover: #182132;

        --mx-modal-bg: #0f172a;
        --mx-modal-header-bg: #111827;
        --mx-modal-body-bg: #0b1220;
        --mx-modal-footer-bg: #111827;
        --mx-modal-backdrop: rgba(2, 6, 23, .72);

        --mx-chat-list-bg: #0f172a;
        --mx-chat-pane-bg: #0f172a;
        --mx-chat-scroll-bg: #0b1220;
        --mx-chat-mine: rgba(37, 99, 235, .24);
        --mx-chat-mine-border: rgba(96, 165, 250, .24);
        --mx-chat-theirs: #111827;
        --mx-chat-theirs-border: rgba(148, 163, 184, .16);

        --mx-badge-surface: #0f172a;
        --mx-empty-border: rgba(148, 163, 184, .28);
        --mx-highlight-bg: rgba(59, 130, 246, .12);
        --mx-highlight-border: rgba(96, 165, 250, .22);
        --mx-new-bg: rgba(248, 113, 113, .12);
        --mx-new-border: rgba(248, 113, 113, .22);
    }

    html,
    body {
        background: var(--mx-bg);
        color: var(--mx-text);
    }

    .mx-header,
    .mx-header *,
    .mx-modal,
    .mx-modal * {
        font-family: var(--mx-font);
    }

    .mx-header {
        position: sticky;
        top: 0;
        z-index: 1030;
        background: var(--mx-header-bg);
        border-bottom: 1px solid var(--mx-border);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: var(--mx-shadow-sm);
    }

    .mx-header__row {
        min-height: 76px;
    }

    .mx-header__logo {
        height: 48px;
        display: flex;
        align-items: center;
    }

    .mx-header__logo img {
        max-height: 40px;
        width: auto;
        display: block;
    }

    .mx-header__actions,
    .mx-header__actions--mobile {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .mx-header__dashboard-btn {
        height: 46px;
        border-radius: 16px !important;
        padding-inline: 16px !important;
        font-weight: 800;
    }

    .mx-icon-btn {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 16px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        position: relative;
        border: 1px solid var(--mx-border) !important;
        background: var(--mx-header-btn) !important;
        color: var(--mx-text) !important;
        box-shadow: var(--mx-shadow-sm);
        transition: .2s ease;
    }

    .mx-icon-btn:hover {
        background: var(--mx-header-btn-hover) !important;
        border-color: var(--mx-border-strong) !important;
        color: var(--mx-text) !important;
        transform: translateY(-1px);
    }

    .mx-icon-btn i {
        font-size: 1.1rem;
    }

    .mx-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 900;
        color: #fff;
        border: 2px solid var(--mx-badge-surface);
    }

    .mx-badge--primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .mx-badge--danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .mx-badge--success { background: linear-gradient(135deg, #22c55e, #16a34a); }

    .mx-modal {
        --bs-modal-bg: var(--mx-modal-bg);
        --bs-modal-color: var(--mx-text);
        --bs-modal-border-color: var(--mx-border);
        --bs-modal-header-border-color: var(--mx-border);
        --bs-modal-footer-border-color: var(--mx-border);
    }

    .mx-modal .modal-dialog {
        max-width: 960px;
    }

    #headerMessagesModal .modal-dialog {
        max-width: 1180px;
    }

    .mx-modal .modal-content {
        background: var(--mx-modal-bg) !important;
        border: 1px solid var(--mx-border) !important;
        border-radius: 28px !important;
        overflow: hidden;
        box-shadow: var(--mx-shadow-lg);
        color: var(--mx-text) !important;
    }

    .mx-modal .modal-header {
        background: var(--mx-modal-header-bg) !important;
        border-bottom: 1px solid var(--mx-border) !important;
        color: var(--mx-text) !important;
        padding: 1rem 1.1rem;
    }

    .mx-modal .modal-body {
        background: var(--mx-modal-body-bg) !important;
        color: var(--mx-text) !important;
        padding: 1rem;
    }

    .mx-modal .modal-footer {
        background: var(--mx-modal-footer-bg) !important;
        border-top: 1px solid var(--mx-border) !important;
        color: var(--mx-text) !important;
        padding: 1rem 1.1rem;
    }

    .mx-modal .btn-close {
        opacity: .9;
        box-shadow: none !important;
    }

    [data-bs-theme="dark"] .mx-modal .btn-close {
        filter: invert(1) grayscale(100%);
    }

    .modal-backdrop.show {
        opacity: 1 !important;
        background: var(--mx-modal-backdrop) !important;
    }

    .mx-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
        direction: rtl;
    }

    .mx-head__side {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .mx-head__icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--mx-border);
        background: var(--mx-surface-2);
        color: var(--mx-text);
        font-size: 1.15rem;
    }

    .mx-head__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 .9rem;
        border-radius: 999px;
        background: var(--mx-surface-2);
        border: 1px solid var(--mx-border);
        color: var(--mx-text-muted);
        font-size: 12px;
        font-weight: 900;
    }

    .mx-panel-list {
        display: grid;
        gap: 1rem;
    }

    .mx-card {
        background: var(--mx-surface);
        border: 1px solid var(--mx-border);
        border-radius: 22px;
        padding: 1rem;
        box-shadow: var(--mx-shadow-sm);
        color: var(--mx-text);
    }

    .mx-card__title {
        font-size: 15px;
        font-weight: 900;
        margin: 0 0 .45rem;
        color: var(--mx-text);
        line-height: 1.9;
        text-align: right;
    }

    .mx-card__text {
        font-size: 13.5px;
        line-height: 2;
        color: var(--mx-text-soft);
        white-space: pre-line;
        text-align: right;
    }

    .mx-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .9rem;
        justify-content: flex-end;
    }

    .mx-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .8rem;
        border-radius: 999px;
        border: 1px solid var(--mx-border);
        background: var(--mx-surface-2);
        color: var(--mx-text-muted);
        font-size: 12px;
        font-weight: 700;
    }

    .mx-empty {
        background: var(--mx-surface);
        border: 1px dashed var(--mx-empty-border);
        border-radius: 24px;
        padding: 2rem 1rem;
        text-align: center;
        color: var(--mx-text-muted);
    }

    .mx-empty__icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 12px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--mx-surface-2);
        border: 1px solid var(--mx-border);
        font-size: 1.4rem;
        color: var(--mx-text);
    }

    .mx-empty__title {
        font-size: 15px;
        font-weight: 900;
        color: var(--mx-text);
        margin-bottom: .35rem;
    }

    .mx-empty__text {
        font-size: 13px;
        line-height: 2;
        color: var(--mx-text-muted);
    }

    .mx-new-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        height: 28px;
        padding: 0 .8rem;
        border-radius: 999px;
        background: var(--mx-new-bg);
        color: var(--mx-danger);
        border: 1px solid var(--mx-new-border);
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    /* messages */
    .mx-msg {
        direction: rtl;
    }

    .mx-msg [data-msg-layout] {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        grid-template-areas: "sidebar pane";
        gap: .9rem;
        min-height: 72vh;
        direction: rtl;
    }

    .mx-msg [data-msg-sidebar] {
        grid-area: sidebar;
        border: 1px solid var(--mx-border);
        border-radius: 22px;
        background: var(--mx-chat-list-bg);
        overflow: hidden;
        box-shadow: var(--mx-shadow-sm);
    }

    .mx-msg [data-msg-pane] {
        grid-area: pane;
        border: 1px solid var(--mx-border);
        border-radius: 22px;
        background: var(--mx-chat-pane-bg);
        overflow: hidden;
        position: relative;
        box-shadow: var(--mx-shadow-sm);
    }

    .mx-msg [data-msg-sidebar-top] {
        padding: .9rem;
        border-bottom: 1px solid var(--mx-border);
        background: var(--mx-surface-2);
        text-align: right;
    }

    .mx-msg [data-msg-sidebar-title] {
        font-size: 13px;
        font-weight: 900;
        color: var(--mx-text);
        margin-bottom: .55rem;
        text-align: right;
    }

    .mx-msg [data-msg-list] {
        max-height: calc(72vh - 92px);
        overflow-y: auto;
        padding: .5rem;
        display: flex;
        flex-direction: column;
        gap: .45rem;
        background: var(--mx-chat-list-bg);
    }

    .mx-msg [data-msg-item] {
        width: 100%;
        border: 1px solid transparent;
        background: transparent;
        color: inherit;
        border-radius: 18px;
        padding: .8rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        direction: rtl;
        text-align: right;
        transition: .2s ease;
    }

    .mx-msg [data-msg-item]:hover {
        background: var(--mx-surface-2);
        border-color: var(--mx-border);
    }

    .mx-msg [data-msg-item].active {
        background: var(--mx-highlight-bg);
        border-color: var(--mx-highlight-border);
    }

    .mx-msg [data-msg-avatar],
    .mx-msg [data-msg-chat-avatar],
    .mx-msg [data-msg-inline-avatar] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 900;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        flex-shrink: 0;
        border-radius: 50%;
    }

    .mx-msg [data-msg-avatar] {
        width: 52px;
        height: 52px;
        min-width: 52px;
        font-size: 15px;
    }

    .mx-msg [data-msg-chat-avatar] {
        width: 46px;
        height: 46px;
        min-width: 46px;
        font-size: 14px;
    }

    .mx-msg [data-msg-inline-avatar] {
        width: 34px;
        height: 34px;
        min-width: 34px;
        font-size: 11px;
    }

    .mx-msg [data-msg-item-body] {
        flex: 1 1 auto;
        min-width: 0;
        text-align: right;
    }

    .mx-msg [data-msg-item-top] {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .2rem;
        direction: rtl;
    }

    .mx-msg [data-msg-name] {
        font-size: 14px;
        font-weight: 900;
        color: var(--mx-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: right;
    }

    .mx-msg [data-msg-preview] {
        display: block;
        font-size: 12.5px;
        color: var(--mx-text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: right;
    }

    .mx-msg [data-msg-item-meta] {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .3rem;
        min-width: 44px;
        flex: 0 0 44px;
    }

    .mx-msg [data-msg-time] {
        font-size: 11px;
        font-weight: 800;
        color: var(--mx-text-muted);
        white-space: nowrap;
    }

    .mx-msg [data-msg-unread-dot] {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--mx-success);
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .12);
    }

    .mx-msg [data-msg-chat-head] {
        padding: .9rem 1rem;
        border-bottom: 1px solid var(--mx-border);
        background: var(--mx-surface-2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        direction: rtl;
    }

    .mx-msg [data-msg-chat-user] {
        display: flex;
        align-items: center;
        gap: .75rem;
        direction: rtl;
    }

    .mx-msg [data-msg-chat-name] {
        font-size: 15px;
        font-weight: 900;
        color: var(--mx-text);
        line-height: 1.4;
        text-align: right;
    }

    .mx-msg [data-msg-chat-status] {
        color: var(--mx-text-muted);
        font-size: 12px;
        margin-top: .15rem;
        text-align: right;
    }

    .mx-msg [data-msg-chat-scroll] {
        height: calc(72vh - 170px);
        overflow-y: auto;
        padding: 1rem;
        background: var(--mx-chat-scroll-bg);
        display: flex;
        flex-direction: column;
        gap: .8rem;
        direction: ltr;
    }

    .mx-msg [data-msg-row] {
        display: flex;
        align-items: flex-end;
        gap: .55rem;
        width: 100%;
    }

    .mx-msg [data-msg-row].mine {
        justify-content: flex-end;
    }

    .mx-msg [data-msg-row].theirs {
        justify-content: flex-start;
    }

    .mx-msg [data-msg-bubble-wrap] {
        max-width: min(78%, 620px);
    }

    .mx-msg [data-msg-bubble-sender] {
        font-size: 11px;
        font-weight: 800;
        margin-bottom: .2rem;
        color: var(--mx-text-muted);
        text-align: left;
        padding-inline: .35rem;
    }

    .mx-msg [data-msg-bubble] {
        border-radius: 20px;
        padding: .7rem .85rem .5rem;
        font-size: 13.4px;
        line-height: 1.9;
        word-break: break-word;
        box-shadow: var(--mx-shadow-sm);
        direction: ltr;
        text-align: left;
        color: var(--mx-text);
    }

    .mx-msg [data-msg-bubble].mine {
        background: var(--mx-chat-mine);
        border: 1px solid var(--mx-chat-mine-border);
        border-top-right-radius: 8px;
    }

    .mx-msg [data-msg-bubble].theirs {
        background: var(--mx-chat-theirs);
        border: 1px solid var(--mx-chat-theirs-border);
        border-top-left-radius: 8px;
    }

    .mx-msg [data-msg-bubble-time] {
        display: block;
        margin-top: .35rem;
        font-size: 10.5px;
        color: var(--mx-text-muted);
        text-align: left;
        direction: ltr;
    }

    .mx-msg [data-msg-compose] {
        padding: .85rem;
        border-top: 1px solid var(--mx-border);
        background: var(--mx-chat-pane-bg);
    }

    .mx-msg [data-msg-compose-form] {
        display: flex;
        align-items: flex-end;
        gap: .55rem;
        direction: rtl;
    }

    .mx-msg [data-msg-compose-form] textarea,
    .mx-msg .form-select {
        border: 1px solid var(--mx-border);
        background: var(--mx-surface);
        color: var(--mx-text);
        box-shadow: none !important;
    }

    .mx-msg [data-msg-compose-form] textarea {
        min-height: 52px;
        resize: none;
        border-radius: 18px;
        text-align: right;
        direction: rtl;
    }

    .mx-msg [data-msg-compose-form] textarea:focus,
    .mx-msg .form-select:focus {
        border-color: var(--mx-primary);
        background: var(--mx-surface);
        color: var(--mx-text);
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .12) !important;
    }

    .mx-msg .form-select option {
        background: var(--mx-surface);
        color: var(--mx-text);
    }

    .mx-msg [data-msg-send-btn] {
        min-width: 92px;
        height: 52px;
        border-radius: 16px !important;
        font-weight: 800;
        white-space: nowrap;
    }

    .mx-msg [data-msg-send-btn].is-loading {
        opacity: .75;
        pointer-events: none;
    }

    .mx-msg [data-msg-empty] {
        height: 100%;
        min-height: 320px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--mx-text-muted);
        padding: 1.5rem;
        background: var(--mx-chat-pane-bg);
    }

    .mx-msg [data-msg-empty-box] {
        max-width: 360px;
    }

    .mx-msg [data-msg-empty-box] i {
        font-size: 2.2rem;
        display: block;
        margin-bottom: .8rem;
        color: var(--mx-text);
    }

    .text-muted {
        color: var(--mx-text-muted) !important;
    }

    @media (max-width: 991.98px) {
        #headerAnnouncementsModal .modal-dialog,
        #headerNotificationsModal .modal-dialog,
        #headerMessagesModal .modal-dialog {
            max-width: 100%;
            margin: 0;
        }

        .mx-modal .modal-content {
            min-height: 100vh;
            border-radius: 0 !important;
        }

        .mx-msg .modal-body {
            padding: 0;
        }

        .mx-msg [data-msg-layout] {
            grid-template-columns: 1fr;
            grid-template-areas: "sidebar";
            gap: 0;
            min-height: calc(100vh - 126px);
        }

        .mx-msg [data-msg-sidebar],
        .mx-msg [data-msg-pane] {
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .mx-msg [data-msg-pane] {
            display: none;
        }

        .mx-msg [data-msg-layout].show-chat {
            grid-template-areas: "pane";
        }

        .mx-msg [data-msg-layout].show-chat [data-msg-sidebar] {
            display: none;
        }

        .mx-msg [data-msg-layout].show-chat [data-msg-pane] {
            display: block;
        }

        .mx-msg [data-msg-list] {
            max-height: calc(100vh - 210px);
        }

        .mx-msg [data-msg-item] {
            border-radius: 0;
            border-bottom: 1px solid var(--mx-border);
        }

        .mx-msg [data-msg-chat-scroll] {
            height: calc(100vh - 255px);
            padding: .85rem;
        }

        .mx-msg [data-msg-bubble-wrap] {
            max-width: 88%;
        }
    }

    @media (max-width: 639.98px) {
        .mx-header__row {
            min-height: 66px;
        }

        .mx-header__logo {
            height: 40px;
        }

        .mx-header__logo img {
            max-height: 34px;
        }

        .mx-icon-btn {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 14px !important;
        }

        .mx-icon-btn i {
            font-size: 1rem;
        }

        .mx-badge {
            min-width: 20px;
            height: 20px;
            font-size: 10px;
        }

        .mx-header__dashboard-btn {
            height: 39px;
            padding-inline: 10px !important;
            font-size: 13px;
        }
    }
</style>

<nav class="mx-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mx-header__row">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="shrink-0">
                    <div class="mx-header__logo">
                        <img src="{{ asset('logo.png') }}" alt="Logo">
                    </div>
                </a>

                <a href="{{ route('dashboard') }}"
                   class="btn btn-primary d-none d-sm-inline-flex align-items-center gap-2 mx-header__dashboard-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>داشبورد</span>
                </a>
            </div>

            <div class="d-none d-sm-flex mx-header__actions">
                <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerAnnouncementsModal" aria-label="اطلاعیه‌ها" title="اطلاعیه‌ها">
                    <i class="bi bi-megaphone"></i>
                    @if($announcementsCount > 0)
                        <span class="mx-badge mx-badge--primary">{{ $announcementsCount }}</span>
                    @endif
                </button>

                <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerNotificationsModal" aria-label="اعلان‌ها" title="اعلان‌ها">
                    <i class="bi bi-bell"></i>
                    @if($notificationsCount > 0)
                        <span class="mx-badge mx-badge--danger">{{ $notificationsCount }}</span>
                    @endif
                </button>

                <button type="button" class="btn mx-icon-btn" data-theme-toggle aria-label="تغییر تم" title="تغییر تم">
                    <i class="bi bi-moon-stars-fill" data-theme-icon></i>
                </button>

                @auth
                    <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerMessagesModal" aria-label="پیام‌ها" title="پیام‌ها">
                        <i class="bi bi-chat-dots"></i>
                        @if($messagesCount > 0)
                            <span class="mx-badge mx-badge--success" data-header-message-badge>{{ $messagesCount }}</span>
                        @endif
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn mx-icon-btn" aria-label="خروج" title="خروج">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                @endauth
            </div>

            <div class="d-flex d-sm-none mx-header__actions--mobile">
                <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerAnnouncementsModal" aria-label="اطلاعیه‌ها" title="اطلاعیه‌ها">
                    <i class="bi bi-megaphone"></i>
                    @if($announcementsCount > 0)
                        <span class="mx-badge mx-badge--primary">{{ $announcementsCount }}</span>
                    @endif
                </button>

                <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerNotificationsModal" aria-label="اعلان‌ها" title="اعلان‌ها">
                    <i class="bi bi-bell"></i>
                    @if($notificationsCount > 0)
                        <span class="mx-badge mx-badge--danger">{{ $notificationsCount }}</span>
                    @endif
                </button>

                <button type="button" class="btn mx-icon-btn" data-theme-toggle aria-label="تغییر تم" title="تغییر تم">
                    <i class="bi bi-moon-stars-fill" data-theme-icon></i>
                </button>

                @auth
                    <button type="button" class="btn mx-icon-btn" data-bs-toggle="modal" data-bs-target="#headerMessagesModal" aria-label="پیام‌ها" title="پیام‌ها">
                        <i class="bi bi-chat-dots"></i>
                        @if($messagesCount > 0)
                            <span class="mx-badge mx-badge--success" data-header-message-badge>{{ $messagesCount }}</span>
                        @endif
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn mx-icon-btn" aria-label="خروج" title="خروج">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>

<div class="modal fade mx-modal" id="headerAnnouncementsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mx-head">
                    <div class="mx-head__side">
                        <div class="text-end">
                            <h5 class="modal-title fw-bold mb-1">اطلاعیه‌ها</h5>
                            <div class="small text-muted">آخرین اطلاعیه‌های سیستم</div>
                        </div>

                        <div class="mx-head__icon">
                            <i class="bi bi-megaphone"></i>
                        </div>

                        <div class="mx-head__count" data-ann-count>{{ $announcementsCount }} مورد</div>
                    </div>

                    <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body">
                @if($announcementsCount > 0)
                    <div class="mx-panel-list">
                        @foreach($headerAnnouncements as $announcement)
                            <div class="mx-card">
                                <h6 class="mx-card__title">{{ $announcement->title }}</h6>
                                <div class="mx-card__text">{{ $announcement->message }}</div>

                                <div class="mx-card__meta">
                                    <span class="mx-chip">
                                        <i class="bi bi-person"></i>
                                        <span>{{ $announcement->creator?->name ?? '---' }}</span>
                                    </span>

                                    <span class="mx-chip">
                                        <i class="bi bi-clock-history"></i>
                                        <span>{{ \Hekmatinasser\Verta\Verta::instance($announcement->created_at)->format('Y/m/d H:i') }}</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mx-empty">
                        <div class="mx-empty__icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <div class="mx-empty__title">اطلاعیه‌ای وجود ندارد</div>
                        <div class="mx-empty__text">در حال حاضر موردی برای نمایش ثبت نشده است.</div>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <a class="btn btn-outline-primary" href="{{ route('announcements.index') }}">مشاهده همه اطلاعیه‌ها</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mx-modal" id="headerNotificationsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mx-head">
                    <div class="mx-head__side">
                        <div class="text-end">
                            <h5 class="modal-title fw-bold mb-1">اعلان‌ها</h5>
                            <div class="small text-muted">اعلان‌های اخیر شما</div>
                        </div>

                        <div class="mx-head__icon">
                            <i class="bi bi-bell"></i>
                        </div>

                        <div class="mx-head__count" data-notif-count>{{ $notificationsCount }} دیده‌نشده</div>
                    </div>

                    <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body">
                @if($headerNotifications->count() > 0)
                    <div class="mx-panel-list">
                        @foreach($headerNotifications as $notification)
                            <div class="mx-card">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <h6 class="mx-card__title mb-0">{{ $notification->title }}</h6>
                                    @if(!$notification->seen)
                                        <span class="mx-new-badge" data-notif-new>جدید</span>
                                    @endif
                                </div>

                                @if(!empty($notification->message))
                                    <div class="mx-card__text">{{ $notification->message }}</div>
                                @endif

                                @php
                                    $notifLeave = $notification->leave;
                                    $canSubstituteAction = $notifLeave && $notifLeave->status === 'pending' && (int) $notifLeave->substitute_user_id === (int) auth()->id();
                                    $canManagerAction = $notifLeave && $notifLeave->status === 'manager_approved' && auth()->user()->hasRole('Manager') && (int) $notifLeave->manager_id === (int) auth()->id();
                                    $canInternalAction = $notifLeave && $notifLeave->status === 'internal_approved' && (auth()->user()->hasRole('Admin') || auth()->user()->hasAnyRole(['internalManager', 'InternalManager']));
                                @endphp

                                @if($canSubstituteAction || $canManagerAction || $canInternalAction)
                                    <div class="d-flex gap-2 mt-3">
                                        <form action="{{ route('leaves.approve', $notifLeave->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-success btn-sm">تایید</button>
                                        </form>

                                        <form action="{{ route('leaves.reject', $notifLeave->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-danger btn-sm">رد</button>
                                        </form>
                                    </div>
                                @endif

                                <div class="mx-card__meta">
                                    <span class="mx-chip">
                                        <i class="bi bi-clock-history"></i>
                                        <span>{{ \Hekmatinasser\Verta\Verta::instance($notification->created_at)->format('Y/m/d H:i') }}</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mx-empty">
                        <div class="mx-empty__icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <div class="mx-empty__title">اعلانی وجود ندارد</div>
                        <div class="mx-empty__text">فعلاً اعلان جدیدی برای شما ثبت نشده است.</div>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mx-modal mx-msg" id="headerMessagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mx-head">
                    <div class="mx-head__side">
                        <div class="text-end">
                            <h5 class="modal-title fw-bold mb-1">پیام‌ها</h5>
                            <div class="small text-muted">نمای گفتگو</div>
                        </div>
                    </div>

                    <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body">
                <div data-msg-layout>
                    <aside data-msg-sidebar>
                        <div data-msg-sidebar-top>
                            <div data-msg-sidebar-title">شروع گفتگوی جدید</div>
                            <select class="form-select form-select-sm" data-msg-new-user>
                                <option value="">یک کاربر را انتخاب کنید...</option>
                                @foreach(($headerMessageUsers ?? collect()) as $msgUser)
                                    <option value="{{ $msgUser->id }}">{{ $msgUser->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div data-msg-list>
                            @forelse(($headerMessages ?? collect()) as $messageItem)
                                @php
                                    $otherUser = $messageItem->sender_id === auth()->id() ? $messageItem->receiver : $messageItem->sender;
                                @endphp

                                @continue(!$otherUser)

                                @php
                                    $isUnread = $messageItem->receiver_id === auth()->id() && is_null($messageItem->seen_at);
                                    $avatarText = trim(mb_substr($otherUser->name, 0, 1));
                                @endphp

                                <button
                                    type="button"
                                    data-msg-item
                                    data-chat-target="{{ $otherUser->id }}"
                                    data-user-name="{{ $otherUser->name }}"
                                >
                                    <span data-msg-avatar>{{ $avatarText }}</span>

                                    <span data-msg-item-body>
                                        <span data-msg-item-top>
                                            <span data-msg-name>{{ $otherUser->name }}</span>
                                        </span>
                                        <span data-msg-preview>{{ \Illuminate\Support\Str::limit($messageItem->body ?? 'پیوست ارسال شده', 36) }}</span>
                                    </span>

                                    <span data-msg-item-meta>
                                        <span data-msg-time>{{ \Hekmatinasser\Verta\Verta::instance($messageItem->created_at)->format('H:i') }}</span>
                                        @if($isUnread)
                                            <span data-msg-unread-dot></span>
                                        @endif
                                    </span>
                                </button>
                            @empty
                                <div class="small text-muted text-center py-4" data-msg-list-empty>
                                    هنوز گفتگویی ندارید.
                                </div>
                            @endforelse
                        </div>
                    </aside>

                    <section data-msg-pane>
                        @if(($headerMessageConversations ?? collect())->count() > 0)
                            @foreach($headerMessageConversations as $threadUserId => $threadMessages)
                                @php
                                    $threadFirst = $threadMessages->first();
                                    $threadUser = $threadFirst
                                        ? ($threadFirst->sender_id === auth()->id() ? $threadFirst->receiver : $threadFirst->sender)
                                        : null;
                                @endphp

                                @if($threadUser)
                                    @php
                                        $threadAvatar = trim(mb_substr($threadUser->name, 0, 1));
                                    @endphp

                                    <div
                                        data-msg-thread="{{ $threadUserId }}"
                                        data-user-name="{{ $threadUser->name }}"
                                        data-user-avatar="{{ $threadAvatar }}"
                                        style="{{ $firstThreadUser && (int) $firstThreadUser->id === (int) $threadUserId ? '' : 'display:none;' }}"
                                    >
                                        <div data-msg-chat-head>
                                            <div data-msg-chat-user>
                                                <div data-msg-chat-avatar>{{ $threadAvatar }}</div>
                                                <div class="text-end">
                                                    <div data-msg-chat-name>{{ $threadUser->name }}</div>
                                                    <div data-msg-chat-status>گفتگوی مستقیم</div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-link text-decoration-none p-0 d-lg-none" data-msg-back>
                                                    <i class="bi bi-arrow-right fs-4"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div data-msg-chat-scroll>
                                            @foreach($threadMessages as $chatItem)
                                                @php
                                                    $isMine = (int) $chatItem->sender_id === (int) auth()->id();
                                                @endphp

                                                <div data-msg-row class="{{ $isMine ? 'mine' : 'theirs' }}">
                                                    @if(!$isMine)
                                                        <div data-msg-inline-avatar>{{ $threadAvatar }}</div>
                                                    @endif

                                                    <div data-msg-bubble-wrap>
                                                        @if(!$isMine)
                                                            <div data-msg-bubble-sender>{{ $threadUser->name }}</div>
                                                        @endif

                                                        <div data-msg-bubble class="{{ $isMine ? 'mine' : 'theirs' }}">
                                                            <div>{{ $chatItem->body ?: 'پیوست ارسال شد.' }}</div>
                                                            <span data-msg-bubble-time>{{ \Hekmatinasser\Verta\Verta::instance($chatItem->created_at)->format('Y/m/d H:i') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div data-msg-compose>
                                            <form
                                                action="{{ route('messages.reply', $threadUserId) }}"
                                                method="POST"
                                                data-msg-compose-form
                                                data-thread-user-id="{{ $threadUserId }}"
                                            >
                                                @csrf
                                                <textarea
                                                    class="form-control form-control-sm"
                                                    name="body"
                                                    rows="2"
                                                    required
                                                    placeholder="پیام خود را بنویسید..."
                                                ></textarea>

                                                <button class="btn btn-primary btn-sm px-3" data-msg-send-btn type="submit">
                                                    <span data-send-text>ارسال</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        <div data-msg-empty data-msg-placeholder style="{{ ($headerMessageConversations ?? collect())->count() > 0 ? 'display:none;' : '' }}">
                            <div data-msg-empty-box>
                                <i class="bi bi-chat-dots"></i>
                                <div class="fw-bold mb-2">هنوز گفتگویی انتخاب نشده است</div>
                                <div>از ستون سمت راست یک گفتگو را انتخاب کنید یا گفتگوی جدید بسازید.</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="modal-footer">
                <small class="text-muted">برای پاسخ سریع، گفتگو را همین‌جا ادامه دهید.</small>
            </div>
        </div>
    </div>
</div>


<link rel="stylesheet" href="{{ asset('lib/bootstrap-icons.min.css') }}">

<script>
    (function () {
        const savedTheme = localStorage.getItem('theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const theme = savedTheme || systemTheme;
        document.documentElement.setAttribute('data-bs-theme', theme);
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggles = document.querySelectorAll('[data-theme-toggle]');

        function nextTheme(theme) {
            return theme === 'dark' ? 'light' : 'dark';
        }

        function syncThemeButtons(theme) {
            const next = nextTheme(theme);

            toggles.forEach((toggle) => {
                const icon = toggle.querySelector('[data-theme-icon]');
                if (icon) {
                    icon.className = next === 'dark' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
                }

                toggle.setAttribute('title', next === 'dark' ? 'تغییر به تم تیره' : 'تغییر به تم روشن');
                toggle.setAttribute('aria-label', next === 'dark' ? 'تغییر به تم تیره' : 'تغییر به تم روشن');
            });
        }

        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        syncThemeButtons(currentTheme);

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', function () {
                const active = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const selected = nextTheme(active);

                document.documentElement.setAttribute('data-bs-theme', selected);
                localStorage.setItem('theme', selected);
                syncThemeButtons(selected);
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const notificationModal = document.getElementById('headerNotificationsModal');
        const announcementModal = document.getElementById('headerAnnouncementsModal');

        const NOTIFICATION_BADGE_DELAY = 1800;
        const ANNOUNCEMENT_BADGE_DELAY = 1800;

        function hideBadges(selector) {
            document.querySelectorAll(selector).forEach((badge) => {
                badge.textContent = '0';
                badge.style.display = 'none';
            });
        }

        const latestAnnouncementId = {{ (int) optional($headerAnnouncements->first())->id }};
        const seenAnnouncementId = parseInt(localStorage.getItem('seen_header_announcement_id') || '0', 10);

        if (latestAnnouncementId > 0 && seenAnnouncementId >= latestAnnouncementId) {
            hideBadges('.mx-badge--primary');
        }

        if (announcementModal) {
            announcementModal.addEventListener('shown.bs.modal', function () {
                if (latestAnnouncementId <= 0) return;

                localStorage.setItem('seen_header_announcement_id', String(latestAnnouncementId));

                setTimeout(() => {
                    const annCountLabel = announcementModal.querySelector('[data-ann-count]');
                    if (annCountLabel) {
                        annCountLabel.textContent = '0 مورد جدید';
                    }
                    hideBadges('.mx-badge--primary');
                }, ANNOUNCEMENT_BADGE_DELAY);
            });
        }

        if (!notificationModal) return;

        let markedSeen = false;

        notificationModal.addEventListener('shown.bs.modal', async function () {
            if (markedSeen) return;

            const unseenCount = {{ (int)($headerNotificationsUnseenCount ?? 0) }};
            if (unseenCount <= 0) return;

            try {
                const response = await fetch("{{ route('notifications.markAllSeen') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });

                if (!response.ok) return;

                markedSeen = true;

                setTimeout(() => {
                    const notifCountLabel = notificationModal.querySelector('[data-notif-count]');
                    if (notifCountLabel) {
                        notifCountLabel.textContent = '0 دیده‌نشده';
                    }

                    notificationModal.querySelectorAll('[data-notif-new]').forEach((el) => el.remove());
                    hideBadges('.mx-badge--danger');
                }, NOTIFICATION_BADGE_DELAY);
            } catch (e) {
                //
            }
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('headerMessagesModal');
    if (!modal) return;

    const layout = modal.querySelector('[data-msg-layout]');
    const list = modal.querySelector('[data-msg-list]');
    const pane = modal.querySelector('[data-msg-pane]');
    const threadButtons = () => modal.querySelectorAll('[data-msg-item][data-chat-target]');
    const threads = () => modal.querySelectorAll('[data-msg-thread]');
    const placeholder = modal.querySelector('[data-msg-placeholder]');
    const newUserSelect = modal.querySelector('[data-msg-new-user]');
    const baseReplyPath = "{{ url('/messages') }}";
    const csrfToken = "{{ csrf_token() }}";
    const markSeenBasePath = "{{ url('/messages') }}";

    function isMobile() {
        return window.innerWidth < 992;
    }

    function scrollThreadToBottom(thread) {
        const scroll = thread ? thread.querySelector('[data-msg-chat-scroll]') : null;
        if (scroll) {
            scroll.scrollTop = scroll.scrollHeight;
        }
    }

    function showListOnMobile() {
        if (isMobile() && layout) layout.classList.remove('show-chat');
    }

    function showChatOnMobile() {
        if (isMobile() && layout) layout.classList.add('show-chat');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function shortPreview(text, max = 36) {
        if (!text) return '';
        return text.length > max ? text.substring(0, max) + '...' : text;
    }

    function nowTime() {
        const d = new Date();
        return d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    }

    function nowDateTime() {
        return new Date().toLocaleString('fa-IR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function bindBackButtons(scope = modal) {
        scope.querySelectorAll('[data-msg-back]').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';

            btn.addEventListener('click', function () {
                showListOnMobile();
            });
        });
    }

    function updateMessageBadges(count) {
        document.querySelectorAll('[data-header-message-badge]').forEach((badge) => {
            if (count <= 0) {
                badge.style.display = 'none';
                badge.textContent = '0';
                return;
            }

            badge.style.display = '';
            badge.textContent = String(count);
        });
    }

    function clearUnreadDot(userId) {
        const btn = modal.querySelector(`[data-msg-item][data-chat-target="${userId}"]`);
        if (!btn) return;
        btn.querySelector('[data-msg-unread-dot]')?.remove();
    }

    async function markThreadSeen(userId) {
        try {
            const response = await fetch(`${markSeenBasePath}/${userId}/seen`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            updateMessageBadges(Number(data?.remaining_unseen_count ?? 0));
            clearUnreadDot(userId);
        } catch (e) {
            //
        }
    }

    function activateThread(userId) {
        let found = false;

        threadButtons().forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.chatTarget === String(userId));
        });

        threads().forEach((thread) => {
            const match = thread.getAttribute('data-msg-thread') === String(userId);
            thread.style.display = match ? '' : 'none';

            if (match) {
                found = true;
                scrollThreadToBottom(thread);
            }
        });

        if (placeholder) {
            placeholder.style.display = found ? 'none' : '';
        }

        if (found) {
            showChatOnMobile();
            markThreadSeen(userId);
        }
    }

    function createThreadButton(userId, userName, previewText, timeText) {
        const btn = document.createElement('button');
        const avatar = (userName || '?').trim().charAt(0);

        btn.type = 'button';
        btn.setAttribute('data-msg-item', '');
        btn.setAttribute('data-chat-target', userId);
        btn.setAttribute('data-user-name', userName);

        btn.innerHTML = `
            <span data-msg-avatar>${escapeHtml(avatar)}</span>
            <span data-msg-item-body>
                <span data-msg-item-top>
                    <span data-msg-name>${escapeHtml(userName)}</span>
                </span>
                <span data-msg-preview>${escapeHtml(shortPreview(previewText || ''))}</span>
            </span>
            <span data-msg-item-meta>
                <span data-msg-time>${escapeHtml(timeText || nowTime())}</span>
            </span>
        `;

        btn.addEventListener('click', function () {
            activateThread(userId);
        });

        return btn;
    }

    function moveThreadButtonToTop(userId, bodyText, timeText) {
        const btn = modal.querySelector(`[data-msg-item][data-chat-target="${userId}"]`);
        if (!btn || !list) return;

        const preview = btn.querySelector('[data-msg-preview]');
        const time = btn.querySelector('[data-msg-time]');
        const unreadDot = btn.querySelector('[data-msg-unread-dot]');

        if (preview) preview.textContent = shortPreview(bodyText || '');
        if (time) time.textContent = timeText || nowTime();
        if (unreadDot) unreadDot.remove();

        list.prepend(btn);
    }

    function createThreadElement(userId, userName) {
        const avatar = (userName || '?').trim().charAt(0);

        const wrapper = document.createElement('div');
        wrapper.setAttribute('data-msg-thread', userId);
        wrapper.setAttribute('data-user-name', userName);
        wrapper.setAttribute('data-user-avatar', avatar);

        wrapper.innerHTML = `
            <div data-msg-chat-head>
                <div data-msg-chat-user>
                    <div data-msg-chat-avatar>${escapeHtml(avatar)}</div>
                    <div class="text-end">
                        <div data-msg-chat-name>${escapeHtml(userName)}</div>
                        <div data-msg-chat-status>گفتگوی مستقیم</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link text-decoration-none p-0 d-lg-none" data-msg-back>
                        <i class="bi bi-arrow-right fs-4"></i>
                    </button>
                </div>
            </div>

            <div data-msg-chat-scroll>
                <div class="text-center text-muted py-5">اولین پیام را ارسال کنید.</div>
            </div>

            <div data-msg-compose>
                <form
                    method="POST"
                    action="${baseReplyPath}/${userId}/reply"
                    data-msg-compose-form
                    data-thread-user-id="${userId}"
                >
                    <textarea
                        class="form-control form-control-sm"
                        name="body"
                        rows="2"
                        required
                        placeholder="پیام خود را بنویسید..."
                    ></textarea>

                    <button class="btn btn-primary btn-sm px-3" data-msg-send-btn type="submit">
                        <span data-send-text>ارسال</span>
                    </button>
                </form>
            </div>
        `;

        pane.insertBefore(wrapper, placeholder);
        bindBackButtons(wrapper);
        bindAjaxForms(wrapper);

        return wrapper;
    }

    function ensureThreadExists(userId, userName) {
        let thread = modal.querySelector(`[data-msg-thread="${userId}"]`);
        if (thread) return thread;

        thread = createThreadElement(userId, userName);

        if (placeholder) {
            placeholder.style.display = 'none';
        }

        return thread;
    }

    function appendMessageToThread(userId, body, isMine, sentAt, userName, avatarText) {
        const thread = ensureThreadExists(userId, userName);
        const scroll = thread.querySelector('[data-msg-chat-scroll]');
        const emptyText = scroll.querySelector('.text-center.text-muted.py-5');
        if (emptyText) emptyText.remove();

        const row = document.createElement('div');
        row.setAttribute('data-msg-row', '');
        row.className = isMine ? 'mine' : 'theirs';

        row.innerHTML = `
            ${!isMine ? `<div data-msg-inline-avatar>${escapeHtml(avatarText || (userName || '?').trim().charAt(0))}</div>` : ''}
            <div data-msg-bubble-wrap>
                ${!isMine ? `<div data-msg-bubble-sender>${escapeHtml(userName || '')}</div>` : ''}
                <div data-msg-bubble class="${isMine ? 'mine' : 'theirs'}">
                    <div>${escapeHtml(body)}</div>
                    <span data-msg-bubble-time>${escapeHtml(sentAt || nowDateTime())}</span>
                </div>
            </div>
        `;

        scroll.appendChild(row);
        scrollThreadToBottom(thread);
    }

    function bindThreadButtons() {
        threadButtons().forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';

            btn.addEventListener('click', function () {
                activateThread(this.dataset.chatTarget);
            });
        });
    }

    async function sendMessageAjax(form) {
        const textarea = form.querySelector('textarea[name="body"]');
        const sendBtn = form.querySelector('[data-msg-send-btn]');
        const sendText = form.querySelector('[data-send-text]');
        const body = (textarea?.value || '').trim();
        const userId = form.dataset.threadUserId;

        if (!body || !userId) return;

        const thread = modal.querySelector(`[data-msg-thread="${userId}"]`);
        const userName = thread?.dataset.userName || modal.querySelector(`[data-msg-item][data-chat-target="${userId}"]`)?.dataset.userName || 'کاربر';
        const avatarText = thread?.dataset.userAvatar || (userName || '?').trim().charAt(0);

        sendBtn?.classList.add('is-loading');
        sendBtn?.setAttribute('disabled', 'disabled');
        if (sendText) sendText.textContent = 'در حال ارسال...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            });

            const data = await response.json();

            if (!response.ok) {
                throw data;
            }

            appendMessageToThread(
                userId,
                data.body ?? body,
                true,
                data.created_at_text ?? nowDateTime(),
                userName,
                avatarText
            );

            moveThreadButtonToTop(
                userId,
                data.body ?? body,
                data.time_text ?? nowTime()
            );

            textarea.value = '';
            textarea.style.height = '';
            activateThread(userId);
        } catch (error) {
            let message = 'ارسال پیام با خطا مواجه شد.';

            if (error?.errors?.body?.[0]) {
                message = error.errors.body[0];
            } else if (error?.message) {
                message = error.message;
            }

            alert(message);
        } finally {
            sendBtn?.classList.remove('is-loading');
            sendBtn?.removeAttribute('disabled');
            if (sendText) sendText.textContent = 'ارسال';
        }
    }

    function bindAjaxForms(scope = modal) {
        scope.querySelectorAll('[data-msg-compose-form]').forEach((form) => {
            if (form.dataset.ajaxBound === '1') return;
            form.dataset.ajaxBound = '1';

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                sendMessageAjax(this);
            });
        });
    }

    if (newUserSelect) {
        newUserSelect.addEventListener('change', function () {
            const userId = this.value;
            const userName = this.options[this.selectedIndex]?.text || 'کاربر';

            if (!userId) return;

            let btn = modal.querySelector(`[data-msg-item][data-chat-target="${userId}"]`);

            if (!btn) {
                const emptyList = modal.querySelector('[data-msg-list-empty]');
                if (emptyList) emptyList.remove();

                btn = createThreadButton(userId, userName, '', nowTime());
                list.prepend(btn);
            }

            bindThreadButtons();
            ensureThreadExists(userId, userName);
            activateThread(userId);
        });
    }

    bindThreadButtons();
    bindBackButtons();
    bindAjaxForms();

    modal.addEventListener('shown.bs.modal', function () {
        if (isMobile()) {
            showListOnMobile();
            return;
        }

        const active = modal.querySelector('[data-msg-item].active');
        if (active) {
            activateThread(active.dataset.chatTarget);
            return;
        }

        const firstButton = modal.querySelector('[data-msg-item][data-chat-target]');
        if (firstButton) {
            activateThread(firstButton.dataset.chatTarget);
        }
    });

    window.addEventListener('resize', function () {
        if (!layout) return;

        if (!isMobile()) {
            layout.classList.remove('show-chat');

            const active = modal.querySelector('[data-msg-item].active');
            if (active) activateThread(active.dataset.chatTarget);
        } else {
            const active = modal.querySelector('[data-msg-item].active');
            if (!active) showListOnMobile();
        }
    });
});
</script>