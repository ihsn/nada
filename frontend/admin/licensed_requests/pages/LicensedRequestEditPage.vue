<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="lr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="lr-page-header mb-4">
      <h1 class="text-h5 font-weight-semibold mb-0">{{ pageHeading }}</h1>
      <div v-if="requestRow.status" class="mt-2">
        <v-chip
          size="small"
          variant="flat"
          :color="requestStatusChipColor"
          class="lr-status-pill font-weight-semibold"
        >
          {{ requestRow.status }}
        </v-chip>
      </div>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4">{{ loadError }}</v-alert>
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <template v-else-if="detail">
      <v-tabs v-model="tab" color="primary" class="mb-4">
        <v-tab value="info">{{ t('request_information', 'Request information') }}</v-tab>
        <v-tab value="process">{{ t('tab_process', 'Process') }}</v-tab>
        <v-tab value="comm">{{ t('tab_communicate', 'Communicate') }}</v-tab>
        <v-tab value="mon">{{ t('tab_monitor', 'Monitor') }}</v-tab>
        <v-tab value="fwd">{{ t('forward_lic_request', 'Forward') }}</v-tab>
      </v-tabs>

      <v-window v-model="tab">
        <v-window-item value="info">
          <v-card class="pa-4" elevation="1">
            <v-table density="comfortable" class="text-body-2">
              <tbody>
                <tr>
                  <th class="text-left font-weight-bold" style="width: 200px">{{ t('request_status', 'Status') }}</th>
                  <td>{{ requestRow.status }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('request_id', 'Request ID') }}</th>
                  <td>{{ requestRow.id }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('dated', 'Dated') }}</th>
                  <td>{{ formatTs(requestRow.created) }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('first_name', 'First name') }}</th>
                  <td>{{ userRow?.fname }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('last_name', 'Last name') }}</th>
                  <td>{{ userRow?.lname }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('organization', 'Organization') }}</th>
                  <td>{{ userRow?.organization }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold">{{ t('email', 'Email') }}</th>
                  <td>{{ userRow?.email }}</td>
                </tr>
                <tr>
                  <th class="font-weight-bold lr-info-datasets-label">{{ t('dataset_requested', 'Dataset(s)') }}</th>
                  <td class="lr-info-datasets-cell">
                    <template v-if="detail.surveys?.length">
                      <div class="lr-info-survey-scroll">
                        <div class="lr-info-survey-stack">
                          <div
                            v-for="s in detail.surveys"
                            :key="s.id"
                            class="lr-info-survey-card"
                          >
                          <div class="d-flex align-start ga-2">
                            <v-icon
                              size="22"
                              color="primary"
                              class="lr-info-survey-icon flex-shrink-0"
                              icon="mdi-folder-table-outline"
                            />
                            <div class="flex-grow-1 min-w-0">
                              <a
                                :href="catalogStudyUrl(s.id)"
                                class="lr-info-survey-title-link text-body-2"
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                <span class="lr-info-survey-title-text">{{ s.title }}</span>
                                <v-icon
                                  size="14"
                                  class="lr-info-survey-ext-icon ms-1"
                                  color="primary"
                                  icon="mdi-open-in-new"
                                />
                              </a>
                              <div class="lr-info-survey-meta text-caption text-medium-emphasis mt-1">
                                <template v-if="s.nation">
                                  <span>{{ s.nation }}</span>
                                </template>
                                <template v-if="formatSurveyYearRange(s)">
                                  <span v-if="s.nation" class="lr-info-survey-meta-sep">·</span>
                                  <span>{{ formatSurveyYearRange(s) }}</span>
                                </template>
                                <template v-if="s.idno">
                                  <span
                                    v-if="s.nation || formatSurveyYearRange(s)"
                                    class="lr-info-survey-meta-sep"
                                    >·</span
                                  >
                                  <span class="lr-info-survey-idno">{{ s.idno }}</span>
                                </template>
                              </div>
                            </div>
                          </div>
                        </div>
                        </div>
                      </div>
                    </template>
                    <span v-else class="text-medium-emphasis">—</span>
                  </td>
                </tr>
                <tr v-if="requestRow.org_rec">
                  <th class="font-weight-bold">{{ t('receiving_organization_name', 'Receiving organization') }}</th>
                  <td>{{ requestRow.org_rec }}</td>
                </tr>
                <tr v-if="requestRow.tel">
                  <th class="font-weight-bold">{{ t('telephone', 'Telephone') }}</th>
                  <td>{{ requestRow.tel }}</td>
                </tr>
                <tr v-if="requestRow.datause">
                  <th class="font-weight-bold">{{ t('intended_use_of_data', 'Intended use') }}</th>
                  <td style="white-space: pre-wrap">{{ requestRow.datause }}</td>
                </tr>
                <tr v-if="requestRow.outputs">
                  <th class="font-weight-bold">{{ t('list_expected_output', 'Expected outputs') }}</th>
                  <td style="white-space: pre-wrap">{{ requestRow.outputs }}</td>
                </tr>
                <tr v-if="requestRow.compdate">
                  <th class="font-weight-bold">{{ t('expected_completion_date', 'Expected completion') }}</th>
                  <td>{{ requestRow.compdate }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </v-window-item>

        <v-window-item value="process">
          <v-card class="pa-4 lr-process-tab-card" elevation="1">
            <div class="lr-field-group">
              <div class="lr-process-field-label">{{ t('request_status', 'Status') }}</div>
              <v-select
                v-model="process.status"
                :items="processStatusItems"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                hide-details
                class="lr-process-status-select"
              />
            </div>

            <div class="lr-field-group">
              <div class="lr-process-field-label">{{ t('grant_access_to_files', 'Grant access to files') }}</div>
              <v-card
                v-if="process.filesBySurvey.length"
                variant="outlined"
                rounded="lg"
                flat
                class="lr-files-grid-card mb-4"
              >
                <v-table density="compact" class="lr-files-grid">
                  <thead>
                    <tr>
                      <th scope="col" class="lr-files-col-check text-center lr-files-col-check--header">
                        <v-checkbox
                          :model-value="allFilesGloballySelected()"
                          :indeterminate="someFilesGloballySelected()"
                          hide-details
                          density="compact"
                          :aria-label="t('select_all_files', 'Select all files')"
                          @update:model-value="(v) => toggleAllFilesGlobally(!!v)"
                        />
                      </th>
                      <th scope="col" class="lr-files-col-file">{{ t('file', 'File') }}</th>
                      <th scope="col" class="lr-files-col-limit">{{ t('download_limit', 'Limit') }}</th>
                      <th scope="col" class="lr-files-col-expiry lr-files-col-expiry--header">
                        <span class="d-block">{{ t('expiry_date', 'Expiry') }}</span>
                        <span class="lr-files-expiry-format-hint d-block text-caption text-medium-emphasis font-weight-regular">
                          YYYY-MM-DD
                        </span>
                      </th>
                    </tr>
                    <tr class="lr-files-subheader">
                      <th class="lr-files-col-check lr-files-subheader-cell" scope="col"></th>
                      <th class="lr-files-col-file lr-files-subheader-cell" scope="col" aria-hidden="true"></th>
                      <th class="lr-files-col-limit lr-files-cell-input lr-files-subheader-cell" scope="col">
                        <v-text-field
                          v-model.number="bulkFileGlobal.limit"
                          type="number"
                          min="1"
                          max="99"
                          density="compact"
                          variant="outlined"
                          hide-details
                          single-line
                          class="lr-files-inline-field"
                          :aria-label="t('download_limit_all_files', 'Download limit for all files')"
                          @update:model-value="scheduleApplyBulkFileSettings"
                        />
                      </th>
                      <th class="lr-files-col-expiry lr-files-cell-input lr-files-subheader-cell" scope="col">
                        <v-text-field
                          v-model="bulkFileGlobal.expiry"
                          type="date"
                          density="compact"
                          variant="outlined"
                          hide-details
                          single-line
                          class="lr-files-inline-field"
                          placeholder="YYYY-MM-DD"
                          title="YYYY-MM-DD"
                          :aria-label="t('expiry_date_all_files', 'Expiry for all files (YYYY-MM-DD)')"
                          @update:model-value="scheduleApplyBulkFileSettings"
                        />
                      </th>
                    </tr>
                  </thead>
                  <tbody
                    v-for="(grp, gIdx) in process.filesBySurvey"
                    :key="grp.survey_id"
                    class="lr-files-tbody-section"
                  >
                    <tr class="lr-files-section-row">
                      <td colspan="4" class="lr-files-section-title text-body-2 font-weight-bold text-high-emphasis">
                        {{ grp.nation }} — {{ grp.title }} ({{ grp.year_start }})
                      </td>
                    </tr>
                    <template v-if="grp.files.length">
                      <tr
                        v-for="(f, fIdx) in grp.files"
                        :key="f.resource_id"
                        class="lr-files-file-row"
                        :class="{ 'lr-files-file-row--stripe': fIdx % 2 === 1 }"
                      >
                        <td class="lr-files-col-check text-center">
                          <v-checkbox
                            v-model="f.selected"
                            hide-details
                            density="compact"
                            :aria-label="`${t('file', 'File')}: ${f.filename}`"
                          />
                        </td>
                        <td class="lr-files-col-file">
                          <span class="lr-files-filename">{{ f.filename }}</span>
                        </td>
                        <td class="lr-files-col-limit lr-files-cell-input">
                          <v-text-field
                            v-model.number="f.download_limit"
                            type="number"
                            min="1"
                            max="99"
                            density="compact"
                            hide-details
                            variant="outlined"
                            single-line
                            class="lr-files-inline-field"
                            :disabled="!f.selected"
                          />
                        </td>
                        <td class="lr-files-col-expiry lr-files-cell-input">
                          <v-text-field
                            v-model="f.expiry_date"
                            type="date"
                            density="compact"
                            hide-details
                            variant="outlined"
                            single-line
                            class="lr-files-inline-field"
                            placeholder="YYYY-MM-DD"
                            title="YYYY-MM-DD"
                            :aria-label="`${t('expiry_date', 'Expiry')} (YYYY-MM-DD)`"
                            :disabled="!f.selected"
                            @blur="f.expiry_date = normalizeExpiryIsoDate(f.expiry_date)"
                          />
                        </td>
                      </tr>
                    </template>
                    <tr v-else class="lr-files-empty-row">
                      <td colspan="4" class="text-medium-emphasis py-2">
                        {{ t('no_microdata_files_found', 'No files') }}
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card>
            </div>

            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('comments', 'Comments') }}</div>
              <v-textarea
                v-model="process.comments"
                rows="5"
                variant="outlined"
                hide-details="auto"
              />
              <div class="text-caption text-medium-emphasis mt-1">{{ t('comments_visible_to_users', '') }}</div>
            </div>

            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('ip_limit', 'IP limit') }}</div>
              <v-text-field
                v-model="process.ip_limit"
                variant="outlined"
                density="comfortable"
                hint="Comma-separated IPs"
                persistent-hint
              />
            </div>

            <div class="lr-field-group">
              <v-switch
                v-model="process.notify"
                :label="t('notify_user_by_email', 'Notify user by email')"
                color="primary"
                density="compact"
                hide-details
                inset
              />
            </div>

            <div v-if="saveMsg" class="lr-tab-feedback-alert-wrap">
              <v-alert :type="saveOk ? 'success' : 'error'" variant="tonal" density="compact">
                {{ saveMsg }}
              </v-alert>
            </div>
            <div class="d-flex align-center gap-3 flex-wrap lr-tab-actions-row">
              <v-btn color="primary" :loading="saving" :disabled="!detail.can_edit" @click="saveProcess">
                {{ t('update', 'Update') }}
              </v-btn>
            </div>

            <div v-if="detail.comments_history?.length" class="lr-process-history-wrap">
              <v-expansion-panels>
                <v-expansion-panel :title="t('request_history', 'History')">
                  <v-expansion-panel-text>
                    <div v-for="(h, idx) in detail.comments_history" :key="idx" class="mb-3 text-body-2">
                      <div class="text-caption text-medium-emphasis">
                        {{ formatTs(h.created) }} — {{ h.first_name }} {{ h.last_name }}
                      </div>
                      <div style="white-space: pre-wrap">{{ h.description }}</div>
                    </div>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </div>
          </v-card>
        </v-window-item>

        <v-window-item value="comm">
          <v-card class="pa-4 mb-4" elevation="1">
            <div class="lr-comm-compose-heading text-subtitle-2 font-weight-bold text-high-emphasis">
              {{ t('compose_email', 'Compose email') }}
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('to', 'To') }}</div>
              <v-text-field v-model="mailForm.to" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('cc', 'CC') }}</div>
              <v-text-field v-model="mailForm.cc" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('subject', 'Subject') }}</div>
              <v-text-field v-model="mailForm.subject" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('body', 'Body') }}</div>
              <v-textarea v-model="mailForm.body" rows="5" variant="outlined" hide-details="auto" />
            </div>
            <div v-if="mailMsg" class="lr-tab-feedback-alert-wrap">
              <v-alert :type="mailOk ? 'success' : 'error'" variant="tonal" density="compact">
                {{ mailMsg }}
              </v-alert>
            </div>
            <v-btn class="lr-tab-action-btn" color="primary" :loading="mailLoading" @click="submitMail">{{
              t('send', 'Send')
            }}</v-btn>
          </v-card>

          <v-card v-if="detail.email_history?.length" class="pa-4" elevation="1">
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-3">{{ t('communicate_history', 'History') }}</div>
            <div v-for="(h, idx) in detail.email_history" :key="idx" class="mb-4 text-body-2">
              <div class="text-caption text-medium-emphasis">
                {{ formatTs(h.created) }} — {{ h.first_name }} {{ h.last_name }}
              </div>
              <pre class="text-caption mt-1" style="white-space: pre-wrap">{{ formatHistoryDesc(h.description) }}</pre>
            </div>
          </v-card>
        </v-window-item>

        <v-window-item value="mon">
          <v-card class="pa-4 mb-4" elevation="1">
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-3">{{ t('download_summary', 'Download summary') }}</div>
            <v-table v-if="detail.download_summary?.length" density="compact">
              <thead>
                <tr>
                  <th class="font-weight-bold">{{ t('file', 'File') }}</th>
                  <th class="font-weight-bold">{{ t('downloaded', 'Downloaded') }}</th>
                  <th class="font-weight-bold">{{ t('download_limit', 'Limit') }}</th>
                  <th class="font-weight-bold">{{ t('last_accessed', 'Last accessed') }}</th>
                  <th class="font-weight-bold">{{ t('expiry_date', 'Expiry') }}</th>
                  <th class="font-weight-bold">{{ t('status', 'Status') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in detail.download_summary" :key="idx">
                  <td>{{ baseName(row.filepath) }}</td>
                  <td>{{ row.downloads }}</td>
                  <td>{{ row.download_limit }}</td>
                  <td>{{ formatTs(row.lastdownloaded) }}</td>
                  <td>{{ formatTs(row.expiry) }}</td>
                  <td>
                    <v-chip v-if="downloadRowActive(row)" size="small" color="success" variant="tonal">{{
                      t('ACTIVE', 'Active')
                    }}</v-chip>
                    <v-chip v-else size="small" color="error" variant="tonal">{{ t('EXPIRED', 'Expired') }}</v-chip>
                  </td>
                </tr>
              </tbody>
            </v-table>
            <p v-else class="text-medium-emphasis">{{ t('no_records_found', 'No records') }}</p>
          </v-card>

          <v-card class="pa-4" elevation="1">
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-3">{{ t('download_log', 'Download log') }}</div>
            <v-table v-if="detail.download_log?.length" density="compact">
              <thead>
                <tr>
                  <th class="font-weight-bold">{{ t('file', 'File') }}</th>
                  <th class="font-weight-bold">{{ t('ip_address', 'IP') }}</th>
                  <th class="font-weight-bold">{{ t('username', 'User') }}</th>
                  <th class="font-weight-bold">{{ t('dated', 'Dated') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in detail.download_log" :key="idx">
                  <td>{{ baseName(row.filepath) }}</td>
                  <td>{{ row.ip }}</td>
                  <td>{{ row.username }}</td>
                  <td>{{ formatTs(row.created) }}</td>
                </tr>
              </tbody>
            </v-table>
            <p v-else class="text-medium-emphasis">{{ t('no_records_found', 'No records') }}</p>
          </v-card>
        </v-window-item>

        <v-window-item value="fwd">
          <v-card class="pa-4 mb-4" elevation="1">
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('to', 'To') }}</div>
              <v-text-field v-model="fwdForm.to" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('cc', 'CC') }}</div>
              <v-text-field v-model="fwdForm.cc" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('subject', 'Subject') }}</div>
              <v-text-field v-model="fwdForm.subject" variant="outlined" density="comfortable" hide-details="auto" />
            </div>
            <div class="lr-field-group">
              <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('body', 'Body') }}</div>
              <v-textarea v-model="fwdForm.body" rows="3" variant="outlined" hide-details="auto" />
            </div>
            <div v-if="fwdMsg" class="lr-tab-feedback-alert-wrap">
              <v-alert :type="fwdOk ? 'success' : 'error'" variant="tonal" density="compact">
                {{ fwdMsg }}
              </v-alert>
            </div>
            <v-btn class="lr-tab-action-btn" color="primary" :loading="fwdLoading" @click="submitForward">{{
              t('send', 'Send')
            }}</v-btn>
          </v-card>

          <v-card v-if="detail.forward_history?.length" class="pa-4" elevation="1">
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-3">{{ t('forward_history', 'Forward history') }}</div>
            <div v-for="(h, idx) in detail.forward_history" :key="idx" class="mb-4 text-body-2">
              <div class="text-caption text-medium-emphasis">
                {{ formatTs(h.created) }} — {{ h.first_name }} {{ h.last_name }}
              </div>
              <pre class="text-caption mt-1" style="white-space: pre-wrap">{{ formatHistoryDesc(h.description) }}</pre>
            </div>
          </v-card>
        </v-window-item>
      </v-window>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLicensedRequestsApi } from '../composables/useLicensedRequestsApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

