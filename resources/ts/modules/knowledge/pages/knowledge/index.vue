<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import CustomEditor from '@/@core/components/CustomEditor.vue'
import { knowledgeTopicTypes } from '@/modules/knowledge/config/knowledgeTopics.config'
import {
  createKnowledgeArticle,
  createKnowledgeLinkAttachment,
  deleteKnowledgeArticle,
  deleteKnowledgeAttachment,
  fetchKnowledgeArticle,
  fetchKnowledgeArticles,
  fetchKnowledgeTags,
  fetchKnowledgeTopics,
  updateKnowledgeArticle,
  uploadKnowledgeAttachment,
} from '@/modules/knowledge/api/knowledge.api'
import type {
  KnowledgeArticle,
  KnowledgeAttachment,
  KnowledgeTag,
  KnowledgeTopic,
} from '@/modules/knowledge/types/knowledge.types'

const loading = ref(false)
const saving = ref(false)
const detailLoading = ref(false)
const uploadLoading = ref(false)
const deletingId = ref<number | null>(null)

const listError = ref('')
const formError = ref('')

const search = ref('')
const selectedTagIds = ref<number[]>([])
const selectedTopicIds = ref<number[]>([])

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const articles = ref<KnowledgeArticle[]>([])

const tags = ref<KnowledgeTag[]>([])
const topics = ref<KnowledgeTopic[]>([])

const editorOpen = ref(false)
const viewerOpen = ref(false)
const viewerLoading = ref(false)
const viewerError = ref('')
const viewArticle = ref<KnowledgeArticle | null>(null)

const viewAttachments = computed(() => viewArticle.value?.attachments ?? [])
const viewTags = computed(() => viewArticle.value?.tags ?? [])
const viewTopics = computed(() => viewArticle.value?.topics ?? [])

const topicSelections = reactive<Record<string, string[]>>({})

const form = reactive({
  id: null as number | null,
  title: '',
  body: '',
  is_published: true,
  tags: [] as string[],
  attachments: [] as KnowledgeAttachment[],
})

const linkForm = reactive({
  type: 'link' as 'link' | 'video',
  url: '',
  title: '',
})

const fileModel = ref<File | File[] | null>(null)

const ensureTopicSelections = () => {
  knowledgeTopicTypes.forEach(type => {
    if (!topicSelections[type.value])
      topicSelections[type.value] = []
  })
}

ensureTopicSelections()

const topicOptionsByType = computed(() => {
  const grouped: Record<string, string[]> = {}
  for (const topic of topics.value) {
    if (!grouped[topic.type])
      grouped[topic.type] = []
    grouped[topic.type].push(topic.name)
  }
  return grouped
})

const topicFilterItems = computed(() => {
  return topics.value.map(topic => ({
    id: topic.id,
    label: `${topicTypeTitle(topic.type)}: ${topic.name}`,
  }))
})

const buildQuery = () => ({
  q: search.value.trim() || undefined,
  tag_ids: selectedTagIds.value.length ? selectedTagIds.value.join(',') : undefined,
  topic_ids: selectedTopicIds.value.length ? selectedTopicIds.value.join(',') : undefined,
  per_page: 20,
  page: page.value,
})

const loadArticles = async (reset = true) => {
  loading.value = true
  listError.value = ''
  try {
    if (reset)
      page.value = 1

    const response = await fetchKnowledgeArticles(buildQuery())
    const next = response.data ?? []

    if (reset)
      articles.value = next
    else
      articles.value = [...articles.value, ...next]

    lastPage.value = response.meta?.last_page ?? page.value
    total.value = response.meta?.total ?? articles.value.length
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'Не удалось загрузить статьи.'
  } finally {
    loading.value = false
  }
}

const loadMore = async () => {
  if (loading.value || page.value >= lastPage.value)
    return
  page.value += 1
  await loadArticles(false)
}

const loadFilters = async () => {
  tags.value = await fetchKnowledgeTags()
  topics.value = await fetchKnowledgeTopics()
}

const resetForm = () => {
  form.id = null
  form.title = ''
  form.body = ''
  form.is_published = true
  form.tags = []
  form.attachments = []
  linkForm.url = ''
  linkForm.title = ''
  linkForm.type = 'link'
  fileModel.value = null
  ensureTopicSelections()
  Object.keys(topicSelections).forEach(key => {
    topicSelections[key] = []
  })
  formError.value = ''
}

