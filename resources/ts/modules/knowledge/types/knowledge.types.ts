export type KnowledgeTag = {
  id: number
  name: string
}

export type KnowledgeTopic = {
  id: number
  name: string
  type: string
}

export type KnowledgeAttachment = {
  id: number
  article_id?: number
  type: 'file' | 'link' | 'video'
  title?: string | null
  url?: string | null
  file_path?: string | null
  file_url?: string | null
  download_url?: string | null
  original_name?: string | null
  mime_type?: string | null
  file_size?: number | null
  created_at?: string
  updated_at?: string
}

export type KnowledgeArticle = {
  id: number
  title: string
  body: string
  is_published?: boolean
  published_at?: string | null
  created_at?: string
  updated_at?: string
  attachments_count?: number
  tags?: KnowledgeTag[]
  topics?: KnowledgeTopic[]
  attachments?: KnowledgeAttachment[]
}

export type KnowledgeListResponse = {
  data: KnowledgeArticle[]
  meta?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}
