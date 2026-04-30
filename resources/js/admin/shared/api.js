export async function readJsonResponse(response, fallbackMessage = "Không thể tải dữ liệu") {
    const text = await response.text();
    let data;
    try {
        data = text ? JSON.parse(text) : {};
    } catch {
        const plain = text
            .replace(/<br\s*\/?>/gi, "\n")
            .replace(/<[^>]*>/g, " ")
            .replace(/\s+/g, " ")
            .trim();
        throw new Error(
            plain ||
                `${fallbackMessage} (${response.status || "không rõ trạng thái"})`,
        );
    }

    if (!response.ok) {
        throw new Error(data?.message || `${fallbackMessage} (${response.status})`);
    }

    return data;
}