const VALID_EDIT_TABS = new Set(['info', 'process', 'comm', 'mon', 'fwd']);

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
  /** Present when URL is `/edit/:id/:tab` */
  tab: {
    type: String,
    default: '',
  },
});

defineOptions({ name: 'LicensedRequestEditPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const route = useRoute();
const router = useRouter();
const { fetchDetail, patchDetail, sendMail, forwardMail } = useLicensedRequestsApi();

function normalizeEditTab(v) {
  const s = String(v ?? '').toLowerCase();
  return VALID_EDIT_TABS.has(s) ? s : 'info';
}

/** Synced with vue-router: `/edit/:id` → info; `/edit/:id/process` → process, etc. */
const tab = computed({
  get() {
    const raw = route.params.tab;
    return normalizeEditTab(raw !== undefined && raw !== null && raw !== '' ? raw : 'info');
  },
  set(next) {
    const v = normalizeEditTab(next);
    const id = String(props.id ?? route.params.id ?? '');
    if (!id) return;
    const dest =
      v === 'info'
        ? { name: 'licensed-request-edit', params: { id } }
        : { name: 'licensed-request-edit-tab', params: { id, tab: v } };
    router.replace(dest).catch(() => {});
  },
});
const detail = ref(null);
const pageLoading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveMsg = ref('');
const saveOk = ref(false);

const mailLoading = ref(false);
const mailMsg = ref('');
const mailOk = ref(false);
const mailForm = reactive({ to: '', cc: '', subject: '', body: '' });

const fwdLoading = ref(false);
const fwdMsg = ref('');
const fwdOk = ref(false);
const fwdForm = reactive({ to: '', cc: '', subject: '', body: '' });

const process = reactive({
  status: 'PENDING',
  comments: '',
  ip_limit: '',
  notify: true,
  filesBySurvey: [],
});

function defaultExpiryDateDaysFromNow(days) {
  const d = new Date();
  d.setDate(d.getDate() + (Number(days) > 0 ? Number(days) : 5));
  return d.toISOString().slice(0, 10);
}

/** Single bulk limit/expiry row (applies to every file across all studies). */
const bulkFileGlobal = reactive({
  limit: 3,
  expiry: defaultExpiryDateDaysFromNow(5),
});

const requestRow = computed(() => detail.value?.request || {});
const userRow = computed(() => detail.value?.request?.user || {});

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  {
    title: t('home', 'Home'),
    href: `${siteBaseUrl.value}/admin`,
  },
  {
    title: t('licensed_requests_breadcrumb', 'Licensed requests'),
    href: `${siteBaseUrl.value}/admin/licensed_requests`,
  },
  {
    title: t('edit', 'Edit'),
    disabled: true,
  },
]);

