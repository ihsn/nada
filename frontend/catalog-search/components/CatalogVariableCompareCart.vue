<template>
  <Teleport to="body">
    <div
      v-if="count > 0"
      class="variable-compare-cart"
      :class="{ 'variable-compare-cart--collapsed': collapsed }"
    >
      <button
        type="button"
        class="variable-compare-cart__header"
        @click="collapsed = !collapsed"
      >
        <span class="variable-compare-cart__header-title">
          {{ t('Compare variables', 'Compare variables') }}
          <v-chip size="x-small" variant="flat" color="white" rounded class="variable-compare-cart__count text-primary">
            {{ count }}
          </v-chip>
        </span>
        <v-icon size="20">{{ collapsed ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </button>

      <div v-show="!collapsed" class="variable-compare-cart__body">
        <div v-if="cartLoading" class="variable-compare-cart__loading text-caption">
          <v-progress-circular indeterminate size="18" width="2" class="me-2" />
          {{ t('loading', 'Loading...') }}
        </div>

        <div v-else-if="cartItems.length" class="variable-compare-cart__list">
          <div
            v-for="item in cartItems"
            :key="`${item.sid}-${item.vid}`"
            class="variable-compare-cart__item"
          >
            <div class="variable-compare-cart__item-main">
              <button
                type="button"
                class="variable-compare-cart__item-name"
                @click="openVariableDialog(item)"
              >
                {{ item.name }}
              </button>
              <div v-if="item.idno" class="variable-compare-cart__item-idno text-caption">
                {{ item.idno }}
              </div>
            </div>
            <v-btn
              icon
              variant="text"
              size="x-small"
              density="compact"
              :aria-label="t('remove', 'Remove')"
              @click="onRemove(item)"
            >
              <v-icon size="18">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <div v-else class="variable-compare-cart__empty text-caption text-medium-emphasis pa-3">
          {{ t('no_records_found', 'No records found') }}
        </div>
      </div>

      <div v-show="!collapsed" class="variable-compare-cart__actions">
        <v-btn
          size="small"
          variant="text"
          @click="onClear"
        >
          {{ t('clear', 'Clear') }}
        </v-btn>
        <v-btn
          size="small"
          color="primary"
          variant="flat"
          append-icon="mdi-open-in-new"
          @click="onCompare"
        >
          {{ t('Compare', 'Compare') }}
        </v-btn>
      </div>

      <CatalogVariableDetailDialog
        v-model="detailOpen"
        :study-id="detailStudyId"
        :variable="detailVariable"
      />
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useVariableCompareCart } from '../composables/useVariableCompareCart';
import CatalogVariableDetailDialog from './CatalogVariableDetailDialog.vue';

defineOptions({ name: 'CatalogVariableCompareCart' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const {
  count,
  cartItems,
  cartLoading,
  selectedIds,
  removeByKey,
  clear,
  refreshCartDetails,
  tryOpenCompare,
  compareItemKey,
} = useVariableCompareCart();

const collapsed = ref(false);
const detailOpen = ref(false);
const detailStudyId = ref(null);
const detailVariable = ref(null);

watch(
  selectedIds,
  () => {
    refreshCartDetails(siteUrl.value);
  },
  { deep: true, immediate: true }
);

function onRemove(item) {
  removeByKey(compareItemKey(item.sid, item.vid));
}

function onClear() {
  clear();
}

function onCompare() {
  tryOpenCompare(siteUrl.value, t);
}

function openVariableDialog(item) {
  detailStudyId.value = item.sid;
  detailVariable.value = { vid: item.vid, name: item.name, labl: item.name };
  detailOpen.value = true;
}
</script>

<style scoped>
.variable-compare-cart {
  position: fixed;
  right: 1.25rem;
  bottom: 0;
  z-index: 2000;
  width: min(360px, calc(100vw - 2rem));
  max-height: 380px;
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid rgba(21, 101, 192, 0.35);
  border-bottom: 0;
  border-radius: 10px 10px 0 0;
  box-shadow: 0 -4px 24px rgba(15, 23, 42, 0.12);
  overflow: hidden;
}

.variable-compare-cart--collapsed {
  max-height: none;
}

.variable-compare-cart__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  padding: 12px 14px;
  border: 0;
  background: #1565c0;
  color: #fff;
  cursor: pointer;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 600;
}

.variable-compare-cart__header-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.variable-compare-cart__count {
  flex-shrink: 0;
}

.variable-compare-cart__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  max-height: 260px;
}

.variable-compare-cart--collapsed .variable-compare-cart__body {
  display: none;
}

.variable-compare-cart__loading {
  display: flex;
  align-items: center;
  padding: 14px;
}

.variable-compare-cart__list {
  padding: 4px 0;
}

.variable-compare-cart__item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px 10px 14px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}

.variable-compare-cart__item:last-child {
  border-bottom: 0;
}

.variable-compare-cart__item-main {
  flex: 1;
  min-width: 0;
}

.variable-compare-cart__item-name {
  display: block;
  width: 100%;
  border: 0;
  padding: 0;
  background: transparent;
  text-align: left;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1565c0;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.variable-compare-cart__item-name:hover {
  text-decoration: underline;
}

.variable-compare-cart__item-idno {
  margin-top: 2px;
  color: rgba(26, 35, 50, 0.55);
}

.variable-compare-cart__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding: 10px 12px;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
  background: #fafbfc;
  box-shadow: 0 -2px 8px rgba(15, 23, 42, 0.04);
}

.variable-compare-cart--collapsed .variable-compare-cart__actions {
  display: none;
}
</style>
