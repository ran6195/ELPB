<template>
  <div class="video-block" ref="videoContainer">
    <div :class="[fullWidth ? '' : 'max-w-7xl mx-auto', roundedCorners ? 'rounded-lg overflow-hidden' : '']" :style="blockStyles">
      <!-- Instagram Reel: portrait centrato -->
      <div
        v-if="block.content.videoUrl && isInstagram"
        class="w-full flex justify-center items-center py-6"
      >
        <!-- Solo video: crop CSS per nascondere header/footer Instagram -->
        <div
          v-if="block.content.instagramCleanMode"
          style="position:relative; overflow:hidden; width:400px; max-width:100%; height:500px; border-radius:4px;"
        >
          <iframe
            :src="embedUrlWithId"
            frameborder="0"
            scrolling="no"
            allowtransparency="true"
            allowfullscreen
            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            style="position:absolute; top:-56px; left:0; width:400px; height:750px; border:none;"
          ></iframe>
        </div>

        <!-- UI completa Instagram -->
        <iframe
          v-else
          :src="embedUrlWithId"
          width="400"
          height="700"
          frameborder="0"
          scrolling="no"
          allowtransparency="true"
          allowfullscreen
          allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
          referrerpolicy="strict-origin-when-cross-origin"
          style="max-width: 100%;"
        ></iframe>
      </div>

      <!-- YouTube / Vimeo / File diretto -->
      <div
        v-else
        class="relative w-full overflow-hidden"
        :style="{ height: block.content.height || '600px', minHeight: block.content.height || '600px' }"
      >
        <!-- YouTube/Vimeo Embed -->
        <iframe
          v-if="block.content.videoUrl && isEmbedUrl"
          ref="iframeRef"
          :src="embedUrlWithId"
          class="w-full h-full"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>

        <!-- Direct Video File -->
        <video
          v-else-if="block.content.videoUrl && !isEmbedUrl"
          ref="videoRef"
          class="w-full h-full object-cover"
          :autoplay="shouldAutoplay"
          :loop="block.content.loop"
          :muted="block.content.muted"
          :controls="block.content.showControls !== false"
          :playsinline="true"
        >
          <source :src="block.content.videoUrl" :type="getVideoType(block.content.videoUrl)">
          Il tuo browser non supporta il tag video.
        </video>

        <!-- Placeholder -->
        <div
          v-else
          class="w-full h-full flex items-center justify-center bg-gray-200"
        >
          <div class="text-center text-gray-400">
            <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-lg font-medium">Inserisci URL video o Instagram Reel</p>
            <p class="text-sm mt-2">Supporta: YouTube, Vimeo, Instagram, MP4, WebM</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

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

const videoContainer = ref(null)
const videoRef = ref(null)
const iframeRef = ref(null)
let observer = null
let hasPlayed = ref(false)

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

const fullWidth = computed(() => {
  return props.block.content.fullWidth !== false
})

// Se playOnScroll è attivo, disabilita autoplay normale
const shouldAutoplay = computed(() => {
  if (props.block.content.playOnScroll) {
    return false // Non fare autoplay, aspetta lo scroll
  }
  return props.block.content.autoplay
})

// Rileva se l'URL è Instagram Reel/Post
const isInstagram = computed(() => {
  const url = props.block.content.videoUrl
  if (!url) return false
  return url.includes('instagram.com/reel/') || url.includes('instagram.com/p/')
})

// Rileva se l'URL è YouTube, Vimeo (non Instagram — gestita separatamente)
const isEmbedUrl = computed(() => {
  const url = props.block.content.videoUrl
  if (!url) return false

  return url.includes('youtube.com') ||
         url.includes('youtu.be') ||
         url.includes('vimeo.com')
})

