<!-- classes: type-<base>, ex: type-room -->
<template>
  <span class="category-badge" :class="`type-${base || 'default'}`">{{ label }}</span>
</template>

<script>
export default {
  props:{ category:[Object,String], baseType:{type:String,default:''}},
  computed:{
    label(){ const c=this.category; return (c?.name ?? c?.type ?? c ?? '').toString() },
    base(){
      const b=(this.baseType||'').toLowerCase()
      if (b) return b
      const c=this.category; const key=(c?.type??c?.slug??c?.name??c??'').toString().toLowerCase()
      return ['activity','menu','dish','room','ingredient'].find(t=>key.includes(t)) || ''
    }
  }
}
</script>
