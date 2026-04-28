const DEFAULT_MESSAGE_PATHS = [
    "success",
    "error",
    "loadError",
    "runtimeError",
    "runtimeMessage",
    "activityError",
    "playerFullError",
    "message",
    "formMessage",
    "editor.error",
    "badgeDraft.error",
];

function readPath(vm, path) {
    return path.split(".").reduce((value, key) => value?.[key], vm);
}

function writePath(vm, path, value) {
    const parts = path.split(".");
    const last = parts.pop();
    const target = parts.reduce((current, key) => current?.[key], vm);
    if (target && Object.prototype.hasOwnProperty.call(target, last)) {
        target[last] = value;
    }
}

function hasPath(vm, path) {
    const parts = path.split(".");
    const last = parts.pop();
    const target = parts.reduce((current, key) => current?.[key], vm);
    return !!target && Object.prototype.hasOwnProperty.call(target, last);
}

export function createAutoDismissMessages({
    paths = DEFAULT_MESSAGE_PATHS,
    delay = 4600,
} = {}) {
    return {
        created() {
            this.__autoDismissTimers = new Map();

            paths.forEach((path) => {
                if (!hasPath(this, path)) return;

                this.$watch(path, (value) => {
                    if (this.__autoDismissTimers.has(path)) {
                        window.clearTimeout(this.__autoDismissTimers.get(path));
                        this.__autoDismissTimers.delete(path);
                    }

                    if (typeof value !== "string" || value.trim() === "") {
                        return;
                    }

                    const timer = window.setTimeout(() => {
                        if (readPath(this, path) === value) {
                            writePath(this, path, "");
                        }
                        this.__autoDismissTimers.delete(path);
                    }, delay);

                    this.__autoDismissTimers.set(path, timer);
                });
            });
        },
        beforeUnmount() {
            if (!this.__autoDismissTimers) return;
            this.__autoDismissTimers.forEach((timer) =>
                window.clearTimeout(timer),
            );
            this.__autoDismissTimers.clear();
        },
    };
}
