import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'queue',
      component: () => import('@/views/QueueView.vue'),
      children: [
        {
          // Nested so the drawer opens over the queue, can be linked to, and closes with Back.
          path: 'items/:id(\\d+)',
          name: 'item',
          component: () => import('@/views/ItemDetailView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
        },
      ],
    },
    {
      path: '/submit',
      name: 'submit',
      component: () => import('@/views/SubmitItemView.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'queue' },
    },
  ],
})

export default router
