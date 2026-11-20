<template>
    <div v-if="article != null" class="push-alert" role="alert">
        <span>
            Добавлена новая статья
            <strong><a :href="`/articles/${article.id}`">{{ article.name }}</a></strong>
        </span>
        <button type="button" class="push-close" aria-label="Закрыть" @click="article = null">×</button>
    </div>
</template>

<script>
export default {
    data() {
        return { article: null };
    },
    created() {
        // Слушаем канал "test" и событие NewArticleEvent (см. app/Events/NewArticleEvent.php)
        if (!window.Echo) {
            return;
        }

        window.Echo.channel('test').listen('NewArticleEvent', (data) => {
            console.log(data);
            this.article = data.article;
        });
    },
};
</script>

<style>
.push-alert {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 1000;
    max-width: 360px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px 16px;
    background: #e8eef7;
    border: 1px solid #c9d7ec;
    border-left: 4px solid #2b4c7e;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}
.push-alert a { color: #2b4c7e; }
.push-close {
    border: 0;
    background: none;
    font-size: 1.3rem;
    line-height: 1;
    cursor: pointer;
    color: #6b6258;
}
</style>
