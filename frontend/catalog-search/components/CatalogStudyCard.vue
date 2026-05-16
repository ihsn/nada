<template>
  <v-card
    :href="row.url"
    variant="outlined"
    class="study-card"
    hover
  >
    <v-card-text class="pa-4">
      <div class="study-card-inner">
        <!-- Left: thumbnail or access icon -->
        <div class="study-card-icon flex-shrink-0">
          <v-img
            v-if="row.thumbnail"
            :src="row.thumbnail"
            width="60"
            height="60"
            cover
            class="rounded-lg"
          />
          <div v-else class="access-icon-box rounded-lg" :class="`bg-${accessInfo.bg}`">
            <v-icon :color="accessInfo.color" size="26">{{ accessInfo.icon }}</v-icon>
          </div>
        </div>

        <!-- Right: content -->
        <div class="study-card-content">
          <!-- Chips row -->
          <div class="d-flex flex-wrap align-center mb-2" style="gap: 6px;">
            <v-chip
              size="x-small"
              :color="accessInfo.color"
              variant="tonal"
              class="font-weight-medium"
            >
              <v-icon start size="12">{{ accessInfo.icon }}</v-icon>
              {{ accessLabel }}
            </v-chip>
            <v-chip
              v-if="typeLabel"
              size="x-small"
              color="secondary"
              variant="tonal"
              class="font-weight-medium"
            >
              {{ typeLabel }}
            </v-chip>
          </div>

          <!-- Title -->
          <div class="study-title text-body-1 font-weight-semibold mb-1">
            {{ row.title }}
          </div>

          <!-- Nation · Year -->
          <div v-if="row.nation || yearRange" class="text-body-2 text-medium-emphasis mb-1">
            <v-icon size="14" class="me-1 opacity-60">mdi-map-marker-outline</v-icon>
            <span v-if="row.nation">{{ row.nation }}</span>
            <span v-if="row.nation && yearRange" class="mx-1 opacity-40">&middot;</span>
            <span v-if="yearRange">{{ yearRange }}</span>
          </div>

          <!-- Authoring entity -->
          <div v-if="row.authoring_entity" class="text-caption text-medium-emphasis mb-1" style="font-style: italic;">
            {{ row.authoring_entity }}
          </div>

          <!-- Collection -->
          <div v-if="row.repo_title" class="text-caption mb-2">
            <v-icon size="13" class="me-1 opacity-50">mdi-folder-outline</v-icon>
            <span class="text-medium-emphasis">{{ row.repo_title }}</span>
          </div>

          <!-- Stats row -->
          <div class="d-flex flex-wrap align-center text-caption text-disabled" style="gap: 14px;">
            <span v-if="row.created">
              <v-icon size="13" class="me-1">mdi-calendar-outline</v-icon>{{ formatDate(row.created) }}
            </span>
            <span v-if="row.total_views > 0">
              <v-icon size="13" class="me-1">mdi-eye-outline</v-icon>{{ Number(row.total_views).toLocaleString() }}
            </span>
            <span v-if="row.idno" class="font-mono opacity-70">{{ row.idno }}</span>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogStudyCard' });

const props = defineProps({
  row: { type: Object, required: true },
});

const { t } = useI18n();

const ACCESS_MAP = {
  open:                { icon: 'mdi-lock-open-variant',   color: 'success',    bg: 'success-lighten-5' },
  cc40:                { icon: 'mdi-creative-commons',    color: 'success',    bg: 'success-lighten-5' },
  public:              { icon: 'mdi-account-check',       color: 'primary',    bg: 'primary-lighten-5' },
  direct:              { icon: 'mdi-database-arrow-down', color: 'info',       bg: 'info-lighten-5'    },
  licensed:            { icon: 'mdi-lock',                color: 'warning',    bg: 'warning-lighten-5' },
  enclave:             { icon: 'mdi-shield-lock',         color: 'deep-purple', bg: 'deep-purple-lighten-5' },
  remote:              { icon: 'mdi-link-variant',        color: 'secondary',  bg: 'grey-lighten-4'    },
  data_na:             { icon: 'mdi-minus-circle-outline', color: 'secondary', bg: 'grey-lighten-4'   },
  research:            { icon: 'mdi-flask-outline',       color: 'blue',       bg: 'blue-lighten-5'    },
  research_public:     { icon: 'mdi-flask-outline',       color: 'blue',       bg: 'blue-lighten-5'    },
  research_license:    { icon: 'mdi-flask-outline',       color: 'warning',    bg: 'warning-lighten-5' },
  research_public_lic: { icon: 'mdi-flask-outline',       color: 'warning',    bg: 'warning-lighten-5' },
};

const DEFAULT_ACCESS = { icon: 'mdi-file-document-outline', color: 'secondary', bg: 'grey-lighten-4' };

const accessInfo = computed(() => ACCESS_MAP[props.row.form_model] ?? DEFAULT_ACCESS);

const accessLabel = computed(() => {
  const key = 'legend_data_' + (props.row.form_model || 'data_na');
  return t(key, props.row.form_model ?? '');
});

const typeLabel = computed(() => {
  if (!props.row.type && !props.row.dtype) return '';
  const key = 'dataset_type_' + (props.row.type || props.row.dtype || '');
  return t(key, '');
});

const yearRange = computed(() => {
  const s = props.row.year_start;
  const e = props.row.year_end;
  if (!s && !e) return '';
  if (!e || s === e) return String(s || e);
  return `${s}–${e}`;
});

function formatDate(dateStr) {
  if (!dateStr) return '';
  try {
    return new Date(dateStr).toLocaleDateString(undefined, {
      year: 'numeric', month: 'short', day: 'numeric',
    });
  } catch {
    return dateStr;
  }
}
</script>

<style scoped>
.study-card {
  transition: box-shadow 0.18s ease, border-color 0.18s ease;
  border-radius: 10px !important;
  border-color: rgba(0, 0, 0, 0.1) !important;
}

.study-card:hover {
  border-color: rgba(25, 118, 210, 0.35) !important;
  box-shadow: 0 4px 16px rgba(25, 118, 210, 0.1) !important;
}

.study-card :deep(a) {
  text-decoration: none;
  color: inherit;
}

.study-card-inner {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.study-card-icon {
  width: 60px;
}

.access-icon-box {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.study-card-content {
  flex: 1;
  min-width: 0;
}

.study-title {
  line-height: 1.4;
  color: rgba(0, 0, 0, 0.85);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.study-card:hover .study-title {
  color: #1565c0;
}

.font-mono {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 0.7rem;
}
</style>
