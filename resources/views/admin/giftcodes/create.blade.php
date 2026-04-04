@extends('admin.layout')
@section('title', 'Tạo Giftcode')

@section('styles')
<style>
    .item-editor {
        margin-top: 8px;
    }

    .item-search-wrap {
        position: relative;
    }

    .item-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 20;
        background: #1c2940;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 8px;
        max-height: 280px;
        overflow-y: auto;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
        display: none;
    }

    .item-search-results.show {
        display: block;
    }

    .item-result {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        cursor: pointer;
        transition: background .15s;
        font-size: 13px;
        color: #c8d8e4;
    }

    .item-result:hover {
        background: rgba(93, 135, 255, .1);
    }

    .item-result img {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        background: rgba(255, 255, 255, .05);
    }

    .item-result .item-id {
        color: #5a6a85;
        font-size: 11px;
    }

    .item-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .item-card {
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 10px;
        padding: 16px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .item-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .05);
        flex-shrink: 0;
    }

    .item-card-body {
        flex: 1;
        min-width: 0;
    }

    .item-card-name {
        font-size: 14px;
        font-weight: 600;
        color: #e4ecf4;
        margin-bottom: 8px;
    }

    .item-card-name .item-card-id {
        font-weight: 400;
        color: #5a6a85;
        font-size: 12px;
        margin-left: 6px;
    }

    .item-card-row {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .item-card-remove {
        background: none;
        border: none;
        color: #fa896b;
        cursor: pointer;
        font-size: 20px;
        padding: 4px;
        line-height: 1;
        transition: color .15s;
    }

    .item-card-remove:hover {
        color: #f3704d;
    }

    .option-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 6px;
    }

    .option-row select {
        max-width: 220px;
    }

    .option-row input {
        max-width: 100px;
    }

    .add-option-btn {
        background: none;
        border: 1px dashed rgba(255, 255, 255, .15);
        color: #5d87ff;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all .15s;
    }

    .add-option-btn:hover {
        background: rgba(93, 135, 255, .08);
        border-color: #5d87ff;
    }

    .remove-option-btn {
        background: none;
        border: none;
        color: #fa896b;
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
    }
</style>
@endsection

