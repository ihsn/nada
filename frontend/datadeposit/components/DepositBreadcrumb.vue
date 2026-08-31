<script setup>
defineProps({
  items: { type: Array, default: () => [] },
});
</script>

<template>
  <nav v-if="items.length" class="dd-inline-breadcrumb" aria-label="Breadcrumb">
    <ol>
      <li
        v-for="(item, i) in items"
        :key="`${item.title}-${i}`"
        :class="{ 'is-current': i === items.length - 1 }"
      >
        <router-link v-if="item.to && i < items.length - 1" :to="item.to">{{ item.title }}</router-link>
        <a v-else-if="item.href && i < items.length - 1" :href="item.href">{{ item.title }}</a>
        <span v-else>{{ item.title }}</span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.dd-inline-breadcrumb {
  margin: 0 0 20px;
  padding: 0;
  padding-inline-start: 0;
}
.dd-inline-breadcrumb ol {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0;
  margin: 0;
  padding: 0;
  padding-inline-start: 0;
  list-style: none;
  font-size: 0.8125rem;
  line-height: 1.4;
}
.dd-inline-breadcrumb li {
  display: inline-flex;
  align-items: center;
  margin: 0;
  margin-inline-start: 0;
  padding: 0;
  list-style: none;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
.dd-inline-breadcrumb li:not(:last-child)::after {
  content: '/';
  margin: 0 4px 0 6px;
  opacity: 0.5;
}
.dd-inline-breadcrumb a,
.dd-inline-breadcrumb :deep(a) {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}
.dd-inline-breadcrumb a:hover {
  text-decoration: underline;
}
.dd-inline-breadcrumb .is-current span {
  color: rgba(var(--v-theme-on-surface), 0.75);
}
</style>
