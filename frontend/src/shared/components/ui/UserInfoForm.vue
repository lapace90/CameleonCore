<template>
  <div class="space-y-4">
    <BaseInput v-model="localUser.name" label="Name" required />
    <BaseInput v-model="localUser.email" label="Email" type="email" required />
  </div>
</template>

<script>
import BaseInput from './BaseInput.vue'

export default {
  name: 'UserInfoForm',
  components: { BaseInput },
  props: {
    modelValue: {
      type: Object,
      default: () => ({ name: '', email: '' })
    }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      localUser: { ...this.modelValue }
    }
  },
  watch: {
    localUser: {
      handler(val) {
        this.$emit('update:modelValue', val)
      },
      deep: true
    },
    modelValue: {
      handler(val) {
        this.localUser = { ...val }
      },
      deep: true
    }
  }
}
</script>

<style scoped>
</style>