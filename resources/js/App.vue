<script setup>
import { onMounted, reactive, ref } from 'vue';
import { apiRequest, clearToken, getToken, saveToken } from './api.js';

const mode = ref('login');
const user = ref(null);
const loading = ref(false);
const error = ref('');
const notice = ref('');
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});
const profileName = ref('');
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

onMounted(async () => {
  if (!getToken()) return;

  try {
    const result = await apiRequest('/api/auth/me');
    user.value = result.user;
    profileName.value = result.user.name;
  } catch {
    clearToken();
  }
});

async function submitAuth() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    if (mode.value === 'forgot') {
      const result = await apiRequest('/api/auth/forgot-password', {
        method: 'POST',
        body: { email: form.email },
        auth: false,
      });
      notice.value = result.message;
      return;
    }

    const isRegister = mode.value === 'register';
    const result = await apiRequest(
        `/api/auth/${isRegister ? 'register' : 'login'}`,
        {
          method: 'POST',
          auth: false,
          body: {
            ...(isRegister ? { name: form.name } : {}),
            email: form.email,
            password: form.password,
            ...(isRegister
                ? { password_confirmation: form.password_confirmation }
                : {}),
          },
        },
    );

    saveToken(result.access_token);
    user.value = result.user;
    profileName.value = result.user.name;
    notice.value = result.verification_email_sent === false
        ? '注册成功，但验证邮件发送失败。'
        : result.message;
    form.password = '';
    form.password_confirmation = '';
  } catch (requestError) {
    error.value = requestError.message;
  } finally {
    loading.value = false;
  }
}

async function updateProfile() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const result = await apiRequest('/api/auth/me', {
      method: 'PATCH',
      body: { name: profileName.value },
    });
    user.value = result.user;
    notice.value = result.message;
  } catch (requestError) {
    error.value = requestError.message;
  } finally {
    loading.value = false;
  }
}

async function updatePassword() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const result = await apiRequest('/api/auth/me/password', {
      method: 'PATCH',
      body: { ...passwordForm },
    });
    notice.value = result.message;
    Object.assign(passwordForm, {
      current_password: '',
      password: '',
      password_confirmation: '',
    });
  } catch (requestError) {
    error.value = requestError.message;
  } finally {
    loading.value = false;
  }
}

async function resendVerification() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const result = await apiRequest('/api/auth/email/verification-notification', {
      method: 'POST',
    });
    notice.value = result.message;
  } catch (requestError) {
    error.value = requestError.message;
  } finally {
    loading.value = false;
  }
}

function logout() {
  clearToken();
  user.value = null;
  mode.value = 'login';
  notice.value = '已清除本机登录状态。';
}
</script>

<template>
  <main class="page-shell">
    <header class="topbar">
      <a class="wordmark" href="/">
        <span class="wordmark-icon">H<span>+</span>V</span>
        <span>账户中心</span>
      </a>
      <span class="environment">
                <span class="environment-dot"></span>
                Hyperf API
            </span>
    </header>

    <section class="intro">
      <p class="eyebrow">ACCOUNT <span>/</span> HYPERF + VUE</p>
      <h1>{{ user ? '个人中心' : '欢迎回来' }}</h1>
      <p class="intro-copy">
        {{ user ? '管理你的账户资料与安全设置。' : '登录或创建账户以继续。' }}
      </p>
    </section>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="error" class="error-message" role="alert">{{ error }}</p>

    <section v-if="user" class="account-content">
      <div class="account-heading">
        <div>
          <h2>{{ user.name }}</h2>
          <p>{{ user.email }}</p>
        </div>
        <button type="button" class="text-button" @click="logout">退出</button>
      </div>

      <div class="verification-row">
        <span>邮箱状态</span>
        <strong>{{ user.email_verified ? '已验证' : '未验证' }}</strong>
        <button
            v-if="!user.email_verified"
            type="button"
            :disabled="loading"
            @click="resendVerification"
        >
          重发验证邮件
        </button>
      </div>

      <form class="auth-form" @submit.prevent="updateProfile">
        <h2>个人资料</h2>
        <label>
          姓名
          <input v-model="profileName" maxlength="255" required>
        </label>
        <button type="submit" :disabled="loading">保存姓名</button>
      </form>

      <form class="auth-form" @submit.prevent="updatePassword">
        <h2>修改密码</h2>
        <label>
          当前密码
          <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password">
        </label>
        <label>
          新密码
          <input v-model="passwordForm.password" type="password" minlength="8" required autocomplete="new-password">
        </label>
        <label>
          确认新密码
          <input v-model="passwordForm.password_confirmation" type="password" minlength="8" required autocomplete="new-password">
        </label>
        <button type="submit" :disabled="loading">更新密码</button>
      </form>
    </section>

    <form v-else class="auth-form auth-form-primary" @submit.prevent="submitAuth">
      <div class="mode-switch" v-if="mode !== 'forgot'">
        <button
            type="button"
            :aria-pressed="mode === 'login'"
            @click="mode = 'login'; error = ''; notice = ''"
        >登录</button>
        <button
            type="button"
            :aria-pressed="mode === 'register'"
            @click="mode = 'register'; error = ''; notice = ''"
        >注册</button>
      </div>

      <h2>{{ mode === 'forgot' ? '找回密码' : mode === 'register' ? '创建账户' : '登录账户' }}</h2>

      <label v-if="mode === 'register'">
        姓名
        <input v-model="form.name" maxlength="255" required autocomplete="name">
      </label>
      <label>
        邮箱
        <input v-model="form.email" type="email" required autocomplete="email">
      </label>
      <label v-if="mode !== 'forgot'">
        密码
        <input
            v-model="form.password"
            type="password"
            :minlength="mode === 'register' ? 8 : undefined"
            required
            :autocomplete="mode === 'register' ? 'new-password' : 'current-password'"
        >
      </label>
      <label v-if="mode === 'register'">
        确认密码
        <input v-model="form.password_confirmation" type="password" minlength="8" required autocomplete="new-password">
      </label>

      <button type="submit" :disabled="loading">
        {{ loading ? '处理中…' : mode === 'forgot' ? '发送重置邮件' : mode === 'register' ? '注册' : '登录' }}
      </button>

      <button
          v-if="mode === 'login'"
          type="button"
          class="text-button"
          @click="mode = 'forgot'; error = ''; notice = ''"
      >忘记密码？</button>
      <button
          v-if="mode === 'forgot'"
          type="button"
          class="text-button"
          @click="mode = 'login'; error = ''; notice = ''"
      >返回登录</button>
    </form>
  </main>
</template>