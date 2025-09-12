<template>
  <div class="flex items-center space-x-4">
    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
      <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="w-full h-full object-cover" />
      <i v-else class="fas fa-user text-gray-500"></i>
    </div>
    <div>
      <p class="font-semibold">{{ this.user.name }}</p>
      <p class="text-sm text-gray-500">{{ user.email }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserInfo',
  props: {
    user: {
      type: Object,
      default: () => ({ name: '', email: '', avatar: '' })
    }
  }
}
</script>

