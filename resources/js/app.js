import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.mount('#app');

// После загрузки: отправить SW список ассетов для прекеширования
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const urls = [
            ...document.querySelectorAll('script[src]'),
            ...document.querySelectorAll('link[rel="stylesheet"][href]'),
            ...document.querySelectorAll('link[rel="modulepreload"][href]'),
        ]
            .map((el) => el.src || el.href)
            .filter(Boolean);

        navigator.serviceWorker.ready.then((reg) => {
            reg.active?.postMessage({ type: 'CACHE_ASSETS', urls });
        });
    });
}
