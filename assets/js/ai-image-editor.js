(function($) {
    'use strict';

    window.AIImageEditor = {
        currentImageId: null,
        currentImageUrl: null,
        originalImageUrl: null, 
        sessionId: null,
        chatHistory: [],
        currentModel: 'gpt-4o',
        availableModels: {
            openai: {
                'gpt-4o': 'GPT-4 Omni (Latest)',
                'gpt-4-turbo': 'GPT-4 Turbo with Vision',
                'gpt-4-vision-preview': 'GPT-4 Vision Preview'
            },
            gemini: {
                'gemini-2.0-flash-exp': 'Gemini 2.0 Flash (Experimental)',
                'gemini-1.5-pro': 'Gemini 1.5 Pro',
                'gemini-1.5-flash': 'Gemini 1.5 Flash'
            }
        },

        open: function(imageId, imageUrl) {
            console.log('AI Editor Opening...', {imageId, imageUrl});
            
            this.currentImageId = imageId;
            this.currentImageUrl = imageUrl;
            this.originalImageUrl = imageUrl; 
            this.sessionId = 'session_' + Date.now();
            this.chatHistory = [];
            
            // Debug: Check if lightbox exists
            console.log('Lightbox exists?', $('#ai-image-editor-lightbox').length);
            
            if ($('#ai-image-editor-lightbox').length === 0) {
                console.log('Creating lightbox...');
                this.createLightbox();
                this.bindEvents();
            }
            
            // Debug: Force background color
            setTimeout(function() {
                $('#ai-chat-history').attr('style', 'background-color: #ffffff !important;');
                console.log('Chat history background set');
            }, 500);
            
            $('body').addClass('ai-editor-active');
            $('#ai-image-editor-lightbox').fadeIn(300);
            this.loadImage();
            this.loadTemplates();
            this.initializeModelSelector();
        },

        createLightbox: function() {
            const lightboxHtml = `
                <div id="ai-image-editor-lightbox" class="sspu-lightbox">
                    <div class="lightbox-overlay"></div>
                    <div class="lightbox-content">
                        <div class="lightbox-header">
                            <h2>🎨 AI Image Editor</h2>
                            <div class="header-controls">
                                <select id="ai-model-selector" class="model-selector">
                                    <optgroup label="OpenAI Models">
                                        <option value="gpt-4o">GPT-4 Omni (Latest)</option>
                                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                    </optgroup>
                                    <optgroup label="Google Gemini">
                                        <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash</option>
                                        <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
                                        <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                                    </optgroup>
                                </select>
                                <button class="close-lightbox" title="Close">&times;</button>
                            </div>
                        </div>
                        
                        <div class="editor-container">
                            <div class="image-panel">
                                <div class="image-tools">
                                    <button class="tool-btn" data-action="zoom-in" title="Zoom In">🔍+</button>
                                    <button class="tool-btn" data-action="zoom-out" title="Zoom Out">🔍-</button>
                                    <button class="tool-btn" data-action="reset-zoom" title="Reset">↺</button>
                                    <button class="tool-btn" data-action="download" title="Download">⬇</button>
                                </div>
                                <div class="current-image">
                                    <div class="image-wrapper">
                                        <img id="ai-editor-image" src="" alt="AI Editor Target Image" />
                                        <div class="image-loading">
                                            <div class="spinner"></div>
                                            <p>Loading image...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="image-info">
                                    <span class="info-item" id="image-dimensions"></span>
                                    <span class="info-item" id="image-size"></span>
                                </div>
                            </div>
                            
                            <div class="chat-panel">
                                <div class="templates-section">
                                    <div class="templates-bar">
                                        <select id="template-selector">
                                            <option value="">Quick Actions...</option>
                                            <optgroup label="Background Replacement">
                                                <option value="lifestyle-bg">Place on Lifestyle Background</option>
                                                <option value="white-bg">Clean White Background</option>
                                                <option value="transparent-bg">Remove Background</option>
                                            </optgroup>
                                            <optgroup label="Product Enhancement">
                                                <option value="add-logo">Add Company Logo</option>
                                                <option value="enhance-lighting">Enhance Lighting</option>
                                                <option value="color-correction">Color Correction</option>
                                            </optgroup>
                                            <optgroup label="E-commerce Optimization">
                                                <option value="amazon-ready">Amazon Listing Ready</option>
                                                <option value="social-media">Social Media Optimized</option>
                                                <option value="hero-image">Hero Image Style</option>
                                            </optgroup>
                                        </select>
                                        <button class="button button-small manage-templates">⚙️</button>
                                    </div>
                                    
                                    <div class="quick-actions">
                                        <button class="quick-action-btn" data-prompt="EXTRACT the existing product exactly as it is (do not modify the product) and place it on a modern lifestyle background suitable for e-commerce">
                                            🏠 Lifestyle BG
                                        </button>
                                        <button class="quick-action-btn" data-prompt="EXTRACT the existing product without any changes and place it on a pure white background with subtle shadow">
                                            ⬜ White BG
                                        </button>
                                        <button class="quick-action-btn" data-prompt="Keep the existing product image exactly as shown and add our company logo in the bottom right corner, make it subtle but visible">
                                            🏷️ Add Logo
                                        </button>
                                        <button class="quick-action-btn" data-prompt="PRESERVE the existing product exactly but enhance the lighting to make it more appealing for online sales">
                                            💡 Enhance
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="chat-history" id="ai-chat-history">
                                    <div class="welcome-message">
                                        <h3>Welcome to AI Image Editor! 👋</h3>
                                        <p><strong>Important:</strong> This tool EXTRACTS your existing product from images - it never recreates or modifies the product itself.</p>
                                        <p>I can help you:</p>
                                        <ul>
                                            <li>Extract products and place them on new backgrounds</li>
                                            <li>Add logos and watermarks to existing images</li>
                                            <li>Enhance lighting and colors while preserving the original product</li>
                                            <li>Create e-commerce ready images from your existing products</li>
                                            <li>Generate variations for A/B testing using your actual product</li>
                                        </ul>
                                        <p>Just describe what you want, or use the quick actions above!</p>
                                    </div>
                                </div>
                                
                                <div class="chat-input-container">
                                    <div class="input-wrapper">
                                        <textarea id="ai-chat-input" 
                                                  placeholder="Describe your edit... e.g., 'Extract the product and place it on a marble countertop with soft studio lighting and add our logo'"
                                                  rows="3"></textarea>
                                        <div class="input-actions">
                                            <span class="char-count">0/1000</span>
                                            <button class="clear-input" title="Clear">✕</button>
                                        </div>
                                    </div>
                                    
                                    <div class="ai-action-buttons">
                                        <button id="ai-generate-dalle" class="button button-primary ai-request-btn" data-service="generate_dalle">
                                            🎨 Generate New Image
                                        </button>
                                        <button id="ai-edit-dalle" class="button button-primary ai-request-btn" data-service="edit_dalle">
                                            ✏️ Edit Image (DALL-E)
                                        </button>
                                        <button id="ai-analyze-current" class="button ai-request-btn" data-service="analyze_current">
                                            🔍 Analyze & Suggest
                                        </button>
                                        <button id="ai-batch-process" class="button ai-request-btn" data-service="batch_process">
                                            📦 Batch Process
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="lightbox-footer">
                            <div class="footer-actions">
                                <button class="button save-to-media">💾 Save to Media</button>
                                <button class="button apply-as-main">📷 Set as Main</button>
                                <button class="button apply-to-gallery">🖼️ Add to Gallery</button>
                                <button class="button export-variations">📤 Export All</button>
                            </div>
                            <div class="footer-status">
                                <span id="editor-status">Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(lightboxHtml);
            this.addStyles();
        },

        addStyles: function() {
            if ($('#ai-editor-styles').length === 0) {
                const styles = `
                    <style id="ai-editor-styles">
                        body.ai-editor-active {
                            overflow: hidden;
                        }
                        
                        .sspu-lightbox {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            z-index: 100000;
                            display: none;
                        }
                        
                        .lightbox-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0, 0, 0, 0.85);
                        }
                        
                        .lightbox-content {
                            position: relative;
                            width: 95%;
                            max-width: 1400px;
                            height: 90vh;
                            margin: 5vh auto;
                            background: #fff;
                            border-radius: 12px;
                            display: flex;
                            flex-direction: column;
                            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        }
                        
                        .lightbox-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 20px 30px;
                            border-bottom: 1px solid #e0e0e0;
                            background: #f8f9fa;
                            border-radius: 12px 12px 0 0;
                        }
                        
                        .lightbox-header h2 {
                            margin: 0;
                            font-size: 24px;
                            color: #333;
                        }
                        
                        .header-controls {
                            display: flex;
                            align-items: center;
                            gap: 15px;
                        }
                        
                        .model-selector {
                            padding: 8px 12px;
                            border: 1px solid #ddd;
                            border-radius: 6px;
                            font-size: 14px;
                        }
                        
                        .close-lightbox {
                            background: none;
                            border: none;
                            font-size: 32px;
                            cursor: pointer;
                            color: #666;
                            padding: 0;
                            width: 40px;
                            height: 40px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 50%;
                            transition: all 0.2s;
                        }
                        
                        .close-lightbox:hover {
                            background: #f0f0f0;
                            color: #333;
                        }
                        
                        .editor-container {
                            flex: 1;
                            display: grid;
                            grid-template-columns: 1fr 480px;
                            gap: 0;
                            overflow: hidden;
                        }
                        
                        .image-panel {
                            background: #1a1a1a;
                            display: flex;
                            flex-direction: column;
                            position: relative;
                        }
                        
                        .image-tools {
                            position: absolute;
                            top: 20px;
                            right: 20px;
                            display: flex;
                            gap: 10px;
                            z-index: 10;
                        }
                        
                        .tool-btn {
                            background: rgba(255, 255, 255, 0.9);
                            border: none;
                            width: 40px;
                            height: 40px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 18px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s;
                        }
                        
                        .tool-btn:hover {
                            background: #fff;
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                        }
                        
                        .current-image {
                            flex: 1;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            position: relative;
                            overflow: hidden;
                        }
                        
                        .image-wrapper {
                            position: relative;
                            max-width: 100%;
                            max-height: 100%;
                        }
                        
                        #ai-editor-image {
                            max-width: 100%;
                            max-height: calc(90vh - 200px);
                            display: block;
                            transition: transform 0.3s;
                        }
                        
                        .image-loading {
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            text-align: center;
                            color: white;
                            display: none;
                        }
                        
                        .image-loading.active {
                            display: block;
                        }
                        
                        .spinner {
                            border: 3px solid rgba(255, 255, 255, 0.3);
                            border-radius: 50%;
                            border-top: 3px solid white;
                            width: 50px;
                            height: 50px;
                            animation: spin 1s linear infinite;
                            margin: 0 auto 20px;
                        }
                        
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        
                        .image-info {
                            position: absolute;
                            bottom: 20px;
                            left: 20px;
                            background: rgba(0, 0, 0, 0.7);
                            padding: 10px 15px;
                            border-radius: 6px;
                            color: white;
                            font-size: 12px;
                        }
                        
                        .info-item {
                            margin-right: 15px;
                        }
                        
                        /* FIXED CHAT PANEL STYLES */
                        .chat-panel {
                            background: #ffffff !important;
                            display: flex;
                            flex-direction: column;
                            border-left: 1px solid #e0e0e0;
                            min-height: 0;
                            position: relative;
                            z-index: 10;
                        }
                        
                        .templates-section {
                            padding: 20px;
                            border-bottom: 1px solid #e0e0e0;
                            background: #f8f9fa !important;
                            position: relative;
                            z-index: 2;
                        }
                        
                        .templates-bar {
                            display: flex;
                            gap: 10px;
                            margin-bottom: 15px;
                        }
                        
                        #template-selector {
                            flex: 1;
                            padding: 10px;
                            border: 1px solid #ddd;
                            border-radius: 6px;
                            font-size: 14px;
                        }
                        
                        .quick-actions {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 10px;
                        }
                        
                        .quick-action-btn {
                            background: #fff;
                            border: 1px solid #ddd;
                            padding: 12px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 14px;
                            transition: all 0.2s;
                            text-align: center;
                        }
                        
                        .quick-action-btn:hover {
                            background: #0073aa;
                            color: white;
                            border-color: #0073aa;
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
                        }
                        
                        /* CRITICAL FIX FOR CHAT HISTORY */
                        .chat-history {
                            flex: 1;
                            overflow-y: auto;
                            padding: 20px;
                            min-height: 0;
                            /* FORCE WHITE BACKGROUND */
                            background-color: #ffffff !important;
                            background: #ffffff !important;
                            position: relative;
                            z-index: 1;
                        }
                        
                        /* Ensure all child elements inherit proper background */
                        .chat-history * {
                            background-color: transparent;
                        }
                        
                        .welcome-message {
                            background: #f0f8ff !important;
                            padding: 20px;
                            border-radius: 8px;
                            margin-bottom: 20px;
                        }
                        
                        .welcome-message h3 {
                            margin: 0 0 15px 0;
                            color: #0073aa;
                        }
                        
                        .welcome-message ul {
                            margin: 10px 0;
                            padding-left: 20px;
                        }
                        
                        .chat-message {
                            margin-bottom: 20px;
                            animation: fadeIn 0.3s;
                        }
                        
                        @keyframes fadeIn {
                            from { opacity: 0; transform: translateY(10px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        
                        .chat-message.user {
                            text-align: right;
                        }
                        
                        .chat-message.user .message-content {
                            background: #0073aa !important;
                            color: white;
                            display: inline-block;
                            padding: 12px 16px;
                            border-radius: 12px 12px 0 12px;
                            max-width: 80%;
                        }
                        
                        .chat-message.ai .message-content {
                            background: #f0f0f0 !important;
                            color: #333;
                            display: inline-block;
                            padding: 12px 16px;
                            border-radius: 12px 12px 12px 0;
                            max-width: 90%;
                        }
                        
                        .chat-message.error .message-content {
                            background: #fee !important;
                            color: #c00;
                            border: 1px solid #fcc;
                        }
                        
                        .chat-message.success .message-content {
                            background: #efe !important;
                            color: #060;
                            border: 1px solid #cfc;
                        }

                        .generated-image-container {
                            margin-top: 10px;
                            text-align: center;
                        }

                        .set-current-image-btn {
                             margin-top: 10px;
                             cursor: pointer;
                        }
                        
                        .generated-image {
                            width: 100%;
                            max-width: 300px;
                            margin-top: 10px;
                            border-radius: 8px;
                            cursor: pointer;
                            transition: transform 0.2s;
                            border: 1px solid #eee;
                        }
                        
                        .generated-image:hover {
                            transform: scale(1.02);
                        }
                        
                        .message-time {
                            font-size: 11px;
                            color: #999;
                            margin-top: 5px;
                        }
                        
                        .chat-input-container {
                            padding: 20px;
                            background: #ffffff !important;
                            border-top: 1px solid #e0e0e0;
                            position: relative;
                            z-index: 2;
                        }
                        
                        .input-wrapper {
                            position: relative;
                            margin-bottom: 15px;
                        }
                        
                        #ai-chat-input {
                            width: 100%;
                            padding: 12px;
                            padding-right: 80px;
                            border: 2px solid #ddd;
                            border-radius: 8px;
                            font-size: 14px;
                            resize: vertical;
                            min-height: 80px;
                            transition: border-color 0.2s;
                        }
                        
                        #ai-chat-input:focus {
                            outline: none;
                            border-color: #0073aa;
                        }
                        
                        .input-actions {
                            position: absolute;
                            bottom: 10px;
                            right: 10px;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        }
                        
                        .char-count {
                            font-size: 12px;
                            color: #999;
                        }
                        
                        .clear-input {
                            background: none;
                            border: none;
                            color: #999;
                            cursor: pointer;
                            font-size: 18px;
                            padding: 5px;
                        }
                        
                        .clear-input:hover {
                            color: #333;
                        }
                        
                        .ai-action-buttons {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 10px;
                        }
                        
                        .ai-request-btn {
                            padding: 12px 20px;
                            border-radius: 6px;
                            font-size: 14px;
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.2s;
                            border: 1px solid;
                        }
                        
                        .ai-request-btn:hover:not(:disabled) {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        }
                        
                        .ai-request-btn:disabled {
                            opacity: 0.6;
                            cursor: not-allowed;
                        }
                        
                        .ai-request-btn.button-primary {
                            background: #0073aa;
                            color: white;
                            border-color: #0073aa;
                        }
                        
                        .ai-request-btn.button-primary:hover:not(:disabled) {
                            background: #005785;
                        }
                        
                        .lightbox-footer {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 20px 30px;
                            border-top: 1px solid #e0e0e0;
                            background: #f8f9fa;
                            border-radius: 0 0 12px 12px;
                        }
                        
                        .footer-actions {
                            display: flex;
                            gap: 10px;
                        }
                        
                        .footer-status {
                            color: #666;
                            font-size: 14px;
                        }
                        
                        @media (max-width: 1024px) {
                            .editor-container {
                                grid-template-columns: 1fr;
                            }
                            
                            .chat-panel {
                                position: absolute;
                                right: 0;
                                top: 0;
                                height: 100%;
                                width: 400px;
                                transform: translateX(100%);
                                transition: transform 0.3s;
                            }
                            
                            .chat-panel.active {
                                transform: translateX(0);
                            }
                        }
                    </style>
                `;
                $('head').append(styles);
            }
        },

        bindEvents: function() {
            const self = this;
            const $body = $('body');
            
            // Unbind any existing events first
            $body.off('.aiEditor');

            // Use namespaced events to prevent conflicts
            $body.on('click.aiEditor', '#ai-image-editor-lightbox .close-lightbox, #ai-image-editor-lightbox .lightbox-overlay', function(e) {
                if ($(e.target).hasClass('lightbox-overlay') || $(e.target).hasClass('close-lightbox')) {
                    self.close();
                }
            });

            $body.on('click.aiEditor', '.ai-request-btn:not(:disabled)', function() {
                const service = $(this).data('service');
                self.sendAIRequest(service);
            });

            $body.on('click.aiEditor', '.quick-action-btn', function() {
                const prompt = $(this).data('prompt');
                $('#ai-chat-input').val(prompt);
                self.updateCharCount();
                self.sendAIRequest('analyze_current');
            });

            // Fix for chat input focus
            $body.on('click.aiEditor', '#ai-chat-input', function() {
                $(this).focus();
            });

            $body.on('input.aiEditor', '#ai-chat-input', function() {
                self.updateCharCount();
            });

            $body.on('keydown.aiEditor', '#ai-chat-input', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    self.sendAIRequest('analyze_current');
                }
            });

            $body.on('click.aiEditor', '.clear-input', function() {
                $('#ai-chat-input').val('').focus();
                self.updateCharCount();
            });

            $body.on('change.aiEditor', '#template-selector', function() {
                const value = $(this).val();
                if (value) {
                    const prompts = {
                        'lifestyle-bg': 'EXTRACT the existing product exactly as it is (do not modify or recreate the product) and place it on a modern lifestyle background with natural lighting',
                        'white-bg': 'EXTRACT the existing product exactly as it is (preserve all details) and place it on a pure white background with professional studio lighting and subtle shadow',
                        'transparent-bg': 'EXTRACT the existing product exactly as it is and remove the background completely, leaving only the product with clean edges',
                        'add-logo': 'Keep the existing product image unchanged and add our company logo to the bottom right corner, make it subtle but visible',
                        'enhance-lighting': 'PRESERVE the existing product exactly as shown but enhance the lighting to make it more appealing for e-commerce',
                        'color-correction': 'PRESERVE the existing product shape and details but correct the colors to be more vibrant and true-to-life',
                        'amazon-ready': 'EXTRACT the existing product without any modifications and optimize for Amazon listing: white background, centered product, proper margins',
                        'social-media': 'EXTRACT the existing product exactly as it is and create a social media optimized version with eye-catching composition',
                        'hero-image': 'EXTRACT the existing product without changes and create a hero image style with dramatic lighting and professional composition'
                    };
                    
                    if (prompts[value]) {
                        $('#ai-chat-input').val(prompts[value]);
                        self.updateCharCount();
                    }
                    $(this).val('');
                }
            });

            $body.on('change.aiEditor', '#ai-model-selector', function() {
                self.currentModel = $(this).val();
                self.updateStatus(`Switched to ${$(this).find(':selected').text()}`);
            });

            $body.on('click.aiEditor', '.tool-btn', function() {
                const action = $(this).data('action');
                self.handleImageTool(action);
            });

            $body.on('click.aiEditor', '#ai-image-editor-lightbox .save-to-media', function() { self.saveToMedia(); });
            $body.on('click.aiEditor', '#ai-image-editor-lightbox .apply-as-main', function() { self.applyAsMainImage(); });
            $body.on('click.aiEditor', '#ai-image-editor-lightbox .apply-to-gallery', function() { self.applyToGallery(); });
            $body.on('click.aiEditor', '#ai-image-editor-lightbox .export-variations', function() { self.exportVariations(); });
            $body.on('click.aiEditor', '.manage-templates', function() { 
                window.open(sspu_ajax.admin_url + 'admin.php?page=sspu-image-templates', '_blank'); 
            });

            $body.on('click.aiEditor', '.generated-image', function() {
                const src = $(this).attr('src');
                window.open(src, '_blank');
            });
            
            $body.on('click.aiEditor', '.set-current-image-btn', function() {
                const imageUrl = $(this).data('url');
                if (imageUrl) {
                    $('#ai-editor-image').attr('src', imageUrl);
                    self.updateStatus('Image loaded into editor.');
                }
            });

            // Ensure chat history is visible
            setTimeout(function() {
                $('#ai-chat-history').css({
                    'background-color': '#ffffff',
                    'background': '#ffffff'
                });
            }, 100);
        },

        initializeModelSelector: function() {
            $('#ai-model-selector').val(this.currentModel);
        },

        updateCharCount: function() {
            const text = $('#ai-chat-input').val();
            const count = text.length;
            $('.char-count').text(`${count}/1000`);
            
            if (count > 1000) {
                $('.char-count').css('color', '#c00');
            } else {
                $('.char-count').css('color', '#999');
            }
        },

        updateStatus: function(message) {
            $('#editor-status').text(message);
        },

        handleImageTool: function(action) {
            const $img = $('#ai-editor-image');
            
            switch(action) {
                case 'zoom-in':
                    const currentScale = parseFloat($img.data('scale') || 1);
                    const newScale = Math.min(currentScale + 0.25, 3);
                    $img.css('transform', `scale(${newScale})`).data('scale', newScale);
                    break;
                    
                case 'zoom-out':
                    const scale = parseFloat($img.data('scale') || 1);
                    const zoomOut = Math.max(scale - 0.25, 0.5);
                    $img.css('transform', `scale(${zoomOut})`).data('scale', zoomOut);
                    break;
                    
                case 'reset-zoom':
                    $img.css('transform', 'scale(1)').data('scale', 1);
                    break;
                    
                case 'download':
                    const link = document.createElement('a');
                    link.href = $img.attr('src');
                    link.download = 'ai-edited-image.png';
                    link.click();
                    break;
            }
        },

        loadImage: function() {
            const self = this;
            const $img = $('#ai-editor-image');
            const $loading = $('#ai-image-editor-lightbox .image-loading');

            $loading.addClass('active');
            $img.attr('src', '').hide();

            $img.off('load').on('load', function() {
                $loading.removeClass('active');
                $img.fadeIn(300);
                
                const img = this;
                $('#image-dimensions').text(`${img.naturalWidth} × ${img.naturalHeight}px`);
                
                fetch(img.src)
                    .then(response => response.blob())
                    .then(blob => {
                        const size = (blob.size / 1024).toFixed(1);
                        $('#image-size').text(`${size} KB`);
                    });
            });

            $img.off('error').on('error', function() {
                $loading.removeClass('active');
                self.addChatMessage('error', 'Could not load the source image. Please try again.');
            });

            $img.attr('src', this.currentImageUrl);
        },

        sendAIRequest: function(aiService) {
            const self = this;
            const prompt = $('#ai-chat-input').val().trim();
            
            if (!prompt && aiService !== 'batch_process') {
                $('#ai-chat-input').focus();
                return;
            }

            if (prompt.length > 1000) {
                self.addChatMessage('error', 'Please keep your prompt under 1000 characters.');
                return;
            }

            const $buttons = $('.ai-request-btn');
            $buttons.prop('disabled', true);
            const $clickedButton = $(`.ai-request-btn[data-service="${aiService}"]`);
            const originalButtonText = $clickedButton.html();
            $clickedButton.html('<span class="spinner" style="display:inline-block;width:16px;height:16px;border-width:2px;vertical-align:middle;margin-right:8px;"></span> Processing...');
            
            self.updateStatus('Processing request...');
            
            if (prompt) {
                this.addChatMessage('user', prompt);
            }

            const serviceMap = {
                'analyze_current': {
                    endpoint: this.currentModel.includes('gemini') ? 'analyze_gemini' : 'analyze_chatgpt',
                    model: this.currentModel
                },
                'generate_dalle': {
                    endpoint: 'edit_dalle',
                    model: 'dall-e-3'
                },
                'edit_dalle': {
                    endpoint: 'edit_dalle',
                    model: 'dall-e-3'
                },
                'batch_process': {
                    endpoint: 'batch_process',
                    model: this.currentModel
                }
            };

            const service = serviceMap[aiService] || { endpoint: aiService, model: this.currentModel };

            this.getImageAsBase64(function(base64Data) {
                $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_ai_edit_image',
                        nonce: sspu_ajax.nonce,
                        image_data: base64Data,
                        prompt: prompt || 'Analyze this product image and suggest improvements for extracting and enhancing the existing product',
                        ai_service: service.endpoint,
                        model: service.model,
                        session_id: self.sessionId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.addChatMessage('ai', response.data.response, response.data.edited_image);
                            if (response.data.edited_image) {
                                self.updateStatus('New image generated with extracted product.');
                            } else {
                                self.updateStatus('Analysis complete');
                            }
                        } else {
                            self.addChatMessage('error', 'Failed: ' + (response.data.message || 'Unknown error'));
                            self.updateStatus('Error occurred');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        self.addChatMessage('error', 'Network error: ' + textStatus + ' - ' + errorThrown);
                        self.updateStatus('Network error');
                    },
                    complete: function() {
                        $buttons.prop('disabled', false);
                        $clickedButton.html(originalButtonText);
                        if (prompt) {
                            $('#ai-chat-input').val('').focus();
                            self.updateCharCount();
                        }
                    }
                });
            });
        },
        
        addChatMessage: function(type, message, imageUrl) {
            const $history = $('#ai-chat-history');
            
            $('.welcome-message').fadeOut(300, function() { $(this).remove(); });
            
            const imageHtml = imageUrl ? `
                <div class="generated-image-container">
                    <img src="${imageUrl}" class="generated-image" alt="Generated Image" />
                    <br>
                    <button class="button button-small set-current-image-btn" data-url="${imageUrl}">Set as Current Image</button>
                </div>
            ` : '';
            
            const messageHtml = `
                <div class="chat-message ${type}">
                    <div class="message-content">
                        ${this.formatMessage(message)}
                        ${imageHtml}
                    </div>
                    <div class="message-time">${new Date().toLocaleTimeString()}</div>
                </div>`;
            
            $history.append(messageHtml);
            $history.animate({ scrollTop: $history[0].scrollHeight }, 300);
            
            this.chatHistory.push({
                type: type,
                message: message,
                imageUrl: imageUrl,
                timestamp: new Date()
            });
        },

        formatMessage: function(message) {
            return message
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>')
                .replace(/• /g, '&bull; ');
        },

        getImageAsBase64: function(callback) {
            const img = document.getElementById('ai-editor-image');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            const tempImg = new Image();
            tempImg.crossOrigin = "anonymous";
            
            tempImg.onload = function() {
                canvas.width = tempImg.naturalWidth;
                canvas.height = tempImg.naturalHeight;
                ctx.drawImage(tempImg, 0, 0);
                
                try {
                    callback(canvas.toDataURL('image/png'));
                } catch (e) {
                    callback(canvas.toDataURL('image/jpeg', 0.95));
                }
            };
            
            tempImg.onerror = function() {
                callback(null);
            };
            
            tempImg.src = img.src;
        },

        loadTemplates: function() {
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_get_image_templates',
                    nonce: sspu_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.templates) {
                        const $selector = $('#template-selector');
                        let customTemplates = '<optgroup label="Custom Templates">';
                        response.data.templates.forEach(function(template) {
                            customTemplates += `<option value="custom-${template.id}">${template.name}</option>`;
                        });
                        customTemplates += '</optgroup>';
                        $selector.append(customTemplates);
                    }
                }
            });
        },

        saveToMedia: function() {
            const self = this;
            const currentImage = $('#ai-editor-image').attr('src');
            
            if (!currentImage || currentImage.startsWith('http')) {
                self.addChatMessage('error', 'No new image to save. Generate or edit an image first.');
                return;
            }

            self.updateStatus('Saving to media library...');

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_save_edited_image',
                    nonce: sspu_ajax.nonce,
                    image_data: currentImage,
                    filename: 'ai-edited-' + this.currentImageId + '-' + Date.now()
                },
                success: function(response) {
                    if (response.success) {
                        self.addChatMessage('success', '✅ Image saved to media library!');
                        self.currentImageId = response.data.attachment_id;
                        self.currentImageUrl = response.data.url;
                        $('#ai-editor-image').attr('src', response.data.url);
                        self.updateStatus('Saved successfully');
                    } else {
                        self.addChatMessage('error', 'Failed to save: ' + response.data.message);
                        self.updateStatus('Save failed');
                    }
                },
                error: function() {
                    self.addChatMessage('error', 'Failed to save image to media library.');
                    self.updateStatus('Save error');
                }
            });
        },

        applyAsMainImage: function() {
            const self = this;
            const currentImageSrc = $('#ai-editor-image').attr('src');
            
            if (currentImageSrc && currentImageSrc.startsWith('data:')) {
                this.saveToMedia();
                setTimeout(() => self.applyAsMainImageCallback(), 2000);
            } else {
                this.applyAsMainImageCallback();
            }
        },

        applyAsMainImageCallback: function() {
            if (!this.currentImageId) {
                this.addChatMessage('error', 'Please save the image to the media library first.');
                return;
            }
            
            $('#sspu-main-image-id').val(this.currentImageId).trigger('change');
            $('#sspu-main-image-preview').html(`<img src="${this.currentImageUrl}" alt="" data-id="${this.currentImageId}" style="max-width: 200px;" />`);
            
            this.addChatMessage('success', '✅ Applied as main product image!');
            this.updateStatus('Set as main image');
            
            setTimeout(() => this.close(), 2000);
        },

        applyToGallery: function() {
            const self = this;
            const currentImageSrc = $('#ai-editor-image').attr('src');

            if (currentImageSrc && currentImageSrc.startsWith('data:')) {
                this.saveToMedia();
                setTimeout(() => self.applyToGalleryCallback(), 2000);
            } else {
                this.applyToGalleryCallback();
            }
        },

        applyToGalleryCallback: function() {
            if (!this.currentImageId) {
                this.addChatMessage('error', 'Please save the image to the media library first.');
                return;
            }
            
            const $hiddenInput = $('#sspu-additional-image-ids');
            const currentIds = $hiddenInput.val() ? $hiddenInput.val().split(',') : [];
            
            if (!currentIds.includes(this.currentImageId.toString())) {
                currentIds.push(this.currentImageId);
                $hiddenInput.val(currentIds.join(',')).trigger('change');
                
                const $preview = $('#sspu-additional-images-preview');
                if ($preview.length) {
                    $preview.append(`<div class="gallery-image" data-id="${this.currentImageId}">
                        <img src="${this.currentImageUrl}" alt="" style="max-width: 150px;" />
                        <button type="button" class="remove-gallery-image" data-id="${this.currentImageId}">&times;</button>
                    </div>`);
                }
                
                this.addChatMessage('success', '✅ Added to product gallery!');
                this.updateStatus('Added to gallery');
                
                setTimeout(() => this.close(), 2000);
            } else {
                this.addChatMessage('info', 'Image is already in the gallery.');
            }
        },

        exportVariations: function() {
            if (this.chatHistory.length === 0) {
                this.addChatMessage('info', 'No variations to export yet.');
                return;
            }
            
            const images = this.chatHistory.filter(h => h.imageUrl).map(h => h.imageUrl);
            if (images.length === 0) {
                this.addChatMessage('info', 'No generated images to export.');
                return;
            }
            
            images.forEach((imageUrl, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = imageUrl;
                    link.download = `variation-${index + 1}.png`;
                    link.click();
                }, index * 500);
            });
            
            this.addChatMessage('success', `✅ Exporting ${images.length} variations...`);
        },

        close: function() {
            $('#ai-image-editor-lightbox').fadeOut(300);
            $('body').removeClass('ai-editor-active');
            
            setTimeout(() => {
                $('#ai-chat-history').empty().append(`
                    <div class="welcome-message">
                        <h3>Welcome to AI Image Editor! 👋</h3>
                        <p><strong>Important:</strong> This tool EXTRACTS your existing product from images - it never recreates or modifies the product itself.</p>
                        <p>Ready for your next image editing session.</p>
                    </div>
                `);
                $('#ai-chat-input').val('');
                this.updateCharCount();
                this.updateStatus('Ready');
            }, 300);
        }
    };

})(jQuery);