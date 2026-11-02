import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'queue',
      component: () => import('@/views/QueueView.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'queue' },
    },
  ],
})

export default router
