import { ref, type Ref } from 'vue'
import { ApiError, type ValidationErrors } from '@/api/client'
import { submitItem } from '@/api/items'
import type { Item, NewItem } from '@/types/item'
import { validateNewItem } from '@/utils/inputValidation'

export interface UseSubmitItem {
  isSubmitting: Ref<boolean>
  errorMessage: Ref<string | null>
  fieldErrors: Ref<ValidationErrors>
  submit: (newItem: NewItem) => Promise<Item | null>
}

export function useSubmitItem(): UseSubmitItem {
  const isSubmitting = ref(false)
  const errorMessage = ref<string | null>(null)
  const fieldErrors = ref<ValidationErrors>({})

  async function submit(newItem: NewItem): Promise<Item | null> {
    errorMessage.value = null
    fieldErrors.value = validateNewItem(newItem)

    if (Object.keys(fieldErrors.value).length > 0) {
      return null
    }

    isSubmitting.value = true

    try {
      return await submitItem(newItem)
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        fieldErrors.value = error.validationErrors
      } else {
        errorMessage.value = error instanceof Error ? error.message : 'Could not submit the item.'
      }
      return null
    } finally {
      isSubmitting.value = false
    }
  }

  return { isSubmitting, errorMessage, fieldErrors, submit }
}
