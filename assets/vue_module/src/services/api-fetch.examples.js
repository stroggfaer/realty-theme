/**
 * ApiFetchService - Примеры использования
 * 
 * Этот файл содержит примеры использования ApiFetchService
 * НЕ импортировать в production код - только для reference
 */

import { ApiFetchService, ApiFetchError } from './api-fetch';

// ============================================
// 1. БАЗОВЫЕ ПРИМЕРЫ
// ============================================

/**
 * Пример 1: GET запрос с query params
 */
async function exampleGetRequest() {
    try {
        const data = await ApiFetchService.get('/wp-json/property/v1/properties', {
            page: 1,
            per_page: 10,
            location: 'amsterdam'
        });
        
        console.log('Properties:', data);
        return data;
    } catch (error) {
        console.error('GET error:', error.message);
        throw error;
    }
}

/**
 * Пример 2: POST запрос с JSON body
 */
async function examplePostJson() {
    try {
        const result = await ApiFetchService.post('/wp-json/property/v1/filter', {
            action: 'property_filter',
            nonce: myData.nonce,
            location: 'Amsterdam',
            price_min: 100000,
            price_max: 500000
        });
        
        console.log('Filter results:', result);
        return result;
    } catch (error) {
        console.error('POST error:', error.message);
        throw error;
    }
}

/**
 * Пример 3: POST запрос с URLSearchParams (WordPress AJAX)
 */
async function examplePostUrlEncoded() {
    const body = new URLSearchParams();
    body.append('action', 'my_cabinet_send_message');
    body.append('nonce', appData.messageNonce);
    body.append('thread_id', '123');
    body.append('content', 'Hello from Vue!');

    try {
        const result = await ApiFetchService.post(appData.ajaxUrl, body);
        
        if (result.success) {
            console.log('Message sent:', result.data);
        }
        
        return result;
    } catch (error) {
        console.error('AJAX error:', error.message);
        throw error;
    }
}

/**
 * Пример 4: Загрузка файлов с FormData
 */
async function exampleFormDataUpload(fileInput) {
    const formData = new FormData();
    formData.append('action', 'upload_property_image');
    formData.append('nonce', myData.uploadNonce);
    formData.append('property_id', '456');
    
    // Добавляем файл (браузер автоматически установит correct Content-Type)
    if (fileInput.files[0]) {
        formData.append('image', fileInput.files[0]);
    }

    try {
        const result = await ApiFetchService.post(myData.ajaxUrl, formData);
        
        if (result.success) {
            console.log('Upload success:', result.data.image_url);
        }
        
        return result;
    } catch (error) {
        console.error('Upload error:', error.message);
        throw error;
    }
}

// ============================================
// 2. ADVANCED FEATURES
// ============================================

/**
 * Пример 5: Custom timeout
 */
async function exampleWithTimeout() {
    try {
        // Быстрый запрос с timeout 5 секунд
        const data = await ApiFetchService.get(
            '/wp-json/property/v1/properties',
            { page: 1 },
            { timeout: 5000 } // 5 секунд вместо стандартных 30
        );
        
        return data;
    } catch (error) {
        if (error.status === 408) {
            console.error('Request timeout!');
        }
        throw error;
    }
}

/**
 * Пример 6: Custom headers
 */