const pageHeading = computed(() => {
  const title = String(requestRow.value?.request_title || '').trim();
  if (title) return title;
  return `#${props.id}`;
});

/** Tonal chip color for request status pill under page title */
const requestStatusChipColor = computed(() => {
  const s = String(requestRow.value?.status || '').toUpperCase();
  const map = {
    PENDING: 'warning',
    APPROVED: 'success',
    DENIED: 'error',
    MOREINFO: 'info',
    CANCELLED: 'secondary',
  };
  return map[s] || 'primary';
});

const processStatusItems = computed(() => [
  { title: t('pending', 'Pending'), value: 'PENDING' },
  { title: t('approve', 'Approve'), value: 'APPROVED' },
  { title: t('deny', 'Deny'), value: 'DENIED' },
  { title: t('request_more_info', 'More info'), value: 'MOREINFO' },
  { title: t('cancel_authorization', 'Cancel'), value: 'CANCELLED' },
]);

function catalogStudyUrl(sid) {
  const base = siteUrl.value?.replace(/\/$/, '') || '';
  return `${base}/admin/catalog/edit/${sid}`;
}

/** Display year or start–end range for request info datasets. */
function formatSurveyYearRange(s) {
  if (!s) return '';
  const ys = s.year_start;
  const ye = s.year_end;
  const ysStr = ys != null && ys !== '' ? String(ys).trim() : '';
  const yeStr = ye != null && ye !== '' ? String(ye).trim() : '';
  if (ysStr && yeStr && ysStr !== yeStr) return `${ysStr}–${yeStr}`;
  if (ysStr) return ysStr;
  if (yeStr) return yeStr;
  return '';
}

