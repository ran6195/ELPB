import { createRouter, createWebHashHistory } from 'vue-router'
import PageList from '../views/PageList.vue'
import PageEditor from '../views/PageEditor.vue'
import PagePreview from '../views/PagePreview.vue'
import PublicPage from '../views/PublicPage.vue'

const routes = [
  {
    path: '/',
    name: 'PageList',
    component: PageList
  },
  {
    path: '/editor/:id?',
    name: 'PageEditor',
    component: PageEditor
  },
  {
    path: '/preview/:id',
    name: 'PagePreview',
    component: PagePreview
  },
  {
    path: '/p/:slug',
    name: 'PublicPage',
    component: PublicPage
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
