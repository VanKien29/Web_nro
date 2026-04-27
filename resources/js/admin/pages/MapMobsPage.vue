<template>
    <div class="map-mob-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">Map - Mob</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }"
                        >Trang chủ</router-link
                    >
                    <span>/</span>
                    <span class="current">Map - Mob</span>
                </nav>
            </div>
            <button
                class="btn btn-outline"
                :disabled="loading"
                @click="loadData"
            >
                <span class="mi" style="font-size: 16px">refresh</span>
                Tải lại
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="map-mob-stats">
            <div class="stat-box">
                <span>Tổng map</span>
                <strong>{{ maps.length }}</strong>
            </div>
            <div class="stat-box">
                <span>Map có mob</span>
                <strong>{{ mapsWithMobCount }}</strong>
            </div>
            <div class="stat-box">
                <span>Tổng mob đặt map</span>
                <strong>{{ totalMobPlacements }}</strong>
            </div>
            <div class="stat-box">
                <span>Mob template</span>
                <strong>{{ mobTemplates.length }}</strong>
            </div>
        </div>

        <div class="map-mob-layout">
            <aside class="map-panel">
                <div class="panel-head compact">
                    <div>
                        <h3>Danh sách map</h3>
                        <p>{{ filteredMaps.length }} map phù hợp</p>
                    </div>
                </div>
                <div class="map-filters">
                    <div class="quick-jump">
                        <input
                            v-model="mapJumpId"
                            class="form-input"
                            type="number"
                            placeholder="Nhảy tới map ID"
                            @keyup.enter="jumpToMap"
                        />
                        <button class="btn btn-outline" @click="jumpToMap">
                            <span class="mi" style="font-size: 16px"
                                >near_me</span
                            >
                            Đi
                        </button>
                    </div>
                    <div class="search-input-wrap">
                        <span class="mi search-icon">search</span>
                        <input
                            v-model="search"
                            class="form-input search-input"
                            placeholder="Tìm ID hoặc tên map..."
                        />
                    </div>
                    <div class="filter-stack">
                        <div class="chip-group">
                            <button
                                type="button"
                                :class="{ active: planetFilter === '' }"
                                @click="planetFilter = ''"
                            >
                                Tất cả hành tinh
                            </button>
                            <button
                                type="button"
                                :class="{ active: planetFilter === '0' }"
                                @click="planetFilter = '0'"
                            >
                                Trái đất
                            </button>
                            <button
                                type="button"
                                :class="{ active: planetFilter === '1' }"
                                @click="planetFilter = '1'"
                            >
                                Namec
                            </button>
                            <button
                                type="button"
                                :class="{ active: planetFilter === '2' }"
                                @click="planetFilter = '2'"
                            >
                                Xayda
                            </button>
                        </div>
                        <div class="chip-group">
                            <button
                                type="button"
                                :class="{ active: mobFilter === '' }"
                                @click="mobFilter = ''"
                            >
                                Tất cả map
                            </button>
                            <button
                                type="button"
                                :class="{ active: mobFilter === 'has' }"
                                @click="mobFilter = 'has'"
                            >
                                Có mob
                            </button>
                            <button
                                type="button"
                                :class="{ active: mobFilter === 'empty' }"
                                @click="mobFilter = 'empty'"
                            >
                                Không mob
                            </button>
                        </div>
                    </div>
                </div>
                <div class="map-list">
                    <button
                        v-for="map in filteredMaps"
                        :key="map.id"
                        type="button"
                        class="map-row"
                        :class="{ active: selectedMapId === map.id }"
                        :data-map-id="map.id"
                        @click="selectMap(map)"
                    >
                        <span>
                            <strong>{{ map.name }}</strong>
                            <small
                                >ID {{ map.id }} ·
                                {{ planetName(map.planet_id) }}</small
                            >
                        </span>
                        <em>{{ map.mob_count }} mob</em>
                    </button>
                    <!-- <div v-if="loading" class="admin-loading-block">
                        <div class="admin-loading-spinner"></div>
                    </div> -->
                    <div
                        v-if="!filteredMaps.length && !loading"
                        class="empty-cell"
                    >
                        Không có map phù hợp.
                    </div>
                </div>
            </aside>

            <section class="mob-panel">
                <div v-if="selectedMap" class="panel">
                    <div class="mob-panel-head">
                        <div>
                            <span class="section-label">Map đang chỉnh</span>
                            <h3>{{ selectedMap.name }}</h3>
                            <p>
                                ID {{ selectedMap.id }} ·
                                {{ planetName(selectedMap.planet_id) }} ·
                                {{ selectedMap.zones }} khu runtime ·
                                {{ selectedMap.player_count }} người trong map
                            </p>
                            <p
                                v-if="selectedMap.zones_forced"
                                class="panel-note"
                            >
                                Map type này bị code ép số khu theo loại map.
                                Sửa cấu hình vẫn được lưu DB nhưng runtime hiện
                                tại ưu tiên số khu theo code.
                            </p>
                        </div>
                        <div class="head-actions">
                            <button class="btn btn-outline" @click="addMob">
                                <span class="mi" style="font-size: 16px"
                                    >add</span
                                >
                                Thêm mob
                            </button>
                            <button
                                class="btn btn-primary"
                                :disabled="saving"
                                @click="saveMapMobs"
                            >
                                <span class="mi" style="font-size: 16px"
                                    >save</span
                                >
                                Lưu và reload
                            </button>
                        </div>
                    </div>

                    <div class="map-meta-grid">
                        <div>
                            <span class="form-label">Max/player khu</span>
                            <input
                                v-model.number="mapMaxPlayer"
                                class="form-input"
                                type="number"
                                min="1"
                                max="100"
                            />
                        </div>
                        <div>
                            <span class="form-label">Số khu cấu hình</span>
                            <input
                                v-model.number="mapZonesConfig"
                                class="form-input"
                                type="number"
                                min="1"
                                max="120"
                                :disabled="selectedMap.zones_forced"
                            />
                        </div>
                        <div>
                            <span>Loại map</span>
                            <strong>{{ selectedMap.type }}</strong>
                        </div>
                        <div>
                            <span>Số khu runtime</span>
                            <strong>{{ selectedMap.zones }}</strong>
                        </div>
                        <div>
                            <span>Mob đang sửa</span>
                            <strong>{{ rows.length }}</strong>
                        </div>
                    </div>

                    <div class="map-sections-grid">
                        <button
                            type="button"
                            class="subpanel subpanel-button"
                            @click="openWaypointModal"
                        >
                            <div class="subpanel-head">
                                <h4>Waypoints</h4>
                                <span>{{ waypointRows.length }}</span>
                            </div>
                            <p class="subpanel-summary">
                                {{
                                    waypointRows.length
                                        ? `${waypointRows[0].name || "Waypoint 1"}${waypointRows.length > 1 ? ` và ${waypointRows.length - 1} cổng khác` : ""}`
                                        : "Chưa có waypoint"
                                }}
                            </p>
                            <small class="subpanel-action"
                                >Bấm để xem và sửa</small
                            >
                        </button>

                        <button
                            type="button"
                            class="subpanel subpanel-button"
                            @click="openNpcModal"
                        >
                            <div class="subpanel-head">
                                <h4>NPCs</h4>
                                <span>{{ npcRows.length }}</span>
                            </div>
                            <div
                                v-if="npcRows.length"
                                class="subpanel-avatar-stack"
                            >
                                <img
                                    v-for="npc in npcRows.slice(0, 4)"
                                    :key="`npc-avatar-${npc.local_id}`"
                                    :src="iconSrc(npc.avatar)"
                                    :alt="npc.name || `NPC ${npc.id}`"
                                />
                            </div>
                            <p class="subpanel-summary">
                                {{
                                    npcRows.length
                                        ? `${npcRows[0].name || `NPC ${npcRows[0].id}`}${npcRows.length > 1 ? ` và ${npcRows.length - 1} NPC khác` : ""}`
                                        : "Chưa có NPC"
                                }}
                            </p>
                            <small class="subpanel-action"
                                >Bấm để xem và sửa</small
                            >
                        </button>

                        <button
                            type="button"
                            class="subpanel subpanel-button"
                            @click="openDropRuleModal"
                        >
                            <div class="subpanel-head">
                                <h4>Item drop map</h4>
                                <span>{{ dropRuleRows.length }}</span>
                            </div>
                            <p class="subpanel-summary">
                                {{
                                    dropRuleRows.length
                                        ? `${dropRuleRows[0].item_name || `Item ${dropRuleRows[0].item_id}`}${dropRuleRows.length > 1 ? ` và ${dropRuleRows.length - 1} rule khác` : ""}`
                                        : "Chưa có rule drop map"
                                }}
                            </p>
                            <small class="subpanel-action"
                                >Bấm để xem và sửa</small
                            >
                        </button>

                        <div class="subpanel">
                            <div class="subpanel-head">
                                <h4>Item cố định</h4>
                                <span>{{
                                    selectedMap.fixed_items?.length || 0
                                }}</span>
                            </div>
                            <div
                                v-if="selectedMap.fixed_items?.length"
                                class="subpanel-list"
                            >
                                <div
                                    v-for="(
                                        item, idx
                                    ) in selectedMap.fixed_items"
                                    :key="`fixed-item-${idx}`"
                                    class="subpanel-row"
                                >
                                    <strong
                                        >{{ item.id }} -
                                        {{ item.name || "Item" }}</strong
                                    >
                                    <small>
                                        SL {{ item.quantity }} · X
                                        {{ item.x }} · Y {{ item.y }} · Mỗi khu
                                    </small>
                                </div>
                            </div>
                            <div v-else class="empty-mini">
                                Map này không có item cố định từ code.
                            </div>
                        </div>
                    </div>

                    <div class="mob-table">
                        <div class="mob-table-head">
                            <span>#</span>
                            <span>Mob</span>
                            <span>Level</span>
                            <span>HP</span>
                            <span>Dame %</span>
                            <span>X</span>
                            <span>Y</span>
                            <span>Runtime</span>
                            <span></span>
                        </div>
                        <div
                            v-for="(row, index) in rows"
                            :key="row.local_id"
                            class="mob-row"
                        >
                            <div class="row-index">{{ index }}</div>
                            <div class="mob-select-cell">
                                <button
                                    type="button"
                                    class="template-picker-button"
                                    @click="openMobPicker(index)"
                                >
                                    <span>
                                        <strong>{{
                                            mobLabel(row.temp_id)
                                        }}</strong>
                                        <small>{{
                                            mobTypeText(row.temp_id)
                                        }}</small>
                                    </span>
                                    <span class="mi">search</span>
                                </button>
                            </div>
                            <input
                                v-model.number="row.level"
                                class="form-input"
                                type="number"
                                min="0"
                                max="127"
                            />
                            <input
                                v-model.number="row.hp"
                                class="form-input"
                                type="number"
                                min="1"
                            />
                            <input
                                v-model.number="row.percent_dame"
                                class="form-input"
                                type="number"
                                min="0"
                                max="100"
                                title="Dame % theo mob_template"
                            />
                            <input
                                v-model.number="row.x"
                                class="form-input"
                                type="number"
                                min="0"
                            />
                            <input
                                v-model.number="row.y"
                                class="form-input"
                                type="number"
                                min="0"
                            />
                            <div class="runtime-cell">
                                <span
                                    class="badge"
                                    :class="
                                        row.alive
                                            ? 'badge-success'
                                            : 'badge-danger'
                                    "
                                >
                                    {{ row.alive ? "Sống" : "Chết" }}
                                </span>
                                <small>HP {{ number(row.live_hp) }}</small>
                            </div>
                            <div class="row-actions">
                                <button
                                    class="icon-action"
                                    title="Nhân bản"
                                    @click="cloneMob(index)"
                                >
                                    <span class="mi">content_copy</span>
                                </button>
                                <button
                                    class="icon-action danger"
                                    title="Xóa"
                                    @click="removeMob(index)"
                                >
                                    <span class="mi">delete</span>
                                </button>
                            </div>
                        </div>
                        <div v-if="!rows.length" class="empty-cell">
                            Map này chưa có mob.
                        </div>
                    </div>
                </div>
                <div v-else class="panel empty-state">
                    Chọn một map để chỉnh mob.
                </div>
            </section>
        </div>

        <div
            v-if="mobPicker.open"
            class="picker-overlay"
            @click.self="closeMobPicker"
        >
            <div class="picker-panel">
                <div class="picker-head">
                    <div>
                        <span class="section-label">Chọn mob template</span>
                        <h3>Dòng #{{ mobPicker.rowIndex }}</h3>
                        <p>
                            {{ filteredMobTemplates.length }} template phù hợp
                        </p>
                    </div>
                    <button class="icon-action" @click="closeMobPicker">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div class="picker-search">
                    <div class="search-input-wrap">
                        <span class="mi search-icon">search</span>
                        <input
                            v-model="mobPicker.search"
                            class="form-input search-input"
                            placeholder="Tìm ID hoặc tên mob..."
                            autofocus
                        />
                    </div>
                    <div class="type-filter">
                        <button
                            type="button"
                            :class="{ active: mobPicker.type === '' }"
                            @click="setMobPickerType('')"
                        >
                            Tất cả TYPE
                        </button>
                        <button
                            v-for="type in mobTypes"
                            :key="type"
                            type="button"
                            :class="{
                                active: String(mobPicker.type) === String(type),
                            }"
                            @click="setMobPickerType(type)"
                        >
                            TYPE {{ type }}
                        </button>
                    </div>
                </div>

                <div class="picker-list">
                    <button
                        v-for="mob in filteredMobTemplates"
                        :key="mob.id"
                        type="button"
                        class="picker-row"
                        :class="{
                            active:
                                rows[mobPicker.rowIndex] &&
                                Number(rows[mobPicker.rowIndex].temp_id) ===
                                    Number(mob.id),
                        }"
                        @click="chooseMobTemplate(mob)"
                    >
                        <span>
                            <strong>{{ mob.id }} - {{ mob.name }}</strong>
                            <small>
                                TYPE {{ mob.type }} · HP mẫu
                                {{ number(mob.hp) }} · Dame
                                {{ mob.percent_dame }}% · Tiềm năng
                                {{ mob.percent_tiem_nang }}%
                            </small>
                        </span>
                        <span class="mi">check</span>
                    </button>
                    <div v-if="!filteredMobTemplates.length" class="empty-cell">
                        Không có mob template phù hợp.
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="waypointModalOpen"
            class="picker-overlay"
            @click.self="closeWaypointModal"
        >
            <div class="picker-panel picker-panel-lg">
                <div class="picker-head">
                    <div>
                        <span class="section-label">Waypoints</span>
                        <h3>{{ selectedMap?.name }}</h3>
                        <p>{{ waypointRows.length }} cổng di chuyển</p>
                    </div>
                    <div class="head-actions">
                        <button class="btn btn-outline" @click="addWaypoint">
                            <span class="mi" style="font-size: 16px">add</span>
                            Thêm waypoint
                        </button>
                        <button class="icon-action" @click="closeWaypointModal">
                            <span class="mi">close</span>
                        </button>
                    </div>
                </div>
                <div class="editor-grid">
                    <div
                        v-for="(waypoint, index) in waypointRows"
                        :key="waypoint.local_id"
                        class="editor-card"
                    >
                        <div class="editor-card-head">
                            <strong>{{
                                waypoint.name || `Waypoint ${index + 1}`
                            }}</strong>
                            <button
                                class="icon-action danger"
                                @click="removeWaypoint(index)"
                            >
                                <span class="mi">delete</span>
                            </button>
                        </div>
                        <div class="editor-row-2">
                            <input
                                v-model="waypoint.name"
                                class="form-input"
                                placeholder="Tên hiển thị"
                            />
                            <button
                                class="picker-lite-button"
                                @click="cycleWaypointTarget(index)"
                            >
                                Sang map: {{ mapOptionName(waypoint.go_map) }}
                            </button>
                        </div>
                        <div class="editor-row-4">
                            <input
                                v-model.number="waypoint.min_x"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Min X"
                            />
                            <input
                                v-model.number="waypoint.min_y"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Min Y"
                            />
                            <input
                                v-model.number="waypoint.max_x"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Max X"
                            />
                            <input
                                v-model.number="waypoint.max_y"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Max Y"
                            />
                        </div>
                        <div class="editor-row-3">
                            <input
                                v-model.number="waypoint.go_map"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Map đích"
                            />
                            <input
                                v-model.number="waypoint.go_x"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Go X"
                            />
                            <input
                                v-model.number="waypoint.go_y"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Go Y"
                            />
                        </div>
                        <div class="editor-flags">
                            <label
                                ><input
                                    v-model="waypoint.is_enter"
                                    type="checkbox"
                                />
                                Cổng vào</label
                            >
                            <label
                                ><input
                                    v-model="waypoint.is_offline"
                                    type="checkbox"
                                />
                                Offline</label
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="npcModalOpen"
            class="picker-overlay"
            @click.self="closeNpcModal"
        >
            <div class="picker-panel picker-panel-lg">
                <div class="picker-head">
                    <div>
                        <span class="section-label">NPCs</span>
                        <h3>{{ selectedMap?.name }}</h3>
                        <p>{{ npcRows.length }} NPC trong map</p>
                    </div>
                    <div class="head-actions">
                        <button class="btn btn-outline" @click="addNpc">
                            <span class="mi" style="font-size: 16px">add</span>
                            Thêm NPC
                        </button>
                        <button class="icon-action" @click="closeNpcModal">
                            <span class="mi">close</span>
                        </button>
                    </div>
                </div>
                <div class="editor-grid">
                    <div
                        v-for="(npc, index) in npcRows"
                        :key="npc.local_id"
                        class="editor-card"
                    >
                        <div class="editor-card-head">
                            <div class="npc-card-meta">
                                <img
                                    v-if="
                                        npc.avatar !== null &&
                                        npc.avatar !== undefined
                                    "
                                    :src="iconSrc(npc.avatar)"
                                    :alt="npc.name || `NPC ${npc.id}`"
                                    class="npc-avatar"
                                />
                                <div>
                                    <strong>{{
                                        npc.name || `NPC ${npc.id}`
                                    }}</strong>
                                    <small>ID {{ npc.id }}</small>
                                </div>
                            </div>
                            <button
                                class="icon-action danger"
                                @click="removeNpc(index)"
                            >
                                <span class="mi">delete</span>
                            </button>
                        </div>
                        <button
                            class="template-picker-button"
                            @click="openNpcPicker(index)"
                        >
                            <span>
                                <strong>{{
                                    npc.name || `NPC ${npc.id}`
                                }}</strong>
                                <small>Avatar {{ npc.avatar ?? "-" }}</small>
                            </span>
                            <span class="mi">search</span>
                        </button>
                        <div class="editor-row-2">
                            <input
                                v-model.number="npc.x"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="X"
                            />
                            <input
                                v-model.number="npc.y"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Y"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="dropRuleModalOpen"
            class="picker-overlay"
            @click.self="closeDropRuleModal"
        >
            <div class="picker-panel picker-panel-lg">
                <div class="picker-head">
                    <div>
                        <span class="section-label">Item drop map</span>
                        <h3>{{ selectedMap?.name }}</h3>
                        <p>
                            {{ dropRuleRows.length }} rule cộng thêm sau khi
                            giết mob
                        </p>
                    </div>
                    <div class="head-actions">
                        <button class="btn btn-outline" @click="addDropRule">
                            <span class="mi" style="font-size: 16px">add</span>
                            Thêm rule
                        </button>
                        <button class="icon-action" @click="closeDropRuleModal">
                            <span class="mi">close</span>
                        </button>
                    </div>
                </div>
                <div class="editor-grid">
                    <div
                        v-for="(rule, index) in dropRuleRows"
                        :key="rule.local_id"
                        class="editor-card"
                    >
                        <div class="editor-card-head">
                            <div class="npc-card-meta">
                                <img
                                    v-if="
                                        rule.icon_id !== null &&
                                        rule.icon_id !== undefined
                                    "
                                    :src="iconSrc(rule.icon_id)"
                                    :alt="
                                        rule.item_name || `Item ${rule.item_id}`
                                    "
                                    class="npc-avatar"
                                />
                                <div>
                                    <strong>{{
                                        rule.item_name || `Item ${rule.item_id}`
                                    }}</strong>
                                    <small>ID {{ rule.item_id }}</small>
                                </div>
                            </div>
                            <button
                                class="icon-action danger"
                                @click="removeDropRule(index)"
                            >
                                <span class="mi">delete</span>
                            </button>
                        </div>
                        <button
                            class="template-picker-button"
                            @click="openItemPicker(index)"
                        >
                            <span>
                                <strong>{{
                                    rule.item_name || `Item ${rule.item_id}`
                                }}</strong>
                                <small>{{ rule.mob_name || "Mọi mob" }}</small>
                            </span>
                            <span class="mi">search</span>
                        </button>
                        <div class="editor-row-4">
                            <input
                                v-model.number="rule.quantity_min"
                                class="form-input"
                                type="number"
                                min="1"
                                placeholder="SL min"
                            />
                            <input
                                v-model.number="rule.quantity_max"
                                class="form-input"
                                type="number"
                                min="1"
                                placeholder="SL max"
                            />
                            <input
                                v-model.number="rule.chance_numerator"
                                class="form-input"
                                type="number"
                                min="0"
                                placeholder="Tử số"
                            />
                            <input
                                v-model.number="rule.chance_denominator"
                                class="form-input"
                                type="number"
                                min="1"
                                placeholder="Mẫu số"
                            />
                        </div>
                        <div class="editor-row-2">
                            <input
                                v-model.number="rule.mob_temp_id"
                                class="form-input"
                                type="number"
                                min="-1"
                                placeholder="Mob temp (-1 mọi mob)"
                            />
                            <input
                                v-model="rule.note"
                                class="form-input"
                                placeholder="Ghi chú"
                            />
                        </div>
                        <div class="editor-flags">
                            <label
                                ><input v-model="rule.active" type="checkbox" />
                                Đang bật</label
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="npcPicker.open"
            class="picker-overlay"
            @click.self="closeNpcPicker"
        >
            <div class="picker-panel">
                <div class="picker-head">
                    <div>
                        <span class="section-label">Chọn NPC template</span>
                        <h3>Dòng #{{ npcPicker.rowIndex }}</h3>
                    </div>
                    <button class="icon-action" @click="closeNpcPicker">
                        <span class="mi">close</span>
                    </button>
                </div>
                <div class="picker-search">
                    <div class="search-input-wrap">
                        <span class="mi search-icon">search</span>
                        <input
                            v-model="npcPicker.search"
                            class="form-input search-input"
                            placeholder="Tìm ID hoặc tên NPC..."
                            autofocus
                        />
                    </div>
                </div>
                <div class="picker-list">
                    <button
                        v-for="npc in filteredNpcTemplates"
                        :key="npc.id"
                        type="button"
                        class="picker-row"
                        @click="chooseNpcTemplate(npc)"
                    >
                        <span class="npc-card-meta">
                            <img
                                v-if="
                                    npc.avatar !== null &&
                                    npc.avatar !== undefined
                                "
                                :src="iconSrc(npc.avatar)"
                                :alt="npc.name"
                                class="npc-avatar"
                            />
                            <span>
                                <strong>{{ npc.id }} - {{ npc.name }}</strong>
                                <small>Avatar {{ npc.avatar ?? "-" }}</small>
                            </span>
                        </span>
                        <span class="mi">check</span>
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="itemPicker.open"
            class="picker-overlay"
            @click.self="closeItemPicker"
        >
            <div class="picker-panel">
                <div class="picker-head">
                    <div>
                        <span class="section-label">Chọn item drop</span>
                        <h3>Dòng #{{ itemPicker.rowIndex }}</h3>
                    </div>
                    <button class="icon-action" @click="closeItemPicker">
                        <span class="mi">close</span>
                    </button>
                </div>
                <div class="picker-search">
                    <div class="search-input-wrap">
                        <span class="mi search-icon">search</span>
                        <input
                            v-model="itemPicker.search"
                            class="form-input search-input"
                            placeholder="Tìm ID hoặc tên item..."
                            autofocus
                        />
                    </div>
                    <div class="type-filter">
                        <button
                            type="button"
                            :class="{ active: itemPicker.type === '' }"
                            @click="itemPicker.type = ''"
                        >
                            Tất cả TYPE
                        </button>
                        <button
                            v-for="type in itemTypes"
                            :key="type"
                            type="button"
                            :class="{
                                active:
                                    String(itemPicker.type) === String(type),
                            }"
                            @click="itemPicker.type = type"
                        >
                            TYPE {{ type }}
                        </button>
                    </div>
                </div>
                <div class="picker-list">
                    <button
                        v-for="item in filteredItemTemplates"
                        :key="item.id"
                        type="button"
                        class="picker-row"
                        @click="chooseItemTemplate(item)"
                    >
                        <span class="npc-card-meta">
                            <img
                                v-if="
                                    item.icon_id !== null &&
                                    item.icon_id !== undefined
                                "
                                :src="iconSrc(item.icon_id)"
                                :alt="item.name"
                                class="npc-avatar"
                            />
                            <span>
                                <strong>{{ item.id }} - {{ item.name }}</strong>
                                <small>TYPE {{ item.type }}</small>
                            </span>
                        </span>
                        <span class="mi">check</span>
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
            maps: [],
            mobTemplates: [],
            mapOptions: [],
            npcTemplates: [],
            itemTemplates: [],
            rows: [],
            waypointRows: [],
            npcRows: [],
            dropRuleRows: [],
            selectedMapId: null,
            mapMaxPlayer: 12,
            mapZonesConfig: 1,
            mapJumpId: "",
            search: "",
            planetFilter: "",
            mobFilter: "has",
            waypointModalOpen: false,
            npcModalOpen: false,
            dropRuleModalOpen: false,
            mobPicker: {
                open: false,
                rowIndex: null,
                search: "",
                type: "",
            },
            npcPicker: {
                open: false,
                rowIndex: null,
                search: "",
            },
            itemPicker: {
                open: false,
                rowIndex: null,
                search: "",
                type: "",
            },
            loading: false,
            saving: false,
            error: "",
            success: "",
        };
    },
    computed: {
        selectedMap() {
            return (
                this.maps.find((map) => map.id === this.selectedMapId) || null
            );
        },
        filteredMaps() {
            const q = this.search.trim().toLowerCase();
            return this.maps.filter((map) => {
                if (
                    this.planetFilter !== "" &&
                    Number(map.planet_id) !== Number(this.planetFilter)
                ) {
                    return false;
                }
                if (this.mobFilter === "has" && Number(map.mob_count) <= 0) {
                    return false;
                }
                if (this.mobFilter === "empty" && Number(map.mob_count) > 0) {
                    return false;
                }
                if (!q) return true;
                return (
                    String(map.id).includes(q) ||
                    String(map.name || "")
                        .toLowerCase()
                        .includes(q)
                );
            });
        },
        mapsWithMobCount() {
            return this.maps.filter((map) => Number(map.mob_count) > 0).length;
        },
        totalMobPlacements() {
            return this.maps.reduce(
                (total, map) => total + Number(map.mob_count || 0),
                0,
            );
        },
        mobTypes() {
            return [
                ...new Set(this.mobTemplates.map((mob) => Number(mob.type))),
            ]
                .filter((type) => Number.isFinite(type))
                .sort((a, b) => a - b);
        },
        filteredMobTemplates() {
            const q = this.mobPicker.search.trim().toLowerCase();
            return this.mobTemplates.filter((mob) => {
                if (
                    this.mobPicker.type !== "" &&
                    Number(mob.type) !== Number(this.mobPicker.type)
                ) {
                    return false;
                }
                if (!q) return true;
                return (
                    String(mob.id).includes(q) ||
                    String(mob.name || "")
                        .toLowerCase()
                        .includes(q)
                );
            });
        },
        filteredNpcTemplates() {
            const q = this.npcPicker.search.trim().toLowerCase();
            return this.npcTemplates.filter((npc) => {
                if (!q) return true;
                return (
                    String(npc.id).includes(q) ||
                    String(npc.name || "")
                        .toLowerCase()
                        .includes(q)
                );
            });
        },
        itemTypes() {
            return [
                ...new Set(this.itemTemplates.map((item) => Number(item.type))),
            ]
                .filter((type) => Number.isFinite(type))
                .sort((a, b) => a - b);
        },
        filteredItemTemplates() {
            const q = this.itemPicker.search.trim().toLowerCase();
            return this.itemTemplates.filter((item) => {
                if (
                    this.itemPicker.type !== "" &&
                    Number(item.type) !== Number(this.itemPicker.type)
                ) {
                    return false;
                }
                if (!q) return true;
                return (
                    String(item.id).includes(q) ||
                    String(item.name || "")
                        .toLowerCase()
                        .includes(q)
                );
            });
        },
    },
    watch: {
        selectedMapId() {
            this.syncRows();
        },
    },
    created() {
        this.loadData();
    },
    methods: {
        token() {
            return document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
        },
        number(value) {
            return Number(value || 0).toLocaleString("vi-VN");
        },
        iconSrc(id) {
            return id === null || id === undefined || Number(id) < 0
                ? ""
                : `/assets/frontend/home/v1/images/x4/${id}.png`;
        },
        planetName(id) {
            return (
                {
                    0: "Trái đất",
                    1: "Namec",
                    2: "Xayda",
                }[Number(id)] || `Planet ${id}`
            );
        },
        mobTemplate(id) {
            return this.mobTemplates.find((mob) => mob.id === Number(id));
        },
        mobLabel(id) {
            const mob = this.mobTemplate(id);
            return mob ? `${mob.id} - ${mob.name}` : "Chưa chọn mob";
        },
        mobTypeText(id) {
            const mob = this.mobTemplate(id);
            if (!mob) return "Chưa chọn template";
            return `TYPE ${mob.type} · HP mẫu ${this.number(mob.hp)} · Dame ${mob.percent_dame}%`;
        },
        localRow(row = {}) {
            return {
                local_id: `${Date.now()}-${Math.random()}`,
                temp_id: Number(row.temp_id ?? this.mobTemplates[0]?.id ?? 0),
                level: Number(row.level ?? 1),
                hp: Number(row.hp ?? this.mobTemplate(row.temp_id)?.hp ?? 1),
                percent_dame: Number(
                    row.percent_dame ??
                        this.mobTemplate(row.temp_id)?.percent_dame ??
                        0,
                ),
                x: Number(row.x ?? 0),
                y: Number(row.y ?? 0),
                live_hp: Number(row.live_hp ?? 0),
                live_status: Number(row.live_status ?? -1),
                alive: !!row.alive,
            };
        },
        localWaypoint(row = {}) {
            return {
                local_id: `${Date.now()}-${Math.random()}`,
                name: row.name || "",
                min_x: Number(row.min_x ?? 0),
                min_y: Number(row.min_y ?? 0),
                max_x: Number(row.max_x ?? 0),
                max_y: Number(row.max_y ?? 0),
                is_enter: !!row.is_enter,
                is_offline: !!row.is_offline,
                go_map: Number(row.go_map ?? 0),
                go_x: Number(row.go_x ?? 0),
                go_y: Number(row.go_y ?? 0),
            };
        },
        localNpc(row = {}) {
            return {
                local_id: `${Date.now()}-${Math.random()}`,
                id: Number(row.id ?? 0),
                name: row.name || "",
                avatar: row.avatar ?? null,
                head: row.head ?? null,
                body: row.body ?? null,
                leg: row.leg ?? null,
                x: Number(row.x ?? 0),
                y: Number(row.y ?? 0),
            };
        },
        localDropRule(row = {}) {
            return {
                local_id: `${Date.now()}-${Math.random()}`,
                item_id: Number(row.item_id ?? 0),
                item_name: row.item_name || "",
                icon_id: row.icon_id ?? null,
                quantity_min: Number(row.quantity_min ?? 1),
                quantity_max: Number(row.quantity_max ?? 1),
                chance_numerator: Number(row.chance_numerator ?? 1),
                chance_denominator: Number(row.chance_denominator ?? 100),
                mob_temp_id:
                    row.mob_temp_id === null ||
                    row.mob_temp_id === undefined ||
                    Number(row.mob_temp_id) < 0
                        ? -1
                        : Number(row.mob_temp_id),
                mob_name: row.mob_name || "",
                active: row.active !== false,
                note: row.note || "",
                options: Array.isArray(row.options) ? row.options : [],
            };
        },
        mapOptionName(id) {
            const map = this.mapOptions.find(
                (entry) => Number(entry.id) === Number(id),
            );
            return map ? `${map.id} - ${map.name}` : `Map ${id}`;
        },
        async loadData() {
            this.loading = true;
            this.error = "";
            try {
                const res = await fetch("/admin/api/runtime/map-mobs", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được map mob");
                }
                this.maps = (data.data?.maps || []).map((map) => ({
                    ...map,
                    id: Number(map.id),
                    max_player: Number(map.max_player || 1),
                    zones: Number(map.zones || 0),
                    zones_config: Number(map.zones_config || map.zones || 0),
                    zones_forced: !!map.zones_forced,
                    waypoints: Array.isArray(map.waypoints)
                        ? map.waypoints
                        : [],
                    npcs: Array.isArray(map.npcs) ? map.npcs : [],
                    fixed_items: Array.isArray(map.fixed_items)
                        ? map.fixed_items
                        : [],
                    drop_rules: Array.isArray(map.drop_rules)
                        ? map.drop_rules
                        : [],
                    mobs: Array.isArray(map.mobs) ? map.mobs : [],
                }));
                this.mapOptions = Array.isArray(data.data?.map_options)
                    ? data.data.map_options
                    : [];
                this.npcTemplates = Array.isArray(data.data?.npc_templates)
                    ? data.data.npc_templates
                    : [];
                this.itemTemplates = Array.isArray(data.data?.item_templates)
                    ? data.data.item_templates
                    : [];
                this.mobTemplates = (data.data?.mob_templates || []).map(
                    (mob) => ({
                        ...mob,
                        id: Number(mob.id),
                    }),
                );
                if (
                    !this.selectedMapId ||
                    !this.maps.some((map) => map.id === this.selectedMapId)
                ) {
                    const first = this.filteredMaps[0] || this.maps[0];
                    this.selectedMapId = first?.id ?? null;
                } else {
                    this.syncRows();
                }
            } catch (e) {
                this.error = e?.message || "Không tải được map mob";
            } finally {
                this.loading = false;
            }
        },
        selectMap(map) {
            this.selectedMapId = map.id;
            this.success = "";
            this.error = "";
            this.scrollMapIntoView(map.id);
        },
        jumpToMap() {
            const id = Number(this.mapJumpId);
            if (!Number.isFinite(id)) return;
            const map = this.maps.find((item) => Number(item.id) === id);
            if (!map) {
                this.error = `Không tìm thấy map ID ${this.mapJumpId}`;
                return;
            }
            this.search = "";
            this.planetFilter = "";
            this.mobFilter = "";
            this.selectMap(map);
        },
        scrollMapIntoView(id) {
            this.$nextTick(() => {
                document
                    .querySelector(`[data-map-id="${id}"]`)
                    ?.scrollIntoView({ block: "center" });
            });
        },
        syncRows() {
            const map = this.selectedMap;
            this.mapMaxPlayer = Number(map?.max_player ?? 12);
            this.mapZonesConfig = Number(map?.zones_config ?? map?.zones ?? 1);
            this.rows = map ? map.mobs.map((row) => this.localRow(row)) : [];
            this.waypointRows = map
                ? (map.waypoints || []).map((row) => this.localWaypoint(row))
                : [];
            this.npcRows = map
                ? (map.npcs || []).map((row) => this.localNpc(row))
                : [];
            this.dropRuleRows = map
                ? (map.drop_rules || []).map((row) => this.localDropRule(row))
                : [];
        },
        applyTemplateDefaults(row) {
            const mob = this.mobTemplate(row.temp_id);
            if (mob && (!row.hp || row.hp <= 1)) {
                row.hp = Number(mob.hp || 1);
            }
            row.percent_dame = Number(mob?.percent_dame || 0);
        },
        openMobPicker(index) {
            this.mobPicker = {
                open: true,
                rowIndex: index,
                search: "",
                type: "",
            };
        },
        closeMobPicker() {
            this.mobPicker.open = false;
            this.mobPicker.rowIndex = null;
        },
        setMobPickerType(type) {
            this.mobPicker.type = type;
        },
        chooseMobTemplate(mob) {
            const row = this.rows[this.mobPicker.rowIndex];
            if (!row) return;
            row.temp_id = Number(mob.id);
            if (!row.hp || row.hp <= 1) {
                row.hp = Number(mob.hp || 1);
            }
            row.percent_dame = Number(mob.percent_dame || 0);
            this.closeMobPicker();
        },
        openWaypointModal() {
            this.waypointModalOpen = true;
        },
        closeWaypointModal() {
            this.waypointModalOpen = false;
        },
        addWaypoint() {
            const last = this.waypointRows[this.waypointRows.length - 1];
            this.waypointRows.push(
                this.localWaypoint({
                    name: "",
                    min_x: last?.min_x ?? 0,
                    min_y: last?.min_y ?? 0,
                    max_x: last?.max_x ?? 24,
                    max_y: last?.max_y ?? 24,
                    go_map: last?.go_map ?? this.selectedMap?.id ?? 0,
                    go_x: last?.go_x ?? 60,
                    go_y: last?.go_y ?? 336,
                }),
            );
        },
        removeWaypoint(index) {
            this.waypointRows.splice(index, 1);
        },
        cycleWaypointTarget(index) {
            const row = this.waypointRows[index];
            if (!row || !this.mapOptions.length) return;
            const currentIndex = this.mapOptions.findIndex(
                (map) => Number(map.id) === Number(row.go_map),
            );
            const next =
                this.mapOptions[
                    (currentIndex + 1 + this.mapOptions.length) %
                        this.mapOptions.length
                ];
            row.go_map = Number(next.id);
        },
        openNpcModal() {
            this.npcModalOpen = true;
        },
        closeNpcModal() {
            this.npcModalOpen = false;
        },
        addNpc() {
            const first = this.npcTemplates[0] || {};
            this.npcRows.push(
                this.localNpc({
                    id: first.id ?? 0,
                    name: first.name ?? "",
                    avatar: first.avatar ?? null,
                    head: first.head ?? null,
                    body: first.body ?? null,
                    leg: first.leg ?? null,
                    x: 100,
                    y: 100,
                }),
            );
        },
        removeNpc(index) {
            this.npcRows.splice(index, 1);
        },
        openNpcPicker(index) {
            this.npcPicker = {
                open: true,
                rowIndex: index,
                search: "",
            };
        },
        closeNpcPicker() {
            this.npcPicker.open = false;
            this.npcPicker.rowIndex = null;
        },
        chooseNpcTemplate(npc) {
            const row = this.npcRows[this.npcPicker.rowIndex];
            if (!row) return;
            row.id = Number(npc.id);
            row.name = npc.name || "";
            row.avatar = npc.avatar ?? null;
            row.head = npc.head ?? null;
            row.body = npc.body ?? null;
            row.leg = npc.leg ?? null;
            this.closeNpcPicker();
        },
        openDropRuleModal() {
            this.dropRuleModalOpen = true;
        },
        closeDropRuleModal() {
            this.dropRuleModalOpen = false;
        },
        addDropRule() {
            const first = this.itemTemplates[0] || {};
            this.dropRuleRows.push(
                this.localDropRule({
                    item_id: first.id ?? 0,
                    item_name: first.name ?? "",
                    icon_id: first.icon_id ?? null,
                    quantity_min: 1,
                    quantity_max: 1,
                    chance_numerator: 1,
                    chance_denominator: 100,
                    mob_temp_id: -1,
                    active: true,
                    note: "",
                    options: [],
                }),
            );
        },
        removeDropRule(index) {
            this.dropRuleRows.splice(index, 1);
        },
        openItemPicker(index) {
            this.itemPicker = {
                open: true,
                rowIndex: index,
                search: "",
                type: "",
            };
        },
        closeItemPicker() {
            this.itemPicker.open = false;
            this.itemPicker.rowIndex = null;
        },
        chooseItemTemplate(item) {
            const row = this.dropRuleRows[this.itemPicker.rowIndex];
            if (!row) return;
            row.item_id = Number(item.id);
            row.item_name = item.name || "";
            row.icon_id = item.icon_id ?? null;
            this.closeItemPicker();
        },
        addMob() {
            const last = this.rows[this.rows.length - 1];
            this.rows.push(
                this.localRow({
                    temp_id: last?.temp_id ?? this.mobTemplates[0]?.id ?? 0,
                    level: last?.level ?? 1,
                    hp: last?.hp ?? this.mobTemplates[0]?.hp ?? 1,
                    percent_dame:
                        last?.percent_dame ??
                        this.mobTemplates[0]?.percent_dame ??
                        0,
                    x: last ? Number(last.x) + 48 : 100,
                    y: last?.y ?? 100,
                    alive: true,
                }),
            );
        },
        cloneMob(index) {
            const row = this.rows[index];
            if (!row) return;
            this.rows.splice(
                index + 1,
                0,
                this.localRow({
                    ...row,
                    x: Number(row.x || 0) + 24,
                }),
            );
        },
        removeMob(index) {
            this.rows.splice(index, 1);
        },
        async saveMapMobs() {
            if (!this.selectedMap) return;
            this.saving = true;
            this.error = "";
            this.success = "";
            const payload = {
                map_id: this.selectedMap.id,
                mobs: this.rows.map((row) => ({
                    temp_id: Number(row.temp_id),
                    level: Number(row.level || 0),
                    hp: Number(row.hp || 1),
                    percent_dame: Number(row.percent_dame || 0),
                    x: Number(row.x || 0),
                    y: Number(row.y || 0),
                })),
                max_player: Number(this.mapMaxPlayer || 1),
                zones: Number(this.mapZonesConfig || 1),
                waypoints: this.waypointRows.map((row) => ({
                    name: row.name || "",
                    min_x: Number(row.min_x || 0),
                    min_y: Number(row.min_y || 0),
                    max_x: Number(row.max_x || 0),
                    max_y: Number(row.max_y || 0),
                    is_enter: !!row.is_enter,
                    is_offline: !!row.is_offline,
                    go_map: Number(row.go_map || 0),
                    go_x: Number(row.go_x || 0),
                    go_y: Number(row.go_y || 0),
                })),
                npcs: this.npcRows.map((row) => ({
                    id: Number(row.id || 0),
                    x: Number(row.x || 0),
                    y: Number(row.y || 0),
                })),
                drop_rules: this.dropRuleRows.map((row) => ({
                    item_id: Number(row.item_id || 0),
                    quantity_min: Number(row.quantity_min || 1),
                    quantity_max: Number(row.quantity_max || 1),
                    chance_numerator: Number(row.chance_numerator || 0),
                    chance_denominator: Number(row.chance_denominator || 1),
                    mob_temp_id:
                        Number(row.mob_temp_id) >= 0
                            ? Number(row.mob_temp_id)
                            : null,
                    active: !!row.active,
                    note: row.note || "",
                    options: Array.isArray(row.options) ? row.options : [],
                })),
            };
            try {
                const res = await fetch("/admin/api/runtime/map-mobs", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.token(),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Lưu map mob thất bại");
                }
                const updated = data.data?.map;
                if (updated) {
                    const normalized = {
                        ...updated,
                        id: Number(updated.id),
                        max_player: Number(updated.max_player || 1),
                        zones: Number(updated.zones || 0),
                        zones_config: Number(
                            updated.zones_config || updated.zones || 0,
                        ),
                        zones_forced: !!updated.zones_forced,
                        waypoints: Array.isArray(updated.waypoints)
                            ? updated.waypoints
                            : [],
                        npcs: Array.isArray(updated.npcs) ? updated.npcs : [],
                        fixed_items: Array.isArray(updated.fixed_items)
                            ? updated.fixed_items
                            : [],
                        drop_rules: Array.isArray(updated.drop_rules)
                            ? updated.drop_rules
                            : [],
                        mobs: Array.isArray(updated.mobs) ? updated.mobs : [],
                    };
                    const idx = this.maps.findIndex(
                        (map) => map.id === normalized.id,
                    );
                    if (idx >= 0) {
                        this.maps.splice(idx, 1, normalized);
                    }
                    this.selectedMapId = normalized.id;
                    this.syncRows();
                } else {
                    await this.loadData();
                }
                this.success = data.message || "Đã lưu và reload mob";
            } catch (e) {
                this.error = e?.message || "Lưu map mob thất bại";
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped>
.map-mob-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top,
.map-mob-stats,
.map-mob-layout,
.panel-head,
.mob-panel-head,
.head-actions,
.map-row,
.runtime-cell,
.row-actions {
    display: flex;
}
.page-top,
.panel-head,
.mob-panel-head,
.map-row {
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.page-title {
    margin: 0;
    color: var(--ds-text-emphasis);
    font-size: 24px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 13px;
    margin-top: 6px;
}
.breadcrumb a {
    color: var(--ds-primary);
    text-decoration: none;
}
.alert {
    border-radius: 10px;
    padding: 12px 14px;
    font-weight: 700;
}
.alert-error {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
    border: 1px solid rgba(var(--ds-danger-rgb), 0.25);
}
.alert-success {
    background: rgba(var(--ds-success-rgb), 0.12);
    color: var(--ds-success);
    border: 1px solid rgba(var(--ds-success-rgb), 0.25);
}
.map-mob-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.stat-box,
.panel,
.map-panel {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface);
    border-radius: 12px;
    box-shadow: var(--ds-shadow-sm);
}
.stat-box {
    padding: 16px;
}
.stat-box span,
.map-meta-grid span,
.section-label {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 800;
}
.stat-box strong {
    display: block;
    color: var(--ds-text-emphasis);
    font-size: 26px;
    margin-top: 6px;
}
.map-mob-layout {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}
.map-panel {
    position: sticky;
    top: 84px;
    padding: 14px;
    max-height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.panel {
    padding: 18px;
}
.panel-head h3,
.mob-panel-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.panel-head p,
.mob-panel-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.panel-note {
    margin-top: 6px !important;
    color: var(--ds-warning);
}
.map-filters {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.quick-jump {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 72px;
    gap: 8px;
}
.quick-jump .btn {
    height: 38px;
    justify-content: center;
}
.form-row-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.filter-stack {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.chip-group button {
    border: 1px solid var(--ds-border);
    border-radius: 999px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    cursor: pointer;
    font-weight: 700;
    padding: 8px 12px;
}
.chip-group button.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}
.search-input-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    width: 16px;
    font-size: 16px;
    line-height: 1;
    pointer-events: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.search-input {
    padding-left: 46px;
}
.form-input {
    width: 100%;
    min-width: 0;
    height: 38px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-input-bg);
    color: var(--ds-text);
    padding: 0 10px;
    outline: none;
}
select.form-input {
    color-scheme: dark;
}
:global(.admin-app.theme-light) select.form-input {
    color-scheme: light;
}
select.form-input option {
    background: var(--ds-surface);
    color: var(--ds-text);
}
.form-input:focus {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.13);
}
.map-list {
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-right: 4px;
}
.map-row {
    width: 100%;
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    color: var(--ds-text);
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    text-align: left;
}
.map-row.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.13);
}
.map-row strong,
.map-row small {
    display: block;
}
.map-row small {
    color: var(--ds-text-muted);
    margin-top: 2px;
}
.map-row em {
    font-style: normal;
    color: var(--ds-primary);
    font-weight: 800;
    white-space: nowrap;
}
.mob-panel {
    min-width: 0;
}
.head-actions {
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.section-label {
    color: var(--ds-primary);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 5px;
}
.map-meta-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin: 16px 0;
}
.map-meta-grid div {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    border-radius: 10px;
    padding: 10px;
}
.map-meta-grid strong {
    display: block;
    color: var(--ds-text-emphasis);
    margin-top: 4px;
}
.meta-input-wrap {
    margin-top: 6px;
}
.map-sections-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.subpanel {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    border-radius: 10px;
    padding: 12px;
    min-width: 0;
}
.subpanel-button {
    text-align: left;
    cursor: pointer;
}
.subpanel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}
.subpanel-head h4 {
    margin: 0;
    color: var(--ds-text-emphasis);
    font-size: 14px;
}
.subpanel-head span {
    color: var(--ds-primary);
    font-size: 12px;
    font-weight: 800;
}
.subpanel-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 220px;
    overflow: auto;
    padding-right: 4px;
}
.subpanel-row {
    border: 1px solid rgba(var(--ds-primary-rgb), 0.12);
    background: rgba(var(--ds-primary-rgb), 0.05);
    border-radius: 8px;
    padding: 9px 10px;
}
.subpanel-row strong,
.subpanel-row small {
    display: block;
}
.subpanel-row strong {
    color: var(--ds-text-emphasis);
}
.subpanel-row small,
.empty-mini {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.subpanel-summary {
    margin: 0;
    color: var(--ds-text-emphasis);
    font-weight: 700;
    line-height: 1.5;
}
.subpanel-action {
    display: inline-block;
    margin-top: 8px;
    color: var(--ds-primary);
    font-size: 12px;
    font-weight: 700;
}
.subpanel-avatar-stack {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.subpanel-avatar-stack img {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    background: rgba(var(--ds-primary-rgb), 0.08);
    object-fit: cover;
    margin-left: -8px;
}
.subpanel-avatar-stack img:first-child {
    margin-left: 0;
}
.mob-table {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.mob-table-head,
.mob-row {
    display: grid;
    grid-template-columns:
        42px minmax(240px, 1.5fr)
        78px 110px 110px 95px 95px 105px 88px;
    gap: 8px;
    align-items: center;
}
.mob-table-head {
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0 10px;
}
.mob-row {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    border-radius: 10px;
    padding: 10px;
}
.row-index {
    color: var(--ds-text-muted);
    font-weight: 800;
}
.mob-select-cell {
    min-width: 0;
}
.template-picker-button {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--ds-border);
    border-radius: 9px;
    background: var(--ds-input-bg);
    color: var(--ds-text);
    padding: 7px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    text-align: left;
    cursor: pointer;
}
.template-picker-button:hover,
.template-picker-button:focus {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.12);
}
.template-picker-button span:first-child {
    min-width: 0;
}
.template-picker-button strong {
    display: block;
    color: var(--ds-text-emphasis);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.template-picker-button .mi {
    flex: 0 0 auto;
    color: var(--ds-primary);
    font-size: 18px;
}
.mob-select-cell small,
.runtime-cell small {
    display: block;
    color: var(--ds-text-muted);
    font-size: 11px;
    margin-top: 4px;
}
.runtime-cell {
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}
.badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 800;
}
.badge-success {
    color: var(--ds-success);
    background: rgba(var(--ds-success-rgb), 0.13);
}
.badge-danger {
    color: var(--ds-danger);
    background: rgba(var(--ds-danger-rgb), 0.13);
}
.row-actions {
    justify-content: flex-end;
    gap: 6px;
}
.icon-action {
    width: 34px;
    height: 34px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    color: var(--ds-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.icon-action.danger {
    color: var(--ds-danger);
}
.empty-cell,
.empty-state {
    color: var(--ds-text-muted);
    text-align: center;
    padding: 24px;
}
.picker-overlay {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(4, 8, 12, 0.66);
    backdrop-filter: blur(4px);
}
.picker-panel {
    width: min(860px, calc(100vw - 32px));
    max-height: min(760px, calc(100vh - 48px));
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-lg, 0 24px 70px rgba(0, 0, 0, 0.45));
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px;
    overflow: hidden;
}
.picker-panel-lg {
    width: min(1120px, calc(100vw - 32px));
}
.picker-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}
.picker-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.picker-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.picker-search {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.type-filter {
    display: flex;
    gap: 8px;
    overflow: auto;
    padding-bottom: 2px;
}
.type-filter button {
    border: 1px solid var(--ds-border);
    border-radius: 999px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    cursor: pointer;
    font-weight: 800;
    padding: 7px 11px;
    white-space: nowrap;
}
.type-filter button.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}
.picker-list {
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-right: 4px;
}
.picker-row {
    width: 100%;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 12px;
    text-align: left;
}
.picker-row:hover,
.picker-row.active {
    border-color: rgba(var(--ds-primary-rgb), 0.7);
    background: rgba(var(--ds-primary-rgb), 0.13);
}
.picker-row strong,
.picker-row small {
    display: block;
}
.picker-row strong {
    color: var(--ds-text-emphasis);
}
.picker-row small {
    color: var(--ds-text-muted);
    margin-top: 3px;
}
.picker-row .mi {
    color: var(--ds-primary);
    opacity: 0;
}
.picker-row.active .mi {
    opacity: 1;
}
.editor-grid {
    overflow: auto;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding-right: 4px;
}
.editor-card {
    border: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.editor-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.editor-row-2,
.editor-row-3,
.editor-row-4 {
    display: grid;
    gap: 8px;
}
.editor-row-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.editor-row-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.editor-row-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.editor-flags {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.editor-flags label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.npc-card-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.npc-card-meta strong,
.npc-card-meta small {
    display: block;
}
.npc-card-meta strong {
    color: var(--ds-text-emphasis);
}
.npc-card-meta small {
    color: var(--ds-text-muted);
}
.npc-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    background: rgba(var(--ds-primary-rgb), 0.08);
    object-fit: cover;
    flex: 0 0 auto;
}
.picker-lite-button {
    border: 1px solid var(--ds-border);
    border-radius: 9px;
    background: var(--ds-input-bg);
    color: var(--ds-text);
    min-height: 38px;
    padding: 0 12px;
    text-align: left;
    cursor: pointer;
}
@media (max-width: 1280px) {
    .map-mob-layout {
        grid-template-columns: 1fr;
    }
    .map-panel {
        position: static;
        max-height: 420px;
    }
    .editor-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 980px) {
    .map-mob-stats,
    .map-meta-grid,
    .map-sections-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .mob-table-head {
        display: none;
    }
    .mob-row {
        grid-template-columns: 42px minmax(0, 1fr) repeat(
                2,
                minmax(90px, 0.4fr)
            );
    }
    .editor-row-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .runtime-cell,
    .row-actions {
        grid-column: span 2;
    }
}
@media (max-width: 640px) {
    .map-mob-stats,
    .map-meta-grid,
    .map-sections-grid,
    .form-row-2,
    .mob-row {
        grid-template-columns: 1fr;
    }
    .editor-row-2,
    .editor-row-3,
    .editor-row-4 {
        grid-template-columns: 1fr;
    }
    .page-top,
    .mob-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }
    .runtime-cell,
    .row-actions {
        grid-column: span 1;
    }
}
</style>
