<template>
  <div class="max-w-lg mx-auto p-4 space-y-6">
    <h1 class="text-2xl font-semibold">Profile</h1>
    <UserInfo :user="user" />
    <ImageUpload v-model="avatar" :src="user.avatar" button-text="Change avatar" />
    <UserInfoForm v-model="user" />
    <BaseButton variant="primary" @click="save">Save</BaseButton>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import UserInfoForm from '@/shared/components/ui/UserInfoForm.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import ImageUpload from '@/admin/components/ui/ImageUpload.vue'
import UserInfo from '@/shared/components/ui/UserInfo.vue'

const user = ref({ name: '', email: '', avatar: '' })
const avatar = ref(null)

function save() {
  console.log({ ...user.value, avatar: avatar.value })
}
</script>

<style scoped></style>