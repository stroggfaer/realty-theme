/**
 * Characteristic Settings Admin JavaScript
 * 
 * Управление справочником характеристик недвижимости
 */

(function($) {
    'use strict';

    var CharacteristicAdmin = {
        nonceGetGroup: '',
        nonceGet: '',
        nonceSave: '',
        nonceMedia: '',
        nonceDeleteGroup: '',
        nonceDeleteCharacteristic: '',
        nonceClone: '',
        mediaUploader: null,
        strings: {
            addGroup: 'Добавить группу',
            editGroup: 'Редактировать группу',
            addCharacteristic: 'Добавить характеристику',
            editCharacteristic: 'Редактировать характеристику',
            cancel: 'Отмена',
            save: 'Сохранить',
            saving: 'Сохранение...',
            error: 'Ошибка загрузки данных',
            errorSaving: 'Ошибка при сохранении',
            errorNetwork: 'Ошибка сети. Проверьте соединение.',
            groupNameRequired: 'Название группы обязательно!',
            titleRequired: 'Название обязательно!',
            styleRequired: 'Выберите стиль отображения!',
            deleteConfirm: 'Вы уверены, что хотите удалить характеристику',
            deleteGroupConfirm: 'Вы уверены, что хотите удалить группу',
            deleteGroupWarning: '\n\nВсе характеристики в этой группе будут отвязаны.',
            cloneSuccess: 'Характеристика склонирована',
            noFile: 'Файл не выбран',
            enterIcon: 'Введите название иконки',
            fileTooBig: 'Файл слишком большой. Максимальный размер: 1MB',
            fileSelect: 'Выбрать иконку',
            fileUse: 'Использовать'
        },

        init: function(nonces, strings) {
            this.nonceGetGroup = nonces.get_group;
            this.nonceGet = nonces.get;
            this.nonceSave = nonces.save;
            this.nonceMedia = nonces.media;
            this.nonceDeleteGroup = nonces.delete_group;
            this.nonceDeleteCharacteristic = nonces.delete_characteristic;
            this.nonceClone = nonces.clone || nonces.save;

            if (strings) {
                this.strings = $.extend({}, this.strings, strings);
            }

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Add new group button
            $('#add-new-group-btn').on('click', function() {
                self.openAddGroupModal();
            });

            // Generate group key from name
            $('#generate-group-key-btn').on('click', function(e) {
                e.preventDefault();
                self.generateGroupKeyFromName();
            });

            // Auto-generate group key when name changes (if key is empty)
            $('#group-name').on('input', function() {
                if (!$('#group-key').val().trim()) {
                    self.generateGroupKeyFromName();
                }
            });

            // Edit group button
            $('.edit-group-btn').on('click', function(e) {
                e.preventDefault();
                self.editGroup($(this).data('group-id'));
            });

            // Close modal buttons
            $('.modal-close-btn, .modal-cancel-btn, .modal-overlay').on('click', function() {
                self.closeAllModals();
            });

            // Add new characteristic button
            $('#add-new-characteristic-btn').on('click', function() {
                self.openAddCharacteristicModal();
            });

            // Edit characteristic button
            $('.edit-characteristic-btn').on('click', function() {
                self.editCharacteristic($(this).data('characteristic-id'));
            });

            // Icon type toggle
            $('input[name="char_icon_type"]').on('change', function() {
                self.toggleIconType($(this).val());
            });

            // Icon preview
            $('#preview-icon-btn').on('click', function() {
                self.updateIconPreview();
            });

            // Media uploader
            $('#upload-media-btn').on('click', function(e) {
                e.preventDefault();
                self.openMediaUploader();
            });

            // Save characteristic
            $('#save-characteristic-btn').on('click', function() {
                self.saveCharacteristic();
            });

            // Delete characteristic
            $('.delete-characteristic-btn').on('click', function(e) {
                e.preventDefault();
                var charId = $(this).data('characteristic-id');
                var charName = $(this).data('characteristic-name');
                if (confirm(self.strings.deleteConfirm + ' "' + charName + '"?')) {
                    self.deleteCharacteristic(charId, $(this));
                }
            });

            // Clone characteristic
            $('.clone-characteristic-btn').on('click', function(e) {
                e.preventDefault();
                self.cloneCharacteristic($(this).data('characteristic-id'));
            });

            // Delete group
            $('.delete-group-btn').on('click', function(e) {
                e.preventDefault();
                self.deleteGroup($(this));
            });

            // Save group
            $('#save-group-btn').on('click', function() {
                self.saveGroup();
            });

            // Initialize sortable for characteristics
            $('#characteristics-sortable').sortable({
                handle: '.characteristics-drag-handle',
                placeholder: 'sortable-placeholder',
                update: function(event, ui) {
                    self.saveCharacteristicsOrder();
                }
            });
        },

        openModal: function(modalId) {
            $(modalId).show();
        },

        closeModal: function(modalId) {
            $(modalId).hide();
        },

        closeAllModals: function() {
            this.closeModal('#group-modal');
            this.closeModal('#characteristic-modal');
        },

        openAddGroupModal: function() {
            $('#group-modal-title').text(this.strings.addGroup);
            $('#group-form')[0].reset();
            $('#group-id').val('');
            $('#group-key').val('');
            $('#group-display-style').prop('disabled', false);
            $('#group-style-warning').hide();
            $('#group-system-template').prop('disabled', false);
            this.openModal('#group-modal');
        },

        editGroup: function(groupId) {
            var self = this;

            $.post(ajaxurl, {
                action: 'characteristic_get_group',
                nonce: this.nonceGetGroup,
                group_id: groupId
            }, function(response) {
                if (response.success) {
                    var group = response.data;

                    $('#group-modal-title').text(self.strings.editGroup);
                    $('#group-id').val(group.term_id);
                    $('#group-name').val(group.name);
                    $('#group-key').val(group.group_key || '');
                    $('#group-description').val(group.description || '');
                    $('#group-type-ui').val(group.type_ui || 'checkbox');
                    $('#group-display-style').val(group.display_style || 'standard');
                    $('#group-sort-order').val(group.sort_order || 0);
                    $('#group-active').prop('checked', group.active == 1);
                    $('#group-use-in-filters').prop('checked', group.use_in_filters == 1);
$('#group-show-in-archive').prop('checked', group.show_in_archive == 1);
                    
                    $('#group-system-template').val(group.group_system_template || '');
                    
                    // Если при редактировании уже выбран шаблон - задизаблить
                    if (group.group_system_template) {
                        $('#group-system-template').prop('disabled', true);
                    } else {
                        $('#group-system-template').prop('disabled', false);
                    }
                    
                    if (group.has_characteristics) {
                        $('#group-display-style').prop('disabled', true);
                        $('#group-style-warning').show();
                    } else {
                        $('#group-display-style').prop('disabled', false);
                        $('#group-style-warning').hide();
                    }

                    self.openModal('#group-modal');
                } else {
                    alert(response.data || self.strings.error);
                }
            });
        },

        saveGroup: function() {
            var self = this;
            var $btn = $('#save-group-btn');
            var $form = $('#group-form');

            var groupName = $('#group-name').val().trim();
            if (!groupName) {
                alert(this.strings.groupNameRequired);
                $('#group-name').focus();
                return;
            }

            var groupKey = $('#group-key').val().trim();
            if (!groupKey) {
                groupKey = this.generateKeyFromName(groupName);
                $('#group-key').val(groupKey);
            }

            var data = {
                action: 'characteristic_save_group',
                nonce: this.nonceSave,
                group_id: $('#group-id').val(),
                group_name: groupName,
                group_key: groupKey,
                group_description: $('#group-description').val(),
                group_type_ui: $('#group-type-ui').val(),
                group_display_style: $('#group-display-style').val(),
                group_sort_order: $('#group-sort-order').val(),
                group_active: $('#group-active').is(':checked') ? 1 : 0,
                group_use_in_filters: $('#group-use-in-filters').is(':checked') ? 1 : 0,
                group_show_in_archive: $('#group-show-in-archive').is(':checked') ? 1 : 0,
                group_system_template: $('#group-system-template').val()
            };

            $btn.prop('disabled', true).text(this.strings.saving);

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    var redirectUrl = window.location.pathname + '?post_type=property&page=characteristics-reference';

                    if (!data.group_id && response.data.term_id) {
                        redirectUrl += '&group_id=' + response.data.term_id;
                    } else if (data.group_id) {
                        redirectUrl += '&group_id=' + data.group_id;
                    }

                    window.location.href = redirectUrl;
                } else {
                    alert(response.data || self.strings.errorSaving);
                    $btn.prop('disabled', false).text(self.strings.save);
                }
            }).fail(function() {
                alert(self.strings.errorNetwork);
                $btn.prop('disabled', false).text(self.strings.save);
            });
        },

        deleteGroup: function($btn) {
            var self = this;
            var groupId = $btn.data('group-id');
            var groupName = $btn.data('group-name');

            if (confirm(this.strings.deleteGroupConfirm + ' "' + groupName + '"?' + this.strings.deleteGroupWarning)) {
                $btn.prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'characteristic_delete_group',
                    nonce: this.nonceDeleteGroup,
                    group_id: groupId
                }, function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data || self.strings.errorSaving);
                        $btn.prop('disabled', false);
                    }
                }).fail(function() {
                    alert(self.strings.errorNetwork);
                    $btn.prop('disabled', false);
                });
            }
        },

        openAddCharacteristicModal: function() {
            var self = this;
            var groupId = $('#characteristic-group-id').val();

            $('#characteristic-form')[0].reset();
            $('#characteristic-id').val('');
            $('#characteristic-modal-title').text(this.strings.addCharacteristic);

            $('input[name="char_icon_type"][value="material_symbol"]').prop('checked', true);
            $('#icon-preview-box').html('<span class="material-symbols-outlined" style="font-size: 32px;">help</span>');
            $('#media-preview-box').html('<p class="description">' + this.strings.noFile + '</p>');
            $('#char-media-id').val('');
            this.inheritStyleFromGroup();
            var styleType = $('#char-style').val();
            this.updateFieldVisibility(styleType);
            this.toggleIconType('material_symbol');

            // Устанавливаем состояние чекбокса "Отображать в фильтре" на основе группы
            $.post(ajaxurl, {
                action: 'characteristic_get_group',
                nonce: this.nonceGetGroup,
                group_id: groupId
            }, function(response) {
                if (response.success) {
                    var group = response.data;
                    var useInFilters = group.use_in_filters == 1;
                    // При создании новой характеристики: если use_in_filters включен - чекбокс включен и активен
                    $('#char-show-in-filter').prop('checked', useInFilters);
                    $('#char-show-in-filter').prop('disabled', !useInFilters);
                } else {
                    $('#char-show-in-filter').prop('checked', false);
                    $('#char-show-in-filter').prop('disabled', true);
                }
            });

            this.openModal('#characteristic-modal');
        },

        inheritStyleFromGroup: function() {
            var groupId = $('#characteristic-group-id').val();
            if (!groupId) return;

            var groupItem = $('.group-item[data-group-id="' + groupId + '"]');
            var groupStyle = groupItem.data('display-style') || 'standard';

            $('#char-style').val(groupStyle);

            var styleLabels = {
                'circle': 'Circle — Круглая иконка с лейблом',
                'standard': 'Standard — Стандартная иконка и текст',
                'prohibited': 'Prohibited — Зачеркнутый (запрет)',
                'text': 'Text — Только текст'
            };

            $('#char-style-display').val(styleLabels[groupStyle] || groupStyle);
        },

        updateFieldVisibility: function(style) {
            $('.char-field-label, .char-field-hint, .char-field-value, .char-field-icon-type').hide();
            $('#material-symbol-row').hide();
            switch(style) {
                case 'circle':
                    $('.char-field-hint, .char-field-value, .char-field-icon-type').show();
                    $('#material-symbol-row').show();
                    break;
                case 'standard':
                    $('.char-field-value, .char-field-icon-type').show();
                    $('#material-symbol-row').show();
                    break;
                case 'prohibited':
                    $('.char-field-hint, .char-field-value, .char-field-icon-type').show();
                    $('#material-symbol-row').show();
                    break;
                case 'text':
                    $('.char-field-label, .char-field-hint, .char-field-value').show();
                    break;
            }
        },

        editCharacteristic: function(charId) {
            var self = this;
            var groupId = $('#characteristic-group-id').val();

            $.post(ajaxurl, {
                action: 'characteristic_get',
                nonce: this.nonceGet,
                characteristic_id: charId
            }, function(response) {
                if (response.success) {
                    var char = response.data;

                    $('#characteristic-modal-title').text(self.strings.editCharacteristic);
                    $('#characteristic-id').val(char.ID);
                    $('#char-title').val(char.post_title);
                    $('#char-description').val(char.post_content || '');
                    $('#char-label').val(char.label || '');
                    $('#char-hint').val(char.hint || '');
                    $('#char-value').val(char.value || '');

                    var charStyle = char.style || 'standard';
                    $('#char-style').val(charStyle);

                    var styleLabels = {
                        'circle': 'Circle — Круглая иконка с лейблом',
                        'standard': 'Standard — Стандартная иконка и текст',
                        'prohibited': 'Prohibited — Зачеркнутый (запрет)',
                        'text': 'Text — Только текст'
                    };
                    $('#char-style-display').val(styleLabels[charStyle] || charStyle);

                    $('#char-icon-type').val(char.icon_type || 'material_symbol');
                    $('#char-icon').val(char.icon || '');
                    $('#char-media-id').val(char.media_id || '');
                    $('#char-parent').val(char.post_parent || 0);
                    $('#char-sort-order').val(char.sort_order || 0);
                    $('#char-active').prop('checked', char.post_status === 'publish');

                    if (char.icon_type === 'upload' && char.media_id) {
                        $('input[name="char_icon_type"][value="upload"]').prop('checked', true);
                        $('#char-media-id').val(char.media_id);
                    } else {
                        $('input[name="char_icon_type"][value="material_symbol"]').prop('checked', true);
                    }

                    self.updateFieldVisibility(charStyle);
                    self.toggleIconType($('input[name="char_icon_type"]:checked').val());
                    
                    if (char.icon_type === 'upload' && char.media_id) {
                        self.updateMediaPreview(char.media_id);
                    } else {
                        self.updateIconPreview(char.icon || 'help');
                    }

                    // Получаем use_in_filters из группы для управления чекбоксом
                    $.post(ajaxurl, {
                        action: 'characteristic_get_group',
                        nonce: self.nonceGetGroup,
                        group_id: groupId
                    }, function(groupResponse) {
                        if (groupResponse.success) {
                            var group = groupResponse.data;
                            var useInFilters = group.use_in_filters == 1;
                            $('#char-show-in-filter').prop('checked', char.show_in_filter == 1);
                            $('#char-show-in-filter').prop('disabled', !useInFilters);
                        } else {
                            $('#char-show-in-filter').prop('checked', char.show_in_filter == 1);
                            $('#char-show-in-filter').prop('disabled', true);
                        }
                    });

                    self.openModal('#characteristic-modal');
                } else {
                    alert(response.data || self.strings.error);
                }
            });
        },

        toggleIconType: function(type) {
            var style = $('#char-style').val();
            if (style === 'text') {
                $('#material-symbol-row').hide();
                $('#media-upload-row').hide();
                return;
            }
            if (type === 'material_symbol') {
                $('#material-symbol-row').show();
                $('#media-upload-row').hide();
            } else {
                $('#material-symbol-row').hide();
                $('#media-upload-row').show();
            }
        },

        updateIconPreview: function() {
            var iconName = $('#char-icon').val().trim();
            if (iconName) {
                $('#icon-preview-box').html('<span class="material-symbols-outlined" style="font-size: 32px;">' + this.escHtml(iconName) + '</span>');
            }
        },

        escHtml: function(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        },

        generateKeyFromName: function(name) {
            var key = name.toLowerCase()
                .replace(/[^a-z0-9а-яё]/gi, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            
            var translitMap = {
                'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
                'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
                'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
                'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '',
                'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
            };
            
            Object.keys(translitMap).forEach(function(cyrillic) {
                key = key.replace(new RegExp(cyrillic, 'g'), translitMap[cyrillic]);
            });
            
            return key;
        },

        generateGroupKeyFromName: function() {
            var name = $('#group-name').val().trim();
            if (name) {
                $('#group-key').val(this.generateKeyFromName(name));
            }
        },

        openMediaUploader: function() {
            var self = this;

            if (this.mediaUploader) {
                this.mediaUploader.open();
                return;
            }

            this.mediaUploader = wp.media({
                title: this.strings.fileSelect,
                button: {
                    text: this.strings.fileUse
                },
                multiple: false,
                library: {
                    type: ['image/png', 'image/svg+xml', 'image/jpeg', 'image/gif']
                }
            });

            this.mediaUploader.on('select', function() {
                var attachment = self.mediaUploader.state().get('selection').first().toJSON();

                if (attachment.filesize > 1048576) {
                    alert(self.strings.fileTooBig);
                    return;
                }

                $('#char-media-id').val(attachment.id);
                self.updateMediaPreview(attachment.id);
            });

            this.mediaUploader.open();
        },

        updateMediaPreview: function(mediaId) {
            var self = this;

            if (!mediaId) {
                $('#media-preview-box').html('<p class="description">' + this.strings.noFile + '</p>');
                $('#char-media-id').val('');
                return;
            }

            $('#char-media-id').val(mediaId);

            $.post(ajaxurl, {
                action: 'characteristic_get_media_url',
                nonce: this.nonceMedia,
                media_id: mediaId
            }, function(response) {
                if (response.success) {
                    $('#media-preview-box').html('<img src="' + response.data.url + '" alt="">');
                } else {
                    $('#media-preview-box').html('<p class="description">' + self.strings.error + '</p>');
                }
            });
        },

        saveCharacteristic: function() {
            var self = this;
            var $btn = $('#save-characteristic-btn');

            var title = $('#char-title').val().trim();
            if (!title) {
                alert(this.strings.titleRequired);
                $('#char-title').focus();
                return;
            }

            var style = $('#char-style').val();
            if (!style) {
                alert(this.strings.styleRequired);
                return;
            }

            var iconType = $('input[name="char_icon_type"]:checked').val();
            var data = {
                action: 'characteristic_save',
                nonce: this.nonceSave,
                characteristic_id: $('#characteristic-id').val(),
                group_id: $('#characteristic-group-id').val(),
                char_title: title,
                char_description: $('#char-description').val(),
                char_label: $('#char-label').val(),
                char_hint: $('#char-hint').val(),
                char_value: $('#char-value').val(),
                char_style: style,
                char_icon_type: iconType,
                char_icon: iconType === 'material_symbol' ? $('#char-icon').val() : '',
                char_media_id: iconType === 'upload' ? $('#char-media-id').val() : '',
                char_parent: $('#char-parent').val(),
                char_sort_order: $('#char-sort-order').val(),
                char_show_in_filter: $('#char-show-in-filter').is(':checked') ? 1 : 0,
                char_active: $('#char-active').is(':checked') ? 'publish' : 'draft'
            };

            $btn.prop('disabled', true).text(this.strings.saving);

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || self.strings.errorSaving);
                    $btn.prop('disabled', false).text(self.strings.save);
                }
            }).fail(function() {
                alert(self.strings.errorNetwork);
                $btn.prop('disabled', false).text(self.strings.save);
            });
        },

        saveCharacteristicsOrder: function() {
            var self = this;
            var order = [];

            $('#characteristics-sortable .characteristic-row').each(function(index) {
                var charId = $(this).data('characteristic-id');
                order.push(charId);
            });

            $.post(ajaxurl, {
                action: 'characteristic_save_order',
                nonce: this.nonceSave,
                order: order,
                group_id: $('#current-group-id').val()
            }, function(response) {
                if (!response.success) {
                    alert(response.data || self.strings.errorSaving);
                    // Reload to restore original order
                    window.location.reload();
                }
            });
        },

        deleteCharacteristic: function(charId, $btn) {
            var self = this;

            $btn.prop('disabled', true);

            $.post(ajaxurl, {
                action: 'characteristic_delete',
                nonce: this.nonceDeleteCharacteristic,
                characteristic_id: charId
            }, function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || self.strings.errorSaving);
                    $btn.prop('disabled', false);
                }
            }).fail(function() {
                alert(self.strings.errorNetwork);
                $btn.prop('disabled', false);
            });
        },

        cloneCharacteristic: function(charId) {
            var self = this;

            $.post(ajaxurl, {
                action: 'characteristic_clone',
                nonce: this.nonceClone,
                characteristic_id: charId
            }, function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || self.strings.errorSaving);
                }
            }).fail(function() {
                alert(self.strings.errorNetwork);
            });
        }
    };

    // Делаем доступным глобально сразу
    window.CharacteristicAdmin = CharacteristicAdmin;

})(jQuery);