const setFormFromArticle = (article: KnowledgeArticle) => {
  form.id = article.id
  form.title = article.title ?? ''
  form.body = article.body ?? ''
  form.is_published = article.is_published ?? true
  form.tags = article.tags?.map(tag => tag.name) ?? []
  form.attachments = article.attachments ?? []

  ensureTopicSelections()
  Object.keys(topicSelections).forEach(key => {
    topicSelections[key] = []
  })

  article.topics?.forEach(topic => {
    if (!topicSelections[topic.type])
      topicSelections[topic.type] = []
    topicSelections[topic.type].push(topic.name)
  })
}

const loadArticle = async (id: number) => {
  detailLoading.value = true
  formError.value = ''
  try {
    const article = await fetchKnowledgeArticle(id)
    if (article)
      setFormFromArticle(article)
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'Не удалось загрузить статью.'
  } finally {
    detailLoading.value = false
  }
}

const openCreate = () => {
  resetForm()
  editorOpen.value = true
}

const openEdit = async (article: KnowledgeArticle) => {
  resetForm()
  setFormFromArticle(article)
  editorOpen.value = true
  await loadArticle(article.id)
}

const openView = async (article: KnowledgeArticle) => {
  viewerOpen.value = true
  viewerLoading.value = true
  viewerError.value = ''
  try {
    const response = await fetchKnowledgeArticle(article.id)
    viewArticle.value = response ?? article
  } catch (error: any) {
    viewerError.value = error?.response?.data?.message ?? 'Не удалось загрузить статью.'
    viewArticle.value = article
  } finally {
    viewerLoading.value = false
  }
}

const closeView = () => {
  viewerOpen.value = false
  viewerError.value = ''
  viewArticle.value = null
}

const editFromView = async () => {
  if (!viewArticle.value)
    return
  viewerOpen.value = false
  await openEdit(viewArticle.value)
}

const closeEditor = () => {
  editorOpen.value = false
  resetForm()
}

const buildTopicsPayload = () => {
  const payload: { type: string; name: string }[] = []
  Object.entries(topicSelections).forEach(([type, names]) => {
    names.forEach(name => {
      const trimmed = name.trim()
      if (trimmed)
        payload.push({ type, name: trimmed })
    })
  })
  return payload
}

const normalizeTags = () => {
  return form.tags
    .map(tag => tag.trim())
    .filter(tag => tag.length > 0)
}

const saveArticle = async () => {
  formError.value = ''
  if (!form.title.trim()) {
    formError.value = 'Укажите название статьи.'
    return
  }
  if (!stripHtml(form.body)) {
    formError.value = 'Добавьте текст статьи.'
    return
  }

  saving.value = true
  try {
    const payload = {
      title: form.title.trim(),
      body: form.body,
      is_published: form.is_published,
      tags: normalizeTags(),
      topics: buildTopicsPayload(),
    }

    let response: KnowledgeArticle | undefined
    if (form.id)
      response = await updateKnowledgeArticle(form.id, payload)
    else
      response = await createKnowledgeArticle(payload)

    if (response)
      setFormFromArticle(response)

    await loadFilters()
    await loadArticles(true)
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'Не удалось сохранить статью.'
  } finally {
    saving.value = false
  }
}

const removeArticle = async (article: KnowledgeArticle) => {
  if (!window.confirm('Удалить статью?'))
    return

  deletingId.value = article.id
  try {
    await deleteKnowledgeArticle(article.id)
    await loadArticles(true)
  } catch (error: any) {
    listError.value = error?.response?.data?.message ?? 'Не удалось удалить статью.'
  } finally {
    deletingId.value = null
  }
}

const handleFileSelect = async (value: File | File[] | null) => {
  if (!form.id)
    return
  const files = Array.isArray(value) ? value : value ? [value] : []
  if (!files.length)
    return

  uploadLoading.value = true
  try {
    for (const file of files)
      await uploadKnowledgeAttachment(form.id, file)

    await loadArticle(form.id)
    fileModel.value = null
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'Не удалось загрузить файл.'
  } finally {
    uploadLoading.value = false
  }
}

const handleAddLink = async () => {
  if (!form.id)
    return
  if (!linkForm.url.trim()) {
    formError.value = 'Укажите ссылку.'
    return
  }

  uploadLoading.value = true
  try {
    await createKnowledgeLinkAttachment(form.id, {
      type: linkForm.type,
      url: linkForm.url.trim(),
      title: linkForm.title.trim() || null,
    })
    linkForm.url = ''
    linkForm.title = ''
    await loadArticle(form.id)
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'Не удалось добавить ссылку.'
  } finally {
    uploadLoading.value = false
  }
}

