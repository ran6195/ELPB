<template>
  <div class="slider-block">
    <div
      :class="[
        'max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16',
        roundedCorners ? 'rounded-lg' : ''
      ]"
      :style="{
        backgroundColor: block.styles?.backgroundColor || '#f9fafb',
        color: block.styles?.textColor || '#1f2937',
        padding: block.styles?.padding || undefined,
        fontFamily: block.styles?.fontFamily || undefined
      }"
    >
      <!-- Title (optional) -->
      <h2
        v-if="block.content.title"
        class="text-3xl md:text-4xl font-bold text-center mb-12"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.textContent)"
        :style="titleFontSize ? { fontSize: titleFontSize } : {}"
      >
        {{ block.content.title }}
      </h2>

      <!-- Swiper Slider -->
      <div class="relative">
        <Swiper
          :modules="modules"
          :slides-per-view="1"
          :space-between="block.content.slideGap ?? 20"
          :loop="loopEnabled"
          :autoplay="block.content.autoplay ? {
            delay: block.content.autoplayDelay || 3000,
            disableOnInteraction: false
          } : false"
          :navigation="block.content.showNavigation ? {
            nextEl: '.swiper-button-next-custom',
            prevEl: '.swiper-button-prev-custom',
          } : false"
          :pagination="block.content.showPagination ? {
            clickable: true,
            el: '.swiper-pagination-custom'
          } : false"
          :breakpoints="{
            768: {
              slidesPerView: block.content.slidesPerViewDesktop || 3,
              spaceBetween: block.content.slideGap ?? 20
            }
          }"
          class="slider-swiper"
        >
          <SwiperSlide v-for="(slide, index) in block.content.slides" :key="index">
            <div
              :class="[
                'slide-item bg-white shadow-lg overflow-hidden',
                roundedCorners ? 'rounded-lg' : ''
              ]"
            >
              <!-- Image -->
              <div
                :class="['overflow-hidden', getAspectRatioClass(block.content.slideAspectRatio)]"
                :style="block.content.slideHeight ? { height: block.content.slideHeight } : {}"
              >
                <img
                  :src="slide.image || 'https://placehold.co/800x800'"
                  :alt="slide.alt || `Slide ${index + 1}`"
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- Optional text content -->
              <div v-if="slide.title || slide.description" class="p-6">
                <h3
                  v-if="slide.title"
                  class="text-xl font-semibold mb-2"
                  :contenteditable="editable"
                  @blur="updateSlide(index, 'title', $event.target.textContent)"
                >
                  {{ slide.title }}
                </h3>
                <p
                  v-if="slide.description"
                  class="text-gray-600"
                  :contenteditable="editable"
                  @blur="updateSlide(index, 'description', $event.target.textContent)"
                >
                  {{ slide.description }}
                </p>
              </div>
            </div>
          </SwiperSlide>

          <!-- Navigation Arrows -->
          <div
            v-if="block.content.showNavigation"
            class="swiper-button-prev-custom absolute left-2 top-1/2 -translate-y-1/2 z-10 w-12 h-12 bg-white/70 backdrop-blur-sm rounded-full shadow-lg flex items-center justify-center cursor-pointer hover:bg-white/90 transition-all hidden md:flex"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </div>
          <div
            v-if="block.content.showNavigation"
            class="swiper-button-next-custom absolute right-2 top-1/2 -translate-y-1/2 z-10 w-12 h-12 bg-white/70 backdrop-blur-sm rounded-full shadow-lg flex items-center justify-center cursor-pointer hover:bg-white/90 transition-all hidden md:flex"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Swiper>

        <!-- Pagination Dots -->
        <div
          v-if="block.content.showPagination"
          class="swiper-pagination-custom mt-8 flex justify-center gap-2"
        ></div>
      </div>

      <!-- Editor Controls -->
      <div v-if="editable" class="mt-8 flex flex-wrap gap-4 justify-center">
        <button
          @click="addSlide"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          + Add Slide
        </button>
        <button
          v-if="block.content.slides.length > 1"
          @click="removeLastSlide"
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
        >
          - Remove Last Slide
        </button>
        <button
          @click="toggleAutoplay"
          class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
        >
          Autoplay: {{ block.content.autoplay ? 'ON' : 'OFF' }}
        </button>
        <button
          @click="toggleNavigation"
          class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
        >
          Arrows: {{ block.content.showNavigation ? 'ON' : 'OFF' }}
        </button>
        <button
          @click="togglePagination"
          class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
        >
          Dots: {{ block.content.showPagination ? 'ON' : 'OFF' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination, Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  roundedCorners: {
    type: Boolean,
    default: true
  }
})

const modules = [Navigation, Pagination, Autoplay]

const titleSizeMap = { xl: '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem', '5xl': '3rem', '6xl': '3.75rem' }
const titleFontSize = computed(() => titleSizeMap[props.block.content.titleSize] || null)

// Calcola se il loop mode può essere abilitato
// Per funzionare correttamente, Swiper necessita di almeno slidesPerView * 2 slide
const canEnableLoop = computed(() => {
  const slides = props.block.content.slides || []
  const slidesPerViewDesktop = props.block.content.slidesPerViewDesktop || 3
  const minSlidesForLoop = slidesPerViewDesktop * 2
  return slides.length >= minSlidesForLoop
})

// Loop è abilitato solo se configurato E ci sono abbastanza slide
const loopEnabled = computed(() => {
  return props.block.content.loop !== false && canEnableLoop.value
})

const updateContent = (field, value) => {
  if (props.editable) {
    props.block.content[field] = value
  }
}

const updateSlide = (index, field, value) => {
  if (props.editable) {
    props.block.content.slides[index][field] = value
  }
}

const addSlide = () => {
  if (props.editable) {
    props.block.content.slides.push({
      image: 'https://placehold.co/800x800',
      alt: `Slide ${props.block.content.slides.length + 1}`,
      title: '',
      description: ''
    })
  }
}

const removeLastSlide = () => {
  if (props.editable && props.block.content.slides.length > 1) {
    props.block.content.slides.pop()
  }
}

const toggleAutoplay = () => {
  if (props.editable) {
    props.block.content.autoplay = !props.block.content.autoplay
  }
}

const toggleNavigation = () => {
  if (props.editable) {
    props.block.content.showNavigation = !props.block.content.showNavigation
  }
}

const togglePagination = () => {
  if (props.editable) {
    props.block.content.showPagination = !props.block.content.showPagination
  }
}

const getAspectRatioClass = (aspectRatio) => {
  const ratio = aspectRatio || 'square'
  const aspectRatioClasses = {
    'square': 'aspect-square',        // 1:1
    '16-9': 'aspect-video',            // 16:9
    '4-3': 'aspect-[4/3]',             // 4:3
    '3-4': 'aspect-[3/4]',             // 3:4 (verticale)
    '21-9': 'aspect-[21/9]',           // 21:9 (ultrawide)
    '3-2': 'aspect-[3/2]'              // 3:2 (fotografico)
  }
  return aspectRatioClasses[ratio] || 'aspect-square'
}
</script>

<style scoped>
.slider-block {
  position: relative;
}

.slide-item {
  height: 100%;
}

/* Swiper custom styles */
:deep(.swiper-pagination-bullet) {
  width: 12px;
  height: 12px;
  background: #000;
  opacity: 0.3;
  transition: opacity 0.3s;
}

:deep(.swiper-pagination-bullet-active) {
  opacity: 1;
}
</style>
