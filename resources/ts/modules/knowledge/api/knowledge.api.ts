import { $api } from '@/utils/api'
import type {
  KnowledgeArticle,
  KnowledgeAttachment,
  KnowledgeListResponse,
  KnowledgeTag,
  KnowledgeTopic,
} from '@/modules/knowledge/types/knowledge.types'

export const fetchKnowledgeArticles = async (params: Record<string, any> = {}): Promise<KnowledgeListResponse> => {
  const response = await $api('/knowledge/articles', { params })

  return {
    data: response?.data ?? [],
    meta: response?.meta ?? response?.data?.meta,
  }
}

export const fetchKnowledgeArticle = async (id: number): Promise<KnowledgeArticle | undefined> => {
  const response = await $api(`/knowledge/articles/${id}`)
  return response?.data as KnowledgeArticle | undefined
}

export const createKnowledgeArticle = async (payload: Record<string, any>): Promise<KnowledgeArticle | undefined> => {
  const response = await $api('/knowledge/articles', {
    method: 'POST',
    body: payload,
  })
  return response?.data as KnowledgeArticle | undefined
}

export const updateKnowledgeArticle = async (
  id: number,
  payload: Record<string, any>,
): Promise<KnowledgeArticle | undefined> => {
  const response = await $api(`/knowledge/articles/${id}`, {
    method: 'PATCH',
    body: payload,
  })
  return response?.data as KnowledgeArticle | undefined
}

export const deleteKnowledgeArticle = async (id: number): Promise<void> => {
  await $api(`/knowledge/articles/${id}`, { method: 'DELETE' })
}

export const fetchKnowledgeTags = async (params: Record<string, any> = {}): Promise<KnowledgeTag[]> => {
  const response = await $api('/knowledge/tags', { params })
  return response?.data ?? []
}

export const fetchKnowledgeTopics = async (params: Record<string, any> = {}): Promise<KnowledgeTopic[]> => {
  const response = await $api('/knowledge/topics', { params })
  return response?.data ?? []
}

export const uploadKnowledgeAttachment = async (
  articleId: number,
  file: File,
): Promise<KnowledgeAttachment | undefined> => {
  const body = new FormData()
  body.append('type', 'file')
  body.append('file', file)

  const response = await $api(`/knowledge/articles/${articleId}/attachments`, {
    method: 'POST',
    body,
  })

  return response?.data as KnowledgeAttachment | undefined
}

export const createKnowledgeLinkAttachment = async (
  articleId: number,
  payload: { type: 'link' | 'video'; url: string; title?: string | null },
): Promise<KnowledgeAttachment | undefined> => {
  const response = await $api(`/knowledge/articles/${articleId}/attachments`, {
    method: 'POST',
    body: payload,
  })

  return response?.data as KnowledgeAttachment | undefined
}

export const deleteKnowledgeAttachment = async (id: number): Promise<void> => {
  await $api(`/knowledge/attachments/${id}`, { method: 'DELETE' })
}