function formatTs(u) {
  if (!u) return '';
  const d = new Date(Number(u) * 1000);
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString();
}

function baseName(path) {
  if (!path) return '';
  const s = String(path).replace(/\\/g, '/');
  const i = s.lastIndexOf('/');
  return i >= 0 ? s.slice(i + 1) : s;
}

/** ISO calendar date for `<input type="date">` and API (YYYY-MM-DD). */
function normalizeExpiryIsoDate(v) {
  if (v == null || v === '') return '';
  const s = String(v).trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
  const d = new Date(s);
  return Number.isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10);
}

function downloadRowActive(row) {
  const now = Date.now() / 1000;
  const exp = Number(row.expiry) || 0;
  const dl = Number(row.downloads) || 0;
  const lim = Number(row.download_limit) || 0;
  return exp >= now && dl < lim;
}

function formatHistoryDesc(desc) {
  if (!desc) return '';
  const s = String(desc);
  if (s.startsWith('a:') || s.includes('{')) {
    try {
      // PHP serialized — show raw for admins
      return s.length > 500 ? `${s.slice(0, 500)}…` : s;
    } catch {
      return s;
    }
  }
  return s;
}

function cloneFilesFromDetail(d) {
  const groups = JSON.parse(JSON.stringify(d.files_by_survey || []));
  for (const g of groups) {
    for (const f of g.files) {
      if (!f.expiry_date && f.expiry) {
        const d0 = new Date(Number(f.expiry) * 1000);
        f.expiry_date = Number.isNaN(d0.getTime()) ? '' : d0.toISOString().slice(0, 10);
      }
      if (f.expiry_date) {
        f.expiry_date = normalizeExpiryIsoDate(f.expiry_date);
      }
    }
  }
  return groups;
}

