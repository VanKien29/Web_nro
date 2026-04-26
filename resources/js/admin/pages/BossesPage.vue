<template>
    <div class="boss-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Quản lý boss</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Boss</span>
                </nav>
            </div>
            <button
                class="btn btn-outline"
                :disabled="loading"
                @click="loadBosses"
            >
                <span class="mi" style="font-size: 16px">refresh</span>
                Tải lại
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="boss-stats">
            <div class="stat-box">
                <span>Boss hiển thị</span>
                <strong>{{ visibleBosses.length }}</strong>
            </div>
            <div class="stat-box">
                <span>Đang bật</span>
                <strong>{{ enabledCount }}</strong>
            </div>
            <div class="stat-box">
                <span>Nhóm boss</span>
                <strong>{{ bossGroups.length }}</strong>
            </div>
            <div class="stat-box">
                <span>Custom</span>
                <strong>{{ customCount }}</strong>
            </div>
        </div>

        <div class="boss-layout">
            <section class="boss-main">
                <div class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Danh sách boss</h3>
                            <p>
                                {{ filteredBosses.length }} boss phù hợp bộ lọc
                                <span v-if="hiddenRoutineCount"
                                    >· đã ẩn {{ hiddenRoutineCount }} boss
                                    phụ</span
                                >
                            </p>
                        </div>
                        <div class="segmented">
                            <button
                                :class="{ active: listMode === 'all' }"
                                @click="listMode = 'all'"
                            >
                                Tất cả
                            </button>
                            <button
                                :class="{ active: listMode === 'grouped' }"
                                @click="listMode = 'grouped'"
                            >
                                Theo nhóm
                            </button>
                            <button
                                :class="{ active: listMode === 'solo' }"
                                @click="listMode = 'solo'"
                            >
                                Đơn lẻ
                            </button>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="search-input-wrap">
                            <span class="mi search-icon">search</span>
                            <input
                                v-model="search"
                                class="form-input search-input"
                                placeholder="Tìm ID, tên, map, manager, nhóm..."
                            />
                        </div>
                        <select
                            v-model="statusFilter"
                            class="form-input compact-filter"
                        >
                            <option value="">Tất cả trạng thái</option>
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="panel table-panel">
                    <div class="boss-list">
                        <details
                            v-for="bucket in paginatedBossBuckets"
                            :key="bucket.key"
                            class="boss-list-group"
                        >
                            <summary class="boss-list-summary">
                                <span class="mi summary-icon">expand_more</span>
                                <span class="summary-avatars">
                                    <span
                                        v-for="member in bucket.members.slice(
                                            0,
                                            4,
                                        )"
                                        :key="
                                            'avatar-' +
                                            member.manager +
                                            '-' +
                                            member.index
                                        "
                                        class="boss-avatar mini-avatar"
                                        :title="avatarTitle(member)"
                                    >
                                        <img
                                            v-if="member.avatar_id"
                                            :src="iconSrc(member.avatar_id)"
                                            :alt="
                                                member.name ||
                                                member.template_name
                                            "
                                            loading="lazy"
                                            decoding="async"
                                        />
                                    </span>
                                </span>
                                <span class="summary-copy">
                                    <strong>{{ bucket.name }}</strong>
                                    <small>
                                        {{ bucket.members.length }} boss
                                        <template v-if="bucket.phaseCount > 1"
                                            >·
                                            {{ bucket.phaseCount }}
                                            phase</template
                                        >
                                        ·
                                        {{
                                            bucket.kind === "group"
                                                ? "Nhóm liên kết"
                                                : "Cùng loại"
                                        }}
                                    </small>
                                </span>
                                <span class="summary-status">
                                    <span
                                        class="badge"
                                        :class="
                                            bucket.enabled
                                                ? 'badge-success'
                                                : 'badge-danger'
                                        "
                                        >{{
                                            bucket.enabled ? "Bật" : "Tắt"
                                        }}</span
                                    >
                                </span>
                            </summary>

                            <div class="boss-list-members">
                                <article
                                    v-for="boss in bucket.members"
                                    :key="boss.manager + '-' + boss.index"
                                    class="boss-list-member"
                                >
                                    <div class="boss-identity">
                                        <div
                                            class="boss-avatar"
                                            :title="avatarTitle(boss)"
                                        >
                                            <img
                                                v-if="boss.avatar_id"
                                                :src="iconSrc(boss.avatar_id)"
                                                :alt="
                                                    boss.name ||
                                                    boss.template_name
                                                "
                                                loading="lazy"
                                                decoding="async"
                                            />
                                        </div>
                                        <div class="boss-copy">
                                            <div class="boss-name">
                                                {{
                                                    boss.name ||
                                                    boss.template_name
                                                }}
                                            </div>
                                            <div class="boss-meta">
                                                ID {{ boss.boss_id }} ·
                                                {{ boss.manager }} #{{
                                                    boss.index
                                                }}
                                                <span
                                                    v-if="boss.custom"
                                                    class="chip chip-warning"
                                                    >Custom</span
                                                >
                                                <span
                                                    v-if="boss.group_size > 1"
                                                    class="chip"
                                                    >{{
                                                        boss.group_role ===
                                                        "parent"
                                                            ? "Nhóm chính"
                                                            : "Nhóm"
                                                    }}:
                                                    {{ boss.group_name }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="
                                                    bossMechanics(boss).length
                                                "
                                                class="mechanic-chips"
                                            >
                                                <span
                                                    v-for="item in bossMechanics(
                                                        boss,
                                                    )"
                                                    :key="item"
                                                    class="mechanic-chip"
                                                    >{{ item }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="
                                                    skillSummary(
                                                        boss.skill_temp,
                                                    )
                                                "
                                                class="boss-skill-summary"
                                            >
                                                {{
                                                    skillSummary(
                                                        boss.skill_temp,
                                                    )
                                                }}
                                            </div>
                                            <div
                                                v-if="
                                                    bossLevels(boss).length > 1
                                                "
                                                class="phase-strip"
                                            >
                                                <button
                                                    v-for="level in bossLevels(
                                                        boss,
                                                    )"
                                                    :key="
                                                        'phase-' +
                                                        boss.manager +
                                                        '-' +
                                                        boss.index +
                                                        '-' +
                                                        level.level_index
                                                    "
                                                    type="button"
                                                    class="phase-chip"
                                                    :class="{
                                                        active:
                                                            Number(
                                                                level.level_index,
                                                            ) ===
                                                            Number(
                                                                boss.current_level,
                                                            ),
                                                    }"
                                                    :title="phaseTitle(level)"
                                                    @click.stop="
                                                        openEdit(
                                                            boss,
                                                            level.level_index,
                                                        )
                                                    "
                                                >
                                                    P{{
                                                        Number(
                                                            level.level_index,
                                                        ) + 1
                                                    }}
                                                    {{ level.name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="boss-list-metrics">
                                        <div>
                                            <span>HP</span>
                                            <strong>{{
                                                number(boss.hp)
                                            }}</strong>
                                            <small
                                                >Max
                                                {{ number(boss.hp_max) }}</small
                                            >
                                        </div>
                                        <div>
                                            <span>Dame</span>
                                            <strong>{{
                                                number(boss.dame)
                                            }}</strong>
                                            <small>{{ boss.status }}</small>
                                        </div>
                                        <div>
                                            <span>Vị trí</span>
                                            <strong v-if="boss.map_id >= 0"
                                                >{{ boss.map_name }} ({{
                                                    boss.map_id
                                                }})</strong
                                            >
                                            <strong v-else>Chưa ở map</strong>
                                            <small v-if="boss.map_id >= 0"
                                                >Khu {{ boss.zone_id }}</small
                                            >
                                        </div>
                                    </div>

                                    <div class="actions-cell boss-list-actions">
                                        <button
                                            class="icon-action"
                                            title="Chi tiết / Sửa"
                                            @click="openEdit(boss)"
                                        >
                                            <span class="mi">edit</span>
                                        </button>
                                        <button
                                            class="icon-action"
                                            title="Respawn"
                                            @click="runAction(boss, 'respawn')"
                                        >
                                            <span class="mi">restart_alt</span>
                                        </button>
                                        <button
                                            v-if="boss.group_size > 1"
                                            class="icon-action"
                                            title="Respawn nhóm"
                                            @click="
                                                runAction(
                                                    boss,
                                                    'respawn',
                                                    'group',
                                                )
                                            "
                                        >
                                            <span class="mi">hub</span>
                                        </button>
                                        <button
                                            class="icon-action"
                                            :title="
                                                boss.enabled ? 'Tắt' : 'Bật'
                                            "
                                            @click="
                                                runAction(
                                                    boss,
                                                    boss.enabled
                                                        ? 'disable'
                                                        : 'enable',
                                                )
                                            "
                                        >
                                            <span class="mi">{{
                                                boss.enabled
                                                    ? "visibility_off"
                                                    : "visibility"
                                            }}</span>
                                        </button>
                                        <button
                                            class="icon-action danger"
                                            title="Xóa"
                                            @click="runAction(boss, 'delete')"
                                        >
                                            <span class="mi">delete</span>
                                        </button>
                                    </div>
                                </article>
                            </div>

                            <div
                                v-if="bucket.kind === 'group'"
                                class="group-actions inline-group-actions"
                            >
                                <button
                                    class="btn btn-outline btn-xs"
                                    @click="runGroupAction(bucket, 'respawn')"
                                >
                                    Respawn nhóm
                                </button>
                                <button
                                    class="btn btn-outline btn-xs"
                                    @click="
                                        runGroupAction(
                                            bucket,
                                            bucket.enabled
                                                ? 'disable'
                                                : 'enable',
                                        )
                                    "
                                >
                                    {{
                                        bucket.enabled ? "Tắt nhóm" : "Bật nhóm"
                                    }}
                                </button>
                                <button
                                    class="btn btn-danger btn-xs"
                                    @click="runGroupAction(bucket, 'delete')"
                                >
                                    Xóa nhóm
                                </button>
                            </div>
                        </details>
                        <div
                            v-if="!bossBuckets.length && !loading"
                            class="empty-cell"
                        >
                            Không có boss phù hợp.
                        </div>
                    </div>
                    <div v-if="bossBuckets.length" class="pagination-bar">
                        <div class="pagination-info">
                            Hiển thị {{ pageStart }}-{{ pageEnd }} /
                            {{ bossBuckets.length }} nhóm/boss
                        </div>
                        <div class="pagination-controls">
                            <button
                                class="page-btn"
                                :disabled="currentPage <= 1"
                                @click="goToPage(1)"
                            >
                                Đầu
                            </button>
                            <button
                                class="page-btn"
                                :disabled="currentPage <= 1"
                                @click="goToPage(currentPage - 1)"
                            >
                                Trước
                            </button>
                            <template
                                v-for="item in paginationPages"
                                :key="item.key"
                            >
                                <span
                                    v-if="item.type === 'ellipsis'"
                                    class="page-ellipsis"
                                    >...</span
                                >
                                <button
                                    v-else
                                    class="page-btn square"
                                    :class="{
                                        active: item.page === currentPage,
                                    }"
                                    @click="goToPage(item.page)"
                                >
                                    {{ item.page }}
                                </button>
                            </template>
                            <button
                                class="page-btn"
                                :disabled="currentPage >= totalPages"
                                @click="goToPage(currentPage + 1)"
                            >
                                Sau
                            </button>
                            <button
                                class="page-btn"
                                :disabled="currentPage >= totalPages"
                                @click="goToPage(totalPages)"
                            >
                                Cuối
                            </button>
                            <input
                                v-model.number="pageInput"
                                class="page-jump"
                                type="number"
                                min="1"
                                :max="totalPages"
                                title="Nhập số trang"
                                @keyup.enter="goToPage(pageInput)"
                                @blur="goToPage(pageInput)"
                            />
                            <select
                                v-model.number="perPage"
                                class="page-size"
                                title="Số boss mỗi trang"
                            >
                                <option :value="25">25/trang</option>
                                <option :value="50">50/trang</option>
                                <option :value="100">100/trang</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="boss-side">
                <div class="panel">
                    <div class="panel-head compact">
                        <div>
                            <h3>Tạo boss</h3>
                            <p>Runtime</p>
                        </div>
                    </div>
                    <div class="segmented full">
                        <button
                            :class="{ active: createMode === 'quick' }"
                            @click="createMode = 'quick'"
                        >
                            Có sẵn
                        </button>
                        <button
                            :class="{ active: createMode === 'custom' }"
                            @click="createMode = 'custom'"
                        >
                            Custom
                        </button>
                    </div>

                    <div v-if="createMode === 'quick'" class="create-section">
                        <div class="form-group">
                            <label class="form-label">Boss ID</label>
                            <input
                                v-model.number="createForm.boss_id"
                                class="form-input"
                                type="number"
                                placeholder="-20"
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số lượng</label>
                            <input
                                v-model.number="createForm.count"
                                class="form-input"
                                type="number"
                                min="1"
                                max="50"
                            />
                        </div>
                        <button
                            class="btn btn-primary btn-block"
                            :disabled="saving"
                            @click="createBoss"
                        >
                            <span class="mi" style="font-size: 16px">add</span>
                            Tạo boss
                        </button>
                        <div class="catalog-list">
                            <button
                                v-for="item in catalog"
                                :key="item.boss_id"
                                type="button"
                                class="catalog-item"
                                @click="createForm.boss_id = item.boss_id"
                            >
                                <span>{{
                                    item.name || item.label || item.boss_id
                                }}</span>
                                <small>ID {{ item.boss_id }}</small>
                            </button>
                        </div>
                    </div>

                    <div v-else class="create-section">
                        <div class="form-row-2 compact-gap">
                            <div class="form-group">
                                <label class="form-label">Boss ID</label>
                                <input
                                    v-model.number="customForm.boss_id"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Số lượng</label>
                                <input
                                    v-model.number="customForm.count"
                                    class="form-input"
                                    type="number"
                                    min="1"
                                    max="50"
                                />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tên boss</label>
                            <input
                                v-model="customForm.name"
                                class="form-input"
                            />
                        </div>
                        <div class="form-row-3 compact-gap">
                            <div class="form-group">
                                <label class="form-label">Head</label
                                ><input
                                    v-model.number="customForm.head"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Body</label
                                ><input
                                    v-model.number="customForm.body"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Leg</label
                                ><input
                                    v-model.number="customForm.leg"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="form-row-2 compact-gap">
                            <div class="form-group">
                                <label class="form-label">HP max</label
                                ><input
                                    v-model.number="customForm.hp_max"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dame</label
                                ><input
                                    v-model.number="customForm.dame"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Map join</label>
                            <input
                                v-model="customForm.map_join"
                                class="form-input"
                                placeholder="79,81,82"
                            />
                        </div>
                        <div class="form-row-2 compact-gap">
                            <div class="form-group">
                                <label class="form-label">Nghỉ giây</label
                                ><input
                                    v-model.number="customForm.seconds_rest"
                                    class="form-input"
                                    type="number"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select
                                    v-model.number="customForm.gender"
                                    class="form-input"
                                >
                                    <option :value="0">Trái đất</option>
                                    <option :value="1">Namek</option>
                                    <option :value="2">Xayda</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Skill</label>
                            <div class="skill-builder">
                                <div
                                    v-for="(row, idx) in customSkillRows"
                                    :key="'custom-skill-' + idx"
                                    class="skill-row"
                                >
                                    <select
                                        v-model.number="row.id"
                                        class="form-input skill-select"
                                        @change="applySkillDefaults(row, true)"
                                    >
                                        <option
                                            v-for="option in skillOptions"
                                            :key="'custom-option-' + option.id"
                                            :value="option.id"
                                        >
                                            {{ option.name }} ·
                                            {{ option.class_name }} (ID
                                            {{ option.id }})
                                        </option>
                                    </select>
                                    <input
                                        v-model.number="row.level"
                                        class="form-input skill-small"
                                        type="number"
                                        min="1"
                                        :max="skillMaxPoint(row.id)"
                                        title="Cấp skill"
                                        @change="applySkillDefaults(row)"
                                    />
                                    <input
                                        v-model.number="row.cooldown"
                                        class="form-input skill-time"
                                        type="number"
                                        min="0"
                                        step="100"
                                        title="Cooldown ms"
                                    />
                                    <button
                                        type="button"
                                        class="icon-action danger"
                                        title="Xóa skill"
                                        @click="
                                            removeSkillRow(customSkillRows, idx)
                                        "
                                    >
                                        <span class="mi">close</span>
                                    </button>
                                </div>
                                <div class="skill-actions">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-sm"
                                        @click="addSkillRow(customSkillRows)"
                                    >
                                        Thêm skill
                                    </button>
                                    <span>{{
                                        skillRowsSummary(customSkillRows)
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="member-head">
                            <span>Boss con trong nhóm</span>
                            <button
                                class="btn btn-outline btn-sm"
                                @click="addGroupMember"
                            >
                                Thêm
                            </button>
                        </div>
                        <div
                            v-for="(member, idx) in customMembers"
                            :key="idx"
                            class="member-card"
                        >
                            <div class="form-row-2 compact-gap">
                                <input
                                    v-model.number="member.boss_id"
                                    class="form-input"
                                    type="number"
                                    placeholder="ID"
                                />
                                <input
                                    v-model="member.name"
                                    class="form-input"
                                    placeholder="Tên"
                                />
                            </div>
                            <div class="form-row-3 compact-gap">
                                <input
                                    v-model.number="member.head"
                                    class="form-input"
                                    type="number"
                                    placeholder="Head"
                                />
                                <input
                                    v-model.number="member.body"
                                    class="form-input"
                                    type="number"
                                    placeholder="Body"
                                />
                                <input
                                    v-model.number="member.leg"
                                    class="form-input"
                                    type="number"
                                    placeholder="Leg"
                                />
                            </div>
                            <button
                                class="member-remove"
                                @click="removeGroupMember(idx)"
                            >
                                Xóa boss con
                            </button>
                        </div>
                        <button
                            class="btn btn-primary btn-block"
                            :disabled="saving"
                            @click="createCustomBoss"
                        >
                            <span class="mi" style="font-size: 16px">add</span>
                            Tạo custom boss
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <div
            v-if="editing"
            class="modal-overlay"
            @click.self="editing = null"
            @wheel.stop
            @touchmove.stop
        >
            <div class="modal-panel">
                <div class="modal-head">
                    <div class="modal-title-row">
                        <div
                            class="boss-avatar boss-avatar-lg"
                            :title="avatarTitle(editing)"
                        >
                            <img
                                v-if="editingAvatarId"
                                :src="iconSrc(editingAvatarId)"
                                :alt="editingDisplayName"
                                loading="lazy"
                                decoding="async"
                            />
                        </div>
                        <div>
                            <h3>Chi tiết / sửa boss</h3>
                            <p>
                                {{ editingDisplayName }} · ID
                                {{ editing.boss_id }} · Phase
                                {{ Number(editForm.level_index) + 1 }}/{{
                                    editing.levels
                                }}
                            </p>
                        </div>
                    </div>
                    <button class="picker-close" @click="editing = null">
                        <span class="mi" style="font-size: 18px">close</span>
                    </button>
                </div>

                <div class="detail-grid">
                    <div>
                        <span>Manager</span
                        ><strong
                            >{{ editing.manager }} #{{ editing.index }}</strong
                        >
                    </div>
                    <div>
                        <span>Nhóm</span
                        ><strong>{{
                            editing.group_size > 1
                                ? `${editing.group_name} (${editing.group_size})`
                                : "Đơn lẻ"
                        }}</strong>
                    </div>
                    <div>
                        <span>Avatar</span
                        ><strong>{{
                            editingAvatarId ? `#${editingAvatarId}` : "Chưa có"
                        }}</strong>
                    </div>
                    <div>
                        <span>Type appear</span
                        ><strong>{{ editing.type_appear || "-" }}</strong>
                    </div>
                    <div>
                        <span>Map hiện tại</span
                        ><strong>{{
                            editing.map_id >= 0
                                ? `${editing.map_name} (${editing.map_id})`
                                : "Chưa ở map"
                        }}</strong>
                    </div>
                    <div>
                        <span>Level</span
                        ><strong
                            >{{ editing.current_level + 1 }} /
                            {{ editing.levels }}</strong
                        >
                    </div>
                    <div>
                        <span>Phase đang sửa</span
                        ><strong
                            >P{{ Number(editForm.level_index) + 1 }} ·
                            {{ editingDisplayName }}</strong
                        >
                    </div>
                    <div>
                        <span>Class xử lý</span
                        ><strong>{{
                            editing.class_simple_name ||
                            editing.class_name ||
                            "-"
                        }}</strong>
                    </div>
                    <div>
                        <span>Cơ chế code</span
                        ><strong>{{
                            bossMechanics(editing).length
                                ? bossMechanics(editing).join(" · ")
                                : "Mặc định"
                        }}</strong>
                    </div>
                </div>

                <div v-if="bossLevels(editing).length > 1" class="phase-editor">
                    <button
                        v-for="level in bossLevels(editing)"
                        :key="'edit-phase-' + level.level_index"
                        type="button"
                        class="phase-edit-btn"
                        :class="{
                            active:
                                Number(level.level_index) ===
                                Number(editForm.level_index),
                        }"
                        @click="switchEditLevel(level.level_index)"
                    >
                        <span class="boss-avatar mini-avatar">
                            <img
                                v-if="level.avatar_id"
                                :src="iconSrc(level.avatar_id)"
                                :alt="level.name"
                                loading="lazy"
                                decoding="async"
                            />
                        </span>
                        <span>
                            <strong
                                >P{{ Number(level.level_index) + 1 }} ·
                                {{ level.name }}</strong
                            >
                            <small
                                >{{ number(level.hp_max) }} HP ·
                                {{ number(level.dame) }} dame ·
                                {{ level.type_appear }}
                                <template
                                    v-if="
                                        Array.isArray(
                                            level.bosses_appear_together,
                                        ) && level.bosses_appear_together.length
                                    "
                                    >· gọi
                                    {{
                                        level.bosses_appear_together.join(", ")
                                    }}</template
                                ></small
                            >
                        </span>
                    </button>
                </div>

                <div class="edit-grid">
                    <div class="form-group">
                        <label class="form-label">Tên hiển thị</label
                        ><input v-model="editForm.name" class="form-input" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tên template</label
                        ><input
                            v-model="editForm.template_name"
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select v-model="editForm.status" class="form-input">
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select
                            v-model.number="editForm.gender"
                            class="form-input"
                        >
                            <option :value="0">Trái đất</option>
                            <option :value="1">Namek</option>
                            <option :value="2">Xayda</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type appear</label>
                        <select
                            v-model="editForm.type_appear"
                            class="form-input"
                        >
                            <option
                                v-for="type in appearTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Boss gọi kèm</label
                        ><input
                            v-model="editForm.bosses_appear_together"
                            class="form-input"
                            placeholder="VD: -23,-24,-25"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">HP hiện tại</label
                        ><input
                            v-model.number="editForm.hp"
                            class="form-input"
                            type="number"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">HP tối đa</label
                        ><input
                            v-model.number="editForm.hp_max"
                            class="form-input"
                            type="number"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dame</label
                        ><input
                            v-model.number="editForm.dame"
                            class="form-input"
                            type="number"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nghỉ giây</label
                        ><input
                            v-model.number="editForm.seconds_rest"
                            class="form-input"
                            type="number"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Map join</label
                        ><input
                            v-model="editForm.map_join"
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Outfit</label
                        ><input v-model="editForm.outfit" class="form-input" />
                    </div>
                    <div class="form-group wide">
                        <label class="form-label">Skill của boss</label>
                        <div class="skill-builder">
                            <div
                                v-for="(row, idx) in editForm.skill_rows"
                                :key="'edit-skill-' + idx"
                                class="skill-row"
                            >
                                <select
                                    v-model.number="row.id"
                                    class="form-input skill-select"
                                    @change="applySkillDefaults(row, true)"
                                >
                                    <option
                                        v-for="option in skillOptions"
                                        :key="'edit-option-' + option.id"
                                        :value="option.id"
                                    >
                                        {{ option.name }} ·
                                        {{ option.class_name }} (ID
                                        {{ option.id }})
                                    </option>
                                </select>
                                <input
                                    v-model.number="row.level"
                                    class="form-input skill-small"
                                    type="number"
                                    min="1"
                                    :max="skillMaxPoint(row.id)"
                                    title="Cấp skill"
                                    @change="applySkillDefaults(row)"
                                />
                                <input
                                    v-model.number="row.cooldown"
                                    class="form-input skill-time"
                                    type="number"
                                    min="0"
                                    step="100"
                                    title="Cooldown ms"
                                />
                                <button
                                    type="button"
                                    class="icon-action danger"
                                    title="Xóa skill"
                                    @click="
                                        removeSkillRow(editForm.skill_rows, idx)
                                    "
                                >
                                    <span class="mi">close</span>
                                </button>
                            </div>
                            <div class="skill-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    @click="addSkillRow(editForm.skill_rows)"
                                >
                                    Thêm skill
                                </button>
                                <span>{{
                                    skillRowsSummary(editForm.skill_rows)
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group wide">
                        <label class="form-label">Chat xuất hiện</label
                        ><textarea
                            v-model="editForm.text_s"
                            class="form-input code-input"
                            rows="3"
                        ></textarea>
                    </div>
                    <div class="form-group wide">
                        <label class="form-label">Chat đánh</label
                        ><textarea
                            v-model="editForm.text_m"
                            class="form-input code-input"
                            rows="3"
                        ></textarea>
                    </div>
                    <div class="form-group wide">
                        <label class="form-label">Chat chết/rời map</label
                        ><textarea
                            v-model="editForm.text_e"
                            class="form-input code-input"
                            rows="3"
                        ></textarea>
                    </div>
                    <div class="spawn-config-card full">
                        <div class="spawn-config-head">
                            <div>
                                <span class="section-label"
                                    >Cấu hình spawn</span
                                >
                                <strong>{{
                                    editingDisplayName || "Boss đang chọn"
                                }}</strong>
                                <small>
                                    Boss ID {{ configForm.boss_id || "-" }} ·
                                    Phase
                                    {{
                                        Number(configForm.level_index || 0) + 1
                                    }}
                                    · {{ configCountText }}
                                </small>
                            </div>
                            <button
                                class="btn btn-primary btn-sm"
                                :disabled="saving"
                                @click="saveBossConfig"
                            >
                                <span class="mi" style="font-size: 16px"
                                    >save</span
                                >
                                Lưu spawn
                            </button>
                        </div>
                        <div class="spawn-config-grid">
                            <div class="form-group">
                                <label class="form-label">Số lượng</label>
                                <input
                                    v-model.number="configForm.count"
                                    class="form-input"
                                    type="number"
                                    min="1"
                                    max="50"
                                />
                            </div>
                            <label class="toggle-row compact-toggle"
                                ><input
                                    v-model="configForm.auto_spawn"
                                    type="checkbox"
                                /><span>Tự spawn khi restart</span></label
                            >
                            <label class="toggle-row compact-toggle"
                                ><input
                                    v-model="configForm.apply_now"
                                    type="checkbox"
                                /><span>Áp dụng ngay</span></label
                            >
                            <label class="toggle-row compact-toggle"
                                ><input
                                    v-model="configForm.active"
                                    type="checkbox"
                                /><span>Bật rule</span></label
                            >
                        </div>
                    </div>
                </div>

                <label class="toggle-row"
                    ><input v-model="editForm.enabled" type="checkbox" /><span
                        >Bật boss</span
                    ></label
                >
                <div class="modal-actions">
                    <button class="btn btn-outline" @click="editing = null">
                        Hủy
                    </button>
                    <button
                        class="btn btn-primary"
                        :disabled="saving"
                        @click="saveEdit"
                    >
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            bosses: [],
            catalog: [],
            bossConfigs: {
                template: [],
                spawn: [],
                runtime: [],
            },
            skillOptions: [],
            loading: false,
            saving: false,
            error: "",
            success: "",
            search: "",
            statusFilter: "",
            listMode: "all",
            page: 1,
            pageInput: 1,
            perPage: 50,
            createMode: "quick",
            createForm: { boss_id: "", count: 1 },
            customForm: {
                boss_id: -9000,
                count: 1,
                name: "Boss custom",
                gender: 2,
                head: 180,
                body: 181,
                leg: 182,
                hp_max: 1000000,
                dame: 10000,
                map_join: "5",
                seconds_rest: 900,
            },
            customSkillRows: [{ id: 0, level: 1, cooldown: 1000 }],
            customMembers: [],
            configForm: {
                section: "spawn",
                boss_id: "",
                level_index: -1,
                rule_key: "",
                manager_key: "main",
                active: true,
                count: 1,
                auto_spawn: true,
                apply_now: false,
            },
            editing: null,
            editForm: {},
            statuses: [
                "REST",
                "RESPAWN",
                "JOIN_MAP",
                "CHAT_S",
                "ACTIVE",
                "DIE",
                "CHAT_E",
                "LEAVE_MAP",
                "AFK",
            ],
            appearTypes: [
                "DEFAULT_APPEAR",
                "APPEAR_WITH_ANOTHER",
                "ANOTHER_LEVEL",
                "CALL_BY_ANOTHER",
            ],
        };
    },
    computed: {
        visibleBosses() {
            return this.bosses.filter(
                (boss) => !this.isRoutineBossHidden(boss),
            );
        },
        hiddenRoutineCount() {
            return this.bosses.length - this.visibleBosses.length;
        },
        enabledCount() {
            return this.visibleBosses.filter((boss) => boss.enabled).length;
        },
        customCount() {
            return this.visibleBosses.filter((boss) => boss.custom).length;
        },
        configCountText() {
            const spawn = this.bossConfigs.spawn?.length || 0;
            return `${spawn} rule spawn`;
        },
        filteredBosses() {
            const q = this.search.trim().toLowerCase();
            return this.visibleBosses.filter((boss) => {
                if (this.statusFilter && boss.status !== this.statusFilter)
                    return false;
                if (!q) return true;
                return [
                    boss.boss_id,
                    boss.name,
                    boss.template_name,
                    boss.manager,
                    boss.map_name,
                    boss.map_id,
                    boss.group_name,
                    boss.class_simple_name,
                    this.bossLevels(boss)
                        .map((level) => level.name)
                        .join(" "),
                ]
                    .join(" ")
                    .toLowerCase()
                    .includes(q);
            });
        },
        displayedBosses() {
            if (this.listMode === "grouped")
                return this.filteredBosses.filter(
                    (boss) => Number(boss.group_size || 0) > 1,
                );
            if (this.listMode === "solo")
                return this.filteredBosses.filter(
                    (boss) => Number(boss.group_size || 0) <= 1,
                );
            return this.filteredBosses;
        },
        totalPages() {
            return Math.max(
                1,
                Math.ceil(this.bossBuckets.length / Number(this.perPage || 50)),
            );
        },
        currentPage() {
            return Math.min(
                Math.max(Number(this.page) || 1, 1),
                this.totalPages,
            );
        },
        paginatedBosses() {
            const start = (this.currentPage - 1) * Number(this.perPage || 50);
            return this.displayedBosses.slice(
                start,
                start + Number(this.perPage || 50),
            );
        },
        paginatedBossBuckets() {
            const start = (this.currentPage - 1) * Number(this.perPage || 50);
            return this.bossBuckets.slice(
                start,
                start + Number(this.perPage || 50),
            );
        },
        pageStart() {
            return this.bossBuckets.length
                ? (this.currentPage - 1) * Number(this.perPage || 50) + 1
                : 0;
        },
        pageEnd() {
            return Math.min(
                this.currentPage * Number(this.perPage || 50),
                this.bossBuckets.length,
            );
        },
        paginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) {
                return Array.from({ length: total }, (_, index) => ({
                    type: "page",
                    page: index + 1,
                    key: `p-${index + 1}`,
                }));
            }
            const pages = new Set([
                1,
                total,
                current - 1,
                current,
                current + 1,
            ]);
            const sorted = Array.from(pages)
                .filter((page) => page >= 1 && page <= total)
                .sort((a, b) => a - b);
            const items = [];
            let previous = 0;
            for (const page of sorted) {
                if (previous && page - previous > 1) {
                    items.push({
                        type: "ellipsis",
                        key: `e-${previous}-${page}`,
                    });
                }
                items.push({ type: "page", page, key: `p-${page}` });
                previous = page;
            }
            return items;
        },
        skillOptionsById() {
            return this.skillOptions.reduce((map, option) => {
                map[Number(option.id)] = option;
                return map;
            }, {});
        },
        bossGroups() {
            const groups = new Map();
            for (const boss of this.filteredBosses) {
                if (!boss.group_key || Number(boss.group_size || 0) <= 1)
                    continue;
                if (!groups.has(boss.group_key)) {
                    groups.set(boss.group_key, {
                        key: boss.group_key,
                        name:
                            boss.group_name || boss.name || boss.template_name,
                        parent_manager:
                            boss.group_parent_manager || boss.manager,
                        parent_index: Number.isInteger(
                            Number(boss.group_parent_index),
                        )
                            ? Number(boss.group_parent_index)
                            : boss.index,
                        members: [],
                        enabled: false,
                    });
                }
                const group = groups.get(boss.group_key);
                group.members.push(boss);
                group.enabled = group.enabled || !!boss.enabled;
            }
            return Array.from(groups.values()).sort(
                (a, b) => b.members.length - a.members.length,
            );
        },
        bossBuckets() {
            const buckets = new Map();
            for (const boss of this.displayedBosses) {
                const isGroup =
                    boss.group_key && Number(boss.group_size || 0) > 1;
                const key = isGroup
                    ? `group:${boss.group_key}`
                    : `same:${boss.manager}:${boss.boss_id}:${this.normalizedBossText(boss.template_name || boss.name)}`;
                if (!buckets.has(key)) {
                    buckets.set(key, {
                        key,
                        kind: isGroup ? "group" : "same",
                        name: isGroup
                            ? boss.group_name || boss.name || boss.template_name
                            : boss.template_name ||
                              boss.name ||
                              `Boss ${boss.boss_id}`,
                        parent_manager:
                            boss.group_parent_manager || boss.manager,
                        parent_index: Number.isInteger(
                            Number(boss.group_parent_index),
                        )
                            ? Number(boss.group_parent_index)
                            : boss.index,
                        members: [],
                        enabled: false,
                        phaseCount: 1,
                    });
                }
                const bucket = buckets.get(key);
                bucket.members.push(boss);
                bucket.enabled = bucket.enabled || !!boss.enabled;
                bucket.phaseCount = Math.max(
                    bucket.phaseCount,
                    this.bossLevels(boss).length,
                );
            }
            return Array.from(buckets.values()).sort((a, b) => {
                if (a.kind !== b.kind) return a.kind === "group" ? -1 : 1;
                if (b.members.length !== a.members.length)
                    return b.members.length - a.members.length;
                return a.name.localeCompare(b.name, "vi");
            });
        },
        editingLevelData() {
            if (!this.editing) return null;
            return this.bossLevelData(this.editing, this.editForm.level_index);
        },
        editingAvatarId() {
            return (
                this.editingLevelData?.avatar_id ||
                this.editing?.avatar_id ||
                null
            );
        },
        editingDisplayName() {
            return (
                this.editingLevelData?.name ||
                this.editing?.name ||
                this.editing?.template_name ||
                ""
            );
        },
    },
    watch: {
        search() {
            this.resetPagination();
        },
        statusFilter() {
            this.resetPagination();
        },
        listMode() {
            this.resetPagination();
        },
        perPage() {
            this.resetPagination();
        },
        editing(value) {
            this.setPageScrollLocked(!!value);
        },
    },
    created() {
        this.loadBosses();
    },
    beforeUnmount() {
        this.setPageScrollLocked(false);
    },
    methods: {
        number(value) {
            return Number(value || 0).toLocaleString("vi-VN");
        },
        resetPagination() {
            this.page = 1;
            this.pageInput = 1;
        },
        goToPage(value) {
            const page = Math.min(
                Math.max(Number(value) || 1, 1),
                this.totalPages,
            );
            this.page = page;
            this.pageInput = page;
        },
        iconSrc(iconId) {
            const id = Number(iconId);
            return Number.isInteger(id) && id >= 0
                ? `/assets/frontend/home/v1/images/x4/${id}.png`
                : "";
        },
        avatarTitle(boss) {
            const outfit = Array.isArray(boss?.outfit) ? boss.outfit : [];
            return `Avatar ${boss?.avatar_id ?? "-"} | Outfit ${outfit.join(", ")}`;
        },
        setPageScrollLocked(locked) {
            if (typeof document === "undefined") return;
            const body = document.body;
            if (!body) return;
            if (locked) {
                if (this._previousBodyOverflow === undefined) {
                    this._previousBodyOverflow = body.style.overflow;
                }
                body.style.overflow = "hidden";
                return;
            }
            if (this._previousBodyOverflow !== undefined) {
                body.style.overflow = this._previousBodyOverflow;
                this._previousBodyOverflow = undefined;
            }
        },
        normalizedBossText(value) {
            return String(value || "")
                .trim()
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d");
        },
        isHiddenBossName(value) {
            const name = this.normalizedBossText(value);
            if (!name) return false;
            return [
                "chien binh",
                "tan binh",
                "doi truong",
                "drabura",
                "mabu",
                "tau pay pay",
                "taupaypay",
                "be na",
            ].some((keyword) => {
                return (
                    name === keyword ||
                    name.startsWith(keyword) ||
                    name.includes(` ${keyword}`) ||
                    name.includes(`-${keyword}`)
                );
            });
        },
        isRoutineBossHidden(boss) {
            return [
                boss?.name,
                boss?.template_name,
                boss?.group_name,
                boss?.group_key,
            ].some((value) => this.isHiddenBossName(value));
        },
        token() {
            return document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
        },
        csv(value) {
            return Array.isArray(value) ? value.join(",") : "";
        },
        lines(value) {
            return Array.isArray(value) ? value.join("\n") : "";
        },
        skillLines(value) {
            return Array.isArray(value)
                ? value
                      .map((row) => (Array.isArray(row) ? row.join(",") : ""))
                      .filter(Boolean)
                      .join("\n")
                : "";
        },
        defaultSkillRow() {
            const first = this.skillOptions[0];
            const id = Number(first?.id ?? 0);
            return {
                id,
                level: 1,
                cooldown: this.skillDefaultCooldown(id, 1) || 1000,
            };
        },
        skillOption(id) {
            return this.skillOptionsById[Number(id)] || null;
        },
        skillMaxPoint(id) {
            return Number(this.skillOption(id)?.max_point || 10);
        },
        skillDefaultCooldown(id, level) {
            const option = this.skillOption(id);
            const point = Number(level) || 1;
            const found = (option?.levels || []).find(
                (row) => Number(row.point) === point,
            );
            return Number(found?.cool_down || 0);
        },
        skillLabel(id) {
            const option = this.skillOption(id);
            return option ? option.name : `Skill #${Number(id) || 0}`;
        },
        skillRowsFromArray(value, withFallback = true) {
            const source = Array.isArray(value)
                ? value
                : String(value || "").split(/\r?\n/);
            const rows = source
                .map((row) =>
                    Array.isArray(row) ? row : String(row).split(/[,\s;]+/),
                )
                .map((row) => {
                    const id = Number(row[0]);
                    const level = Math.max(1, Number(row[1]) || 1);
                    const rawCooldown =
                        row[2] ?? this.skillDefaultCooldown(id, level) ?? 1000;
                    const cooldown = Number(rawCooldown || 1000);
                    return { id, level, cooldown };
                })
                .filter(
                    (row) =>
                        Number.isFinite(row.id) &&
                        Number.isFinite(row.level) &&
                        Number.isFinite(row.cooldown),
                );
            return rows.length
                ? rows
                : withFallback
                  ? [this.defaultSkillRow()]
                  : [];
        },
        skillRowsPayload(rows) {
            return (rows || [])
                .map((row) => [
                    Number(row.id),
                    Math.max(1, Number(row.level) || 1),
                    Math.max(0, Number(row.cooldown) || 0),
                ])
                .filter(
                    (row) =>
                        Number.isFinite(row[0]) &&
                        Number.isFinite(row[1]) &&
                        Number.isFinite(row[2]),
                );
        },
        applySkillDefaults(row, resetCooldown = false) {
            row.level = Math.min(
                Math.max(Number(row.level) || 1, 1),
                this.skillMaxPoint(row.id),
            );
            if (resetCooldown || !Number(row.cooldown)) {
                row.cooldown =
                    this.skillDefaultCooldown(row.id, row.level) || 1000;
            }
        },
        addSkillRow(rows) {
            rows.push(this.defaultSkillRow());
        },
        removeSkillRow(rows, index) {
            rows.splice(index, 1);
            if (!rows.length) {
                rows.push(this.defaultSkillRow());
            }
        },
        msLabel(value) {
            const ms = Number(value) || 0;
            if (ms >= 60000) return `${Math.round(ms / 1000)}s`;
            if (ms >= 1000) return `${(ms / 1000).toLocaleString("vi-VN")}s`;
            return `${ms}ms`;
        },
        skillRowsSummary(rows) {
            return (rows || [])
                .map(
                    (row) =>
                        `${this.skillLabel(row.id)} cấp ${Number(row.level) || 1} · ${this.msLabel(row.cooldown)}`,
                )
                .join(" | ");
        },
        skillSummary(value) {
            const rows = this.skillRowsFromArray(value, false);
            if (!rows.length) return "";
            const summary = rows
                .slice(0, 2)
                .map(
                    (row) =>
                        `${this.skillLabel(row.id)} cấp ${Number(row.level) || 1} (${this.msLabel(row.cooldown)})`,
                )
                .join(" · ");
            return rows.length > 2 ? `${summary} +${rows.length - 2}` : summary;
        },
        bossMechanics(boss) {
            const mechanics = [];
            if (boss?.has_custom_attack)
                mechanics.push(`Attack: ${boss.attack_owner || "riêng"}`);
            if (boss?.has_custom_reward)
                mechanics.push(`Drop: ${boss.reward_owner || "riêng"}`);
            if (boss?.class_simple_name) mechanics.push(boss.class_simple_name);
            return mechanics;
        },
        phaseTitle(level) {
            const together = Array.isArray(level?.bosses_appear_together)
                ? level.bosses_appear_together.filter(
                      (id) => Number.isFinite(Number(id)) && Number(id) !== 0,
                  )
                : [];
            const parts = [
                `HP ${this.number(level?.hp_max)}`,
                `Dame ${this.number(level?.dame)}`,
                level?.type_appear || "",
            ].filter(Boolean);
            if (together.length) {
                parts.push(`Gọi kèm boss ${together.join(", ")}`);
            }
            return parts.join(" · ");
        },
        bossLevels(boss) {
            const levels = Array.isArray(boss?.levels_data)
                ? boss.levels_data
                : [];
            if (levels.length) return levels;
            return [
                {
                    level_index: Math.max(0, Number(boss?.current_level) || 0),
                    name: boss?.template_name || boss?.name || "",
                    gender: boss?.gender,
                    type_appear: boss?.type_appear,
                    hp_max: boss?.hp_max,
                    dame: boss?.data_dame ?? boss?.dame,
                    seconds_rest: boss?.seconds_rest,
                    outfit: boss?.outfit || [],
                    map_join: boss?.map_join || [],
                    skill_temp: boss?.skill_temp || [],
                    text_s: boss?.text_s || [],
                    text_m: boss?.text_m || [],
                    text_e: boss?.text_e || [],
                    avatar_id: boss?.avatar_id,
                },
            ];
        },
        bossLevelData(boss, levelIndex) {
            const levels = this.bossLevels(boss);
            const target = Number.isFinite(Number(levelIndex))
                ? Number(levelIndex)
                : Math.max(0, Number(boss?.current_level) || 0);
            return (
                levels.find((level) => Number(level.level_index) === target) ||
                levels[0] ||
                null
            );
        },
        outfitFromForm(form) {
            return [
                form.head,
                form.body,
                form.leg,
                form.bag ?? -1,
                form.aura ?? -1,
                form.eff ?? -1,
            ].map((value) => Number(value ?? -1));
        },
        memberPayload(member) {
            return {
                boss_id: Number(member.boss_id),
                name: member.name || "Boss con",
                gender: Number(member.gender ?? this.customForm.gender),
                outfit: [member.head, member.body, member.leg, -1, -1, -1].map(
                    (value) => Number(value ?? -1),
                ),
                hp_max: Number(member.hp_max || this.customForm.hp_max),
                dame: Number(member.dame || this.customForm.dame),
                map_join: member.map_join || this.customForm.map_join,
                seconds_rest: Number(
                    member.seconds_rest ?? this.customForm.seconds_rest,
                ),
                skill_temp: this.skillRowsPayload(
                    member.skill_rows || this.customSkillRows,
                ),
                type_appear: "APPEAR_WITH_ANOTHER",
            };
        },
        customPayload() {
            return {
                custom: true,
                boss_id: Number(this.customForm.boss_id),
                count: Number(this.customForm.count || 1),
                name: this.customForm.name,
                gender: Number(this.customForm.gender),
                outfit: this.outfitFromForm(this.customForm),
                hp_max: Number(this.customForm.hp_max),
                dame: Number(this.customForm.dame),
                map_join: this.customForm.map_join,
                seconds_rest: Number(this.customForm.seconds_rest),
                skill_temp: this.skillRowsPayload(this.customSkillRows),
                text_s: "",
                text_m: "",
                text_e: "",
                type_appear: "DEFAULT_APPEAR",
                group_members: this.customMembers.map((member) =>
                    this.memberPayload(member),
                ),
            };
        },
        addGroupMember() {
            const base = Number(this.customForm.boss_id || -9000);
            this.customMembers.push({
                boss_id: base - this.customMembers.length - 1,
                name: `Boss con ${this.customMembers.length + 1}`,
                head: this.customForm.head,
                body: this.customForm.body,
                leg: this.customForm.leg,
            });
        },
        removeGroupMember(index) {
            this.customMembers.splice(index, 1);
        },
        async loadBosses() {
            this.loading = true;
            this.error = "";
            try {
                const res = await fetch("/admin/api/runtime/bosses", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok)
                    throw new Error(data.message || "Không tải được boss");
                this.bosses = data.data?.bosses || [];
                this.catalog = data.data?.catalog || [];
                this.skillOptions = data.data?.skill_options || [];
                await this.loadBossConfigs(true);
                this.customSkillRows.forEach((row) =>
                    this.applySkillDefaults(row),
                );
                this.pageInput = this.currentPage;
            } catch (e) {
                this.error = e?.message || "Không tải được boss";
            } finally {
                this.loading = false;
            }
        },
        async loadBossConfigs(silent = false) {
            try {
                const res = await fetch("/admin/api/runtime/boss-configs", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok)
                    throw new Error(
                        data.message || "Không tải được cấu hình boss",
                    );
                this.bossConfigs = {
                    template: data.data?.template || [],
                    spawn: data.data?.spawn || [],
                    runtime: data.data?.runtime || [],
                };
            } catch (e) {
                if (!silent) {
                    this.error = e?.message || "Không tải được cấu hình boss";
                }
            }
        },
        async postJson(url, method, payload) {
            const res = await fetch(url, {
                method,
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.token(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok || !data.ok)
                throw new Error(data.message || "Thao tác thất bại");
            return data;
        },
        async createBoss() {
            await this.persistAction(
                () =>
                    this.postJson(
                        "/admin/api/runtime/bosses",
                        "POST",
                        this.createForm,
                    ),
                "Đã tạo boss",
                "Tạo boss thất bại",
            );
        },
        async createCustomBoss() {
            await this.persistAction(
                () =>
                    this.postJson(
                        "/admin/api/runtime/bosses",
                        "POST",
                        this.customPayload(),
                    ),
                "Đã tạo custom boss",
                "Tạo custom boss thất bại",
            );
        },
        async saveBossConfig() {
            const payload = {
                ...this.configForm,
                section: "spawn",
                count: Math.max(
                    1,
                    Math.min(50, Number(this.configForm.count || 1)),
                ),
            };
            if (!Number(payload.boss_id)) {
                this.error = "Cần mở một boss trước khi lưu cấu hình spawn.";
                return;
            }
            await this.persistAction(
                () =>
                    this.postJson(
                        "/admin/api/runtime/boss-configs",
                        "POST",
                        payload,
                    ),
                "Đã lưu cấu hình boss",
                "Lưu cấu hình boss thất bại",
            );
        },
        async runAction(boss, action, scope = "single") {
            const isGroup = scope === "group";
            if (
                action === "delete" &&
                !confirm(
                    `Xóa ${isGroup ? "nhóm boss" : "boss"} ${boss.name || boss.boss_id}?`,
                )
            )
                return;
            await this.persistAction(
                () =>
                    this.postJson("/admin/api/runtime/bosses/action", "POST", {
                        manager: boss.manager,
                        index: boss.index,
                        action,
                        scope,
                    }),
                "Đã thực hiện lệnh boss",
                "Lệnh boss thất bại",
            );
        },
        runGroupAction(group, action) {
            return this.runAction(
                {
                    manager: group.parent_manager,
                    index: group.parent_index,
                    name: group.name,
                },
                action,
                "group",
            );
        },
        async persistAction(action, successMessage, failMessage) {
            this.saving = true;
            this.error = "";
            this.success = "";
            try {
                const data = await action();
                this.success = data.message || successMessage;
                await this.loadBosses();
                this.refreshEditingReference();
            } catch (e) {
                this.error = e?.message || failMessage;
            } finally {
                this.saving = false;
            }
        },
        refreshEditingReference() {
            if (!this.editing) return;
            const updated = this.bosses.find(
                (boss) =>
                    boss.manager === this.editing.manager &&
                    Number(boss.index) === Number(this.editing.index),
            );
            if (!updated) return;
            this.editing = updated;
        },
        spawnRuleForBoss(bossId, managerKey = "main") {
            const id = Number(bossId);
            const manager = String(managerKey || "main");
            return (
                this.bossConfigs.spawn.find(
                    (rule) =>
                        Number(rule.boss_id) === id &&
                        String(rule.manager_key || "main") === manager,
                ) ||
                this.bossConfigs.spawn.find(
                    (rule) => Number(rule.boss_id) === id,
                ) ||
                null
            );
        },
        openEdit(boss, levelIndex = null) {
            this.editing = boss;
            const targetIndex =
                levelIndex === null || levelIndex === undefined
                    ? Math.max(0, Number(boss.current_level) || 0)
                    : Number(levelIndex);
            const level = this.bossLevelData(boss, targetIndex) || {};
            const isCurrentLevel =
                Number(level.level_index) ===
                Math.max(0, Number(boss.current_level) || 0);
            const outfit = Array.isArray(level.outfit) ? level.outfit : [];
            this.editForm = {
                manager: boss.manager,
                index: boss.index,
                level_index: Number(level.level_index ?? targetIndex),
                enabled: !!boss.enabled,
                name: isCurrentLevel
                    ? boss.name || level.name || ""
                    : level.name || "",
                template_name:
                    level.name || boss.template_name || boss.name || "",
                gender: Number(level.gender ?? boss.gender ?? 2),
                hp: isCurrentLevel
                    ? boss.hp || level.hp_max || 1
                    : level.hp_max || 1,
                hp_max: level.hp_max || boss.hp_max || 1,
                dame: level.dame || boss.dame || 1,
                seconds_rest: level.seconds_rest ?? boss.seconds_rest ?? 0,
                status: boss.status || "REST",
                type_appear:
                    level.type_appear || boss.type_appear || "DEFAULT_APPEAR",
                bosses_appear_together: this.csv(
                    level.bosses_appear_together || [],
                ),
                outfit: this.csv(outfit),
                map_join: this.csv(level.map_join),
                skill_rows: this.skillRowsFromArray(level.skill_temp),
                text_s: this.lines(level.text_s),
                text_m: this.lines(level.text_m),
                text_e: this.lines(level.text_e),
            };
            const managerKey = boss.manager || "main";
            const spawnRule = this.spawnRuleForBoss(boss.boss_id, managerKey);
            this.configForm = {
                ...this.configForm,
                section: "spawn",
                boss_id: Number(boss.boss_id || 0),
                level_index: Number(level.level_index ?? targetIndex),
                rule_key:
                    spawnRule?.rule_key ||
                    `spawn:${managerKey}:${Number(boss.boss_id || 0)}`,
                manager_key: managerKey,
                active: spawnRule?.active ?? true,
                count: Number(spawnRule?.count || 1),
                auto_spawn: spawnRule?.auto_spawn ?? true,
                apply_now: false,
            };
        },
        switchEditLevel(levelIndex) {
            if (!this.editing) return;
            this.openEdit(this.editing, levelIndex);
        },
        async saveEdit() {
            const { skill_rows, ...form } = this.editForm;
            const payload = {
                ...form,
                gender: Number(this.editForm.gender),
                hp: Number(this.editForm.hp),
                hp_max: Number(this.editForm.hp_max),
                dame: Number(this.editForm.dame),
                seconds_rest: Number(this.editForm.seconds_rest),
                skill_temp: this.skillRowsPayload(skill_rows),
            };
            this.saving = true;
            this.error = "";
            this.success = "";
            try {
                const data = await this.postJson(
                    "/admin/api/runtime/bosses",
                    "PUT",
                    payload,
                );
                this.success = data.message || "Đã cập nhật boss";
                this.editing = null;
                await this.loadBosses();
            } catch (e) {
                this.error = e?.message || "Cập nhật boss thất bại";
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped>
.boss-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
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
    color: var(--ds-text-muted);
}
.boss-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.stat-box {
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: var(--ds-shadow-sm);
}
.stat-box span {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.stat-box strong {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 24px;
    margin-top: 4px;
}
.boss-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 18px;
    align-items: start;
}
.boss-main,
.boss-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.boss-side {
    position: sticky;
    top: 86px;
    max-height: calc(100vh - 104px);
    overflow: auto;
    padding-right: 2px;
}
.panel {
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--ds-shadow-sm);
}
.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}
.panel-head.compact {
    margin-bottom: 12px;
}
.panel-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
    font-size: 16px;
}
.panel-head p {
    margin: 3px 0 0;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.segmented {
    display: inline-flex;
    padding: 3px;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-body-bg);
}
.segmented.full {
    width: 100%;
    margin-bottom: 14px;
}
.segmented button {
    border: 0;
    background: transparent;
    color: var(--ds-text-muted);
    padding: 7px 12px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
}
.segmented.full button {
    flex: 1;
}
.segmented button.active {
    background: rgba(var(--ds-primary-rgb), 0.2);
    color: var(--ds-text-emphasis);
}
.filter-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.search-input-wrap {
    position: relative;
    min-width: 280px;
    flex: 1;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
}
.search-input {
    padding-left: 40px !important;
}
.compact-filter {
    width: 190px;
}
.group-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 10px;
    max-height: 360px;
    overflow: auto;
    padding-right: 4px;
}
.boss-menu-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 10px;
    max-height: 420px;
    overflow: auto;
    padding-right: 4px;
}
.boss-menu {
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-body-bg);
    overflow: hidden;
}
.boss-menu summary {
    display: grid;
    grid-template-columns: 22px minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    padding: 11px 12px;
    cursor: pointer;
    list-style: none;
    color: var(--ds-text-emphasis);
}
.boss-menu summary::-webkit-details-marker {
    display: none;
}
.boss-menu[open] summary .mi {
    transform: rotate(180deg);
}
.boss-menu-title {
    font-weight: 800;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.boss-menu-meta {
    color: var(--ds-text-muted);
    font-size: 11px;
    white-space: nowrap;
}
.boss-menu-members {
    display: grid;
    gap: 6px;
    padding: 0 10px 10px;
}
.boss-menu-member {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
    width: 100%;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 7px 8px;
    color: var(--ds-text);
    background: var(--ds-surface-2);
    text-align: left;
    cursor: pointer;
}
.boss-menu-member:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.55);
}
.boss-menu-member strong,
.boss-menu-member small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.boss-menu-member small {
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-top: 2px;
}
.menu-actions {
    padding: 0 10px 10px;
}
.group-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-body-bg);
}
.group-main {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.group-avatars {
    display: flex;
    align-items: center;
    min-width: 74px;
}
.group-avatar {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-left: -8px;
}
.group-avatar:first-child {
    margin-left: 0;
}
.group-avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}
.group-copy {
    min-width: 0;
}
.group-title {
    color: var(--ds-text-emphasis);
    font-weight: 700;
    font-size: 13px;
}
.group-meta {
    color: var(--ds-text-muted);
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 520px;
}
.group-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.table-panel {
    padding: 0;
    overflow: hidden;
}
.table-wrap {
    overflow-x: auto;
}
.boss-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
}
.boss-list-group {
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    background: var(--ds-surface-2);
    overflow: hidden;
}
.boss-list-summary {
    display: grid;
    grid-template-columns: 26px auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    cursor: pointer;
    list-style: none;
    color: var(--ds-text-emphasis);
}
.boss-list-summary::-webkit-details-marker {
    display: none;
}
.boss-list-group[open] .summary-icon {
    transform: rotate(180deg);
}
.summary-icon {
    color: var(--ds-text-muted);
    transition: transform 0.16s ease;
}
.summary-avatars {
    display: flex;
    align-items: center;
    min-width: 58px;
}
.summary-avatars .boss-avatar {
    margin-left: -9px;
    box-shadow: 0 0 0 2px var(--ds-surface-2);
}
.summary-avatars .boss-avatar:first-child {
    margin-left: 0;
}
.summary-copy {
    min-width: 0;
}
.summary-copy strong,
.summary-copy small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.summary-copy small {
    color: var(--ds-text-muted);
    font-size: 12px;
    margin-top: 2px;
}
.summary-status {
    display: flex;
    justify-content: flex-end;
}
.boss-list-members {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0 12px 12px;
}
.boss-list-member {
    display: grid;
    grid-template-columns: minmax(320px, 1.35fr) minmax(300px, 1fr) auto;
    gap: 14px;
    align-items: center;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface);
    padding: 12px;
}
.boss-list-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}
.boss-list-metrics > div {
    min-width: 0;
    border: 1px solid var(--ds-border);
    border-radius: 9px;
    background: var(--ds-body-bg);
    padding: 8px 10px;
}
.boss-list-metrics span,
.boss-list-metrics small {
    display: block;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.boss-list-metrics strong {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 13px;
    margin: 2px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.boss-list-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.inline-group-actions {
    padding: 0 12px 12px;
}
.boss-identity {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 280px;
}
.boss-avatar {
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 10px;
    border: 1px solid var(--ds-border);
    background:
        radial-gradient(
            circle at 50% 18%,
            rgba(var(--ds-primary-rgb), 0.18),
            transparent 34px
        ),
        var(--ds-body-bg);
    overflow: hidden;
}
.boss-avatar-lg {
    width: 64px;
    height: 64px;
    border-radius: 12px;
}
.mini-avatar {
    width: 34px;
    height: 34px;
    border-radius: 8px;
}
.boss-avatar img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.boss-copy {
    min-width: 0;
}
.boss-name {
    font-weight: 800;
    color: var(--ds-text-emphasis);
}
.boss-meta {
    margin-top: 3px;
    font-size: 11px;
    color: var(--ds-text-muted);
}
.boss-skill-summary {
    margin-top: 5px;
    color: var(--ds-primary);
    font-size: 11px;
    max-width: 420px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mechanic-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 6px;
}
.mechanic-chip {
    border: 1px solid rgba(var(--ds-primary-rgb), 0.28);
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-primary);
    border-radius: 999px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 700;
}
.phase-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 7px;
}
.phase-chip {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
}
.phase-chip.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}
.chip {
    display: inline-flex;
    margin-left: 6px;
    padding: 1px 6px;
    border-radius: 999px;
    color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.12);
}
.chip-warning {
    color: var(--ds-warning);
    background: rgba(var(--ds-warning-rgb), 0.14);
}
.metric-main {
    color: var(--ds-text-emphasis);
    font-weight: 800;
}
.muted {
    color: var(--ds-text-muted);
}
.status-text {
    display: inline-block;
    margin-left: 6px;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.actions-cell {
    text-align: right;
    white-space: nowrap;
}
.icon-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 4px;
    border-radius: 8px;
    border: 1px solid var(--ds-border);
    color: var(--ds-text);
    background: transparent;
    cursor: pointer;
}
.icon-action:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    color: var(--ds-primary);
}
.icon-action.danger:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.55);
    color: var(--ds-danger);
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid var(--ds-border);
    background: var(--ds-surface);
}
.pagination-info {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.page-btn,
.page-jump,
.page-size {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 8px;
    height: 32px;
    font-size: 12px;
    font-weight: 700;
}
.page-btn {
    padding: 0 10px;
    cursor: pointer;
}
.page-btn.square {
    min-width: 32px;
    padding: 0 8px;
}
.page-btn.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.18);
    color: var(--ds-primary);
}
.page-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.page-ellipsis {
    color: var(--ds-text-muted);
    padding: 0 4px;
}
.page-jump {
    width: 58px;
    padding: 0 8px;
}
.page-size {
    padding: 0 8px;
}
.create-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.compact-gap {
    gap: 8px;
}
.catalog-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 260px;
    overflow: auto;
}
.catalog-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 8px;
    padding: 8px 9px;
    cursor: pointer;
    text-align: left;
}
.catalog-item small {
    color: var(--ds-text-muted);
    font-size: 11px;
}
.member-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 700;
    margin-top: 2px;
}
.member-card {
    border: 1px dashed var(--ds-border);
    border-radius: 10px;
    padding: 10px;
    background: var(--ds-body-bg);
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.member-remove {
    border: 0;
    background: transparent;
    color: var(--ds-danger);
    text-align: left;
    cursor: pointer;
    font-weight: 700;
}
.code-input {
    min-height: 76px;
    font-family: Consolas, "Courier New", monospace;
    font-size: 12px;
    line-height: 1.45;
}
.skill-builder {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.skill-row {
    display: grid;
    grid-template-columns: minmax(170px, 1fr) 76px 108px 34px;
    gap: 8px;
    align-items: center;
}
.skill-select {
    min-width: 0;
}
.skill-small,
.skill-time {
    min-width: 0;
}
.skill-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.skill-actions span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.spawn-config-card {
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    border-radius: 10px;
    background: rgba(var(--ds-primary-rgb), 0.06);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.spawn-config-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.spawn-config-head > div {
    min-width: 0;
}
.spawn-config-head .btn {
    flex: 0 0 auto;
}
.spawn-config-head strong,
.spawn-config-head small,
.section-label {
    display: block;
}
.section-label {
    color: var(--ds-primary);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.spawn-config-head strong {
    color: var(--ds-text-emphasis);
}
.spawn-config-head small {
    color: var(--ds-text-muted);
    font-size: 12px;
    margin-top: 3px;
}
.spawn-config-grid {
    display: grid;
    grid-template-columns: minmax(140px, 180px) repeat(3, minmax(0, 1fr));
    gap: 10px;
    align-items: end;
}
.spawn-config-grid .toggle-row {
    align-self: stretch;
    min-height: 40px;
    margin: 0;
    padding: 0 12px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    font-weight: 700;
    line-height: 1.2;
}
.spawn-config-grid .toggle-row span {
    overflow: hidden;
    text-overflow: ellipsis;
}
.compact-toggle {
    margin: 0;
}
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 80;
    background: rgba(0, 0, 0, 0.58);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.modal-panel {
    width: min(980px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 18px;
    box-shadow: var(--ds-shadow-xl);
}
.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 16px;
}
.modal-title-row {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.modal-title-row h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.modal-title-row p {
    margin: 3px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.picker-close {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 8px;
    width: 34px;
    height: 34px;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 16px;
}
.detail-grid > div {
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    padding: 9px 10px;
    min-width: 0;
}
.detail-grid span {
    display: block;
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-bottom: 4px;
}
.detail-grid strong {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 13px;
    word-break: break-word;
}
.phase-editor {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 8px;
    margin-bottom: 16px;
}
.phase-edit-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 10px;
    padding: 8px;
    text-align: left;
    cursor: pointer;
}
.phase-edit-btn.active {
    border-color: rgba(var(--ds-primary-rgb), 0.75);
    background: rgba(var(--ds-primary-rgb), 0.13);
}
.phase-edit-btn strong,
.phase-edit-btn small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.phase-edit-btn small {
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-top: 2px;
}
.edit-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.edit-grid .wide {
    grid-column: span 2;
}
.edit-grid .full {
    grid-column: 1 / -1;
}
.form-row-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.form-row-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.toggle-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 12px 0 16px;
    color: var(--ds-text);
}
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-xs {
    padding: 5px 8px;
    font-size: 12px;
}
@media (max-width: 1180px) {
    .boss-layout {
        grid-template-columns: 1fr;
    }
    .boss-side {
        position: static;
        max-height: none;
    }
    .boss-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .boss-list-member {
        grid-template-columns: 1fr;
        align-items: stretch;
    }
    .boss-list-actions {
        justify-content: flex-start;
    }
}
@media (max-width: 760px) {
    .edit-grid,
    .detail-grid,
    .form-row-2,
    .form-row-3,
    .spawn-config-grid,
    .skill-row {
        grid-template-columns: 1fr;
    }
    .spawn-config-head {
        flex-direction: column;
    }
    .edit-grid .wide {
        grid-column: span 1;
    }
    .edit-grid .full {
        grid-column: span 1;
    }
    .group-card {
        align-items: flex-start;
        flex-direction: column;
    }
    .boss-stats {
        grid-template-columns: 1fr;
    }
    .boss-list {
        padding: 10px;
    }
    .boss-list-summary {
        grid-template-columns: 24px minmax(0, 1fr) auto;
    }
    .summary-avatars {
        display: none;
    }
    .boss-list-metrics {
        grid-template-columns: 1fr;
    }
    .pagination-bar,
    .skill-actions {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
