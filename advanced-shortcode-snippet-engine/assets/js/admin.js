/**
 * Admin JavaScript for Advanced Shortcode & Snippet Engine
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        ASSEAdmin.init();
    });

    // Main admin object
    var ASSEAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initCodeEditor();
            this.initStatusToggles();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Save snippet button
            $(document).on('click', '#asse-save-snippet', this.saveSnippet);
            
            // Delete snippet button
            $(document).on('click', '.asse-delete-snippet', this.deleteSnippet);
            
            // Validate code button
            $(document).on('click', '#asse-validate-code', this.validateCode);
            
            // Type change handler
            $(document).on('change', '#snippet-type', this.handleTypeChange);
            
            // Auto-save on Ctrl+S
            $(document).on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    $('#asse-save-snippet').trigger('click');
                }
            });
        },
        
        /**
         * Initialize CodeMirror editor
         */
        initCodeEditor: function() {
            var codeEditor = document.getElementById('snippet-code-editor');
            
            if (codeEditor && typeof wp.codeEditor !== 'undefined') {
                var settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                
                settings.codemirror = _.extend({}, settings.codemirror, {
                    lineNumbers: true,
                    lineWrapping: true,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    theme: 'default',
                    viewportMargin: Infinity,
                });
                
                this.editor = wp.codeEditor.initialize(codeEditor, settings);
            }
        },
        
        /**
         * Initialize status toggle switches
         */
        initStatusToggles: function() {
            $('.asse-toggle-switch input').on('change', function() {
                var $toggle = $(this);
                var snippetId = $toggle.data('snippet-id');
                var newStatus = $toggle.prop('checked') ? 'active' : 'inactive';
                
                ASSEAdmin.toggleStatus(snippetId, newStatus);
            });
        },
        
        /**
         * Save snippet via AJAX
         */
        saveSnippet: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('form');
            
            // Get form data
            var snippetData = {
                id: $form.find('#snippet-id').val(),
                title: $form.find('#snippet-title').val(),
                slug: $form.find('#snippet-slug').val(),
                type: $form.find('#snippet-type').val(),
                code: ASSEAdmin.getEditorContent(),
                status: $form.find('#snippet-status').val(),
                priority: $form.find('#snippet-priority').val(),
                scope: $form.find('#snippet-scope').val(),
                tags: $form.find('#snippet-tags').val(),
                categories: $form.find('#snippet-categories').val(),
            };
            
            // Show loading state
            $button.prop('disabled', true).text(asseAdmin.strings.saving);
            
            // Send AJAX request
            $.ajax({
                url: asseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'asse_save_snippet',
                    nonce: asseAdmin.nonce,
                    snippet: snippetData,
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        ASSEAdmin.showNotice(response.data.message, 'success');
                        
                        // Update ID if new snippet
                        if (!snippetData.id && response.data.id) {
                            $form.find('#snippet-id').val(response.data.id);
                        }
                    } else {
                        ASSEAdmin.showNotice(response.data.message || asseAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    ASSEAdmin.showNotice(asseAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Snippet');
                }
            });
        },
        
        /**
         * Delete snippet via AJAX
         */
        deleteSnippet: function(e) {
            e.preventDefault();
            
            if (!confirm(asseAdmin.strings.confirmDelete)) {
                return;
            }
            
            var $button = $(this);
            var snippetId = $button.data('snippet-id');
            
            $.ajax({
                url: asseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'asse_delete_snippet',
                    nonce: asseAdmin.nonce,
                    id: snippetId,
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row from table
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                        ASSEAdmin.showNotice(response.data.message, 'success');
                    } else {
                        ASSEAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ASSEAdmin.showNotice('Error deleting snippet', 'error');
                }
            });
        },
        
        /**
         * Toggle snippet status via AJAX
         */
        toggleStatus: function(snippetId, status) {
            $.ajax({
                url: asseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'asse_toggle_status',
                    nonce: asseAdmin.nonce,
                    id: snippetId,
                    status: status,
                },
                success: function(response) {
                    if (!response.success) {
                        ASSEAdmin.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        /**
         * Validate code via AJAX
         */
        validateCode: function(e) {
            e.preventDefault();
            
            var code = ASSEAdmin.getEditorContent();
            var type = $('#snippet-type').val();
            
            $.ajax({
                url: asseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'asse_validate_code',
                    nonce: asseAdmin.nonce,
                    code: code,
                    type: type,
                },
                success: function(response) {
                    if (response.success) {
                        ASSEAdmin.showNotice(response.data.message, 'success');
                    } else {
                        ASSEAdmin.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        /**
         * Get content from editor
         */
        getEditorContent: function() {
            if (this.editor && this.editor.codemirror) {
                return this.editor.codemirror.getValue();
            }
            return $('#snippet-code-editor').val();
        },
        
        /**
         * Handle snippet type change
         */
        handleTypeChange: function() {
            var type = $(this).val();
            var mode = 'htmlmixed';
            
            switch(type) {
                case 'php':
                    mode = 'application/x-httpd-php';
                    break;
                case 'css':
                    mode = 'css';
                    break;
                case 'js':
                    mode = 'javascript';
                    break;
                case 'json':
                    mode = 'application/json';
                    break;
                case 'sql':
                    mode = 'text/x-sql';
                    break;
            }
            
            if (ASSEAdmin.editor && ASSEAdmin.editor.codemirror) {
                ASSEAdmin.editor.codemirror.setOption('mode', mode);
            }
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.asse-admin-container .notice').remove();
            
            // Add new notice
            $('.asse-admin-header').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Expose to global scope
    window.ASSEAdmin = ASSEAdmin;

})(jQuery);