async function exampleWithCustomHeaders() {
    try {
        const data = await ApiFetchService.get(
            '/wp-json/property/v1/properties',
            { page: 1 },
            {
                headers: {
                    'X-Custom-Header': 'custom-value',
                    'Authorization': 'Bearer token123'
                }
            }
        );
        
        return data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

/**
 * Пример 7: PUT/PATCH для обновления данных
 */
async function exampleUpdateProperty(propertyId, updates) {
    try {
        // PATCH - частичное обновление
        const result = await ApiFetchService.patch(
            `/wp-json/property/v1/properties/${propertyId}`,
            {
                nonce: myData.updateNonce,
                ...updates
            }
        );
        
        console.log('Property updated:', result);
        return result;
    } catch (error) {
        console.error('Update error:', error.message);
        throw error;
    }
}

/**
 * Пример 8: DELETE запрос
 */
async function exampleDeleteProperty(propertyId) {
    try {
        const result = await ApiFetchService.delete(
            `/wp-json/property/v1/properties/${propertyId}`,
            { nonce: myData.deleteNonce }
        );
        
        console.log('Property deleted:', result);
        return result;
    } catch (error) {
        console.error('Delete error:', error.message);
        throw error;
    }
}

// ============================================
// 3. ERROR HANDLING
// ============================================

/**
 * Пример 9: Обработка ошибок с ApiFetchError
 */
async function exampleErrorHandling() {
    try {
        const data = await ApiFetchService.get('/wp-json/property/v1/properties', {
            page: 1
        });
        
        return data;
        
    } catch (error) {
        // Проверяем тип ошибки
        if (error instanceof ApiFetchError) {
            switch (error.status) {
                case 0:
                    console.error('Network error - check connection');
                    break;
                case 401:
                    console.error('Unauthorized - redirect to login');
                    window.location.href = '/login';
                    break;
                case 403:
                    console.error('Forbidden - insufficient permissions');
                    break;
                case 404:
                    console.error('Not found');
                    break;
                case 408:
                    console.error('Request timeout');
                    break;
                case 500:
                    console.error('Server error:', error.data);
                    break;
                default:
                    console.error(`HTTP ${error.status}: ${error.message}`);
            }
        } else {
            console.error('Unexpected error:', error);
        }
        
        throw error;
    }
}

// ============================================
// 4. REAL-WORLD EXAMPLES
// ============================================

/**
 * Пример 10: WordPress AJAX - отправка сообщения
 */
async function exampleSendMessage(appData, messageData) {
    const body = new URLSearchParams();
    body.append('action', 'my_cabinet_send_message');
    body.append('nonce', appData.messageNonce);
    body.append('thread_id', messageData.threadId);
    body.append('content', messageData.content);

    try {
        const result = await ApiFetchService.post(appData.ajaxUrl, body);
        
        if (result.success) {
            console.log('Message sent successfully');
            return result.data;
        } else {
            throw new Error(result.data?.message || 'Failed to send message');
        }
    } catch (error) {
        if (error instanceof ApiFetchError && error.status === 408) {
            alert('Превышено время ожидания. Попробуйте ещё раз.');
        }
        throw error;
    }
}

/**
 * Пример 11: Загрузка профиля пользователя
 */
async function exampleLoadProfile(appData) {
    const body = new URLSearchParams();
    body.append('action', 'my_cabinet_get_profile');
    body.append('nonce', appData.profileNonce);

    try {
        const result = await ApiFetchService.post(appData.ajaxUrl, body);
        
        if (result.success) {
            return {
                firstName: result.data.first_name,
                lastName: result.data.last_name,
                email: result.data.email
            };
        }
        
        return null;
    } catch (error) {
        console.error('Failed to load profile:', error.message);
        return null;
    }
}

/**
 * Пример 12: Параллельные запросы
 */
async function exampleParallelRequests() {
    try {
        // Запускаем оба запроса параллельно
        const [properties, filters] = await Promise.all([
            ApiFetchService.get('/wp-json/property/v1/properties', { page: 1 }),
            ApiFetchService.get('/wp-json/property/v1/filters')
        ]);
        
        return {
            properties,
            filters
        };
    } catch (error) {
        console.error('Parallel request failed:', error);
        throw error;
    }
}

// ============================================
// 5. COMPARISON: BEFORE vs AFTER
// ============================================

/**
 * ДО (старый подход с fetch):
 */
async function oldApproach(ajaxUrl, nonce) {
    const body = new URLSearchParams();
    body.append('action', 'my_cabinet_login');
    body.append('nonce', nonce);
    body.append('email', 'user@example.com');
    body.append('password', 'password123');

    // ❌ Много boilerplate кода
    const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    });

    // ❌ Ручная проверка HTTP status
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    // ❌ Ручной parsing response
    return response.json();
}

/**
 * ПОСЛЕ (новый подход с ApiFetchService):
 */
async function newApproach(ajaxUrl, nonce) {
    const body = new URLSearchParams();
    body.append('action', 'my_cabinet_login');
    body.append('nonce', nonce);
    body.append('email', 'user@example.com');
    body.append('password', 'password123');

    // ✅ Одна строка - всё остальное автоматически
    return ApiFetchService.post(ajaxUrl, body);
}

// ============================================
// EXPORT (только для examples)
// ============================================

export {
    exampleGetRequest,
    examplePostJson,
    examplePostUrlEncoded,
    exampleFormDataUpload,
    exampleWithTimeout,
    exampleWithCustomHeaders,
    exampleUpdateProperty,
    exampleDeleteProperty,
    exampleErrorHandling,
    exampleSendMessage,
    exampleLoadProfile,
    exampleParallelRequests,
    oldApproach,
    newApproach
};
