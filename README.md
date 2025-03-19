# Настройка и запуск

---

1. Создайте файл `.env` на базе `.env.dist`, указав в нём подходящие настройки.
1. Запустите проект с помощью команды:
```bash
make init
```

# API

---

После запуска контейнеров, API доступно по адресу web-контейнера [/api/doc](http://localhost/api/doc) .

# Управление контейнерами

---

1. Для запуска используйте команду:
```bash
make start
```
1. Для остановки:
```bash
make stop
```
