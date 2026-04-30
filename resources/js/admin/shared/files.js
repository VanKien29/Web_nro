export function filesToPayload(files) {
    return Promise.all(
        files.map(
            (file) =>
                new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => {
                        const dataUrl = String(reader.result || "");
                        const comma = dataUrl.indexOf(",");
                        resolve({
                            name: file.name,
                            data: comma >= 0 ? dataUrl.slice(comma + 1) : dataUrl,
                        });
                    };
                    reader.onerror = () =>
                        reject(new Error(`Không đọc được file ${file.name}`));
                    reader.readAsDataURL(file);
                }),
        ),
    );
}