// Converte URL in formato embed
const embedUrlWithId = computed(() => {
  const url = props.block.content.videoUrl
  if (!url) return ''

  // Instagram Reel
  if (url.includes('instagram.com/reel/')) {
    const reelId = url.split('/reel/')[1].split('/')[0].split('?')[0]
    if (reelId) return `https://www.instagram.com/reel/${reelId}/embed/`
  }

  // Instagram Post
  if (url.includes('instagram.com/p/')) {
    const postId = url.split('/p/')[1].split('/')[0].split('?')[0]
    if (postId) return `https://www.instagram.com/p/${postId}/embed/`
  }

  // YouTube
  if (url.includes('youtube.com') || url.includes('youtu.be')) {
    let videoId = ''

    // https://www.youtube.com/watch?v=VIDEO_ID
    if (url.includes('watch?v=')) {
      videoId = url.split('watch?v=')[1].split('&')[0]
    }
    // https://youtu.be/VIDEO_ID
    else if (url.includes('youtu.be/')) {
      videoId = url.split('youtu.be/')[1].split('?')[0]
    }
    // https://www.youtube.com/embed/VIDEO_ID (già embed)
    else if (url.includes('youtube.com/embed/')) {
      return url
    }
    // https://www.youtube.com/shorts/VIDEO_ID
    else if (url.includes('youtube.com/shorts/')) {
      videoId = url.split('youtube.com/shorts/')[1].split('?')[0]
    }

    if (videoId) {
      // Aggiungi parametri per autoplay, loop, muted
      const params = ['enablejsapi=1'] // Abilita JS API per controllo da JS

      // Se playOnScroll è attivo, non autoplay subito
      if (props.block.content.autoplay && !props.block.content.playOnScroll) {
        params.push('autoplay=1')
      }

      if (props.block.content.loop) params.push('loop=1', `playlist=${videoId}`)
      if (props.block.content.muted) params.push('mute=1')
      if (props.block.content.showControls === false) params.push('controls=0')

      const queryString = '?' + params.join('&')
      return `https://www.youtube.com/embed/${videoId}${queryString}`
    }
  }

  // Vimeo
  if (url.includes('vimeo.com')) {
    let videoId = ''

    // https://vimeo.com/VIDEO_ID
    if (url.includes('vimeo.com/') && !url.includes('player.vimeo.com')) {
      videoId = url.split('vimeo.com/')[1].split('?')[0].split('/')[0]
    }
    // https://player.vimeo.com/video/VIDEO_ID (già embed)
    else if (url.includes('player.vimeo.com/video/')) {
      return url
    }

    if (videoId) {
      // Aggiungi parametri per autoplay, loop, muted
      const params = []

      // Se playOnScroll è attivo, non autoplay subito
      if (props.block.content.autoplay && !props.block.content.playOnScroll) {
        params.push('autoplay=1')
      }

      if (props.block.content.loop) params.push('loop=1')
      if (props.block.content.muted) params.push('muted=1')

      const queryString = params.length > 0 ? '?' + params.join('&') : ''
      return `https://player.vimeo.com/video/${videoId}${queryString}`
    }
  }

  return url
})

// Intersection Observer per play on scroll
onMounted(() => {
  if (!props.editable && props.block.content.playOnScroll && videoContainer.value) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !hasPlayed.value) {
            hasPlayed.value = true
            playVideo()
          }
        })
      },
      {
        threshold: 0.5 // Il video deve essere almeno per il 50% visibile
      }
    )

    observer.observe(videoContainer.value)
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

const playVideo = () => {
  // Se è un video HTML5 diretto
  if (videoRef.value) {
    videoRef.value.play().catch(err => {
      console.log('Video play prevented:', err)
    })
  }

  // Se è un iframe YouTube
  if (iframeRef.value && props.block.content.videoUrl.includes('youtube')) {
    iframeRef.value.contentWindow.postMessage(
      '{"event":"command","func":"playVideo","args":""}',
      '*'
    )
  }

  // Se è un iframe Vimeo
  if (iframeRef.value && props.block.content.videoUrl.includes('vimeo')) {
    iframeRef.value.contentWindow.postMessage(
      '{"method":"play"}',
      '*'
    )
  }
}

const getVideoType = (url) => {
  if (!url) return 'video/mp4'

  const extension = url.split('.').pop().toLowerCase().split('?')[0]
  const mimeTypes = {
    'mp4': 'video/mp4',
    'webm': 'video/webm',
    'ogg': 'video/ogg',
    'ogv': 'video/ogg',
    'mov': 'video/quicktime'
  }

  return mimeTypes[extension] || 'video/mp4'
}
</script>
