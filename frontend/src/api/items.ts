import { request } from './client'
import type { Item, ItemListParams, NewItem, ReviewPayload } from '@/types/item'

interface ResourceResponse<T> {
  data: T
}

export function toItemListQuery(params: ItemListParams): string {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value) {
      query.set(key, value)
    }
  }

  return query.toString()
}

export async function listItems(params: ItemListParams, signal?: AbortSignal): Promise<Item[]> {
  const query = toItemListQuery(params)
  const response = await request<ResourceResponse<Item[]>>(`/items${query ? `?${query}` : ''}`, {
    signal,
  })

  return response.data
}

export async function getItem(id: number, signal?: AbortSignal): Promise<Item> {
  const response = await request<ResourceResponse<Item>>(`/items/${id}`, { signal })

  return response.data
}

export async function submitItem(newItem: NewItem): Promise<Item> {
  const response = await request<ResourceResponse<Item>>('/items', {
    method: 'POST',
    body: newItem,
  })

  return response.data
}

export async function reviewItem(id: number, review: ReviewPayload): Promise<Item> {
  const response = await request<ResourceResponse<Item>>(`/items/${id}/review`, {
    method: 'POST',
    body: review,
  })

  return response.data
}
