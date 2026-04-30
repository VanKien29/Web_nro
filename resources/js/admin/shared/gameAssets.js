export function gameAssetGenderLabel(gender, fallbackPrefix = "Gender") {
    const labels = {
        0: "Trái Đất",
        1: "Namek",
        2: "Xayda",
        3: "Chung",
    };
    const value = Number(gender);
    if (labels[value]) return labels[value];
    return fallbackPrefix ? `${fallbackPrefix} ${gender}` : gender;
}

export function summarizeIconData(value, visibleCount = 10) {
    const ids = String(value || "")
        .replace(/^\s*\[|\]\s*$/g, "")
        .split(/[,\s]+/)
        .map((part) => part.trim())
        .filter(Boolean);
    const visible = ids.slice(0, visibleCount);

    return {
        count: ids.length,
        visible,
        hidden: Math.max(0, ids.length - visible.length),
        text:
            visible.join(", ") +
            (ids.length > visible.length
                ? ` ... +${ids.length - visible.length}`
                : ""),
        title: ids.join(", "),
    };
}
