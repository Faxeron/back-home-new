<script setup lang="ts">
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import { useEstimateEditor } from '@/modules/estimates/composables/useEstimateEditor'
import {
  ESTIMATE_ITEM_HEADERS,
  ESTIMATE_ITEM_LABELS,
} from '@/modules/estimates/config/estimateItemsTable.config'

const props = defineProps<{ estimateId?: number | null }>()

const {
  estimateId,
  estimate,
  form,
  itemsSorted,
  groupTotals,
  grandTotal,
  loading,
  saving,
  errorMessage,
  formatCurrency,
  formatPhoneFromDigits,
  addSku,
  addQty,
  productSuggestions,
  productLoading,
  templateOptions,
  templateCheckLoading,
  vuetifyProduct,
  vuetifySearch,
  handleVuetifySearch,
  handleVuetifySelect,
  addItemBySku,
  applyTemplateOnly,
  updateItemQty,
  updateItemPrice,
  clientMatches,
  clientLookupLoading,
  isClientLocked,
  showClientLookup,
  selectClient,
  createNewClient,
  clearSelectedClient,
  clientLink,
  montajLink,
  copiedKey,
  copyToClipboard,
  saveEstimate,
} = useEstimateEditor(props.estimateId ?? null)
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="flex flex-column gap-2">
        <h2 class="text-xl font-semibold">
          {{ estimateId ? `Смета #${estimateId}` : 'Создание сметы' }}
        </h2>
        <div class="flex items-center gap-2">
          <VBtn
            color="primary"
            prepend-icon="tabler-device-floppy"
            :loading="saving"
            @click="saveEstimate"
          >
            Сохранить
          </VBtn>
        </div>
      </div>
      <div v-if="estimateId && (clientLink || montajLink)" class="estimate-links">
        <div v-if="clientLink" class="estimate-link-card">
          <div class="estimate-link-label">Клиент</div>
          <div class="estimate-link-actions">
            <a
              :href="clientLink"
              class="estimate-link-action"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="pi pi-external-link" aria-hidden="true" />
              Открыть смету
            </a>
            <VBtn
              icon="tabler-copy"
              variant="text"
              size="small"
              aria-label="Копировать ссылку"
              @click="copyToClipboard(clientLink, 'client')"
            />
          </div>
          <div v-if="copiedKey === 'client'" class="estimate-link-copied">Ссылка скопирована</div>
        </div>
        <div v-if="montajLink" class="estimate-link-card">
          <div class="estimate-link-label">Монтажник</div>
          <div class="estimate-link-actions">
            <a
              :href="montajLink"
              class="estimate-link-action"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="pi pi-external-link" aria-hidden="true" />
              Открыть смету
            </a>
            <VBtn
              icon="tabler-copy"
              variant="text"
              size="small"
              aria-label="Копировать ссылку"
              @click="copyToClipboard(montajLink, 'montaj')"
            />
          </div>
          <div v-if="copiedKey === 'montaj'" class="estimate-link-copied">Ссылка скопирована</div>
        </div>
      </div>
    </div>

    <VCard class="estimate-vuexy-card">
      <VCardText>
        <VRow align="stretch">
          <VCol
            cols="12"
            lg="8"
            class="d-flex"
          >
            <VCard variant="outlined" class="estimate-vuexy-frame flex-grow-1">
              <div class="estimate-vuexy-legend">Добавить позицию</div>
              <VCardText class="estimate-vuexy-content d-flex flex-column h-100">
                <VRow class="align-center">
                  <VCol cols="12" md="3">
                    <VTextField
                      v-model.number="addQty"
                      type="number"
                      min="0"
                      label="Кол-во"
                      hide-details
                    />
                  </VCol>
                  <VCol cols="12" md="9">
                    <VAutocomplete
                      v-model="vuetifyProduct"
                      v-model:search="vuetifySearch"
                      :items="productSuggestions"
                      item-title="name"
                      return-object
                      :loading="productLoading"
                      :no-data-text="'Нет данных'"
                      hide-details
                      label="Товар"
                      placeholder="Начните вводить название"
                      @update:search="handleVuetifySearch"
                      @update:modelValue="handleVuetifySelect"
                    />
                  </VCol>
                </VRow>
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <VBtn
                    color="primary"
                    prepend-icon="tabler-plus"
                    :loading="saving"
                    :disabled="!addSku.trim()"
                    @click="addItemBySku"
                  >
                    Добавить товар
                  </VBtn>
                  <VBtn
                    v-for="template in templateOptions"
                    :key="template.id"
                    variant="tonal"
                    prepend-icon="tabler-bolt"
                    :loading="saving || templateCheckLoading"
                    :disabled="!addSku.trim()"
                    @click="applyTemplateOnly(template.id)"
                  >
                    {{ template.title }}
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol
            cols="12"
            lg="4"
            class="d-flex"
          >
            <VCard variant="outlined" class="estimate-vuexy-frame flex-grow-1">
              <div class="estimate-vuexy-legend">Клиент</div>
              <VCardText class="estimate-vuexy-content d-flex flex-column h-100">
                <div class="d-flex flex-column gap-3">
                  <VTextField
                    v-model="form.client_name"
                    label="Имя *"
                    hide-details
                    :readonly="isClientLocked"
                  />
                  <AppPhoneField
                    v-model="form.client_phone"
                    placeholder="+7 000 000 00 00"
                    hide-details
                    :readonly="isClientLocked"
                  />
                  <div v-if="isClientLocked" class="d-flex justify-end">
                    <VBtn
                      variant="text"
                      size="small"
                      prepend-icon="tabler-edit"
                      @click="clearSelectedClient"
                    >
                      Сменить клиента
                    </VBtn>
                  </div>
                  <VCard v-if="showClientLookup" variant="outlined">
                    <VList density="compact">
                      <VListItem @click="createNewClient">
                        <template #prepend>
                          <VIcon icon="tabler-user-plus" size="18" />
                        </template>
                        <VListItemTitle>Создать нового клиента</VListItemTitle>
                      </VListItem>
                      <VDivider />
                      <VListItem v-if="clientLookupLoading">
                        <VListItemTitle>Поиск...</VListItemTitle>
                      </VListItem>
                      <VListItem v-else-if="!clientMatches.length">
                        <VListItemTitle>Совпадений нет</VListItemTitle>
                      </VListItem>
                      <template v-else>
                        <VListItem
                          v-for="client in clientMatches"
                          :key="client.id"
                          @click="selectClient(client)"
                        >
                          <VListItemTitle>{{ client.name || 'Без имени' }}</VListItemTitle>
                          <VListItemSubtitle>
                            {{ client.phone || formatPhoneFromDigits(client.phone_normalized) || 'Без телефона' }}
                          </VListItemSubtitle>
                        </VListItem>
                      </template>
                    </VList>
                  </VCard>
                  <VTextField
                    v-model="form.site_address"
                    label="Адрес участка"
                    hide-details
                  />
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <Card v-if="estimate?.counterparty">
      <template #content>
        <div class="mb-2 text-sm text-muted">Клиент (снимок из справочника)</div>
        <div class="grid">
          <div class="col-12 md:col-3">
            <label class="text-sm text-muted">ID контрагента</label>
            <InputText :model-value="String(estimate.counterparty?.id ?? '')" class="w-full" disabled />
          </div>
          <div class="col-12 md:col-3">
            <label class="text-sm text-muted">Тип</label>
            <InputText :model-value="estimate.counterparty?.type ?? ''" class="w-full" disabled />
          </div>
          <div class="col-12 md:col-3">
            <label class="text-sm text-muted">Имя</label>
            <InputText :model-value="estimate.counterparty?.name ?? ''" class="w-full" disabled />
          </div>
          <div class="col-12 md:col-3">
            <label class="text-sm text-muted">Телефон</label>
            <InputText :model-value="estimate.counterparty?.phone ?? ''" class="w-full" disabled />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>{{ ESTIMATE_ITEM_LABELS.title }}</template>
      <template #content>
        <div v-if="errorMessage" class="mb-3 text-sm" style="color: #dc2626">
          {{ errorMessage }}
        </div>
        <DataTable
          :value="itemsSorted"
          dataKey="id"
          :loading="loading"
          rowGroupMode="subheader"
          groupRowsBy="groupLabel"
          class="p-datatable-sm"
          stripedRows
        >
          <template #groupheader="{ data }">
            <div class="flex items-center gap-3 py-1">
              <span class="font-semibold">{{ data.groupLabel }}</span>
            </div>
          </template>
          <template #groupfooter="{ data }">
            <td colspan="8" class="estimate-group-summary">
              <div class="flex w-full justify-end text-sm font-medium">
                {{ ESTIMATE_ITEM_LABELS.groupTotal }}: {{ formatCurrency(groupTotals[data.groupLabel]) }}
              </div>
            </td>
          </template>

          <Column field="product.scu" :header="ESTIMATE_ITEM_HEADERS.sku" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.product?.scu ?? '-' }}
            </template>
          </Column>
          <Column field="product.name" :header="ESTIMATE_ITEM_HEADERS.name">
            <template #body="{ data }">
              {{ data.product?.name ?? '-' }}
            </template>
          </Column>
          <Column field="typeLabel" :header="ESTIMATE_ITEM_HEADERS.type" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.typeLabel }}
            </template>
          </Column>
          <Column field="product.unit" :header="ESTIMATE_ITEM_HEADERS.unit" style="inline-size: 8ch;">
            <template #body="{ data }">
              {{ data.product?.unit?.name ?? '-' }}
            </template>
          </Column>
          <Column field="qty" :header="ESTIMATE_ITEM_HEADERS.qty" style="inline-size: 10ch;">
            <template #body="{ data }">
              <InputNumber
                v-model="data.qty"
                class="w-full"
                :min="1"
                :step="1"
                showButtons
                buttonLayout="horizontal"
                incrementButtonIcon="pi pi-plus"
                decrementButtonIcon="pi pi-minus"
                @blur="updateItemQty(data)"
              />
            </template>
          </Column>
          <Column field="price" :header="ESTIMATE_ITEM_HEADERS.price" style="inline-size: 12ch;">
            <template #body="{ data }">
              <InputNumber
                v-model="data.price"
                class="w-full"
                :min="0"
                @blur="updateItemPrice(data)"
              />
            </template>
          </Column>
          <Column field="total" :header="ESTIMATE_ITEM_HEADERS.total" style="inline-size: 14ch;">
            <template #body="{ data }">
              {{ formatCurrency(data.total) }}
            </template>
          </Column>
          <Column field="groupLabel" :header="ESTIMATE_ITEM_HEADERS.group" style="inline-size: 16ch;">
            <template #body="{ data }">
              {{ data.groupLabel }}
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-6 text-muted">{{ ESTIMATE_ITEM_LABELS.emptyEditor }}</div>
          </template>
        </DataTable>
        <Divider />
        <div class="flex justify-end">
          <span class="text-lg font-semibold">
            {{ ESTIMATE_ITEM_LABELS.total }}: {{ formatCurrency(grandTotal) }}
          </span>
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.estimate-group-summary {
  background: #e8f4ff;
  display: flex;
  justify-content: flex-end;
  padding-right: 12px;
}

.estimate-links {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 4px;
}

.estimate-link-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 10px;
  background: rgb(var(--v-theme-surface));
  min-width: 260px;
  min-height: 86px;
}

.estimate-link-label {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.estimate-link-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  text-decoration: none;
}

.estimate-link-action:hover {
  text-decoration: underline;
}

.estimate-link-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.estimate-link-copied {
  font-size: 12px;
  color: rgb(var(--v-theme-success));
  font-weight: 500;
}

.estimate-vuexy-card {
  border: 1px solid #e5e7eb;
}

.estimate-vuexy-frame {
  position: relative;
  overflow: visible;
}

.estimate-vuexy-legend {
  position: absolute;
  top: -10px;
  left: 14px;
  padding: 0 6px;
  font-size: 12px;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  background: rgb(var(--v-theme-surface));
}

.estimate-vuexy-content {
  padding-top: 18px;
}
</style>