const handleRemoveAttachment = async (attachment: KnowledgeAttachment) => {
  if (!window.confirm('Удалить вложение?'))
    return
  try {
    await deleteKnowledgeAttachment(attachment.id)
    if (form.id)
      await loadArticle(form.id)
  } catch (error: any) {
    formError.value = error?.response?.data?.message ?? 'Не удалось удалить вложение.'
  }
}

const resetFilters = () => {
  search.value = ''
  selectedTagIds.value = []
  selectedTopicIds.value = []
  loadArticles(true)
}

const debouncedReload = useDebounceFn(() => {
  loadArticles(true)
}, 350)

watch([search, selectedTagIds, selectedTopicIds], () => {
  debouncedReload()
}, { deep: true })

onMounted(async () => {
  await loadFilters()
  await loadArticles(true)
})

const topicTypeTitle = (type: string) =>
  knowledgeTopicTypes.find(item => item.value === type)?.title ?? type

const stripHtml = (value?: string) =>
  (value ?? '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()

const formatDate = (value?: string) => {
  if (!value)
    return ''
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('ru-RU')
}

const formatFileSize = (value?: number | null) => {
  if (!value)
    return ''
  const units = ['B', 'KB', 'MB', 'GB']
  let size = value
  let unitIndex = 0
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }
  return `${size.toFixed(1)} ${units[unitIndex]}`
}
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3">
      <div>
        <h2 class="text-h5 mb-1">База знаний</h2>
        <div class="text-sm text-muted">Статьи, документы и инструкции для менеджеров.</div>
      </div>
      <VBtn color="primary" prepend-icon="tabler-plus" @click="openCreate">
        Новая статья
      </VBtn>
    </div>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <VRow>
          <VCol cols="12" md="4">
            <VTextField
              v-model="search"
              label="Поиск по тексту и вложениям"
              prepend-inner-icon="tabler-search"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="selectedTagIds"
              :items="tags"
              item-title="name"
              item-value="id"
              label="Теги"
              multiple
              chips
              clearable
              hide-details
              no-data-text="Нет тегов"
            />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="selectedTopicIds"
              :items="topicFilterItems"
              item-title="label"
              item-value="id"
              label="Темы"
              multiple
              chips
              clearable
              hide-details
              no-data-text="Нет тем"
            />
          </VCol>
        </VRow>

        <div class="d-flex align-center justify-space-between">
          <div class="text-sm text-muted">Всего: {{ total }}</div>
          <VBtn variant="text" size="small" @click="resetFilters">Сбросить</VBtn>
        </div>

        <VAlert
          v-if="listError"
          type="error"
          variant="tonal"
        >
          {{ listError }}
        </VAlert>

        <VProgressLinear v-if="loading" indeterminate color="primary" />

        <div v-if="!loading && !articles.length" class="text-sm text-muted">
          Статей пока нет.
        </div>

        <VRow v-else>
          <VCol
            v-for="item in articles"
            :key="item.id"
            cols="12"
            md="6"
          >
            <VCard class="knowledge-card">
              <VCardText class="d-flex flex-column gap-2 knowledge-card-body">
                <div class="d-flex align-center justify-space-between gap-2">
                  <div class="text-subtitle-1 font-weight-bold">
                    {{ item.title }}
                  </div>
                  <div class="d-flex align-center gap-1 text-xs text-muted">
                    <span>Файлов:</span>
                    <VChip size="x-small" color="secondary" variant="tonal">
                      {{ item.attachments_count ?? 0 }}
                    </VChip>
                  </div>
                </div>
                <div class="text-sm text-muted">
                  {{ stripHtml(item.body).slice(0, 140) }}{{ stripHtml(item.body).length > 140 ? '…' : '' }}
                </div>
                <div class="d-flex flex-wrap gap-1 knowledge-card-tags">
                  <VChip
                    v-for="tag in item.tags ?? []"
                    :key="`tag-${item.id}-${tag.id}`"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ tag.name }}
                  </VChip>
                  <VChip
                    v-for="topic in item.topics ?? []"
                    :key="`topic-${item.id}-${topic.id}`"
                    size="x-small"
                    color="primary"
                    variant="tonal"
                  >
                    {{ topicTypeTitle(topic.type) }}: {{ topic.name }}
                  </VChip>
                </div>
              </VCardText>
              <VCardActions class="justify-space-between gap-2">
                <div class="text-xs text-muted">
                  Обновлено: {{ formatDate(item.updated_at) }}
                </div>
                <VBtn
                  variant="text"
                  prepend-icon="tabler-eye"
                  @click="openView(item)"
                >
                  Открыть
                </VBtn>
                <VTooltip text="Редактировать">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      variant="text"
                      icon="tabler-edit"
                      @click="openEdit(item)"
                    />
                  </template>
                </VTooltip>
                <VTooltip text="Удалить">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      variant="text"
                      color="error"
                      icon="tabler-trash"
                      :loading="deletingId === item.id"
                      @click="removeArticle(item)"
                    />
                  </template>
                </VTooltip>
              </VCardActions>
            </VCard>
          </VCol>
        </VRow>

        <div v-if="page < lastPage" class="d-flex justify-center">
          <VBtn variant="text" size="small" :loading="loading" @click="loadMore">
            Показать еще
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </div>

  <VDialog v-model="editorOpen" max-width="980">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ form.id ? `Статья #${form.id}` : 'Новая статья' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="closeEditor" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <VAlert
          v-if="formError"
          type="error"
          variant="tonal"
        >
          {{ formError }}
        </VAlert>

        <VProgressLinear v-if="detailLoading" indeterminate color="primary" />

        <VTextField v-model="form.title" label="Название" hide-details />

        <VCombobox
          v-model="form.tags"
          :items="tags.map(tag => tag.name)"
          label="Теги"
          multiple
          chips
          clearable
          hide-details
          no-data-text="Нет тегов"
        />

        <div>
          <div class="text-sm font-weight-semibold mb-2">Темы</div>
          <VExpansionPanels variant="accordion">
            <VExpansionPanel
              v-for="topicType in knowledgeTopicTypes"
              :key="topicType.value"
            >
              <VExpansionPanelTitle>
                {{ topicType.title }}
              </VExpansionPanelTitle>
              <VExpansionPanelText>
                <VCombobox
                  v-model="topicSelections[topicType.value]"
                  :items="topicOptionsByType[topicType.value] ?? []"
                  :label="`Добавить ${topicType.title.toLowerCase()}`"
                  multiple
                  chips
                  clearable
                  hide-details
                  no-data-text="Нет тем"
                />
              </VExpansionPanelText>
            </VExpansionPanel>
          </VExpansionPanels>
        </div>

        <div>
          <div class="text-sm font-weight-semibold mb-2">Контент статьи</div>
          <CustomEditor v-model="form.body" placeholder="Напишите текст статьи..." />
        </div>

        <div class="attachments">
          <div class="text-sm font-weight-semibold mb-2">Вложения</div>
          <div v-if="!form.id" class="text-sm text-muted">
            Сначала сохраните статью, чтобы загружать файлы и ссылки.
          </div>
          <div v-else class="d-flex flex-column gap-3">
            <VFileInput
              v-model="fileModel"
              label="Добавить файлы"
              prepend-icon="tabler-upload"
              multiple
              hide-details
              :loading="uploadLoading"
              :disabled="uploadLoading"
              @update:modelValue="handleFileSelect"
            />

            <VRow>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="linkForm.type"
                  :items="[
                    { title: 'Ссылка', value: 'link' },
                    { title: 'Видео', value: 'video' },
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Тип"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="5">
                <VTextField v-model="linkForm.url" label="URL" hide-details />
              </VCol>
              <VCol cols="12" md="3">
                <VTextField v-model="linkForm.title" label="Название" hide-details />
              </VCol>
              <VCol cols="12" md="1" class="d-flex align-center">
                <VBtn icon="tabler-plus" :loading="uploadLoading" @click="handleAddLink" />
              </VCol>
            </VRow>

            <VList v-if="form.attachments.length" class="attachments-list">
              <VListItem
                v-for="attachment in form.attachments"
                :key="attachment.id"
              >
                <template #prepend>
                  <VAvatar
                    color="primary"
                    variant="tonal"
                    size="32"
                  >
                    <VIcon :icon="attachment.type === 'file' ? 'tabler-file' : 'tabler-link'" />
                  </VAvatar>
                </template>
                <VListItemTitle>
                  {{ attachment.title || attachment.original_name || attachment.url }}
                </VListItemTitle>
                <VListItemSubtitle>
                  <span v-if="attachment.type === 'file'">
                    {{ attachment.original_name }}
                    <span v-if="attachment.file_size">• {{ formatFileSize(attachment.file_size) }}</span>
                  </span>
                  <span v-else>{{ attachment.url }}</span>
                </VListItemSubtitle>
                <template #append>
                  <div class="d-flex align-center gap-2">
                    <VBtn
                      v-if="attachment.type === 'file' && attachment.download_url"
                      icon="tabler-download"
                      variant="text"
                      :href="attachment.download_url"
                      target="_blank"
                    />
                    <VBtn
                      v-else-if="attachment.url"
                      icon="tabler-external-link"
                      variant="text"
                      :href="attachment.url"
                      target="_blank"
                    />
                    <VBtn
                      icon="tabler-trash"
                      variant="text"
                      color="error"
                      @click="handleRemoveAttachment(attachment)"
                    />
                  </div>
                </template>
              </VListItem>
            </VList>
          </div>
        </div>
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="closeEditor">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" @click="saveArticle">Сохранить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="viewerOpen" max-width="980">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ viewArticle?.title || 'Статья' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="closeView" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <VAlert
          v-if="viewerError"
          type="error"
          variant="tonal"
        >
          {{ viewerError }}
        </VAlert>

        <VProgressLinear v-if="viewerLoading" indeterminate color="primary" />

        <template v-if="viewArticle">
          <div class="d-flex flex-wrap gap-1">
            <VChip
              v-for="tag in viewTags"
              :key="`view-tag-${viewArticle.id}-${tag.id}`"
              size="x-small"
              variant="tonal"
            >
              {{ tag.name }}
            </VChip>
            <VChip
              v-for="topic in viewTopics"
              :key="`view-topic-${viewArticle.id}-${topic.id}`"
              size="x-small"
              color="primary"
              variant="tonal"
            >
              {{ topicTypeTitle(topic.type) }}: {{ topic.name }}
            </VChip>
          </div>

          <div class="knowledge-article-body" v-html="viewArticle.body" />

          <div class="text-xs text-muted">
            Обновлено: {{ formatDate(viewArticle.updated_at) }}
          </div>

          <div>
            <div class="text-sm font-weight-semibold mb-2">Вложения</div>
            <div v-if="!viewAttachments.length" class="text-sm text-muted">
              Нет вложений.
            </div>
            <VList v-else class="attachments-list">
              <VListItem
                v-for="attachment in viewAttachments"
                :key="`view-attachment-${attachment.id}`"
              >
                <template #prepend>
                  <VAvatar
                    color="primary"
                    variant="tonal"
                    size="32"
                  >
                    <VIcon :icon="attachment.type === 'file' ? 'tabler-file' : 'tabler-link'" />
                  </VAvatar>
                </template>
                <VListItemTitle>
                  {{ attachment.title || attachment.original_name || attachment.url }}
                </VListItemTitle>
                <VListItemSubtitle>
                  <span v-if="attachment.type === 'file'">
                    {{ attachment.original_name }}
                    <span v-if="attachment.file_size">• {{ formatFileSize(attachment.file_size) }}</span>
                  </span>
                  <span v-else>{{ attachment.url }}</span>
                </VListItemSubtitle>
                <template #append>
                  <div class="d-flex align-center gap-2">
                    <VBtn
                      v-if="attachment.type === 'file' && attachment.download_url"
                      icon="tabler-download"
                      variant="text"
                      :href="attachment.download_url"
                      target="_blank"
                    />
                    <VBtn
                      v-else-if="attachment.url"
                      icon="tabler-external-link"
                      variant="text"
                      :href="attachment.url"
                      target="_blank"
                    />
                  </div>
                </template>
              </VListItem>
            </VList>
          </div>
        </template>
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="closeView">Закрыть</VBtn>
        <VBtn color="primary" prepend-icon="tabler-edit" @click="editFromView">Редактировать</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.knowledge-card {
  border-radius: 16px;
}

.knowledge-card-body {
  min-height: 220px;
}

.knowledge-card-tags {
  margin-top: auto;
}

.knowledge-article-body :deep(p) {
  margin-block-end: 0.75rem;
}

.knowledge-article-body :deep(ul),
.knowledge-article-body :deep(ol) {
  padding-inline-start: 1.5rem;
  margin-block-end: 0.75rem;
}

.attachments-list {
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
</style>
