import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import VerifyEmailView from './components/web/VerifyEmailView.vue';
import ResetPasswordView from './components/web/ResetPasswordView.vue';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: App },
        { path: '/verify-email', component: VerifyEmailView },
        { path: '/reset-password', component: ResetPasswordView },
    ],
});