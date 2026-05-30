/**
 * JavaScript для метабокса "Характеристики" объекта недвижимости
 * 
 * Группы слева, характеристики справа, выбранные - сгруппированы по группам
 */

(function($) {
    'use strict';

    var PropertyCharacteristicsMetabox = {
        selectedGroups: null,
        sourceList: null,
        groupsList: null,
        currentGroupId: null,
        currentGroupName: null,
        currentPage: 1,
        currentPerPage: 20,
        currentSearch: '',
        searchTimeout: null,
        isLoading: false,

        init: function() {
            this.selectedGroups = $('#selected-groups-list');
            this.sourceList = $('#available-characteristics-list');
            this.groupsList = $('#char-groups-list');
            this.searchInput = $('#characteristics-search');
            this.perPageSelect = $('#characteristics-per-page');

            // Определяем первую группу
            var firstGroup = this.groupsList.find('.group-item').first();
            if (firstGroup.length) {
                this.currentGroupId = firstGroup.data('group-id');
                this.currentGroupName = firstGroup.data('group-name');
            }

            this.bindEvents();
            this.initSortable();
        },

        initSortable: function() {
            var self = this;

            // Сортировка списка групп (слева)
            this.groupsList.sortable({
                handle: '.group-item',
                axis: 'y',
                update: function(event, ui) {
                    self.saveGroupsOrder();
                }
            });

            // Если есть выбранные характеристики - инициализируем сортировку
            var hasSelected = this.selectedGroups.find('.selected-group').length;
            if (hasSelected > 0) {
                // Сортировка групп в выбранных
                this.selectedGroups.sortable({
                    items: '.selected-group',
                    axis: 'y',
                    placeholder: 'selected-group-placeholder',
                    stop: function(event, ui) {
                        console.log('Groups sortable stop');
                        self.rebuildInputs();
                    }
                });

                // Сортировка характеристик внутри каждой группы
                this.selectedGroups.find('.selected-items').each(function() {
                    if (!$(this).data('sortable-init')) {
                        $(this).data('sortable-init', '1');
                        $(this).sortable({
                            items: '.selected-item',
                            axis: 'y',
                            placeholder: 'selected-item-placeholder',
                            stop: function(event, ui) {
                                console.log('Items sortable stop');
                                self.rebuildInputs();
                            }
                        });
                    }
                });

                // Инициализируем скрытые поля
                this.rebuildInputs();
            }
        },

        initItemsSortable: function() {
            var self = this;
            this.selectedGroups.find('.selected-items').each(function() {
                if (!$(this).data('sortable-init')) {
                    $(this).data('sortable-init', '1');
                    $(this).sortable({
                        items: '.selected-item',
                        axis: 'y',
                        placeholder: 'selected-item-placeholder',
                        stop: function(event, ui) {
                            // При любом изменении - перестраиваем поля
                            self.rebuildInputs();
                        }
                    });
                }
            });
        },

        saveGroupsOrder: function() {
            var order = [];
            this.groupsList.find('.group-item').each(function() {
                order.push($(this).data('group-id'));
            });
            // Здесь можно добавить AJAX сохранение порядка групп
            console.log('Groups order:', order);
        },

        saveSelectedGroupsOrder: function() {
            this.rebuildInputs();
        },

        saveSelectedItemsOrder: function() {
            this.rebuildInputs();
        },

        rebuildInputs: function() {
            var self = this;

            console.log('rebuildInputs called');

            // Удаляем все старые поля и textarea
            self.selectedGroups.find('input[name^="property_characteristics"]').remove();
            self.selectedGroups.find('textarea[name="property_characteristics_data"]').remove();
            $('#property-characteristics-data-field').remove();

            // Собираем все характеристики по порядку DOM (группы + элементы внутри)
            var data = [];
            
            // Сначала собираем порядок групп
            this.selectedGroups.find('.selected-group').each(function() {
                var $group = $(this);
                var groupId = $group.data('group-id');
                var groupName = $group.find('.group-name').text();
                console.log('Group order:', groupName);
                // Потом порядок элементов внутри каждой группы
                $group.find('.selected-item').each(function() {
                    var charId = $(this).data('char-id');
                    var charTitle = $(this).find('.char-title').text();
                    console.log('  Char:', charId, charTitle);
                    data.push({
                        'characteristic_id': charId,
                        'group_id': groupId,
                        'order': data.length + 1
                    });
                });
            });

            console.log('Total data:', JSON.stringify(data));

            // Добавляем JSON в скрытое поле перед метабоксом
            if (data.length > 0) {
                var jsonStr = JSON.stringify(data);
                $('#property-characteristics-data-field').remove();
                var $field = $('<textarea id="property-characteristics-data-field" name="property_characteristics_data" style="display:none;"></textarea>');
                $field.text(jsonStr);
                $('#property-characteristics-metabox').before($field);
                console.log('property_characteristics_data field created', jsonStr);
            } else {
                $('#property-characteristics-data-field').remove();
            }
        },

        bindEvents: function() {
            var self = this;

            // Выбор группы
            this.groupsList.on('click', '.group-item', function(e) {
                e.preventDefault();
                self.selectGroup($(this).data('group-id'));
            });

            // Клик по характеристике - добавление в текущую группу
            this.sourceList.on('click', '.characteristic-item', function(e) {
                e.preventDefault();
                var charId = $(this).data('char-id');
                var charTitle = $(this).find('.char-title').text();
                var charIcon = $(this).find('.char-icon').html();
                self.addCharacteristic(charId, charTitle, charIcon);
            });

            // Клик по крестику - удаление
            this.selectedGroups.on('click', '.remove-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.removeCharacteristic($(this).data('char-id'));
            });

            // Поиск характеристик
            this.searchInput.on('input', function() {
                var value = $(this).val();
                self.currentSearch = value;
                self.currentPage = 1;

                clearTimeout(self.searchTimeout);
                self.searchTimeout = setTimeout(function() {
                    self.loadCharacteristics();
                }, 300);
            });

            // Изменение количества на странице
            this.perPageSelect.on('change', function() {
                self.currentPerPage = parseInt($(this).val(), 10);
                self.currentPage = 1;
                self.loadCharacteristics();
            });

            // Пагинация
            this.sourceList.on('click', '.page-btn', function(e) {
                e.preventDefault();
                var page = parseInt($(this).data('page'), 10);
                if (page && page !== self.currentPage) {
                    self.currentPage = page;
                    self.loadCharacteristics();
                }
            });

            // Перестроить данные перед сохранением формы
            $('#publish, #save-post').on('click', function() {
                self.rebuildInputs();
            });
            $(document).on('submit', '#post', function() {
                self.rebuildInputs();
            });
        },

        selectGroup: function(groupId) {
            var $group = this.groupsList.find('.group-item[data-group-id="' + groupId + '"]');
            
            this.currentGroupId = groupId;
            this.currentGroupName = $group.data('group-name');
            this.currentPage = 1;
            this.currentSearch = '';

            // Активная группа
            this.groupsList.find('.group-item').removeClass('active');
            $group.addClass('active');

            // Очищаем поиск
            this.searchInput.val('');

            // Очищаем всё перед загрузкой
            this.sourceList.find('.characteristics-source-list, .characteristics-pagination, .no-characteristics-notice, .error').remove();

            this.loadCharacteristics();
        },

        loadCharacteristics: function() {
            var self = this;

            if (!this.currentGroupId || this.isLoading) {
                return;
            }

            this.isLoading = true;

            // Очищаем всё перед загрузкой
            this.sourceList.find('.characteristics-source-list, .characteristics-pagination, .no-characteristics-notice, .error').remove();

            $.post(realtyCharacteristicsData.ajax_url, {
                action: 'realty_get_characteristics_by_group',
                nonce: realtyCharacteristicsData.nonce,
                group_id: this.currentGroupId,
                search: this.currentSearch,
                page: this.currentPage,
                per_page: this.currentPerPage
            }, function(response) {
                self.isLoading = false;
                if (response.success) {
                    self.renderSourceList(response.data.items, response.data);
                } else {
                    self.sourceList.find('.characteristics-source-list, .characteristics-pagination').remove();
                    self.sourceList.append('<p class="error">' + (response.data.message || 'Ошибка') + '</p>');
                }
            }).fail(function() {
                self.isLoading = false;
                self.sourceList.find('.characteristics-source-list, .characteristics-pagination').remove();
                self.sourceList.append('<p class="error">Ошибка загрузки</p>');
            });
        },

        renderSourceList: function(characteristics, meta) {
            var self = this;
            var selectedIds = this.getSelectedIds();

            // Удаляем старый список и пагинацию
            this.sourceList.find('.characteristics-source-list').remove();
            this.sourceList.find('.characteristics-pagination').remove();

            if (!characteristics || characteristics.length === 0) {
                this.sourceList.find('.characteristics-source-list, .characteristics-pagination').remove();
                this.sourceList.append('<p class="no-characteristics-notice">Характеристики не найдены</p>');
                return;
            }

            var html = '<ul class="characteristics-source-list">';
            characteristics.forEach(function(char) {
                var isSelected = selectedIds.indexOf(char.ID) !== -1;
                var iconHtml = self.renderIconHtml(char);

                html += '<li class="characteristic-item' + (isSelected ? ' selected' : '') + '" data-char-id="' + char.ID + '">';
                html += '<span class="dashicons dashicons-menu drag-handle"></span>';
                html += '<span class="char-icon">' + iconHtml + '</span>';
                html += '<span class="char-title">' + self.escapeHtml(char.title) + '</span>';
                if (isSelected) {
                    html += '<span class="already-selected">✓</span>';
                }
                html += '</li>';
            });
            html += '</ul>';

            // Пагинация
            if (meta && meta.total_pages > 1) {
                html += this.renderPagination(meta);
            }

            this.sourceList.append(html);
        },

        renderPagination: function(meta) {
            var html = '<div class="characteristics-pagination">';
            var startPage = Math.max(1, meta.current_page - 2);
            var endPage = Math.min(meta.total_pages, meta.current_page + 2);

            if (meta.current_page > 1) {
                html += '<button type="button" class="page-btn button button-small" data-page="' + (meta.current_page - 1) + '">&larr;</button>';
            }

            for (var i = startPage; i <= endPage; i++) {
                html += '<button type="button" class="page-btn button button-small' + (i === meta.current_page ? ' current' : '') + '" data-page="' + i + '">' + i + '</button>';
            }

            if (meta.current_page < meta.total_pages) {
                html += '<button type="button" class="page-btn button button-small" data-page="' + (meta.current_page + 1) + '">&rarr;</button>';
            }

            html += '<span class="pagination-info">' + meta.total + ' / ' + meta.total_pages + '</span>';
            html += '</div>';

            return html;
        },

        renderIconHtml: function(char) {
            if (char.icon_type === 'upload' && char.media_url) {
                return '<img src="' + this.escapeHtml(char.media_url) + '" alt="">';
            } else if (char.icon) {
                return '<span class="material-symbols-outlined">' + this.escapeHtml(char.icon) + '</span>';
            }
            return '<span class="material-symbols-outlined">help</span>';
        },

        addCharacteristic: function(charId, charTitle, charIcon) {
            var self = this;

            if (!this.currentGroupId) {
                return;
            }

            // Проверяем, уже выбрана
            var selectedIds = this.getSelectedIds();
            if (selectedIds.indexOf(charId) !== -1) {
                return;
            }

            // Проверяем, существует ли группа в выбранных
            var groupDiv = this.selectedGroups.find('.selected-group[data-group-id="' + this.currentGroupId + '"]');
            
            if (!groupDiv.length) {
                // Создаём новую группу
                var groupHtml = '<div class="selected-group" data-group-id="' + this.currentGroupId + '">';
                groupHtml += '<div class="selected-group-header">';
                groupHtml += '<span class="dashicons dashicons-menu drag-handle"></span>';
                groupHtml += '<span class="group-name">' + this.escapeHtml(this.currentGroupName) + '</span>';
                groupHtml += '</div>';
                groupHtml += '<ul class="selected-items"></ul>';
                groupHtml += '</div>';

                // Удаляем "нет выбранных"
                this.selectedGroups.find('.no-selection-notice').remove();
                this.selectedGroups.append(groupHtml);
                groupDiv = this.selectedGroups.find('.selected-group[data-group-id="' + this.currentGroupId + '"]');

                // Инициализируем sortable для групп, если ещё не инициализирован
                if (!this.selectedGroups.data('sortable-init')) {
                    this.selectedGroups.data('sortable-init', '1');
                    this.selectedGroups.sortable({
                        items: '.selected-group',
                        axis: 'y',
                        placeholder: 'selected-group-placeholder',
                        stop: function(event, ui) {
                            console.log('Groups sortable stop');
                            self.rebuildInputs();
                        }
                    });
                }
            }

            // Добавляем характеристику в группу
            var itemHtml = '<li class="selected-item" data-char-id="' + charId + '">';
            itemHtml += '<span class="char-icon">' + charIcon + '</span>';
            itemHtml += '<span class="char-title">' + this.escapeHtml(charTitle) + '</span>';
            itemHtml += '<span class="remove-btn" data-char-id="' + charId + '">&times;</span>';
            itemHtml += '</li>';

            groupDiv.find('.selected-items').append(itemHtml);

            // Обновляем скрытые поля и sortable для новых групп
            this.rebuildInputs();
            this.initItemsSortable();

            // Обновляем счётчик в списке групп
            this.updateGroupCount(this.currentGroupId);

            // Отмечаем в источнике как выбранную
            var sourceItem = this.sourceList.find('.characteristic-item[data-char-id="' + charId + '"]');
            if (sourceItem.length) {
                sourceItem.addClass('selected');
                sourceItem.find('.already-selected').remove();
                sourceItem.append('<span class="already-selected">✓</span>');
            }
        },

        removeCharacteristic: function(charId) {
            // Находим и удаляем характеристику
            var item = this.selectedGroups.find('.selected-item[data-char-id="' + charId + '"]');
            if (!item.length) return;

            // Получаем group-id перед удалением
            var groupDiv = item.closest('.selected-group');
            var groupId = groupDiv.data('group-id');

            item.remove();

            // Если группа пуста - удаляем
            if (groupDiv.find('.selected-item').length === 0) {
                groupDiv.remove();
            }

            // Обновляем счётчик
            if (groupId) {
                this.updateGroupCount(groupId);
            }

            // Если нет выбранных - показываем сообщение
            if (this.selectedGroups.find('.selected-item').length === 0) {
                this.selectedGroups.find('input[type="hidden"]').remove();
                $('#property-characteristics-data-field').remove();
                this.selectedGroups.append('<p class="no-selection-notice">Кликните на характеристику для добавления</p>');
            } else {
                this.rebuildInputs();
            }

            // Убираем отметку в источнике
            var sourceItem = this.sourceList.find('.characteristic-item[data-char-id="' + charId + '"]');
            if (sourceItem.length) {
                sourceItem.removeClass('selected');
                sourceItem.find('.already-selected').remove();
            }
        },

        getSelectedIds: function() {
            var ids = [];
            this.selectedGroups.find('.selected-item').each(function() {
                ids.push($(this).data('char-id'));
            });
            return ids;
        },

        updateGroupCount: function(groupId) {
            var count = this.selectedGroups.find('.selected-group[data-group-id="' + groupId + '"] .selected-item').length;
            var counter = this.groupsList.find('#count-' + groupId);
            if (counter.length) {
                counter.text(count);
            }
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        if ($('#property-characteristics-metabox').length) {
            PropertyCharacteristicsMetabox.init();
        }
    });

})(jQuery);