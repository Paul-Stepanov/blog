<script setup lang="ts">
/**
 * ContactPage — страница контактов.
 *
 * @description Заголовок + вводный текст (max-width 65ch) + ContactForm
 * (name/email/subject/message). Ошибки формы (422/429) обрабатываются внутри
 * ContactForm → useContact. useSeo для meta.
 */
import { useSeo } from '@/composables/useSeo'
import ContactForm from '@/features/contact/components/ContactForm.vue'

useSeo({
  title: 'Контакты',
  description:
    'Свяжитесь со мной — вопросы, предложения и идеи для сотрудничества.',
})
</script>

<template>
  <section class="contact-page container">
    <header class="contact-page__header">
      <p class="contact-page__eyebrow text-caps">Связь</p>
      <h1 class="contact-page__title">Напишите мне</h1>
      <p class="contact-page__intro">
        Есть вопрос, предложение или идея для сотрудничества? Заполните форму —
        отвечаю в течение пары дней.
      </p>
    </header>

    <ContactForm />
  </section>
</template>

<style scoped>
.contact-page {
  padding-block: var(--space-8) var(--space-9);
  max-width: 720px;
}

.contact-page__header {
  margin-bottom: var(--space-7);
}

.contact-page__eyebrow {
  color: var(--color-accent-strong);
  margin-bottom: var(--space-2);
}

.contact-page__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-3xl);
  margin-bottom: var(--space-3);
}

.contact-page__intro {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  max-width: 65ch;
}
</style>