function flattenFilesForPatch(groups) {
  const out = [];
  for (const g of groups) {
    for (const f of g.files) {
      out.push({
        resource_id: f.resource_id,
        selected: !!f.selected,
        download_limit: Number(f.download_limit) || 3,
        expiry: normalizeExpiryIsoDate(f.expiry_date) || '',
      });
    }
  }
  return out;
}

function iterAllFileRows(groups) {
  const rows = [];
  for (const g of groups || []) {
    for (const f of g.files || []) {
      rows.push(f);
    }
  }
  return rows;
}

function allFilesGloballySelected() {
  const rows = iterAllFileRows(process.filesBySurvey);
  if (!rows.length) return false;
  return rows.every((f) => f.selected);
}

function someFilesGloballySelected() {
  const rows = iterAllFileRows(process.filesBySurvey);
  if (!rows.length) return false;
  const n = rows.filter((f) => f.selected).length;
  return n > 0 && n < rows.length;
}

function toggleAllFilesGlobally(checked) {
  for (const g of process.filesBySurvey) {
    for (const f of g.files || []) {
      f.selected = checked;
    }
  }
}

function applyBulkValuesToFileRows(files, lim, exp) {
  for (const f of files) {
    f.download_limit = lim;
    if (exp) {
      f.expiry_date = exp;
    }
  }
}

