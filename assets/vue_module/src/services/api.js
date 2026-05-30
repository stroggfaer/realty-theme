// Сервисный объект с методами GET и POST можно дополнить;
export const ApiService = (function() {
    // Приватный метод;
    function ajaxRequest(type, url, data = {}, dataType = 'json', prefix = 'default') {
        return new Promise((resolve, reject) => {
            jQuery.ajax({ type: type,  url: url, data: data, dataType: dataType, cache: false, customPrefix: `${prefix}`,
                success(response) {
                    if(dataType === 'json') {
                        try {
                            resolve(response);
                        } catch (error) {
                            reject({ message: "Failed to parse JSON", response });
                        }
                    } else {
                        // Для типов данных 'html', 'text', 'xml' и других — возвращаем как есть
                        resolve(response);
                    }
                },
                error(jqXHR, textStatus, errorThrown) {
                    reject({ textStatus, errorThrown, jqXHR });
                }
            });
        });
    }
    // Публичные методы get и post;
    return {
        get(url, data = {}, dataType = 'json', prefix = 'default') {
            return ajaxRequest('GET', url, data, dataType, prefix);
        },
        post(url, data = {}, dataType = 'json', prefix = 'default') {
            return ajaxRequest('POST', url, data, dataType, prefix);
        },
        delete(url, data = {}, dataType = 'json', prefix = 'default') {
            return ajaxRequest('DELETE', url, data, dataType, prefix);
        },
        put(url, data = {}, dataType = 'json', prefix = 'default') {
            return ajaxRequest('PUT', url, data, dataType, prefix);
        },
        patch(url, data = {}, dataType = 'json', prefix = 'default') {
            return ajaxRequest('PATCH', url, data, dataType, prefix);
        }
    }
})();
