<template>
  <img
    class="category-icon"
    :src="src"
    alt=""
    width="40"
    height="40"
    decoding="async"
    draggable="false"
  />
</template>

<script setup>
import { computed } from 'vue'
import everyday from '../assets/category-icons/cat-everyday.png'
import food from '../assets/category-icons/cat-food.png'
import animals from '../assets/category-icons/cat-animals.png'
import nature from '../assets/category-icons/cat-nature.png'
import movies from '../assets/category-icons/cat-movies.png'
import places from '../assets/category-icons/cat-places.png'
import transport from '../assets/category-icons/cat-transport.png'
import clothes from '../assets/category-icons/cat-clothes.png'
import furniture from '../assets/category-icons/cat-furniture.png'
import profession from '../assets/category-icons/cat-profession.png'
import school from '../assets/category-icons/cat-school.png'
import celebrities from '../assets/category-icons/cat-celebrities.png'
import feelings from '../assets/category-icons/cat-feelings.png'
import sport from '../assets/category-icons/cat-sport.png'
import tech from '../assets/category-icons/cat-tech.png'
import phrases from '../assets/category-icons/cat-phrases.png'
import random from '../assets/category-icons/cat-random.png'

const ICONS = {
  everyday,
  food,
  animals,
  nature,
  movies,
  places,
  transport,
  clothes,
  furniture,
  profession,
  school,
  celebrities,
  feelings,
  sport,
  tech,
  phrases,
  random,
}

const props = defineProps({
  name: { type: String, required: true },
})

const src = computed(() => ICONS[props.name] || ICONS.everyday)
</script>

<style scoped>
.category-icon {
  width: 2rem;
  height: 2rem;
  object-fit: cover;
  border-radius: 6px;
  flex-shrink: 0;
  display: block;
  pointer-events: none;
}
</style>