/** Apply bulk limit + expiry to every file row in every study. */
function applyBulkFileSettings() {
  const lim = Math.min(99, Math.max(1, Number(bulkFileGlobal.limit) || 3));
  const exp = normalizeExpiryIsoDate(bulkFileGlobal.expiry);
  bulkFileGlobal.expiry = exp;
  for (const g of process.filesBySurvey) {
    if (g.files?.length) {
      applyBulkValuesToFileRows(g.files, lim, exp);
    }
  }
}

/** Run after v-model sync so header fields see the latest value (listener order). */
function scheduleApplyBulkFileSettings() {
  nextTick(() => applyBulkFileSettings());
}

function applyDetail(d) {
  detail.value = d;
  const st = d.request?.status || '';
  const allowed = ['PENDING', 'APPROVED', 'DENIED', 'MOREINFO', 'CANCELLED'];
  process.status = allowed.includes(st) ? st : 'PENDING';
  process.comments = '';
  process.ip_limit = d.request?.ip_limit || '';
  process.notify = true;
  process.filesBySurvey = cloneFilesFromDetail(d);
  bulkFileGlobal.limit = 3;
  bulkFileGlobal.expiry = defaultExpiryDateDaysFromNow(5);

  mailForm.to = d.default_email_to || '';
  mailForm.cc = '';
  mailForm.subject = `RE: [#${d.request?.id}] - ${d.request?.request_title || ''}`;
  mailForm.body = '';

  fwdForm.to = '';
  fwdForm.cc = '';
  fwdForm.subject = `FWD: [#${d.request?.id}] - ${d.request?.request_title || ''}`;
  fwdForm.body = '';
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  try {
    const rid = props.id ?? route.params.id;
    const d = await fetchDetail(rid);
    applyDetail(d);
  } catch (e) {
    loadError.value = e?.response?.data?.message || e?.message || 'Failed to load';
    detail.value = null;
  } finally {
    pageLoading.value = false;
  }
}

