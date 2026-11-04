import type { ValidationErrors } from '@/api/client'
import type { NewItem } from '@/types/item'

/*
 * Mirrors StoreItemRequest and App\Rules\PlainText so users see problems before submitting.
 * The API stays the authority: it runs the same checks and its 422 errors are shown the same way.
 */

export const NEW_ITEM_MAX_LENGTHS: Record<keyof NewItem, number> = {
  title: 120,
  body: 500,
  submitter_name: 80,
}

const FIELD_LABELS: Record<keyof NewItem, string> = {
  title: 'title',
  body: 'body',
  submitter_name: 'submitter name',
}

// Same set as PlainText::INVISIBLE_CHARACTERS: zero-width space, word joiner, byte order mark,
// and bidirectional embedding, override and isolate controls.
const INVISIBLE_CHARACTERS = /[​⁠﻿‪-‮⁦-⁩]/u
const CONTROL_CHARACTERS = /\p{Cc}/u
const CONTROL_CHARACTERS_EXCEPT_LINE_BREAKS = /[^\P{Cc}\t\n\r]/u
const LINE_BREAK = /[\r\n]/
const PERSON_NAME = /^[\p{L}\p{M}][\p{L}\p{M} '’.-]*$/u

function characterCount(value: string): number {
  // Count code points like the API's mb_strlen, so an emoji counts as one character.
  return [...value].length
}

function plainTextProblem(label: string, value: string, allowsLineBreaks: boolean): string | null {
  if (!allowsLineBreaks && LINE_BREAK.test(value)) {
    return `The ${label} must be a single line.`
  }

  const controlCharacters = allowsLineBreaks
    ? CONTROL_CHARACTERS_EXCEPT_LINE_BREAKS
    : CONTROL_CHARACTERS

  if (controlCharacters.test(value) || INVISIBLE_CHARACTERS.test(value)) {
    return `The ${label} contains characters that are not allowed.`
  }

  return null
}

function contentProblem(field: keyof NewItem, value: string): string | null {
  if (field === 'submitter_name') {
    return PERSON_NAME.test(value)
      ? null
      : 'The submitter name may only contain letters, spaces, apostrophes, hyphens and periods.'
  }

  return plainTextProblem(FIELD_LABELS[field], value, field === 'body')
}

function fieldProblem(field: keyof NewItem, rawValue: string): string | null {
  // The API trims input before validating, so whitespace-only counts as missing here too.
  const value = rawValue.trim()
  const label = FIELD_LABELS[field]

  if (value === '') {
    return `The ${label} field is required.`
  }

  if (characterCount(value) > NEW_ITEM_MAX_LENGTHS[field]) {
    return `The ${label} field must not be greater than ${NEW_ITEM_MAX_LENGTHS[field]} characters.`
  }

  return contentProblem(field, value)
}

export function validateNewItem(item: NewItem): ValidationErrors {
  const errors: ValidationErrors = {}

  for (const field of Object.keys(NEW_ITEM_MAX_LENGTHS) as (keyof NewItem)[]) {
    const problem = fieldProblem(field, item[field])

    if (problem) {
      errors[field] = [problem]
    }
  }

  return errors
}
