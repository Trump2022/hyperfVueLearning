<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { apiRequest } from '../../api.js';

const route = useRoute();
const status = ref('验证中');
const error = ref('');

onMounted(async () => {
  const token = route.query.token;

  if (typeof token !== 'string' || token === '') {
    status.value = '验证链接无效';
    return;
  }

  try {
    const result = await apiRequest(
       `/api/auth/verify-email?token=${encodeURIComponent(token)}`,
      { auth: false },
    );
    status.value = result.message;
  } catch (requestError) {
    status.value = '邮箱验证失败';
    error.value = requestError.message;
  }
});
</script>

<template>
  <main class="page-shell">
    <header class="topbar">
      <a class="wordmark" href="/">
        <span class="wordmark-icon">H<span>+</span>V</span>
        <span>账户验证</span>
      </a>
    </header>

    <section class="intro">
      <p class="eyebrow">ACCOUNT <span>/</span> EMAIL</p>
      <h1>{{ status }}</h1>
      <p v-if="error" class="error-message">{{ error }}</p>
      <RouterLink to="/">返回首页</RouterLink>
    </section>
  </main>
</template>