@section('content')
<div class="card" style="max-width:720px;">
    <form method="POST" action="{{ route('admin.giftcodes.store') }}" id="giftcodeForm">
        @csrf
        <div class="form-group">
            <label class="form-label">Code</label>
            <input class="form-input" name="code" value="{{ old('code') }}" required>
            @error('code') <div style="color:#fa896b; font-size:13px;">{{ $message }}</div> @enderror
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label class="form-label">Số lượng (count_left)</label>
                <input class="form-input" name="count_left" type="number" value="{{ old('count_left', 100) }}">
            </div>
            <div class="form-group">
                <label class="form-label">MTV (1 lần/tài khoản)</label>
                <input class="form-input" name="mtv" type="number" value="{{ old('mtv', 0) }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Vật phẩm</label>
            <div class="item-search-wrap">
                <input class="form-input" id="itemSearch" placeholder="Tìm vật phẩm (tên hoặc ID)..."
                    autocomplete="off">
                <div class="item-search-results" id="itemSearchResults"></div>
            </div>
            <div class="item-list" id="itemList"></div>
            <input type="hidden" name="detail" id="detailInput" value="{{ old('detail', '[]') }}">
        </div>

        <div class="form-group">
            <label class="form-label">Ngày hết hạn</label>
            <input class="form-input" name="expired" type="date" value="{{ old('expired') }}">
        </div>
        <div class="form-group">
            <label class="form-check"><input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked'
                    : '' }}> Active</label>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary" type="submit">
                <span class="material-icons-round" style="font-size:16px;">add</span> Tạo giftcode
            </button>
            <a href="{{ route('admin.giftcodes.index') }}" class="btn btn-outline">Huỷ</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function() {
    const allOptions = @json($options);
    const iconBase = '/assets/frontend/home/v1/images/x4/';
    let items = [];

    // Restore from old('detail')
    try {
        const old = JSON.parse(document.getElementById('detailInput').value);
        if (Array.isArray(old) && old.length) {
            // Fetch item names for pre-filled items
            old.forEach(function(item) {
                items.push({
                    temp_id: item.temp_id,
                    name: 'Item #' + item.temp_id,
                    icon_id: item.temp_id,
                    quantity: item.quantity || 1,
                    options: item.options || []
                });
            });
            renderItems();
            // Try to resolve names
            const ids = old.map(function(i){ return i.temp_id; });
            ids.forEach(function(id) {
                fetch('/admin/api/items/search?q=' + id, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                    .then(function(r){ return r.json(); })
                    .then(function(data){
                        if (data.length) {
                            const found = data[0];
                            items.forEach(function(it){
                                if (it.temp_id === found.id) {
                                    it.name = found.name;
                                    it.icon_id = found.icon_id;
                                }
                            });
                            renderItems();
                        }
                    });
            });
        }
    } catch(e) {}

    // Search items
    let searchTimeout;
    const searchInput = document.getElementById('itemSearch');
    const searchResults = document.getElementById('itemSearchResults');

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 1) { searchResults.classList.remove('show'); return; }
        searchTimeout = setTimeout(function() {
            fetch('/admin/api/items/search?q=' + encodeURIComponent(q), {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                searchResults.innerHTML = '';
                if (!data.length) {
                    searchResults.innerHTML = '<div style="padding:12px; color:#5a6a85; font-size:13px;">Không tìm thấy</div>';
                }
                data.forEach(function(item) {
                    const div = document.createElement('div');
                    div.className = 'item-result';
                    div.innerHTML = '<img src="' + iconBase + item.icon_id + '.png" onerror="this.src=\'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><rect fill=%22%231c2940%22 width=%2232%22 height=%2232%22 rx=%224%22/></svg>\'">' +
                        '<div><div>' + escapeHtml(item.name) + '</div><div class="item-id">ID: ' + item.id + '</div></div>';
                    div.addEventListener('click', function() {
                        addItem(item);
                        searchInput.value = '';
                        searchResults.classList.remove('show');
                    });
                    searchResults.appendChild(div);
                });
                searchResults.classList.add('show');
            });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('show');
        }
    });

    function addItem(item) {
        items.push({
            temp_id: item.id,
            name: item.name,
            icon_id: item.icon_id,
            quantity: 1,
            options: []
        });
        renderItems();
        syncDetail();
    }

    function renderItems() {
        const list = document.getElementById('itemList');
        list.innerHTML = '';
        items.forEach(function(item, idx) {
            const card = document.createElement('div');
            card.className = 'item-card';
            let optionsHtml = '';
            item.options.forEach(function(opt, oi) {
                const optName = allOptions.find(function(o){ return o.id === opt.id; });
                const displayName = optName ? optName.name.replace('#', opt.param) : 'Option #' + opt.id;
                optionsHtml += '<div class="option-row">' +
                    '<select class="form-input" style="padding:6px 10px; font-size:13px;" onchange="window._gcEditor.changeOption(' + idx + ',' + oi + ',this.value)">' +
                    '<option value="">-- Chọn chỉ số --</option>' +
                    allOptions.map(function(o) {
                        return '<option value="' + o.id + '"' + (o.id === opt.id ? ' selected' : '') + '>' + escapeHtml(o.name) + '</option>';
                    }).join('') +
                    '</select>' +
                    '<input type="number" class="form-input" style="padding:6px 10px; font-size:13px;" value="' + opt.param + '" placeholder="Param" onchange="window._gcEditor.changeParam(' + idx + ',' + oi + ',this.value)">' +
                    '<button type="button" class="remove-option-btn" onclick="window._gcEditor.removeOption(' + idx + ',' + oi + ')" title="Xóa">&times;</button>' +
                    '</div>';
            });

            card.innerHTML = '<img class="item-card-icon" src="' + iconBase + item.icon_id + '.png" onerror="this.src=\'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 48 48%22><rect fill=%22%231c2940%22 width=%2248%22 height=%2248%22 rx=%228%22/></svg>\'">' +
                '<div class="item-card-body">' +
                    '<div class="item-card-name">' + escapeHtml(item.name) + '<span class="item-card-id">ID: ' + item.temp_id + '</span></div>' +
                    '<div class="item-card-row">' +
                        '<label style="font-size:12px; color:#5a6a85;">Số lượng:</label>' +
                        '<input type="number" class="form-input" style="width:100px; padding:6px 10px; font-size:13px;" value="' + item.quantity + '" min="1" onchange="window._gcEditor.changeQty(' + idx + ',this.value)">' +
                    '</div>' +
                    '<div style="font-size:12px; color:#5a6a85; margin-bottom:4px;">Chỉ số (Options):</div>' +
                    optionsHtml +
                    '<button type="button" class="add-option-btn" onclick="window._gcEditor.addOption(' + idx + ')" style="margin-top:6px;">' +
                        '+ Thêm chỉ số</button>' +
                '</div>' +
                '<button type="button" class="item-card-remove" onclick="window._gcEditor.removeItem(' + idx + ')" title="Xóa vật phẩm">&times;</button>';
            list.appendChild(card);
        });
    }

    function syncDetail() {
        const detail = items.map(function(item) {
            return {
                temp_id: item.temp_id,
                quantity: parseInt(item.quantity) || 1,
                options: item.options.map(function(o) {
                    return { id: parseInt(o.id) || 0, param: parseInt(o.param) || 0 };
                })
            };
        });
        document.getElementById('detailInput').value = JSON.stringify(detail);
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    window._gcEditor = {
        changeQty: function(idx, val) {
            items[idx].quantity = parseInt(val) || 1;
            syncDetail();
        },
        removeItem: function(idx) {
            items.splice(idx, 1);
            renderItems();
            syncDetail();
        },
        addOption: function(idx) {
            items[idx].options.push({ id: 0, param: 0 });
            renderItems();
            syncDetail();
        },
        changeOption: function(idx, oi, val) {
            items[idx].options[oi].id = parseInt(val) || 0;
            renderItems();
            syncDetail();
        },
        changeParam: function(idx, oi, val) {
            items[idx].options[oi].param = parseInt(val) || 0;
            syncDetail();
        },
        removeOption: function(idx, oi) {
            items[idx].options.splice(oi, 1);
            renderItems();
            syncDetail();
        }
    };
})();
</script>
@endsection