async function saveProcess() {
  saving.value = true;
  saveMsg.value = '';
  try {
    const rid = props.id ?? route.params.id;
    const payload = {
      status: process.status,
      comments: process.comments,
      ip_limit: process.ip_limit,
      notify: process.notify,
      files: flattenFilesForPatch(process.filesBySurvey),
    };
    const d = await patchDetail(rid, payload);
    applyDetail(d);
    saveOk.value = true;
    saveMsg.value = t('form_update_success', 'Your changes have been saved!');
  } catch (e) {
    saveOk.value = false;
    saveMsg.value = e?.response?.data?.message || e?.message || 'Error';
  } finally {
    saving.value = false;
  }
}

async function submitMail() {
  mailLoading.value = true;
  mailMsg.value = '';
  try {
    const rid = props.id ?? route.params.id;
    const d = await sendMail(rid, { ...mailForm });
    applyDetail(d);
    mailOk.value = true;
    mailMsg.value = t('email_sent', 'Sent');
  } catch (e) {
    mailOk.value = false;
    mailMsg.value = e?.response?.data?.message || e?.message || 'Error';
  } finally {
    mailLoading.value = false;
  }
}

async function submitForward() {
  fwdLoading.value = true;
  fwdMsg.value = '';
  try {
    const rid = props.id ?? route.params.id;
    const d = await forwardMail(rid, { ...fwdForm });
    applyDetail(d);
    fwdOk.value = true;
    fwdMsg.value = t('email_sent', 'Sent');
  } catch (e) {
    fwdOk.value = false;
    fwdMsg.value = e?.response?.data?.message || e?.message || 'Error';
  } finally {
    fwdLoading.value = false;
  }
}

watch(
  () => route.params.id,
  () => load(),
  { immediate: false }
);

onMounted(() => load());
</script>

<style scoped>
.lr-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.lr-breadcrumbs :deep(.v-breadcrumbs-item),
.lr-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}

.lr-status-pill {
  text-transform: none;
  letter-spacing: 0.02em;
}

/* Flat chip: ensure label uses theme on-* color at full opacity (readable on colored fill) */
.lr-status-pill :deep(.v-chip__content) {
  opacity: 1;
}

/* Request information tab — dataset(s) list */
.lr-info-datasets-label {
  vertical-align: top !important;
}

.lr-info-datasets-cell {
  vertical-align: top;
}

.lr-info-survey-scroll {
  max-height: min(45vh, 320px);
  overflow-y: scroll;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable;
  scrollbar-width: thin;
  scrollbar-color: rgba(var(--v-theme-on-surface-variant), 0.5) rgba(var(--v-theme-on-surface), 0.08);
  padding-right: 2px;
}

.lr-info-survey-scroll::-webkit-scrollbar {
  width: 10px;
}

.lr-info-survey-scroll::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 5px;
}

.lr-info-survey-scroll::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface-variant), 0.45);
  border-radius: 5px;
}

.lr-info-survey-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-on-surface-variant), 0.65);
}

.lr-info-survey-stack {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.lr-info-survey-card {
  margin: 0;
  padding: 10px 8px 12px 0;
  border: none;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 0;
  background: transparent;
}

.lr-info-survey-title-link {
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
  display: inline-flex;
  align-items: flex-start;
  gap: 2px;
  line-height: 1.35;
}

.lr-info-survey-title-link:hover .lr-info-survey-title-text {
  text-decoration: underline;
}

.lr-info-survey-ext-icon {
  flex-shrink: 0;
  margin-top: 2px;
  opacity: 0.88;
}

.lr-info-survey-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0 4px;
}

.lr-info-survey-meta-sep {
  opacity: 0.55;
  user-select: none;
}

.lr-info-survey-idno {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 0.75rem;
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.07);
  color: rgb(var(--v-theme-on-surface));
}

.lr-info-survey-icon {
  margin-top: 1px;
  opacity: 0.92;
}

/* Communicate tab: space below "Compose email" (typography can override margin utilities) */
.lr-comm-compose-heading {
  display: block;
  padding-bottom: 20px;
}

/* Process tab: “Request history” expansion block below Update */
.lr-process-history-wrap {
  margin-top: 32px;
}

