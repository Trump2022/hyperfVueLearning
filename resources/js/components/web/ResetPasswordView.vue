<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../../api.js';

const route = useRoute();
const router = useRouter();

const email = ref('');
const token = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const message = ref('');
const error = ref('');
const loading = ref(false);

onMounted(() => {
  email.value = typeof route.query.email === 'string' ? route.query.email : '';
  token.value = typeof route.query.token === 'string' ? route.query.token : '';

  // 从链接读取 token 后移除 URL 查询参数，避免 token 留在地址栏。
  router.replace({ path: '/reset-password' });

  if (!email.value || !token.value) {
    error.value = '重置链接无效或已过期。';
  }
});

async function submit() {
  loading.value = true;
  message.value = '';
  error.value = '';

  try {
    const result = await apiRequest('/api/auth/reset-password', {
      method: 'POST',
      auth: false,
      body: {
        email: email.value,
        token: token.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      },
    });

    message.value = result.message;
    token.value = '';
  } catch (requestError) {
    error.value = requestError.message;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <main class="page-shell">
    <header class="topbar">
      <a class="wordmark" href="/">
        <span class="wordmark-icon">H<span>+</span>V</span>
        <span>重置密码</span>
      </a>
    </header>

    <section class="intro">
      <p class="eyebrow">ACCOUNT <span>/</span> PASSWORD RESET</p>
      <h1>设置新密码</h1>
      <p class="intro-copy">{{ email }}</p>

      <form v-if="token" class="auth-form" @submit.prevent="submit">
        <label>
          新密码
          <input v-model="password" type="password" minlength="8" required autocomplete="new-password">
        </label>
        <label>
          确认新密码
          <input v-model="passwordConfirmation" type="password" minlength="8" required autocomplete="new-password">
        </label>
        <button type="submit" :disabled="loading">
          {{ loading ? '提交中…' : '重置密码' }}
        </button>
      </form>

      <p v-if="message" role="status">{{ message }}</p>
      <p v-if="error" class="error-message" role="alert">{{ error }}</p>
    </section>
  </main>
</template>