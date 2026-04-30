<template>
    <div>
        <!-- Page header -->
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa tài khoản" : "Tạo tài khoản mới" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <router-link :to="{ name: 'admin.accounts' }"
                        >Tài khoản</router-link
                    >
                    <span>/</span>
                    <span class="current">{{
                        isEdit ? form.username || "..." : "Tạo mới"
                    }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.accounts' }"
                class="btn btn-outline"
            >
                <span class="mi" style="font-size: 16px">arrow_back</span> Quay
                lại
            </router-link>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>
        <div v-if="loadingAccount" class="muted-line">
            <span class="admin-loading-spinner"></span>
        </div>

        <form @submit.prevent="save">
            <div class="form-layout">
                <!-- LEFT: Main -->
                <div class="form-main">
                    <div v-if="isEdit" class="card detail-hub">
                        <div class="card-header">
                            <h3>Trung tâm tài khoản</h3>
                        </div>

                        <div v-if="activityError" class="alert alert-error">
                            {{ activityError }}
                        </div>
                        <div v-else-if="activityLoading" class="muted-line">
                            <span class="admin-loading-spinner"></span>
                        </div>
                        <template v-else-if="activity">
                            <div class="hub-summary-grid">
                                <div class="hub-summary-card">
                                    <span class="hub-summary-label"
                                        >Tạo lúc</span
                                    >
                                    <strong>{{
                                        formatDateTime(
                                            activity.overview.create_time,
                                        )
                                    }}</strong>
                                    <small
                                        >IP:
                                        {{
                                            activity.overview.ip_address || "—"
                                        }}</small
                                    >
                                </div>
                                <div class="hub-summary-card">
                                    <span class="hub-summary-label"
                                        >Đăng nhập cuối</span
                                    >
                                    <strong>{{
                                        formatDateTime(
                                            activity.overview.last_time_login,
                                        )
                                    }}</strong>
                                    <small
                                        >Logout:
                                        {{
                                            formatDateTime(
                                                activity.overview
                                                    .last_time_logout,
                                            )
                                        }}</small
                                    >
                                </div>
                                <div class="hub-summary-card">
                                    <span class="hub-summary-label"
                                        >Tổng nạp</span
                                    >
                                    <strong
                                        >{{
                                            fmt(
                                                activity.topup_summary
                                                    .total_amount,
                                            )
                                        }}đ</strong
                                    >
                                    <small
                                        >{{
                                            fmt(
                                                activity.topup_summary
                                                    .total_count,
                                            )
                                        }}
                                        giao dịch</small
                                    >
                                </div>
                                <div class="hub-summary-card">
                                    <span class="hub-summary-label"
                                        >Bảo mật</span
                                    >
                                    <strong>{{
                                        activity.overview.mkc2
                                            ? "Đã đặt MKC2"
                                            : "Chưa có MKC2"
                                    }}</strong>
                                    <small
                                        >Gmail:
                                        {{
                                            activity.overview.gmail ||
                                            activity.overview.email ||
                                            "—"
                                        }}</small
                                    >
                                </div>
                            </div>

                            <div class="detail-tabs">
                                <button
                                    type="button"
                                    class="detail-tab-btn"
                                    :class="{
                                        active: activeDetailTab === 'overview',
                                    }"
                                    @click="activeDetailTab = 'overview'"
                                >
                                    Tổng quan
                                </button>
                                <button
                                    type="button"
                                    class="detail-tab-btn"
                                    :class="{
                                        active: activeDetailTab === 'topup',
                                    }"
                                    @click="activeDetailTab = 'topup'"
                                >
                                    Lịch sử nạp
                                </button>
                                <button
                                    type="button"
                                    class="detail-tab-btn"
                                    :class="{
                                        active: activeDetailTab === 'logs',
                                    }"
                                    @click="activeDetailTab = 'logs'"
                                >
                                    Nhật ký admin
                                </button>
                            </div>

                            <div
                                v-if="activeDetailTab === 'overview'"
                                class="detail-panel"
                            >
                                <div class="detail-grid">
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Email</span
                                        >
                                        <span class="detail-field-value">{{
                                            activity.overview.email || "—"
                                        }}</span>
                                    </div>
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Gmail</span
                                        >
                                        <span class="detail-field-value">{{
                                            activity.overview.gmail || "—"
                                        }}</span>
                                    </div>
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Lượt quay</span
                                        >
                                        <span class="detail-field-value">{{
                                            fmt(activity.overview.luotquay)
                                        }}</span>
                                    </div>
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Vàng tài khoản</span
                                        >
                                        <span class="detail-field-value">{{
                                            fmt(activity.overview.vang)
                                        }}</span>
                                    </div>
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Event point</span
                                        >
                                        <span class="detail-field-value">{{
                                            fmt(activity.overview.event_point)
                                        }}</span>
                                    </div>
                                    <div class="detail-field">
                                        <span class="detail-field-label"
                                            >Cập nhật gần nhất</span
                                        >
                                        <span class="detail-field-value">{{
                                            formatDateTime(
                                                activity.overview.update_time,
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="activeDetailTab === 'topup'"
                                class="detail-panel detail-stack"
                            >
                                <div class="mini-summary-row">
                                    <div class="mini-summary-box">
                                        <span>Gần nhất</span>
                                        <strong>{{
                                            formatDateTime(
                                                activity.topup_summary
                                                    .last_topup_at,
                                            )
                                        }}</strong>
                                    </div>
                                    <div class="mini-summary-box">
                                        <span>Tổng giao dịch</span>
                                        <strong>{{
                                            fmt(
                                                activity.topup_summary
                                                    .total_count,
                                            )
                                        }}</strong>
                                    </div>
                                    <div class="mini-summary-box">
                                        <span>Tổng nạp</span>
                                        <strong
                                            >{{
                                                fmt(
                                                    activity.topup_summary
                                                        .total_amount,
                                                )
                                            }}đ</strong
                                        >
                                    </div>
                                </div>

                                <div class="detail-subsection">
                                    <div class="detail-subtitle">
                                        Topup transactions
                                    </div>
                                    <div
                                        v-if="!activity.topups.length"
                                        class="muted-line"
                                    >
                                        Chưa có giao dịch topup.
                                    </div>
                                    <div v-else class="activity-list">
                                        <div
                                            v-for="row in activity.topups"
                                            :key="'topup-' + row.id"
                                            class="activity-row"
                                        >
                                            <div>
                                                <div class="activity-title">
                                                    {{ fmt(row.amount) }}đ -
                                                    {{
                                                        row.source ||
                                                        row.currency ||
                                                        "Khác"
                                                    }}
                                                </div>
                                                <div class="activity-sub">
                                                    {{ row.trans_id
                                                    }}<span v-if="row.note">
                                                        | {{ row.note }}</span
                                                    >
                                                </div>
                                            </div>
                                            <div class="activity-time">
                                                {{
                                                    formatDateTime(
                                                        row.created_at,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-subsection">
                                    <div class="detail-subtitle">
                                        Log nạp thẻ
                                    </div>
                                    <div
                                        v-if="!activity.card_logs.length"
                                        class="muted-line"
                                    >
                                        Chưa có log thẻ.
                                    </div>
                                    <div v-else class="activity-list">
                                        <div
                                            v-for="row in activity.card_logs"
                                            :key="'card-' + row.id"
                                            class="activity-row"
                                        >
                                            <div>
                                                <div class="activity-title">
                                                    {{ row.type }} -
                                                    {{ fmt(row.amount) }}đ
                                                    <span
                                                        class="badge"
                                                        :class="
                                                            row.status
                                                                ? 'badge-success'
                                                                : 'badge-warning'
                                                        "
                                                    >
                                                        {{
                                                            row.status
                                                                ? "Thành công"
                                                                : "Chờ xử lý"
                                                        }}
                                                    </span>
                                                </div>
                                                <div class="activity-sub">
                                                    {{ row.trans_id }} | Seri:
                                                    {{ row.seri }} | Pin:
                                                    {{ row.pin }}
                                                </div>
                                            </div>
                                            <div class="activity-time">
                                                {{
                                                    formatDateTime(
                                                        row.created_at,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="activeDetailTab === 'logs'"
                                class="detail-panel"
                            >
                                <div
                                    v-if="!activity.admin_logs.length"
                                    class="muted-line"
                                >
                                    Chưa có nhật ký admin cho tài khoản này.
                                </div>
                                <div v-else class="activity-list">
                                    <div
                                        v-for="row in activity.admin_logs"
                                        :key="'log-' + row.id"
                                        class="activity-log-card"
                                    >
                                        <div class="activity-row-head">
                                            <div>
                                                <div class="activity-title">
                                                    {{
                                                        row.summary ||
                                                        "Không có mô tả"
                                                    }}
                                                </div>
                                                <div class="activity-sub">
                                                    {{
                                                        row.admin_username ||
                                                        "admin"
                                                    }}
                                                    -
                                                    {{
                                                        formatDateTime(
                                                            row.created_at,
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-xs"
                                                @click="
                                                    toggleLogExpanded(row.id)
                                                "
                                            >
                                                {{
                                                    isLogExpanded(row.id)
                                                        ? "Thu gọn"
                                                        : "Chi tiết"
                                                }}
                                            </button>
                                        </div>
                                        <div
                                            v-if="isLogExpanded(row.id)"
                                            class="log-state-grid"
                                        >
                                            <div class="log-state-box">
                                                <div class="log-state-title">
                                                    Trước
                                                </div>
                                                <pre>{{
                                                    formatState(
                                                        row.before_state,
                                                    )
                                                }}</pre>
                                            </div>
                                            <div class="log-state-box">
                                                <div class="log-state-title">
                                                    Sau
                                                </div>
                                                <pre>{{
                                                    formatState(row.after_state)
                                                }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Thông tin đăng nhập</h3>
                        </div>
                        <div class="form-group">
                            <label class="form-label"
                                >Username <span class="required">*</span></label
                            >
                            <input
                                v-model="form.username"
                                class="form-input"
                                required
                                :disabled="isEdit"
                                :style="
                                    isEdit
                                        ? 'opacity:.6;cursor:not-allowed'
                                        : ''
                                "
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Password
                                <small
                                    v-if="isEdit"
                                    style="color: var(--ds-text-muted)"
                                    >(để trống nếu không đổi)</small
                                >
                            </label>
                            <input
                                v-model="form.password"
                                class="form-input"
                                type="text"
                                :required="!isEdit"
                                placeholder="Nhập mật khẩu..."
                            />
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h3>Tài nguyên</h3></div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Cash</label>
                                <input
                                    v-model.number="form.cash"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Danap</label>
                                <input
                                    v-model.number="form.danap"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Coin</label>
                                <input
                                    v-model.number="form.coin"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Điểm đã nhận</label>
                                <input
                                    v-model.number="form.diem_da_nhan"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Điểm danh</label>
                                <input
                                    v-model.number="form.diem_danh"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="card" v-if="isEdit">
                        <div class="card-header">
                            <h3>Dữ liệu nhân vật (player)</h3>
                        </div>

                        <div v-if="!playerInfo" class="muted-line">
                            Tài khoản này chưa có nhân vật.
                        </div>

                        <div v-else>
                            <div class="player-summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">ID</span>
                                    <span class="summary-value">{{
                                        playerInfo.id
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Tên</span>
                                    <span class="summary-value">{{
                                        playerInfo.name || "—"
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Giới tính</span>
                                    <span class="summary-value">{{
                                        genderLabel(playerInfo.gender)
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Sức mạnh</span>
                                    <span class="summary-value">{{
                                        fmt(playerInfo.power)
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"
                                        >Nhiệm vụ hiện tại</span
                                    >
                                    <span class="summary-value">{{
                                        taskPreviewText(playerInfo.task)
                                    }}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline btn-sm"
                                @click="togglePlayerFull"
                                :disabled="playerFullLoading"
                            >
                                <span class="mi" style="font-size: 14px">{{
                                    showPlayerFull ? "visibility_off" : "list"
                                }}</span>
                                {{
                                    showPlayerFull
                                        ? "Ẩn dữ liệu player"
                                        : "Hiển thị dữ liệu player"
                                }}
                            </button>
                        </div>

                        <div v-if="showPlayerFull" class="player-full-wrap">
                            <div v-if="playerFullLoading" class="muted-line">
                                <span class="admin-loading-spinner"></span>
                            </div>
                            <div
                                v-else-if="playerFullError"
                                class="alert alert-error"
                            >
                                {{ playerFullError }}
                            </div>
                            <div
                                v-else-if="playerFull === null"
                                class="muted-line"
                            >
                                Tài khoản chưa có nhân vật.
                            </div>
                            <template v-else-if="playerFull">
                                <div class="task-banner">
                                    <strong>Nhiệm vụ chính:</strong>
                                    {{
                                        taskPreviewText(playerFull.summary.task)
                                    }}
                                </div>

                                <div
                                    v-if="parsedSections.length"
                                    class="parsed-wrap"
                                >
                                    <div
                                        class="parsed-box"
                                        v-for="section in parsedSections"
                                        :key="section.key"
                                    >
                                        <div class="parsed-title">
                                            {{ section.title }}
                                        </div>
                                        <div
                                            class="parsed-row"
                                            v-for="item in section.items"
                                            :key="`${section.key}-${item.index}`"
                                        >
                                            <span class="parsed-label"
                                                >[{{ item.index }}]
                                                {{ item.label }}</span
                                            >
                                            <span class="parsed-value">{{
                                                formatParsedValue(item.value)
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="player-rows">
                                    <div
                                        class="player-row"
                                        v-for="field in playerFields"
                                        :key="field.name"
                                    >
                                        <div class="player-row-key">
                                            <div class="player-row-title">
                                                {{ field.label || field.name }}
                                            </div>
                                            <div class="player-row-name">
                                                {{ field.name }}
                                            </div>
                                        </div>
                                        <div class="player-row-value">
                                            <div class="player-row-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline btn-xs"
                                                    @click="
                                                        togglePlayerField(
                                                            field.name,
                                                        )
                                                    "
                                                    :disabled="
                                                        isPlayerSectionLoading(
                                                            field.name,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isPlayerSectionLoading(
                                                            field.name,
                                                        )
                                                            ? "Đang tải..."
                                                            : isFieldExpanded(
                                                                    field.name,
                                                                )
                                                              ? "Ẩn dữ liệu"
                                                              : isPlayerSectionLoaded(
                                                                      field.name,
                                                                  )
                                                                ? "Xem dữ liệu"
                                                                : "Tải dữ liệu"
                                                    }}
                                                </button>
                                                <button
                                                    v-if="
                                                        isPlayerSectionLoaded(
                                                            field.name,
                                                        )
                                                    "
                                                    type="button"
                                                    class="btn btn-outline btn-xs"
                                                    @click="
                                                        loadPlayerSection(
                                                            field.name,
                                                            true,
                                                        )
                                                    "
                                                    :disabled="
                                                        isPlayerSectionLoading(
                                                            field.name,
                                                        )
                                                    "
                                                >
                                                    Tải lại
                                                </button>
                                            </div>
                                            <div
                                                v-if="
                                                    playerSectionErrorText(
                                                        field.name,
                                                    )
                                                "
                                                class="player-row-note player-row-error"
                                            >
                                                {{
                                                    playerSectionErrorText(
                                                        field.name,
                                                    )
                                                }}
                                            </div>
                                            <div
                                                v-else-if="
                                                    !isPlayerSectionLoaded(
                                                        field.name,
                                                    )
                                                "
                                                class="player-row-note"
                                            >
                                                Dữ liệu lớn chỉ tải khi bấm xem.
                                            </div>
                                            <template
                                                v-else-if="
                                                    isFieldExpanded(field.name)
                                                "
                                            >
                                                <div
                                                    v-if="
                                                        sectionParsedItems(
                                                            field.name,
                                                        ).length ||
                                                        isPlayerBadgeList(
                                                            field.name,
                                                        )
                                                    "
                                                    class="player-section-parsed"
                                                >
                                                    <div
                                                        v-if="
                                                            isPlayerBadgeList(
                                                                field.name,
                                                            )
                                                        "
                                                        class="player-badge-wrap"
                                                    >
                                                        <div class="badge-toolbar">
                                                            <input
                                                                v-model="badgeDraft.search"
                                                                class="form-input badge-search-input"
                                                                placeholder="Tìm badge ID hoặc tên..."
                                                                @input="debouncedLoadBadgeTemplates"
                                                            />
                                                            <input
                                                                v-model.number="badgeDraft.days"
                                                                class="form-input badge-days-input"
                                                                type="number"
                                                                min="1"
                                                                placeholder="Ngày"
                                                            />
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline btn-sm"
                                                                @click="savePlayerBadges"
                                                                :disabled="badgeDraft.saving"
                                                            >
                                                                Lưu badges
                                                            </button>
                                                        </div>
                                                        <div
                                                            v-if="
                                                                badgeDraft.error
                                                            "
                                                            class="player-row-note player-row-error"
                                                        >
                                                            {{
                                                                badgeDraft.error
                                                            }}
                                                        </div>
                                                        <div
                                                            v-if="
                                                                badgeTemplates.length
                                                            "
                                                            class="badge-template-list"
                                                        >
                                                            <button
                                                                type="button"
                                                                v-for="badge in badgeTemplates"
                                                                :key="`badge-template-${badge.id}`"
                                                                @click="
                                                                    addPlayerBadge(
                                                                        badge,
                                                                    )
                                                                "
                                                            >
                                                                <span
                                                                    >#{{
                                                                        badge.id
                                                                    }}
                                                                    {{
                                                                        badge.name
                                                                    }}</span
                                                                >
                                                                <small
                                                                    >Effect
                                                                    {{
                                                                        badge.id_effect
                                                                    }}</small
                                                                >
                                                            </button>
                                                        </div>
                                                        <div class="player-badge-list">
                                                            <div
                                                                class="player-badge-row"
                                                                v-for="item in sectionParsedItems(
                                                                    field.name,
                                                                )"
                                                                :key="`${field.name}-${item.index}`"
                                                            >
                                                                <div>
                                                                    <strong
                                                                        >#{{
                                                                            item
                                                                                .value
                                                                                ?.id
                                                                        }}
                                                                        {{
                                                                            item.label
                                                                        }}</strong
                                                                    >
                                                                    <small>{{
                                                                        formatPlayerBadgeMeta(
                                                                            item.value,
                                                                        )
                                                                    }}</small>
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    class="icon-mini danger"
                                                                    @click="
                                                                        removePlayerBadge(
                                                                            item.index,
                                                                        )
                                                                    "
                                                                >
                                                                    <span
                                                                        class="mi"
                                                                        >delete</span
                                                                    >
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            isPlayerItemList(
                                                                field.name,
                                                            )
                                                        "
                                                        class="player-item-list"
                                                    >
                                                        <div
                                                            class="player-item-row"
                                                            v-for="item in sectionParsedItems(
                                                                field.name,
                                                            )"
                                                            :key="`${field.name}-${item.index}`"
                                                        >
                                                            <div>
                                                                <strong
                                                                    >[{{
                                                                        item.index
                                                                    }}]
                                                                    {{
                                                                        item.label
                                                                    }}</strong
                                                                >
                                                                <small>{{
                                                                    formatPlayerItemMeta(
                                                                        item.value,
                                                                    )
                                                                }}</small>
                                                            </div>
                                                            <span
                                                                v-if="
                                                                    item.value
                                                                        ?.icon_id !==
                                                                        null &&
                                                                    item.value
                                                                        ?.icon_id !==
                                                                        undefined
                                                                "
                                                                class="player-item-icon-id"
                                                                >Icon
                                                                {{
                                                                    item.value
                                                                        ?.icon_id
                                                                }}</span
                                                            >
                                                        </div>
                                                    </div>
                                                    <div v-else>
                                                        <div
                                                            class="parsed-row"
                                                            v-for="item in sectionParsedItems(
                                                                field.name,
                                                            )"
                                                            :key="`${field.name}-${item.index}`"
                                                        >
                                                            <span
                                                                class="parsed-label"
                                                                >[{{
                                                                    item.index
                                                                }}]
                                                                {{
                                                                    item.label
                                                                }}</span
                                                            >
                                                            <span
                                                                class="parsed-value"
                                                                >{{
                                                                    formatParsedValue(
                                                                        item.value,
                                                                    )
                                                                }}</span
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                                <pre>{{
                                                    getFieldDisplay(
                                                        field,
                                                        false,
                                                    )
                                                }}</pre>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Sidebar -->
                <div class="form-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h3>Quyền & Trạng thái</h3>
                        </div>
                        <div class="toggle-field">
                            <label class="toggle-row-label">Admin</label>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    v-model="form.is_admin"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-field">
                            <label class="toggle-row-label">Active</label>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    v-model="form.active"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-field">
                            <label
                                class="toggle-row-label"
                                style="color: var(--ds-danger)"
                                >Ban</label
                            >
                            <label class="toggle toggle-danger">
                                <input
                                    type="checkbox"
                                    v-model="form.ban"
                                    true-value="1"
                                    false-value="0"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button
                            class="btn btn-primary btn-block"
                            :disabled="saving"
                            style="margin-top: 16px"
                        >
                            <span class="mi" style="font-size: 16px">{{
                                isEdit ? "save" : "add"
                            }}</span>
                            {{
                                saving
                                    ? "Đang lưu..."
                                    : isEdit
                                      ? "Lưu thay đổi"
                                      : "Tạo tài khoản"
                            }}
                        </button>
                    </div>

                    <div class="card danger-card" v-if="isEdit">
                        <div class="card-header">
                            <h3 style="color: var(--ds-danger)">
                                Vùng nguy hiểm
                            </h3>
                        </div>
                        <p class="danger-text">
                            Xoá tài khoản sẽ không thể hoàn tác.
                        </p>
                        <button
                            type="button"
                            class="btn btn-danger btn-block btn-sm"
                            @click="del"
                        >
                            <span class="mi" style="font-size: 14px"
                                >delete</span
                            >
                            Xoá tài khoản
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            form: {
                username: "",
                password: "",
                cash: 0,
                danap: 0,
                coin: 0,
                diem_da_nhan: 0,
                diem_danh: 0,
                is_admin: "0",
                active: "1",
                ban: "0",
            },
            error: "",
            success: "",
            saving: false,
            playerInfo: null,
            playerFull: null,
            playerFullLoading: false,
            playerFullError: "",
            showPlayerFull: false,
            expandedFields: {},
            playerSectionCache: {},
            playerSectionLoading: {},
            playerSectionErrors: {},
            activity: null,
            activityLoading: false,
            activityError: "",
            activeDetailTab: "overview",
            expandedLogs: {},
            loadingAccount: false,
            badgeTemplates: [],
            badgeSearchTimer: null,
            badgeDraft: {
                search: "",
                days: 30,
                saving: false,
                error: "",
            },
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
        playerFields() {
            const order = [
                "data_point",
                "data_inventory",
                "data_location",
                "data_task",
                "items_body",
                "items_bag",
                "items_box",
                "item_mails_box",
                "items_daban",
                "dataBadges",
                "data_item_time",
                "pet",
                "giftcode",
                "event_point_boss",
            ];
            const fields = this.playerFull?.fields || [];
            const fieldMap = new Map(
                fields.map((field) => [field.name, field]),
            );
            return order
                .filter((name) => fieldMap.has(name))
                .map((name) => fieldMap.get(name));
        },
        parsedSections() {
            const parsed = this.playerFull?.parsed || {};
            const titleMap = {
                data_inventory: "Túi đồ",
                data_location: "Vị trí",
                data_point: "Chỉ số",
                data_task: "Nhiệm vụ",
                dataBadges: "Danh hiệu",
            };
            return [
                "data_inventory",
                "data_location",
                "data_point",
                "data_task",
                "dataBadges",
            ]
                .filter((k) => parsed[k] && Array.isArray(parsed[k].items))
                .map((k) => ({
                    key: k,
                    title: titleMap[k] || k,
                    items: parsed[k].items,
                }));
        },
    },
    watch: {
        "$route.fullPath"() {
            this.handleRouteChange();
        },
    },
    created() {
        this.handleRouteChange();
    },
    unmounted() {
        window.clearTimeout(this.badgeSearchTimer);
    },
    methods: {
        createDefaultForm() {
            return {
                username: "",
                password: "",
                cash: 0,
                danap: 0,
                coin: 0,
                diem_da_nhan: 0,
                diem_danh: 0,
                is_admin: "0",
                active: "1",
                ban: "0",
            };
        },
        resetAccountState() {
            this.playerInfo = null;
            this.playerFull = null;
            this.playerFullLoading = false;
            this.playerFullError = "";
            this.showPlayerFull = false;
            this.expandedFields = {};
            this.playerSectionCache = {};
            this.playerSectionLoading = {};
            this.playerSectionErrors = {};
            this.activity = null;
            this.activityLoading = false;
            this.activityError = "";
            this.activeDetailTab = "overview";
            this.expandedLogs = {};
        },
        handleRouteChange() {
            this.error = "";
            this.success = "";
            this.form.password = "";

            if (!this.isEdit) {
                this.loadingAccount = false;
                this.form = this.createDefaultForm();
                this.resetAccountState();
                return;
            }

            this.loadAccount();
        },
        fmt(n) {
            return Number(n || 0).toLocaleString("vi-VN");
        },
        genderLabel(gender) {
            if (gender === 0 || gender === "0") return "Trái Đất";
            if (gender === 1 || gender === "1") return "Namek";
            if (gender === 2 || gender === "2") return "Xayda";
            return "Không rõ";
        },
        taskPreviewText(task) {
            if (!task || task.id === null || task.id === undefined) {
                return "Chưa có nhiệm vụ";
            }
            const taskName = task.name ? ` - ${task.name}` : "";
            const taskIndex =
                task.index !== null && task.index !== undefined
                    ? ` | bước: ${task.index}`
                    : "";
            const taskCount =
                task.count !== null && task.count !== undefined
                    ? ` | tiến độ: ${task.count}`
                    : "";
            return `#${task.id}${taskName}${taskIndex}${taskCount}`;
        },
        normalizeValue(v) {
            if (v === null || v === undefined) return "(trống)";
            if (typeof v === "string") return v;
            try {
                return JSON.stringify(v);
            } catch {
                return String(v);
            }
        },
        shouldCollapseField(field) {
            const value = this.normalizeValue(
                this.playerSectionCache?.[field.name]?.raw,
            );
            return !!field.is_long || value.length > 260;
        },
        isFieldExpanded(name) {
            return !!this.expandedFields[name];
        },
        toggleFieldExpanded(name) {
            this.expandedFields = {
                ...this.expandedFields,
                [name]: !this.expandedFields[name],
            };
        },
        getFieldDisplay(field, shortMode = true) {
            const text = this.normalizeValue(
                this.playerSectionCache?.[field.name]?.raw,
            );
            if (
                shortMode &&
                this.shouldCollapseField(field) &&
                text.length > 260
            ) {
                return `${text.slice(0, 260)} ...`;
            }
            return text;
        },
        formatParsedValue(v) {
            const text = this.normalizeValue(v);
            if (text.length > 80) {
                return `${text.slice(0, 80)} ...`;
            }
            return text;
        },
        formatDateTime(value) {
            if (!value) return "—";
            const date = new Date(String(value).replace(" ", "T"));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString("vi-VN");
        },
        formatState(value) {
            if (!value) return "(không có)";
            if (typeof value === "string") return value;
            try {
                return JSON.stringify(value, null, 2);
            } catch {
                return String(value);
            }
        },
        isLogExpanded(id) {
            return !!this.expandedLogs[id];
        },
        toggleLogExpanded(id) {
            this.expandedLogs = {
                ...this.expandedLogs,
                [id]: !this.expandedLogs[id],
            };
        },
        isPlayerSectionLoaded(name) {
            return !!this.playerSectionCache?.[name];
        },
        isPlayerSectionLoading(name) {
            return !!this.playerSectionLoading?.[name];
        },
        playerSectionErrorText(name) {
            return this.playerSectionErrors?.[name] || "";
        },
        sectionParsedItems(name) {
            const parsed = this.playerSectionCache?.[name]?.parsed;
            return Array.isArray(parsed?.items) ? parsed.items : [];
        },
        isPlayerItemList(name) {
            return this.playerSectionCache?.[name]?.parsed?.type === "item_list";
        },
        isPlayerBadgeList(name) {
            return this.playerSectionCache?.[name]?.parsed?.type === "badge_list";
        },
        formatPlayerItemMeta(value) {
            if (!value || typeof value !== "object") return "Không có metadata";
            const parts = [];
            if (value.quantity !== undefined && value.quantity !== null) {
                parts.push(`SL ${this.fmt(value.quantity)}`);
            }
            if (value.type !== undefined && value.type !== null) {
                parts.push(`Type ${value.type}`);
            }
            if (value.options !== undefined && value.options !== null) {
                parts.push(`${this.fmt(value.options)} option`);
            }
            return parts.length ? parts.join(" | ") : "Không có metadata";
        },
        formatPlayerBadgeMeta(value) {
            if (!value || typeof value !== "object") return "Không có metadata";
            const parts = [];
            if (value.id_effect !== undefined && value.id_effect !== null) {
                parts.push(`Effect ${value.id_effect}`);
            }
            if (value.id_item !== undefined && value.id_item !== null) {
                parts.push(`Item ${value.id_item}`);
            }
            if (value.days_left !== undefined && value.days_left !== null) {
                parts.push(value.days_left > 0 ? `${value.days_left} ngày` : "Hết hạn");
            }
            if (value.is_use) {
                parts.push("Đang dùng");
            }
            return parts.length ? parts.join(" | ") : "Không có metadata";
        },
        debouncedLoadBadgeTemplates() {
            window.clearTimeout(this.badgeSearchTimer);
            this.badgeSearchTimer = window.setTimeout(() => {
                this.loadBadgeTemplates();
            }, 250);
        },
        async loadBadgeTemplates() {
            this.badgeDraft.error = "";
            try {
                const params = new URLSearchParams();
                if (this.badgeDraft.search) {
                    params.set("search", this.badgeDraft.search);
                }
                const res = await fetch(`/admin/api/badges?${params.toString()}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được badge");
                }
                this.badgeTemplates = data.data || [];
            } catch (error) {
                this.badgeDraft.error = error?.message || "Không tải được badge";
            }
        },
        async addPlayerBadge(badge) {
            if (!this.isPlayerSectionLoaded("dataBadges")) {
                await this.loadPlayerSection("dataBadges");
            }
            const days = Math.max(1, Number(this.badgeDraft.days || 30));
            const expires = Date.now() + days * 86400000;
            const current = this.playerSectionCache.dataBadges || {
                raw: "[]",
                parsed: { type: "badge_list", items: [] },
            };
            const raw = this.parseJsonArray(current.raw);
            raw.push({
                idBadGes: Number(badge.id),
                timeofUseBadges: Math.trunc(expires),
                isUse: raw.length === 0,
            });
            this.playerSectionCache = {
                ...this.playerSectionCache,
                dataBadges: {
                    ...current,
                    raw: JSON.stringify(raw),
                    parsed: this.localParsedBadges(raw),
                },
            };
        },
        removePlayerBadge(index) {
            const current = this.playerSectionCache.dataBadges;
            if (!current) return;
            const raw = this.parseJsonArray(current.raw).filter(
                (_, rowIndex) => String(rowIndex) !== String(index),
            );
            this.playerSectionCache = {
                ...this.playerSectionCache,
                dataBadges: {
                    ...current,
                    raw: JSON.stringify(raw),
                    parsed: {
                        type: "badge_list",
                        count: this.sectionParsedItems("dataBadges").length,
                        items: this.sectionParsedItems("dataBadges").filter(
                            (item) => String(item.index) !== String(index),
                        ),
                    },
                },
            };
        },
        parseJsonArray(value) {
            try {
                const decoded =
                    typeof value === "string" ? JSON.parse(value) : value;
                return Array.isArray(decoded) ? decoded : [];
            } catch {
                return [];
            }
        },
        localParsedBadges(raw) {
            return {
                type: "badge_list",
                count: raw.length,
                items: raw.map((badge, index) => ({
                    index,
                    label: `Badge #${badge.idBadGes}`,
                    value: {
                        id: Number(badge.idBadGes || 0),
                        expires_at: Number(badge.timeofUseBadges || 0),
                        days_left: Math.floor(
                            (Number(badge.timeofUseBadges || 0) - Date.now()) /
                                86400000,
                        ),
                        is_use: !!badge.isUse,
                    },
                })),
            };
        },
        async savePlayerBadges() {
            const current = this.playerSectionCache.dataBadges;
            if (!current) return;
            this.badgeDraft.saving = true;
            this.badgeDraft.error = "";
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}/badges`,
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": token,
                        },
                        body: JSON.stringify({
                            badges: this.parseJsonArray(current.raw),
                        }),
                    },
                );
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không lưu được badges");
                }
                await this.loadPlayerSection("dataBadges", true);
                this.success = data.message || "Đã lưu badges";
            } catch (error) {
                this.badgeDraft.error =
                    error?.message || "Không lưu được badges";
            } finally {
                this.badgeDraft.saving = false;
            }
        },
        async togglePlayerField(name) {
            const expanded = this.isFieldExpanded(name);
            this.expandedFields = {
                ...this.expandedFields,
                [name]: !expanded,
            };

            if (!expanded && !this.isPlayerSectionLoaded(name)) {
                await this.loadPlayerSection(name);
            }
            if (!expanded && name === "dataBadges" && !this.badgeTemplates.length) {
                await this.loadBadgeTemplates();
            }
        },
        async togglePlayerFull() {
            this.showPlayerFull = !this.showPlayerFull;
            if (
                this.showPlayerFull &&
                !this.playerFull &&
                !this.playerFullLoading
            ) {
                await this.loadPlayerFull();
            }
        },
        async loadPlayerFull() {
            this.playerFullError = "";
            this.playerFullLoading = true;
            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}/player-full`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (data.ok) {
                    this.playerFull = data.data;
                } else {
                    this.playerFullError =
                        data.message || "Không tải được dữ liệu player";
                }
            } catch {
                this.playerFullError = "Không tải được dữ liệu player";
            } finally {
                this.playerFullLoading = false;
            }
        },
        async loadPlayerSection(name, force = false) {
            if (
                !force &&
                (this.isPlayerSectionLoaded(name) ||
                    this.isPlayerSectionLoading(name))
            ) {
                return;
            }

            this.playerSectionLoading = {
                ...this.playerSectionLoading,
                [name]: true,
            };
            this.playerSectionErrors = {
                ...this.playerSectionErrors,
                [name]: "",
            };

            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}/player-sections/${name}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(
                        data.message || "Không tải được dữ liệu mục này",
                    );
                }
                this.playerSectionCache = {
                    ...this.playerSectionCache,
                    [name]: data.data || null,
                };
            } catch (error) {
                this.playerSectionErrors = {
                    ...this.playerSectionErrors,
                    [name]: error?.message || "Không tải được dữ liệu mục này",
                };
            } finally {
                this.playerSectionLoading = {
                    ...this.playerSectionLoading,
                    [name]: false,
                };
            }
        },
        async loadAccountActivity() {
            this.activityLoading = true;
            this.activityError = "";
            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}/activity`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(
                        data.message || "Không tải được lịch sử tài khoản",
                    );
                }
                this.activity = data.data || null;
            } catch (error) {
                this.activityError =
                    error?.message || "Không tải được lịch sử tài khoản";
            } finally {
                this.activityLoading = false;
            }
        },
        async loadAccount() {
            this.loadingAccount = true;
            this.error = "";
            this.resetAccountState();
            try {
                const res = await fetch(
                    `/admin/api/accounts/${this.$route.params.id}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                let data;
                try {
                    data = await res.json();
                } catch {
                    throw new Error("API trả về dữ liệu không hợp lệ");
                }

                if (!res.ok || !data?.ok || !data?.data) {
                    throw new Error(
                        data?.message || "Không thể tải dữ liệu tài khoản",
                    );
                }

                const a = data.data;
                this.form.username = a.username || "";
                this.form.password = "";
                this.form.cash = Number(a.cash || 0);
                this.form.danap = Number(a.danap || 0);
                this.form.coin = Number(a.coin || 0);
                this.form.diem_da_nhan = Number(a.diem_da_nhan || 0);
                this.form.diem_danh = Number(a.diem_danh || 0);
                this.form.is_admin = Number(a.is_admin) ? "1" : "0";
                this.form.active = Number(a.active) ? "1" : "0";
                this.form.ban = Number(a.ban) ? "1" : "0";
                this.playerInfo = a.player || null;

                await this.loadAccountActivity();
            } catch (error) {
                this.error = error?.message || "Không thể tải dữ liệu";
            } finally {
                this.loadingAccount = false;
            }
        },
        async save() {
            this.error = "";
            this.success = "";
            this.saving = true;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const body = { ...this.form };
                body.is_admin = parseInt(body.is_admin);
                body.active = parseInt(body.active);
                body.ban = parseInt(body.ban);
                body.cash = parseInt(body.cash || 0);
                body.danap = parseInt(body.danap || 0);
                body.coin = parseInt(body.coin || 0);
                body.diem_da_nhan = parseInt(body.diem_da_nhan || 0);
                body.diem_danh = parseInt(body.diem_danh || 0);
                if (this.isEdit && !body.password) {
                    delete body.password;
                }

                const url = this.isEdit
                    ? `/admin/api/accounts/${this.$route.params.id}`
                    : "/admin/api/accounts";
                const method = this.isEdit ? "PUT" : "POST";

                const res = await fetch(url, {
                    method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.ok) {
                    this.success = data.message;
                    if (!this.isEdit) {
                        this.$router.push({ name: "admin.accounts" });
                    } else {
                        await this.loadAccountActivity();
                    }
                } else {
                    this.error = data.message || "Lỗi";
                }
            } catch {
                this.error = "Lỗi kết nối";
            } finally {
                this.saving = false;
            }
        },
        async del() {
            if (!confirm("Bạn chắc chắn muốn xoá tài khoản này?")) return;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                await fetch(`/admin/api/accounts/${this.$route.params.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                });
                this.$router.push({ name: "admin.accounts" });
            } catch {
                this.error = "Lỗi kết nối";
            }
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.breadcrumb a {
    color: var(--ds-text-muted);
}
.breadcrumb a:hover {
    color: var(--ds-primary);
}
.breadcrumb span {
    color: var(--ds-gray-300);
}
.breadcrumb .current {
    color: var(--ds-text);
}
.required {
    color: var(--ds-danger);
}
.form-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
.form-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.form-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 92px;
}
.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 16px;
}
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
    }
}
@media (max-width: 740px) {
    .form-row-3,
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
.toggle-field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed var(--ds-border);
}
.toggle-field:last-of-type {
    border-bottom: none;
}
.toggle-row-label {
    font-size: 14px;
    color: var(--ds-text);
}
.toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--ds-gray-200);
    border-radius: 24px;
    transition: 0.3s;
}
.toggle-slider::before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: var(--ds-gray-400);
    border-radius: 50%;
    transition: 0.3s;
}
.toggle input:checked + .toggle-slider {
    background: var(--ds-primary);
}
.toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
    background: #fff;
}
.toggle-danger input:checked + .toggle-slider {
    background: var(--ds-danger);
}
.danger-card {
    border: 1px solid rgba(var(--ds-danger-rgb), 0.2) !important;
}
.danger-text {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-bottom: 12px;
}
.muted-line {
    font-size: 13px;
    color: var(--ds-text-muted);
}
.detail-hub {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.hub-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.hub-summary-card {
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 14px;
    background: var(--ds-surface-2, var(--ds-gray-100));
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.hub-summary-label {
    font-size: 11px;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.hub-summary-card strong {
    color: var(--ds-text-emphasis);
    font-size: 14px;
}
.hub-summary-card small {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.detail-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.detail-tab-btn {
    min-height: 36px;
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
    font-weight: 600;
}
.detail-tab-btn.active {
    border-color: rgba(var(--ds-primary-rgb), 0.3);
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-text-emphasis);
}
.detail-panel {
    border-top: 1px dashed var(--ds-border);
    padding-top: 16px;
}
.detail-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.detail-field {
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 12px 14px;
    background: var(--ds-surface-2, var(--ds-gray-100));
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.detail-field-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ds-text-muted);
}
.detail-field-value {
    color: var(--ds-text);
    font-size: 13px;
}
.mini-summary-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.mini-summary-box {
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 12px 14px;
    background: rgba(var(--ds-primary-rgb), 0.06);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mini-summary-box span {
    font-size: 12px;
    color: var(--ds-text-muted);
}
.mini-summary-box strong {
    font-size: 14px;
    color: var(--ds-text-emphasis);
}
.detail-subsection {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.detail-subtitle {
    font-size: 13px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.activity-row,
.activity-log-card {
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 12px;
    background: var(--ds-surface-2, var(--ds-gray-100));
}
.activity-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.activity-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.activity-sub {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-top: 4px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.activity-time {
    font-size: 12px;
    color: var(--ds-text-muted);
    white-space: nowrap;
}
.activity-row-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.log-state-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 12px;
}
.log-state-box {
    border: 1px dashed var(--ds-border);
    border-radius: 10px;
    padding: 10px;
    background: var(--ds-surface);
}
.log-state-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 8px;
}
.log-state-box pre {
    margin: 0;
    font-size: 12px;
    line-height: 1.45;
    color: var(--ds-text);
    white-space: pre-wrap;
    word-break: break-word;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    max-height: 260px;
    overflow: auto;
}
.player-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    margin-bottom: 12px;
}
.summary-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.summary-label {
    font-size: 11px;
    color: var(--ds-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.summary-value {
    font-size: 13px;
    color: var(--ds-text);
}
.player-full-wrap {
    margin-top: 12px;
    border-top: 1px dashed var(--ds-border);
    padding-top: 12px;
}
.task-banner {
    font-size: 13px;
    color: var(--ds-text);
    background: rgba(var(--ds-primary-rgb), 0.08);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.25);
    border-radius: 8px;
    padding: 8px 10px;
    margin-bottom: 12px;
}
.parsed-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}
.parsed-box {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 8px 10px;
    background: var(--ds-surface-1);
}
.parsed-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 6px;
}
.parsed-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: center;
    font-size: 12px;
    padding: 2px 0;
}
.parsed-label {
    color: var(--ds-text-muted);
}
.parsed-value {
    color: var(--ds-text);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    text-align: right;
}
.player-rows {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 620px;
    overflow: auto;
    padding-right: 4px;
}
.player-row {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 12px;
    align-items: start;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 10px;
    background: var(--ds-surface-1);
}
.player-row-title {
    color: var(--ds-text-emphasis);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
}
.player-row-name {
    font-size: 11px;
    color: var(--ds-text-muted);
    margin-top: 3px;
}
.player-row-value pre {
    margin: 0;
    font-size: 12px;
    line-height: 1.45;
    color: var(--ds-text);
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.player-row-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.player-row-note {
    font-size: 12px;
    color: var(--ds-text-muted);
}
.player-row-error {
    color: var(--ds-danger);
}
.player-section-parsed {
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    padding: 8px 10px;
    background: var(--ds-surface);
    margin-bottom: 8px;
}
.player-item-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.player-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-1);
    padding: 8px 10px;
    min-width: 0;
}
.player-item-row strong,
.player-item-row small {
    display: block;
}
.player-item-row strong {
    color: var(--ds-text-emphasis);
    font-size: 12px;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.player-item-row small {
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-top: 2px;
}
.player-item-icon-id {
    flex: 0 0 auto;
    color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.1);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
}
.player-badge-wrap {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.badge-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 92px auto;
    gap: 8px;
    align-items: center;
}
.badge-search-input,
.badge-days-input {
    min-width: 0;
}
.badge-template-list,
.player-badge-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.badge-template-list button,
.player-badge-row {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-1);
    color: var(--ds-text);
    padding: 8px 10px;
}
.badge-template-list button {
    cursor: pointer;
    text-align: left;
}
.badge-template-list button:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.45);
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.badge-template-list span,
.badge-template-list small,
.player-badge-row strong,
.player-badge-row small {
    display: block;
}
.badge-template-list span,
.player-badge-row strong {
    color: var(--ds-text-emphasis);
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.badge-template-list small,
.player-badge-row small {
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-top: 2px;
}
.player-badge-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.icon-mini {
    width: 30px;
    height: 30px;
    border: 1px solid var(--ds-border);
    border-radius: 7px;
    background: var(--ds-surface);
    color: var(--ds-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex: 0 0 auto;
}
.icon-mini.danger {
    color: var(--ds-danger);
}
.btn-xs {
    margin-top: 8px;
    padding: 4px 8px;
    font-size: 11px;
    min-height: 26px;
}
@media (max-width: 900px) {
    .hub-summary-grid,
    .detail-grid,
    .mini-summary-row,
    .player-summary-grid {
        grid-template-columns: 1fr;
    }
    .parsed-wrap {
        grid-template-columns: 1fr;
    }
    .player-item-list {
        grid-template-columns: 1fr;
    }
    .badge-toolbar,
    .badge-template-list,
    .player-badge-list {
        grid-template-columns: 1fr;
    }
    .player-row {
        grid-template-columns: 1fr;
    }
    .activity-row,
    .activity-row-head,
    .log-state-grid {
        grid-template-columns: 1fr;
        display: grid;
    }
    .activity-time {
        white-space: normal;
    }
}
</style>