/* Communicate / Forward: spacing around feedback alert (wrapper avoids v-alert margin resets) */
.lr-tab-feedback-alert-wrap {
  margin-top: 32px;
  margin-bottom: 20px;
}

/* Primary actions on Process / Communicate / Forward tabs */
.lr-tab-actions-row {
  margin-top: 28px;
}

.lr-tab-action-btn {
  margin-top: 28px;
}

.lr-field-group + .lr-field-group {
  margin-top: 16px;
}

.lr-process-tab-card .lr-field-group + .lr-field-group {
  margin-top: 22px;
}

.lr-process-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  margin-bottom: 10px;
  color: rgb(var(--v-theme-on-surface));
}

.lr-process-status-select {
  max-width: 28rem;
}

/* Process tab — grant files table */
.lr-files-grid-card {
  overflow-x: auto;
  overflow-y: visible;
  /* Softer outline than default outlined card (less harsh than near-black) */
  border-color: rgba(var(--v-theme-on-surface), 0.14) !important;
  box-shadow: none;
}

.lr-files-grid {
  width: 100%;
  table-layout: fixed;
  /* Compact density defaults thead to 40px — too short for header checkbox */
  --v-table-header-height: 48px;
}

.lr-files-grid :deep(thead tr:first-child th) {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgb(var(--v-theme-on-surface));
  background: rgba(var(--v-theme-on-surface), 0.07);
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding: 6px 8px;
  vertical-align: middle;
}

.lr-files-grid :deep(thead tr.lr-files-subheader th.lr-files-subheader-cell) {
  text-transform: none;
  letter-spacing: normal;
  font-size: inherit;
  font-weight: 400;
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding: 4px 8px 6px;
  vertical-align: middle;
  height: auto;
  min-height: 34px;
}

.lr-files-grid :deep(thead th.lr-files-col-check--header) {
  text-transform: none;
  letter-spacing: normal;
  font-size: inherit;
  font-weight: 400;
}

.lr-files-grid :deep(thead th.lr-files-col-check--header .v-selection-control) {
  justify-content: center;
}

.lr-files-grid :deep(tbody.lr-files-tbody-section:not(:first-of-type) .lr-files-section-row td) {
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.lr-files-grid :deep(.lr-files-section-title) {
  padding-top: 10px;
  padding-bottom: 8px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.lr-files-grid :deep(tbody td) {
  padding: 4px 8px;
  vertical-align: middle;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.lr-files-grid :deep(tbody.lr-files-tbody-section:last-of-type tr:last-child td) {
  border-bottom: none;
}

.lr-files-grid :deep(tr.lr-files-file-row.lr-files-file-row--stripe) {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.lr-files-col-check {
  width: 52px;
}

.lr-files-col-limit {
  width: 96px;
}

.lr-files-col-expiry {
  width: 248px;
}

.lr-files-filename {
  display: block;
  word-break: break-word;
  line-height: 1.35;
  font-size: 0.875rem;
  color: rgb(var(--v-theme-on-surface));
}

.lr-files-cell-input :deep(.v-input) {
  margin-bottom: 0;
}

.lr-files-cell-input :deep(.v-field) {
  min-width: 0;
}

/* Extra-dense outlined fields in this table (both limit + expiry columns). */
.lr-files-grid .lr-files-inline-field :deep(.v-input--density-compact) {
  --v-input-control-height: 22px;
  font-size: 0.75rem;
}

.lr-files-grid .lr-files-inline-field :deep(.v-input--density-compact .v-field--variant-outlined) {
  --v-field-padding-bottom: 0px;
  --v-field-padding-top: 0px;
  --v-field-padding-start: 8px;
  --v-field-padding-end: 8px;
}

.lr-files-grid .lr-files-inline-field :deep(.v-field__input) {
  /* Beat Vuetify max(control-height, 1.5rem + padding) so rows stay short */
  min-height: 22px !important;
  padding-top: 1px !important;
  padding-bottom: 1px !important;
}

.lr-files-grid .lr-files-inline-field :deep(.v-field__input input) {
  font-size: 0.75rem;
  line-height: 1.2;
  min-height: 1.125rem;
}

.lr-files-grid .lr-files-inline-field :deep(.v-field--variant-outlined .v-field__outline) {
  --v-field-border-width: 1px;
}

.lr-files-grid :deep(thead tr:first-child th.lr-files-col-expiry--header .lr-files-expiry-format-hint) {
  text-transform: none;
  letter-spacing: 0.04em;
  font-size: 0.625rem;
  line-height: 1.25;
  margin-top: 2px;
}
</style>
