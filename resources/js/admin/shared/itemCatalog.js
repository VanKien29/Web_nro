export function itemPickerTypes(picker = {}) {
    const fromOptions = Array.isArray(picker.typeOptions) ? picker.typeOptions : [];
    if (fromOptions.length) {
        const dedup = new Map();
        fromOptions
            .map((option) => ({
                id: Number(option?.id),
                name: String(option?.name || "").trim(),
            }))
            .filter(
                (option) =>
                    Number.isFinite(option.id) &&
                    option.name &&
                    !["undefined", "null", "nan"].includes(option.name.toLowerCase()),
            )
            .forEach((option) => {
                if (!dedup.has(option.id)) dedup.set(option.id, option);
            });

        return Array.from(dedup.values()).sort((left, right) => left.id - right.id);
    }

    const rawTypes = picker.types;
    const typeSource = Array.isArray(rawTypes)
        ? rawTypes
        : rawTypes && typeof rawTypes === "object"
          ? Object.values(rawTypes)
          : [];
    const rowTypes = (picker.rows || []).map((row) => row?.type);
    const ids = [...new Set([...typeSource, ...rowTypes]
        .map((type) => String(type ?? "").trim())
        .filter((type) => type && !["undefined", "null", "nan"].includes(type.toLowerCase())))];

    return ids
        .map((id) => ({ id: Number(id), name: `Type ${id}` }))
        .filter((option) => Number.isFinite(option.id))
        .sort((left, right) => left.id - right.id);
}

export function itemTypeLabel(typeValue, typeOptions = []) {
    const key = String(typeValue ?? "").trim();
    const found = typeOptions.find((type) => String(type.id) === key);
    if (found) return `TYPE ${found.id} - ${found.name}`;
    return key ? `TYPE ${key}` : "Không rõ TYPE";
}

export function itemGenderLabel(genderValue) {
    const value =
        genderValue === null || genderValue === undefined
            ? ""
            : String(genderValue);

    return (
        {
            0: "Trái Đất",
            1: "Namek",
            2: "Xayda",
            3: "Chung",
        }[value] || "Không rõ hệ"
    );
}

export function normalizePickerPage(page, totalPages) {
    const max = Math.max(1, Number(totalPages) || 1);
    const value = Number(page) || 1;
    return Math.min(max, Math.max(1, value));
}

export function applyItemPickerResponse(picker, data = {}) {
    picker.rows = data?.data || [];
    picker.types = data?.types || picker.types;
    picker.typeOptions = data?.type_options || picker.typeOptions;
    picker.page = data?.page || 1;
    picker.pageInput = String(picker.page);
    picker.totalPages = data?.total_pages || 1;
    picker.total = data?.total || 0;
}

export function resetItemPickerResults(picker) {
    picker.rows = [];
    picker.total = 0;
    picker.totalPages = 1;
}
