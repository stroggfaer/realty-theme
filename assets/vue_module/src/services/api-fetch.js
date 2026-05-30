/**
 * ApiFetchService - универсальный сервис для HTTP-запросов через fetch API
 * 
 * Поддерживает:
 * - GET, POST, PUT, PATCH, DELETE методы
 * - JSON body, FormData, URLSearchParams
 * - Автоматическое определение FormData
 * - GET query params в URL
 * - Timeout с AbortController
 * - HTTP error handling (response.ok validation)
 * - Автоматический response parsing (json/text)
 * - Кастомные headers
 */
export const ApiFetchService = (function() {
    'use strict';

    /**
     * Дефолтные настройки
     */
    const DEFAULT_TIMEOUT = 30000; // 30 секунд

    /**
     * Приватный метод: сборка URL с query params для GET запросов
     * 
     * @param {string} url - базовый URL
     * @param {Object} params - параметры для query string
     * @returns {string} URL с query params
     */
    function buildUrlWithParams(url, params) {
        if (!params || typeof params !== 'object' || Object.keys(params).length === 0) {
            return url;
        }

        const urlObj = new URL(url, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                urlObj.searchParams.append(key, value);
            }
        });

        return urlObj.toString();
    }

    /**
     * Приватный метод: определение типа body и соответствующих headers
     * 
     * @param {*} body - тело запроса
     * @returns {Object} { body, headers }
     */
    function prepareBody(body) {
        // FormData - браузер сам установит Content-Type с boundary
        if (body instanceof FormData) {
            return { body, headers: {} };
        }

        // URLSearchParams
        if (body instanceof URLSearchParams) {
            return {
                body: body.toString(),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            };
        }

        // Plain string
        if (typeof body === 'string') {
            return {
                body,
                headers: { 'Content-Type': 'text/plain' }
            };
        }

        // Object - сериализуем в JSON
        if (body && typeof body === 'object') {
            return {
                body: JSON.stringify(body),
                headers: { 'Content-Type': 'application/json' }
            };
        }

        // Null/undefined/other
        return { body: null, headers: {} };
    }

    /**
     * Приватный метод: обработка response
     * 
     * @param {Response} response - fetch response
     * @returns {Promise<*>} распарсенные данные
     */
    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        // JSON response
        if (contentType.includes('application/json')) {
            return await response.json();
        }

        // HTML, text, XML и другие
        return await response.text();
    }

    /**
     * Приватный метод: основной HTTP запрос
     * 
     * @param {string} method - HTTP метод (GET, POST, PUT, PATCH, DELETE)
     * @param {string} url - URL endpoint
     * @param {*} data - тело запроса или query params для GET
     * @param {Object} options - дополнительные настройки
     * @param {Object} options.headers - кастомные headers
     * @param {number} options.timeout - timeout в мс (по умолчанию 30000)
     * @param {string} options.responseType - 'json' | 'text' (автоматически если не указан)
     * @returns {Promise<*>} response data
     */
    async function request(method, url, data = {}, options = {}) {
        const {
            headers = {},
            timeout = DEFAULT_TIMEOUT,
            responseType = null
        } = options;

        // AbortController для timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            let finalUrl = url;
            let fetchOptions = {
                method: method.toUpperCase(),
                signal: controller.signal,
                headers: {}
            };

            // GET запросы - data как query params
            if (method.toUpperCase() === 'GET') {
                finalUrl = buildUrlWithParams(url, data);
            } else {
                // POST/PUT/PATCH/DELETE - data как body
                const { body, headers: bodyHeaders } = prepareBody(data);
                fetchOptions.body = body;
                Object.assign(fetchOptions.headers, bodyHeaders);
            }

            // Мержим кастомные headers (имеют приоритет)
            Object.assign(fetchOptions.headers, headers);

            // Выполняем запрос
            const response = await fetch(finalUrl, fetchOptions);

            // HTTP error handling (4xx, 5xx)
            if (!response.ok) {
                const errorData = await parseResponse(response).catch(() => null);
                throw new ApiFetchError(
                    `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    errorData
                );
            }

            // Парсим response
            if (responseType === 'text') {
                return await response.text();
            }

            return await parseResponse(response);

        } catch (error) {
            // AbortError (timeout)
            if (error.name === 'AbortError') {
                throw new ApiFetchError(
                    `Request timeout (${timeout}ms)`,
                    408,
                    null
                );
            }

            // Наш ApiFetchError - пробрасываем дальше
            if (error instanceof ApiFetchError) {
                throw error;
            }

            // Network errors, CORS, и другие
            throw new ApiFetchError(
                error.message || 'Network error',
                0,
                null
            );

        } finally {
            clearTimeout(timeoutId);
        }
    }

    /**
     * Класс ошибки для ApiFetchService
     */
    class ApiFetchError extends Error {
        constructor(message, status, data) {
            super(message);
            this.name = 'ApiFetchError';
            this.status = status;
            this.data = data;
        }
    }

    // Публичные методы
    return {
        /**
         * GET запрос
         * 
         * @param {string} url - URL endpoint
         * @param {Object} params - query params
         * @param {Object} options - дополнительные настройки
         * @returns {Promise<*>} response data
         * 
         * @example
         * const data = await ApiFetchService.get('/wp-json/property/v1/properties', {
         *     page: 1,
         *     per_page: 10
         * });
         */
        get(url, params = {}, options = {}) {
            return request('GET', url, params, options);
        },

        /**
         * POST запрос
         * 
         * @param {string} url - URL endpoint
         * @param {*} data - body (Object, FormData, URLSearchParams)
         * @param {Object} options - дополнительные настройки
         * @returns {Promise<*>} response data
         * 
         * @example
         * const res = await ApiFetchService.post(ajaxUrl, {
         *     action: 'property_filter',
         *     nonce: nonceValue,
         *     location: 'Amsterdam'
         * });
         */
        post(url, data = {}, options = {}) {
            return request('POST', url, data, options);
        },

        /**
         * PUT запрос
         * 
         * @param {string} url - URL endpoint
         * @param {*} data - body
         * @param {Object} options - дополнительные настройки
         * @returns {Promise<*>} response data
         */
        put(url, data = {}, options = {}) {
            return request('PUT', url, data, options);
        },

        /**
         * PATCH запрос
         * 
         * @param {string} url - URL endpoint
         * @param {*} data - body
         * @param {Object} options - дополнительные настройки
         * @returns {Promise<*>} response data
         */
        patch(url, data = {}, options = {}) {
            return request('PATCH', url, data, options);
        },

        /**
         * DELETE запрос
         * 
         * @param {string} url - URL endpoint
         * @param {*} data - body или query params
         * @param {Object} options - дополнительные настройки
         * @returns {Promise<*>} response data
         */
        delete(url, data = {}, options = {}) {
            return request('DELETE', url, data, options);
        },

        /**
         * Экспорт класса ошибки для внешней обработки
         */
        ApiFetchError
    };
})();
