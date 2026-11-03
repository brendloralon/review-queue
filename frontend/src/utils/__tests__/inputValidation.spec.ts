import { describe, expect, it } from 'vitest'
import { validateNewItem } from '../inputValidation'
import type { NewItem } from '@/types/item'

const validItem: NewItem = { title: 'Typo', body: 'Small typo.', submitter_name: 'Dana' }

function errorsWith(changes: Partial<NewItem>) {
  return validateNewItem({ ...validItem, ...changes })
}

describe('validateNewItem', () => {
  it('accepts ordinary text in any language, with line breaks in the body', () => {
    expect(errorsWith({})).toEqual({})
    expect(
      errorsWith({
        title: 'Héllo — שלום 👋',
        body: 'Line one\nLine two\twith a tab.',
        submitter_name: "Anne-Marie O'Brien",
      }),
    ).toEqual({})
    expect(errorsWith({ submitter_name: 'נועה כהן' })).toEqual({})
  })

  it('keeps HTML as plain text, since output escaping prevents XSS', () => {
    expect(errorsWith({ title: 'Score < 30 shows <script>alert(1)</script>' })).toEqual({})
  })

  it('requires every field, counting whitespace-only as missing', () => {
    expect(validateNewItem({ title: '', body: '  ', submitter_name: '\n' })).toEqual({
      title: ['The title field is required.'],
      body: ['The body field is required.'],
      submitter_name: ['The submitter name field is required.'],
    })
  })

  it('limits lengths, counting an emoji as one character', () => {
    expect(errorsWith({ title: 'a'.repeat(121) }).title).toEqual([
      'The title field must not be greater than 120 characters.',
    ])
    expect(errorsWith({ title: '👋'.repeat(120) })).toEqual({})
  })

  it('rejects control and invisible characters', () => {
    const notAllowed = ['The title contains characters that are not allowed.']

    expect(errorsWith({ title: 'Broken\u0000title' }).title).toEqual(notAllowed)
    expect(errorsWith({ title: 'Free​money' }).title).toEqual(notAllowed)
    expect(errorsWith({ body: 'Invoice ‮fdp.exe' }).body).toEqual([
      'The body contains characters that are not allowed.',
    ])
    expect(errorsWith({ body: 'Looks normal\u001B[2J' }).body).toBeDefined()
  })

  it('keeps the title to a single line', () => {
    expect(errorsWith({ title: 'First line\nSecond line' }).title).toEqual([
      'The title must be a single line.',
    ])
  })

  it('only allows name characters in the submitter name', () => {
    const expected = [
      'The submitter name may only contain letters, spaces, apostrophes, hyphens and periods.',
    ]

    expect(errorsWith({ submitter_name: 'Agent 007' }).submitter_name).toEqual(expected)
    expect(errorsWith({ submitter_name: '<b>Dana</b>' }).submitter_name).toEqual(expected)
    expect(errorsWith({ submitter_name: '-Dana' }).submitter_name).toEqual(expected)
  })
})
