export function buildPaginationItems(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }

    const pages = new Set([1, total, current - 1, current, current + 1]);
    if (current <= 3) {
        pages.add(2);
        pages.add(3);
        pages.add(4);
    }
    if (current >= total - 2) {
        pages.add(total - 1);
        pages.add(total - 2);
        pages.add(total - 3);
    }

    return [...pages]
        .filter((page) => page >= 1 && page <= total)
        .sort((left, right) => left - right)
        .reduce((items, page, index, sorted) => {
            if (index > 0 && page - sorted[index - 1] > 1) {
                items.push(`ellipsis-${sorted[index - 1]}-${page}`);
            }
            items.push(page);
            return items;
        }, []);
}

export function fixJson(value) {
    if (typeof value !== "string") return value;
    let normalized = value.trim();
    normalized = normalized.replace(/,\s*([\]}])/g, "$1");
    normalized = normalized.replace(/([\[{])\s*,/g, "$1");
    normalized = normalized.replace(/,\s*,/g, ",");
    return normalized;
}

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
}
