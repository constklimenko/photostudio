# Changelog

## 2026-06-24 — Этап 1-2: Миграции, модели, Filament

### Выполнено
- Установлен Filament 4 (v4.11.7)
- Создан админ-пользователь (admin@fotoskazka.ru)
- Созданы миграции для всех таблиц:
  - `add_phone_and_status_to_users_table`
  - `roles`, `role_user`, `categories`, `media`, `mediaables`
  - `pages`, `services`, `projects`, `albums`, `photos`
  - `posts`, `testimonials`, `inquiries`
- Созданы Eloquent модели со связями:
  - Role, Category, Media, Page, Service
  - Project, Album, Photo, Post, Testimonial, Inquiry
  - Обновлён User (добавлены phone, status, связи)
- Создан RoleSeeder с 5 ролями (admin, photographer, client, parent, class_manager)
- Все миграции выполнены, тесты проходят
