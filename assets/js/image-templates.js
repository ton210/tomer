(function($) {
    'use strict';

    let currentCategory = 'all';
    let templates = [];
    let selectedTemplate = null;

    $(document).ready(function() {
        // Only initialize if we're on the templates page
        if ($('.template-management').length) {
            initializeTemplateManagement();
        }
        
        // Initialize template selector in AI image editor
        if ($('#template-selector').length) {
            loadTemplatesForSelector();
        }
    });

    function initializeTemplateManagement() {
        loadTemplates();
        
        // Category filter buttons
        $('.category-filter').on('click', function() {
            $('.category-filter').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).data('category');
            filterTemplates();
        });
        
        // Create new template button
        $('#create-new-template').on('click', function() {
            openTemplateEditor();
        });
        
        // Search functionality
        $('#template-search').on('input', function() {
            filterTemplates();
        });
    }

    function loadTemplates() {
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_get_image_templates',
                nonce: sspu_ajax.nonce,
                category: 'all'
            },
            success: function(response) {
                if (response.success) {
                    templates = response.data.templates;
                    renderTemplates();
                }
            },
            error: function() {
                showNotification('Failed to load templates', 'error');
            }
        });
    }

    function renderTemplates() {
        const $container = $('#templates-list');
        $container.empty();
        
        if (templates.length === 0) {
            $container.html('<p class="no-templates">No templates found. Create your first template!</p>');
            return;
        }
        
        const templatesHtml = templates.map(template => `
            <div class="template-card" data-template-id="${template.template_id}">
                <div class="template-header">
                    <h3>${escapeHtml(template.name)}</h3>
                    ${template.is_global ? '<span class="badge badge-global">Global</span>' : ''}
                    <span class="badge badge-${template.ai_service}">${template.ai_service.toUpperCase()}</span>
                </div>
                <div class="template-category">
                    <span class="category-tag">${formatCategory(template.category)}</span>
                </div>
                <div class="template-prompt">
                    <p>${escapeHtml(template.prompt)}</p>
                </div>
                ${template.example_images.length > 0 ? `
                    <div class="template-examples">
                        ${template.example_images.slice(0, 3).map(img => 
                            `<img src="${img}" alt="Example" />`
                        ).join('')}
                        ${template.example_images.length > 3 ? 
                            `<span class="more-examples">+${template.example_images.length - 3} more</span>` : ''
                        }
                    </div>
                ` : ''}
                <div class="template-stats">
                    <span class="usage-count">Used ${template.usage_count} times</span>
                </div>
                <div class="template-actions">
                    <button class="button use-template" data-template-id="${template.template_id}">
                        Use Template
                    </button>
                    ${template.is_owner || isAdmin() ? `
                        <button class="button edit-template" data-template-id="${template.template_id}">
                            Edit
                        </button>
                        <button class="button button-link-delete delete-template" data-template-id="${template.template_id}">
                            Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        $container.html(templatesHtml);
        
        // Bind events
        $('.use-template').on('click', handleUseTemplate);
        $('.edit-template').on('click', handleEditTemplate);
        $('.delete-template').on('click', handleDeleteTemplate);
    }

    function filterTemplates() {
        const searchTerm = $('#template-search').val().toLowerCase();
        
        $('.template-card').each(function() {
            const $card = $(this);
            const templateId = $card.data('template-id');
            const template = templates.find(t => t.template_id == templateId);
            
            let visible = true;
            
            // Category filter
            if (currentCategory !== 'all' && template.category !== currentCategory) {
                visible = false;
            }
            
            // Search filter
            if (searchTerm && visible) {
                const matchesSearch = 
                    template.name.toLowerCase().includes(searchTerm) ||
                    template.prompt.toLowerCase().includes(searchTerm);
                if (!matchesSearch) {
                    visible = false;
                }
            }
            
            $card.toggle(visible);
        });
    }

    function openTemplateEditor(templateId = null) {
        const template = templateId ? templates.find(t => t.template_id == templateId) : null;
        const isEdit = !!template;
        
        const modalHtml = `
            <div class="template-editor-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${isEdit ? 'Edit Template' : 'Create New Template'}</h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="template-form">
                            <div class="form-group">
                                <label for="template-name">Template Name *</label>
                                <input type="text" id="template-name" value="${isEdit ? escapeHtml(template.name) : ''}" required />
                            </div>
                            
                            <div class="form-group">
                                <label for="template-category">Category *</label>
                                <select id="template-category" required>
                                    <option value="background" ${isEdit && template.category === 'background' ? 'selected' : ''}>Background</option>
                                    <option value="lifestyle" ${isEdit && template.category === 'lifestyle' ? 'selected' : ''}>Lifestyle</option>
                                    <option value="variations" ${isEdit && template.category === 'variations' ? 'selected' : ''}>Variations</option>
                                    <option value="branding" ${isEdit && template.category === 'branding' ? 'selected' : ''}>Branding</option>
                                    <option value="enhancement" ${isEdit && template.category === 'enhancement' ? 'selected' : ''}>Enhancement</option>
                                    <option value="hero" ${isEdit && template.category === 'hero' ? 'selected' : ''}>Hero Shots</option>
                                    <option value="custom" ${isEdit && template.category === 'custom' ? 'selected' : ''}>Custom</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="template-prompt">Prompt Template *</label>
                                <textarea id="template-prompt" rows="5" required>${isEdit ? escapeHtml(template.prompt) : ''}</textarea>
                                <p class="help-text">Use {product_name} as a placeholder for the product name</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="template-ai-service">AI Service</label>
                                <select id="template-ai-service">
                                    <option value="chatgpt" ${isEdit && template.ai_service === 'chatgpt' ? 'selected' : ''}>ChatGPT (DALL-E 3)</option>
                                    <option value="gemini" ${isEdit && template.ai_service === 'gemini' ? 'selected' : ''}>Google Gemini</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Example Images (Optional)</label>
                                <button type="button" id="select-example-images" class="button">
                                    Select Example Images
                                </button>
                                <div id="example-images-preview"></div>
                                <input type="hidden" id="example-image-ids" />
                            </div>
                            
                            ${isAdmin() ? `
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="template-is-global" ${isEdit && template.is_global ? 'checked' : ''} />
                                        Make this a global template (available to all users)
                                    </label>
                                </div>
                            ` : ''}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="button button-primary" id="save-template">
                            ${isEdit ? 'Update Template' : 'Create Template'}
                        </button>
                        <button class="button cancel-modal">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Bind modal events
        $('.close-modal, .cancel-modal').on('click', closeModal);
        $('#save-template').on('click', function() {
            saveTemplate(templateId);
        });
        
        // Example images selector
        $('#select-example-images').on('click', function() {
            selectExampleImages();
        });
        
        // If editing, load example images
        if (isEdit && template.example_images && template.example_images.length > 0) {
            displayExampleImages(template.example_images);
        }
    }

    function saveTemplate(templateId = null) {
        const formData = {
            action: 'sspu_save_image_template',
            nonce: sspu_ajax.nonce,
            name: $('#template-name').val().trim(),
            prompt: $('#template-prompt').val().trim(),
            category: $('#template-category').val(),
            ai_service: $('#template-ai-service').val(),
            example_images: $('#example-image-ids').val().split(',').filter(id => id),
            is_global: $('#template-is-global').is(':checked') ? 1 : 0
        };
        
        if (templateId) {
            formData.template_id = templateId;
        }
        
        if (!formData.name || !formData.prompt) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }
        
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Template saved successfully', 'success');
                    closeModal();
                    loadTemplates();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Failed to save template', 'error');
            }
        });
    }

    function handleUseTemplate() {
        const templateId = $(this).data('template-id');
        
        // If in image editor, load template
        if (window.AIImageEditor && window.AIImageEditor.loadTemplate) {
            window.AIImageEditor.loadTemplate(templateId);
            return;
        }
        
        // Otherwise, get template details
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_use_image_template',
                nonce: sspu_ajax.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    selectedTemplate = response.data.template;
                    showNotification('Template loaded: ' + selectedTemplate.name, 'success');
                    
                    // If template selector exists, update it
                    if ($('#template-selector').length) {
                        $('#template-selector').val(templateId);
                        $('#ai-chat-input').val(selectedTemplate.prompt);
                    }
                }
            }
        });
    }

    function handleEditTemplate() {
        const templateId = $(this).data('template-id');
        openTemplateEditor(templateId);
    }

    function handleDeleteTemplate() {
        const templateId = $(this).data('template-id');
        
        if (!confirm('Are you sure you want to delete this template?')) {
            return;
        }
        
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_delete_image_template',
                nonce: sspu_ajax.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Template deleted successfully', 'success');
                    loadTemplates();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Failed to delete template', 'error');
            }
        });
    }

    function selectExampleImages() {
        const mediaUploader = wp.media({
            title: 'Select Example Images',
            button: { text: 'Use these images' },
            multiple: true,
            library: { type: 'image' }
        });

        mediaUploader.on('select', function() {
            const attachments = mediaUploader.state().get('selection').toJSON();
            const imageIds = attachments.map(att => att.id);
            const imageUrls = attachments.map(att => att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
            
            $('#example-image-ids').val(imageIds.join(','));
            displayExampleImages(imageUrls);
        });

        mediaUploader.open();
    }

    function displayExampleImages(imageUrls) {
        const $preview = $('#example-images-preview');
        $preview.empty();
        
        imageUrls.forEach(url => {
            $preview.append(`<img src="${url}" alt="Example" class="example-thumb" />`);
        });
    }

    function loadTemplatesForSelector() {
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_get_image_templates',
                nonce: sspu_ajax.nonce,
                category: 'all'
            },
            success: function(response) {
                if (response.success) {
                    const $selector = $('#template-selector');
                    $selector.empty();
                    $selector.append('<option value="">Select a template...</option>');
                    
                    // Group by category
                    const categories = {};
                    response.data.templates.forEach(template => {
                        if (!categories[template.category]) {
                            categories[template.category] = [];
                        }
                        categories[template.category].push(template);
                    });
                    
                    // Add templates by category
                    Object.keys(categories).forEach(category => {
                        $selector.append(`<optgroup label="${formatCategory(category)}">`);
                        categories[category].forEach(template => {
                            $selector.append(`<option value="${template.template_id}">${template.name}</option>`);
                        });
                        $selector.append('</optgroup>');
                    });
                }
            }
        });
    }

    function closeModal() {
        $('.template-editor-modal').fadeOut(function() {
            $(this).remove();
        });
    }

    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        
        if ($('.template-management').length) {
            $('.template-management').prepend($notification);
        } else {
            $('body').prepend($notification);
        }
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    function formatCategory(category) {
        const categoryNames = {
            'background': 'Background',
            'lifestyle': 'Lifestyle',
            'variations': 'Variations',
            'branding': 'Branding',
            'enhancement': 'Enhancement',
            'hero': 'Hero Shots',
            'custom': 'Custom'
        };
        return categoryNames[category] || category;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function isAdmin() {
        // This should be set from PHP via localization
        return window.sspu_ajax && window.sspu_ajax.is_admin;
    }

    // Export for use in other scripts
    window.SSPUImageTemplates = {
        loadTemplatesForSelector: loadTemplatesForSelector,
        getSelectedTemplate: function() { return selectedTemplate; }
    };

})(jQuery);