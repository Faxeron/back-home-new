<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'

defineOptions({
  name: 'AppPhoneField',
  inheritAttrs: false,
})

const props = defineProps<{
  modelValue?: string | null
  labelInField?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const elementId = computed(() => {
  const attrs = useAttrs()
  const token = attrs.id
  const id = useId()
  return token ? `app-phone-field-${token}` : id
})

const label = computed(() => useAttrs().label as string | undefined)

const formatPhone = (value: string): string => {
  const digits = (value ?? '').replace(/\D/g, '')
  if (!digits) return ''
  let normalized = digits
  if (normalized.startsWith('8')) normalized = `7${normalized.slice(1)}`
  if (!normalized.startsWith('7')) normalized = `7${normalized}`
  normalized = normalized.slice(0, 11)
  const parts = normalized.split('')
  const chunk = (from: number, to: number) => parts.slice(from, to).join('')
  const result = [
    `+${parts[0] ?? '7'}`,
    chunk(1, 4),
    chunk(4, 7),
    chunk(7, 9),
    chunk(9, 11),
  ].filter(Boolean)
  return result.join(' ').trim()
}

const model = computed({
  get: () => props.modelValue ?? '',
  set: value => emit('update:modelValue', formatPhone(String(value ?? ''))),
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
        type: 'tel',
        inputmode: 'tel',
        autocomplete: 'tel',
        maxlength: 16,
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
