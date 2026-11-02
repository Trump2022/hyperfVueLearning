<script setup>
import { ref } from 'vue';

const loading = ref(false);
const response = ref(null);
const error = ref('');

async function pingApi() {
    loading.value = true;
    error.value = '';

    try {
        const result = await fetch('/api/ping');

        if (!result.ok) {
            throw new Error(`HTTP ${result.status}`);
        }

        response.value = await result.json();
    } catch (requestError) {
        response.value = null;
        error.value = `${requestError.message}。请确认 HyPerf 服务已运行。`;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="page-shell">
        <header class="topbar">
            <a class="wordmark" href="/" aria-label="HyPerf Vue 学习台首页">
                <span class="wordmark-icon">H<span>+</span>V</span>
                <span>学习台</span>
            </a>
            <span class="environment"><span class="environment-dot"></span>本地开发</span>
        </header>

        <section class="intro">
            <p class="eyebrow">LESSON 01 <span>/</span> HTTP API</p>
            <h1>让 Vue <span>连上</span><br>HyPerf</h1>
            <p class="intro-copy">点击发送请求。Vue 通过 Vite 代理访问 HyPerf，收到 JSON 后显示在下方。</p>
        </section>

        <section class="api-console" aria-label="API 请求测试">
            <div class="request-row">
                <span class="http-method">GET</span>
                <code>/api/ping</code>
                <button type="button" :disabled="loading" @click="pingApi">
                    {{ loading ? '请求中…' : '发送请求' }}
                    <span aria-hidden="true">↗</span>
                </button>
            </div>

            <div class="response-area" aria-live="polite">
                <div class="response-label">
                    <span>响应内容</span>
                    <span v-if="response" class="response-state">200 · 成功</span>
                    <span v-else-if="error" class="response-state response-state-error">请求失败</span>
                    <span v-else class="response-state">等待请求</span>
                </div>
                <pre v-if="response">{{ JSON.stringify(response, null, 2) }}</pre>
                <p v-else-if="error" class="error-message">{{ error }}</p>
                <p v-else class="empty-response">响应会显示在这里</p>
            </div>
        </section>

        <footer class="page-footer">
            <span>Vue 前端</span>
            <span class="footer-connector" aria-hidden="true"></span>
            <span>HyPerf API</span>
            <span class="footer-port">:5173 <span>→</span> :9501</span>
        </footer>
    </main>
</template>