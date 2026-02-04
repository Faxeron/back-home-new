<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'

defineOptions({
  name: 'AppMaskedField',
  inheritAttrs: false,
})

const props = defineProps<{
  modelValue?: string | null
  mask: string
  labelInField?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const elementId = computed(() => {
  const attrs = useAttrs()
  const token = attrs.id
  const id = useId()
  return token ? `app-masked-field-${token}` : id
})

const label = computed(() => useAttrs().label as string | undefined)

const formatMasked = (value: string, mask: string): string => {
  const digits = (value ?? '').replace(/\D/g, '')
  if (!digits) return ''
  let result = ''
  let index = 0

  for (const char of mask) {
    if (char === '0') {
      if (index >= digits.length) break
      result += digits[index]
      index += 1
      continue
    }

    if (!result) continue
    if (index >= digits.length) break
    result += char
  }

  return result
}

const model = computed({
  get: () => props.modelValue ?? '',
  set: value => emit('update:modelValue', formatMasked(String(value ?? ''), props.mask)),
})
</script>

<template>
  <div
    class="app-text-field flex-grow-1"
    :class="$attrs.class"
  >
    <VLabel
      v-if="label && !props.labelInField"
      :for="elementId"
      class="mb-1 text-body-2 text-wrap"
      style="line-height: 15px;"
      :text="label"
    />
    <VTextField
      v-model="model"
      v-bind="{
        ...$attrs,
        class: null,
        label: props.labelInField ? label : undefined,
        variant: 'outlined',
        id: elementId,
        type: 'text',
        inputmode: 'numeric',
        autocomplete: 'off',
        maxlength: props.mask.length,
      }"
    >
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot
          :name="name"
          v-bind="slotProps || {}"
        />
      </template>
    </VTextField>
  </div>
</template>
