# Базовый образ — официальный Playwright (браузеры + системные зависимости).
# ВНИМАНИЕ: npm-пакет @playwright/test в образ НЕ входит, ставим его здесь.
# Версия @playwright/test (package.json) обязана совпадать с версией браузеров в образе.
FROM mcr.microsoft.com/playwright:v1.62.0-noble

# Браузеры уже есть в базовом образе (/ms-playwright) — пропускаем их повторную загрузку при npm ci.
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1

WORKDIR /e2e

COPY package.json package-lock.json ./
RUN npm ci
