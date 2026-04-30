<template>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="header">
                <div class="brand">
                    <div class="brand-logo">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                        >
                            <path
                                d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"
                                fill="black"
                            />
                        </svg>
                    </div>
                    <span class="brand-text">Ngọc Rồng</span>
                </div>
                <div class="header-actions">
                    <a href="/" class="home-btn">
                        <i class="fas fa-arrow-left"></i> Về Trang chủ
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="form-wrapper">
                    <div class="form-section">
                        <h1 class="welcome-text">Welcome back!</h1>
                        <p class="subtitle">Đăng nhập để tiếp tục</p>

                        <div v-if="error" class="error-msg">{{ error }}</div>

                        <form @submit.prevent="submit" class="login-form">
                            <div class="input-group">
                                <label>Tài khoản</label>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    placeholder="jabber@gmail.com"
                                    required
                                />
                            </div>

                            <div class="input-group">
                                <label>Mật khẩu</label>
                                <div class="password-wrapper">
                                    <input
                                        v-model="form.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        placeholder="********"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="toggle-password"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                            />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="form-footer">
                                <label class="checkbox-label">
                                    <input type="checkbox" />
                                    <span class="custom-checkbox"></span>
                                    Nhớ tài khoản
                                </label>
                                <a href="#" class="forgot-link"
                                    >Quên mật khẩu?</a
                                >
                            </div>

                            <button
                                type="submit"
                                class="submit-btn"
                                :disabled="loading"
                            >
                                <span v-if="loading">
                                    <span class="admin-loading-dot"></span>
                                    Đang đăng nhập...
                                </span>
                                <span v-else>Đăng nhập &rarr;</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="illustration-section">
                    <div class="illustration-container">
                        <!-- Enhanced SVG Illustration with more items -->
                        <svg viewBox="0 0 450 400" class="main-svg">
                            <!-- Decorative background elements -->
                            <circle
                                cx="350"
                                cy="50"
                                r="40"
                                fill="#3ddc84"
                                opacity="0.05"
                            />
                            <rect
                                x="50"
                                y="300"
                                width="30"
                                height="30"
                                rx="6"
                                transform="rotate(15 50 300)"
                                fill="#3ddc84"
                                opacity="0.08"
                            />

                            <!-- Floating UI Cards (Decorative) -->
                            <rect
                                x="280"
                                y="60"
                                width="100"
                                height="60"
                                rx="8"
                                fill="#151515"
                                stroke="#222"
                                stroke-width="1"
                            />
                            <rect
                                x="300"
                                y="80"
                                width="60"
                                height="6"
                                rx="3"
                                fill="#3ddc84"
                                opacity="0.2"
                            />
                            <rect
                                x="300"
                                y="95"
                                width="40"
                                height="6"
                                rx="3"
                                fill="#333"
                            />

                            <rect
                                x="50"
                                y="150"
                                width="80"
                                height="100"
                                rx="8"
                                fill="#151515"
                                stroke="#222"
                                stroke-width="1"
                            />
                            <circle
                                cx="90"
                                cy="180"
                                r="15"
                                fill="#3ddc84"
                                opacity="0.1"
                            />
                            <rect
                                x="70"
                                y="210"
                                width="40"
                                height="4"
                                rx="2"
                                fill="#333"
                            />
                            <rect
                                x="70"
                                y="220"
                                width="30"
                                height="4"
                                rx="2"
                                fill="#333"
                            />

                            <!-- Character & Laptop (Enhanced) -->
                            <path
                                d="M180 320 L320 320 M170 310 L330 310 M170 230 L330 230"
                                stroke="white"
                                stroke-width="2"
                                opacity="0.3"
                            />
                            <rect
                                x="170"
                                y="230"
                                width="160"
                                height="80"
                                fill="#0c0c0c"
                                stroke="white"
                                stroke-width="2"
                                rx="6"
                            />

                            <!-- Character (Simplified) -->
                            <circle cx="250" cy="160" r="30" fill="white" />
                            <path
                                d="M250 190 Q250 260 300 270 L340 260"
                                stroke="white"
                                stroke-width="4"
                                fill="none"
                            />

                            <!-- Floating Icons -->
                            <!-- Lightning 1 -->
                            <path
                                d="M360 120 L345 150 H370 L355 180"
                                fill="#3ddc84"
                            >
                                <animateTransform
                                    attributeName="transform"
                                    type="translate"
                                    values="0 0; 0 -10; 0 0"
                                    dur="3s"
                                    repeatCount="indefinite"
                                />
                            </path>
                            <!-- Lightning 2 -->
                            <path
                                d="M130 80 L120 100 H135 L125 120"
                                fill="#3ddc84"
                                opacity="0.6"
                            >
                                <animateTransform
                                    attributeName="transform"
                                    type="translate"
                                    values="0 0; 0 10; 0 0"
                                    dur="4s"
                                    repeatCount="indefinite"
                                />
                            </path>

                            <!-- Data Waves -->
                            <path
                                d="M100 50 Q120 30 140 50 T180 50"
                                stroke="#3ddc84"
                                stroke-width="2"
                                fill="none"
                                opacity="0.4"
                            >
                                <animate
                                    attributeName="stroke-dasharray"
                                    from="0,150"
                                    to="150,0"
                                    dur="3s"
                                    repeatCount="indefinite"
                                />
                            </path>

                            <!-- Cubes/Dots -->
                            <rect
                                x="380"
                                y="250"
                                width="10"
                                height="10"
                                rx="2"
                                fill="#3ddc84"
                                opacity="0.5"
                            />
                            <circle
                                cx="395"
                                cy="275"
                                r="4"
                                fill="#3ddc84"
                                opacity="0.3"
                            />
                            <circle
                                cx="375"
                                cy="285"
                                r="3"
                                fill="#3ddc84"
                                opacity="0.2"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            form: { username: "", password: "" },
            error: "",
            loading: false,
            showPassword: false,
        };
    },
    methods: {
        async submit() {
            this.error = "";
            this.loading = true;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                const res = await fetch("/admin/api/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    this.$router.push({ name: "admin.dashboard" });
                } else {
                    this.error = data.message || "Invalid account or password";
                }
            } catch {
                this.error = "Connection error, please try again";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.login-container {
    min-height: 100vh;
    background-color: #080808;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
}

.login-card {
    width: 100%;
    max-width: 1200px;
    min-height: 90vh;
    display: flex;
    flex-direction: column;
    position: relative;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
}

.home-btn {
    text-decoration: none;
    color: #3ddc84;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 16px;
    border: 1px solid rgba(61, 220, 132, 0.3);
    border-radius: 8px;
    transition: all 0.3s;
    background: rgba(61, 220, 132, 0.05);
}

.home-btn:hover {
    background: #3ddc84;
    color: #000;
}

.home-btn i {
    margin-right: 6px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-logo {
    width: 42px;
    height: 42px;
    background-color: #3ddc84;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    box-shadow: 0 0 15px rgba(61, 220, 132, 0.3);
}

.brand-text {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 3px;
}

.trustpilot-box {
    display: flex;
    align-items: center;
    gap: 15px;
    max-width: 500px;
}

.testimonial {
    font-size: 13px;
    font-style: italic;
    color: #888;
    text-align: right;
    line-height: 1.4;
}

.user-avatars {
    display: flex;
    margin-left: 10px;
}

.user-avatars img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #000;
    margin-left: -10px;
}

.tp-rating {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stars {
    color: #3ddc84;
    font-size: 14px;
}

.tp-text {
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    color: #fff;
}

/* Main Content */
.main-content {
    flex: 1;
    display: flex;
    gap: 60px;
    align-items: center;
    padding: 40px 0;
}

.form-wrapper {
    flex: 1;
    background: #111;
    border: 1px solid #222;
    border-radius: 16px;
    padding: 50px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    max-width: 500px;
}

.form-section {
    width: 100%;
}

.welcome-text {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 8px;
}

.subtitle {
    color: #666;
    font-size: 16px;
    margin-bottom: 40px;
}

.input-group {
    margin-bottom: 25px;
}

.input-group label {
    display: block;
    font-size: 14px;
    color: #aaa;
    margin-bottom: 10px;
}

.input-group input {
    width: 100%;
    padding: 15px 20px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 10px;
    color: #fff;
    font-size: 15px;
    transition: all 0.3s;
}

.input-group input:focus {
    border-color: #3ddc84;
    background: #202020;
    outline: none;
    box-shadow: 0 0 0 2px rgba(61, 220, 132, 0.1);
}

.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #444;
    cursor: pointer;
    width: 20px;
}

.form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 35px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    color: #888;
}

.checkbox-label input:checked + .custom-checkbox {
    background-color: #3ddc84;
    border-color: #3ddc84;
}

.checkbox-label input {
    display: none;
}

.custom-checkbox {
    width: 18px;
    height: 18px;
    border: 1px solid #444;
    border-radius: 4px;
    display: inline-block;
    transition: all 0.2s;
}

.forgot-link {
    color: #888;
    text-decoration: none;
    font-size: 14px;
}

.forgot-link:hover {
    color: #3ddc84;
}

.submit-btn {
    width: 100%;
    padding: 18px;
    background-color: #3ddc84;
    color: #000;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(61, 220, 132, 0.2);
}

.submit-btn:hover {
    background-color: #32c473;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(61, 220, 132, 0.3);
}

.submit-btn:active {
    transform: translateY(0);
}

.error-msg {
    color: #ff4d4d;
    background: rgba(255, 77, 77, 0.1);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    border-left: 4px solid #ff4d4d;
}

/* Illustration */
.illustration-section {
    flex: 1;
    display: flex;
    justify-content: center;
    padding-left: 40px;
}

.illustration-container {
    width: 100%;
    max-width: 550px;
}

.main-svg {
    width: 100%;
    height: auto;
    filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.4));
}

/* Footer */
.page-footer {
    display: flex;
    justify-content: space-between;
    padding: 30px 0;
    border-top: 1px solid #222;
    font-size: 14px;
    color: #666;
}

.create-link {
    color: #3ddc84;
    text-decoration: none;
    font-weight: 600;
}

.create-link:hover {
    text-decoration: underline;
}

.phone {
    color: #ccc;
    font-weight: 600;
    margin-left: 5px;
}

@media (max-width: 1024px) {
    .main-content {
        flex-direction: column;
        gap: 40px;
    }
    .form-wrapper {
        max-width: 100%;
    }
}

@media (max-width: 900px) {
    .illustration-section {
        display: none;
    }
    .header {
        flex-direction: column;
        gap: 20px;
    }
}
</style>
