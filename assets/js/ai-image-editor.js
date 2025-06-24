(function($) {
    /**
     * AIImageEditor with Enhanced Debugging
     * Main object attached to the window to manage the AI image editing lightbox.
     * Enhanced with comprehensive console logging for variant image processing
     */
    window.AIImageEditor = {
        // --- STATE PROPERTIES --- //
        cI: null, // Current Image ID
        cU: null, // Current Image URL
        oU: null, // Original Image URL
        sI: null, // Session ID
        cH: [], // Chat History
        cM: "gemini-2.0-flash-preview-image-generation", // Current Model - Default to Gemini 2.0 Flash
        selectedReferenceImage: null, // Selected reference image for mimicking
        mimicImages: [], // Available mimic reference images
        imageHistory: [], // Image history for undo/redo
        historyIndex: -1, // Current position in history
        openedFromVariants: false, // Track if opened from variants tab
        variantRowElement: null, // Store the variant row element
        originalVariantImageId: null, // Store original variant image ID for debugging
        
        // Synced models with PHP backend
        aM: { // Available Models
            openai: {
                "dall-e-3": {
                    name: "DALL-E 3",
                    supports_vision: false,
                    supports_generation: true,
                    description: "Advanced text-to-image generation"
                }
            },
            gemini: {
                "gemini-2.0-flash-preview-image-generation": {
                    name: "Gemini 2.0 Flash (Experimental)",
                    supports_vision: true,
                    supports_generation: true,
                    description: "Fast multimodal model for image editing"
                },
                "gemini-1.5-pro": {
                    name: "Gemini 1.5 Pro",
                    supports_vision: true,
                    supports_generation: false,
                    description: "Advanced reasoning with vision"
                },
                "gemini-1.5-flash": {
                    name: "Gemini 1.5 Flash",
                    supports_vision: true,
                    supports_generation: false,
                    description: "Fast and efficient analysis"
                }
            },
            vertex: {
                "gemini-2.0-flash-preview-image-generation": {
                    name: "Vertex AI Gemini 2.0",
                    supports_vision: true,
                    supports_generation: true,
                    description: "Enterprise-grade Gemini via Vertex AI"
                }
            }
        },

        // --- CORE METHODS --- //

        /**
         * Initializes and opens the AI Image Editor lightbox.
         * @param {string} imageId - The ID of the image to edit.
         * @param {string} imageUrl - The URL of the image to edit.
         * @param {object} options - Additional options (e.g., fromVariants, variantRow)
         */
        open: function(imageId, imageUrl, options = {}) {
            console.group('🎨 AI Image Editor - Opening');
            console.log('📸 Image ID:', imageId);
            console.log('🔗 Image URL:', imageUrl);
            console.log('⚙️ Options:', options);
            
            this.cI = imageId;
            this.cU = imageUrl;
            this.oU = imageUrl;
            this.sI = "session_" + Date.now();
            this.cH = [];
            this.selectedReferenceImage = null;
            this.mimicImages = [];
            
            // Check if opened from variants
            this.openedFromVariants = options.fromVariants || false;
            this.variantRowElement = options.variantRow || null;
            
            // Store original variant image ID for debugging
            if (this.openedFromVariants && this.variantRowElement) {
                this.originalVariantImageId = $(this.variantRowElement).find('.sspu-variant-image-id').val();
                console.log('🏷️ Original Variant Image ID:', this.originalVariantImageId);
                console.log('📍 Variant Row Element:', this.variantRowElement);
            }
            
            // Initialize history
            this.imageHistory = [];
            this.historyIndex = -1;

            // Create the lightbox HTML and CSS if it doesn't exist yet
            if ($("#ai-image-editor-lightbox").length === 0) {
                console.log('🔨 Creating lightbox UI...');
                this.createLightbox();
                this.bindEvents();
            }

            // Show/hide variant save button based on context
            if (this.openedFromVariants) {
                console.log('✅ Showing variant save button');
                $('.save-as-variant').show();
            } else {
                console.log('❌ Hiding variant save button');
                $('.save-as-variant').hide();
            }

            $("body").addClass("ai-editor-active");
            $("#ai-image-editor-lightbox").fadeIn(300);

            this.loadImage();
            this.loadTemplates();
            this.initializeModelSelector();
            
            console.log('🚀 AI Image Editor opened successfully');
            console.log('📊 Session ID:', this.sI);
            console.groupEnd();
        },

        /**
         * Creates and appends the lightbox HTML structure to the body.
         */
        createLightbox: function() {
            console.log('🏗️ Creating lightbox HTML structure...');
            const lightboxHTML = `
                <div id="ai-image-editor-lightbox" class="sspu-lightbox">
                    <div class="lightbox-overlay"></div>
                    <div class="lightbox-content">
                        <div class="lightbox-header">
                            <h2>🎨 AI Image Editor</h2>
                            <div class="header-controls">
                                <select id="ai-model-selector" class="model-selector">
                                    <optgroup label="OpenAI Models">
                                        <option value="dall-e-3">DALL-E 3 (Generate)</option>
                                    </optgroup>
                                    <optgroup label="Google Models">
                                        <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash (Experimental)</option>
                                        <option value="gemini-1.5-pro">Gemini 1.5 Pro (Analysis)</option>
                                        <option value="gemini-1.5-flash">Gemini 1.5 Flash (Analysis)</option>
                                    </optgroup>
                                    <optgroup label="Vertex AI" id="vertex-models" style="display:none;">
                                        <option value="vertex-gemini-2.0">Vertex AI Gemini 2.0</option>
                                    </optgroup>
                                </select>
                                <button class="close-lightbox" title="Close">&times;</button>
                            </div>
                        </div>
                        <div class="editor-container">
                            <div class="image-panel">
                                <div class="image-tools">
                                    <button class="tool-btn smart-rotate-btn" data-action="smart-rotate" title="Smart Rotate">
                                        🔄
                                    </button>
                                    <button class="tool-btn undo-btn" data-action="undo" title="Undo" disabled>
                                        ◀️
                                    </button>
                                    <button class="tool-btn redo-btn" data-action="redo" title="Redo" disabled>
                                        ▶️
                                    </button>
                                    <div class="tool-separator"></div>
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
                                                <option value="tomer-template">TOMER'S TEMPLATE</option>
                                            </optgroup>
                                            <optgroup label="Smart Features">
                                                <option value="smart-rotate-flat">Smart Rotate (Flat Front)</option>
                                                <option value="mimic-style">Mimic Reference Style</option>
                                            </optgroup>
                                        </select>
                                        <button class="button button-small manage-templates">⚙️</button>
                                    </div>
                                    <div class="quick-actions">
                                        <button class="quick-action-btn" data-prompt="EXTRACT the existing product exactly as it is (do not modify the product) and place it on a modern lifestyle background suitable for e-commerce">🏠 Lifestyle BG</button>
                                        <button class="quick-action-btn" data-prompt="EXTRACT the existing product without any changes and place it on a pure white background with subtle shadow">⬜ White BG</button>
                                        <button class="quick-action-btn" data-prompt="Keep the existing product image exactly as shown and add our company logo in the bottom right corner, make it subtle but visible">🏷️ Add Logo</button>
                                        <button class="quick-action-btn mimic-style-btn" data-action="open-mimic">🎯 Mimic Style</button>
                                    </div>
                                </div>

                                <!-- Mimic Style Panel (initially hidden) -->
                                <div class="mimic-panel" id="mimic-style-panel" style="display: none;">
                                    <div class="mimic-header">
                                        <h3>🎯 Mimic Reference Style</h3>
                                        <button class="close-mimic-panel">&times;</button>
                                    </div>
                                    <div class="mimic-content">
                                        <div class="mimic-categories">
                                            <select id="mimic-category-filter">
                                                <option value="all">All Categories</option>
                                                <option value="ecommerce">E-commerce</option>
                                                <option value="lifestyle">Lifestyle</option>
                                                <option value="hero">Hero Shots</option>
                                                <option value="custom">Custom</option>
                                            </select>
                                            <button class="button button-small upload-reference-btn">📁 Upload Reference</button>
                                        </div>
                                        <div class="reference-images-grid" id="reference-images-grid">
                                            <div class="loading-references">
                                                <div class="spinner"></div>
                                                <p>Loading reference images...</p>
                                            </div>
                                        </div>
                                        <div class="mimic-custom-prompt">
                                            <textarea id="mimic-custom-prompt" placeholder="Optional: Add specific instructions for style matching..." rows="2"></textarea>
                                        </div>
                                        <div class="mimic-actions">
                                            <button class="button button-primary mimic-generate-btn" disabled>
                                                🎯 Generate with Selected Style
                                            </button>
                                        </div>
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
                                            <li><strong>🔄 Smart Rotate</strong> - Auto-align products to be flat and front-facing</li>
                                            <li><strong>🎯 Mimic reference styles</strong> - Match the exact style of professional product photos</li>
                                            <li><strong>🛍️ TOMER'S TEMPLATE</strong> - Amazon-ready product images with perfect styling</li>
                                        </ul>
                                        <p>Using <strong>${this.aM.gemini[this.cM]?.name || 'Gemini 2.0 Flash'}</strong> for advanced image generation!</p>
                                        <p>Just describe what you want, use quick actions, or try the smart features!</p>
                                    </div>
                                </div>
                                <div class="chat-input-container">
                                    <div class="input-wrapper">
                                        <textarea id="ai-chat-input" placeholder="Describe your edit... e.g., 'Extract the product and place it on a marble countertop with soft studio lighting and add our logo'" rows="3"></textarea>
                                        <div class="input-actions">
                                            <span class="char-count">0/1000</span>
                                            <button class="clear-input" title="Clear">✕</button>
                                        </div>
                                    </div>
                                    <div class="ai-action-buttons">
                                        <button id="ai-generate-btn" class="button button-primary ai-request-btn" data-service="generate">🎨 Generate New Image</button>
                                        <button id="ai-batch-process" class="button ai-request-btn" data-service="batch_process">📦 Batch Process</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="lightbox-footer">
                            <div class="footer-actions">
                                <button class="button save-to-media">💾 Save to Media</button>
                                <button class="button save-as-variant" style="display:none;">📷 Save as Variant Image</button>
                                <button class="button apply-as-main">📷 Set as Main</button>
                                <button class="button apply-to-gallery">🖼️ Add to Gallery</button>
                                <button class="button export-variations">📤 Export All</button>
                            </div>
                            <div class="footer-status">
                                <span id="editor-status">Ready</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            $("body").append(lightboxHTML);
            this.addStyles();
            
            // Check for Vertex AI availability
            if (sspu_ajax.vertex_ai_enabled) {
                console.log('✅ Vertex AI is enabled');
                $('#vertex-models').show();
            } else {
                console.log('❌ Vertex AI is not enabled');
            }
        },

        /**
         * Creates and injects the CSS styles for the lightbox.
         */
        addStyles: function() {
            if ($("#ai-editor-styles").length === 0) {
                console.log('🎨 Injecting CSS styles...');
                const styles = `
                    <style id="ai-editor-styles">
                        body.ai-editor-active { overflow: hidden; }
                        .sspu-lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 100000; display: none; }
                        .lightbox-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); }
                        .lightbox-content { position: relative; width: 95%; max-width: 1400px; height: 90vh; margin: 5vh auto; background: #fff; border-radius: 12px; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
                        .lightbox-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; border-radius: 12px 12px 0 0; }
                        .lightbox-header h2 { margin: 0; font-size: 24px; color: #333; }
                        .header-controls { display: flex; align-items: center; gap: 15px; }
                        .model-selector { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
                        .close-lightbox { background: transparent; border: none; font-size: 32px; cursor: pointer; color: #666; padding: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s; }
                        .close-lightbox:hover { background: #f0f0f0; color: #333; }
                        .editor-container { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 0; overflow: hidden; }
                        .image-panel { background: #1a1a1a; display: flex; flex-direction: column; position: relative; }
                        .image-tools { position: absolute; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 10; align-items: center; }
                        .tool-btn { background: rgba(255,255,255,0.9); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
                        .tool-btn:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
                        .tool-separator {
                            width: 1px;
                            height: 30px;
                            background: rgba(255,255,255,0.3);
                            margin: 0 10px;
                        }
                        .smart-rotate-btn {
                            background: rgba(139, 92, 246, 0.9) !important;
                            font-size: 20px;
                        }
                        .smart-rotate-btn:hover {
                            background: rgb(139, 92, 246) !important;
                            transform: translateY(-2px) rotate(180deg);
                        }
                        .undo-btn, .redo-btn {
                            font-size: 16px;
                        }
                        .tool-btn:disabled, .tool-btn.disabled {
                            opacity: 0.5;
                            cursor: not-allowed;
                        }
                        .tool-btn:disabled:hover, .tool-btn.disabled:hover {
                            transform: none;
                            background: rgba(255,255,255,0.9);
                        }
                        .tool-btn .spinner {
                            display: inline-block;
                            width: 16px;
                            height: 16px;
                            border: 2px solid #333;
                            border-top-color: transparent;
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                        }
                        .current-image { flex: 1; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
                        .image-wrapper { position: relative; max-width: 100%; max-height: 100%; }
                        #ai-editor-image { max-width: 100%; max-height: calc(90vh - 200px); display: block; transition: transform 0.3s; }
                        .image-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #fff; display: none; }
                        .image-loading.active { display: block; }
                        .spinner { border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 3px solid #fff; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
                        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                        .image-info { position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.7); padding: 10px 15px; border-radius: 6px; color: #fff; font-size: 12px; }
                        .info-item { margin-right: 15px; }
                        .chat-panel { background: #fff !important; display: flex; flex-direction: column; border-left: 1px solid #e0e0e0; min-height: 0; position: relative; z-index: 10; }
                        .templates-section { padding: 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa !important; position: relative; z-index: 2; }
                        .templates-bar { display: flex; gap: 10px; margin-bottom: 15px; }
                        #template-selector { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
                        .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
                        .quick-action-btn { background: #fff; border: 1px solid #ddd; padding: 12px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.2s; text-align: center; }
                        .quick-action-btn:hover { background: #0073aa; color: #fff; border-color: #0073aa; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,115,170,0.3); }
                        
                        /* Mimic Panel Styles */
                        .mimic-panel { 
                            background: #f8f9fa !important; 
                            border: 1px solid #e0e0e0; 
                            border-radius: 8px; 
                            margin: 20px; 
                            overflow: hidden;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        }
                        .mimic-header { 
                            display: flex; 
                            justify-content: space-between; 
                            align-items: center; 
                            padding: 15px 20px; 
                            background: #fff; 
                            border-bottom: 1px solid #e0e0e0; 
                        }
                        .mimic-header h3 { margin: 0; font-size: 16px; color: #333; font-weight: 600; }
                        .close-mimic-panel { 
                            background: transparent; 
                            border: none; 
                            font-size: 20px; 
                            cursor: pointer; 
                            color: #666; 
                            padding: 5px; 
                            border-radius: 50%;
                            transition: all 0.2s;
                        }
                        .close-mimic-panel:hover { background: #f0f0f0; color: #333; }
                        .mimic-content { padding: 20px; }
                        .mimic-categories { 
                            display: flex; 
                            gap: 10px; 
                            margin-bottom: 20px; 
                            align-items: center; 
                        }
                        #mimic-category-filter { 
                            flex: 1; 
                            padding: 8px 12px; 
                            border: 1px solid #ddd; 
                            border-radius: 6px; 
                            font-size: 14px;
                        }
                        .upload-reference-btn { 
                            white-space: nowrap; 
                            padding: 8px 12px !important; 
                            font-size: 14px !important; 
                        }
                        .reference-images-grid { 
                            display: grid; 
                            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
                            gap: 15px; 
                            max-height: 300px; 
                            overflow-y: auto; 
                            margin-bottom: 20px; 
                            padding: 10px;
                            border: 1px solid #e0e0e0;
                            border-radius: 6px;
                            background: #fff;
                        }
                        .reference-image-item { 
                            border: 2px solid #e0e0e0; 
                            border-radius: 8px; 
                            overflow: hidden; 
                            cursor: pointer; 
                            transition: all 0.2s; 
                            background: #fff; 
                        }
                        .reference-image-item:hover { 
                            border-color: #0073aa; 
                            transform: translateY(-2px); 
                            box-shadow: 0 4px 12px rgba(0,115,170,0.2); 
                        }
                        .reference-image-item.selected { 
                            border-color: #0073aa; 
                            box-shadow: 0 0 0 3px rgba(0,115,170,0.2); 
                        }
                        .reference-image-preview { 
                            position: relative; 
                            height: 120px; 
                            overflow: hidden; 
                        }
                        .reference-image-preview img { 
                            width: 100%; 
                            height: 100%; 
                            object-fit: cover; 
                        }
                        .reference-overlay { 
                            position: absolute; 
                            bottom: 0; 
                            left: 0; 
                            right: 0; 
                            background: linear-gradient(transparent, rgba(0,0,0,0.8)); 
                            color: #fff; 
                            padding: 10px; 
                            font-size: 12px; 
                        }
                        .reference-name { 
                            display: block; 
                            font-weight: 600; 
                            margin-bottom: 2px; 
                        }
                        .reference-usage { 
                            font-size: 11px; 
                            opacity: 0.9; 
                        }
                        .reference-info { 
                            padding: 12px; 
                        }
                        .reference-info h4 { 
                            margin: 0 0 8px 0; 
                            font-size: 14px; 
                            color: #333; 
                            font-weight: 600;
                        }
                        .reference-info p { 
                            margin: 0 0 8px 0; 
                            font-size: 12px; 
                            color: #666; 
                            line-height: 1.4; 
                        }
                        .reference-keywords { 
                            font-size: 11px; 
                            color: #999; 
                            font-style: italic; 
                        }
                        .no-references { 
                            text-align: center; 
                            color: #666; 
                            padding: 40px 20px; 
                            font-style: italic; 
                        }
                        .loading-references { 
                            text-align: center; 
                            padding: 40px 20px; 
                            color: #666; 
                        }
                        .mimic-custom-prompt { 
                            margin-bottom: 20px; 
                        }
                        #mimic-custom-prompt { 
                            width: 100%; 
                            padding: 10px; 
                            border: 1px solid #ddd; 
                            border-radius: 6px; 
                            font-size: 14px; 
                            resize: vertical; 
                            font-family: inherit;
                        }
                        .mimic-actions { 
                            text-align: center; 
                        }
                        .mimic-generate-btn { 
                            padding: 12px 24px; 
                            font-size: 14px; 
                            font-weight: 600; 
                            border-radius: 6px;
                        }
                        .mimic-generate-btn:disabled { 
                            opacity: 0.6; 
                            cursor: not-allowed; 
                        }
                        
                        .chat-history { flex: 1; overflow-y: auto; padding: 20px; min-height: 0; background-color: #fff !important; position: relative; z-index: 1; }
                        .chat-history * { background-color: transparent; }
                        .welcome-message { background: #f0f8ff !important; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                        .welcome-message h3 { margin: 0 0 15px 0; color: #0073aa; }
                        .welcome-message ul { margin: 10px 0; padding-left: 20px; }
                        .chat-message { margin-bottom: 20px; animation: fadeIn 0.3s; }
                        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
                        .chat-message.user { text-align: right; }
                        .chat-message.user .message-content { background: #0073aa !important; color: #fff; display: inline-block; padding: 12px 16px; border-radius: 12px 12px 0 12px; max-width: 80%; }
                        .chat-message.ai .message-content { background: #f0f0f0 !important; color: #333; display: inline-block; padding: 12px 16px; border-radius: 12px 12px 12px 0; max-width: 90%; }
                        .chat-message.system .message-content { background: #8b5cf6 !important; color: #fff; display: inline-block; padding: 12px 16px; border-radius: 12px; max-width: 80%; }
                        
                        /* Enhanced message styles */
                        .message-content {
                            line-height: 1.6;
                            word-wrap: break-word;
                            overflow-wrap: break-word;
                        }
                        
                        .message-content strong {
                            font-weight: 600;
                            color: inherit;
                        }
                        
                        .message-content em {
                            font-style: italic;
                            color: inherit;
                        }
                        
                        .message-content code {
                            background: rgba(0,0,0,0.1);
                            padding: 2px 4px;
                            border-radius: 3px;
                            font-family: 'Courier New', monospace;
                            font-size: 0.9em;
                        }
                        
                        .message-content a {
                            color: #0073aa;
                            text-decoration: underline;
                        }
                        
                        .message-content a:hover {
                            color: #005177;
                        }
                        
                        .toggle-message-btn {
                            background: transparent;
                            border: 1px solid #ddd;
                            color: #666;
                            padding: 4px 8px;
                            font-size: 12px;
                            border-radius: 4px;
                            cursor: pointer;
                            margin-top: 8px;
                            transition: all 0.2s;
                        }
                        
                        .toggle-message-btn:hover {
                            background: #f0f0f0;
                            border-color: #999;
                            color: #333;
                        }
                        
                        .chat-message.ai .message-content code {
                            background: rgba(255,255,255,0.2);
                        }
                        
                        .chat-message.user .message-content code {
                            background: rgba(255,255,255,0.3);
                        }
                        
                        /* Better list styling */
                        .message-content ul {
                            margin: 10px 0;
                            padding-left: 20px;
                        }
                        
                        .message-content li {
                            margin: 4px 0;
                        }
                        
                        /* Improved error and success message styling */
                        .chat-message.error .message-content {
                            border-left: 4px solid #d63638;
                            padding-left: 12px;
                            background: #fcf2f3 !important;
                            color: #d63638 !important;
                        }
                        
                        .chat-message.success .message-content {
                            border-left: 4px solid #00a32a;
                            padding-left: 12px;
                            background: #f0f8f0 !important;
                            color: #00a32a !important;
                        }
                        
                        /* Loading state for messages */
                        .message-loading {
                            opacity: 0.7;
                            position: relative;
                        }
                        
                        .message-loading::after {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: linear-gradient(90deg, 
                                transparent, 
                                rgba(255,255,255,0.4), 
                                transparent
                            );
                            animation: shimmer 1.5s infinite;
                        }
                        
                        @keyframes shimmer {
                            0% { transform: translateX(-100%); }
                            100% { transform: translateX(100%); }
                        }
                        
                        .generated-image-container { margin-top: 10px; text-align: center; }
                        .set-current-image-btn { margin-top: 10px; cursor: pointer; }
                        .generated-image { width: 100%; max-width: 300px; margin-top: 10px; border-radius: 8px; cursor: pointer; transition: transform .2s; border: 1px solid #eee; }
                        .generated-image:hover { transform: scale(1.02); }
                        .message-time { font-size: 11px; color: #999; margin-top: 5px; }
                        .chat-input-container { padding: 20px; background: #fff !important; border-top: 1px solid #e0e0e0; position: relative; z-index: 2; }
                        .input-wrapper { position: relative; margin-bottom: 15px; }
                        #ai-chat-input { width: 100%; padding: 12px 45px 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 80px; transition: border-color 0.2s; }
                        #ai-chat-input:focus { outline: 0; border-color: #0073aa; }
                        .input-actions { position: absolute; bottom: 10px; right: 10px; display: flex; align-items: center; gap: 10px; }
                        .char-count { font-size: 12px; color: #999; }
                        .clear-input { background: transparent; border: none; color: #999; cursor: pointer; font-size: 18px; padding: 5px; }
                        .clear-input:hover { color: #333; }
                        .ai-action-buttons { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
                        .ai-request-btn { padding: 12px 20px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid; }
                        .ai-request-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
                        .ai-request-btn:disabled { opacity: 0.6; cursor: not-allowed; }
                        #ai-generate-btn { background: #0073aa; color: #fff; border-color: #0073aa; }
                        #ai-generate-btn:hover:not(:disabled) { background: #005785; }
                        #ai-batch-process { background: #f0f0f0; color: #333; border-color: #ddd; }
                        #ai-batch-process:hover:not(:disabled) { background: #e0e0e0; }
                        .lightbox-footer { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; border-top: 1px solid #e0e0e0; background: #f8f9fa; border-radius: 0 0 12px 12px; }
                        .footer-actions { display: flex; gap: 10px; }
                        .footer-status { color: #666; font-size: 14px; }
                        
                        /* Save as variant button highlight */
                        .save-as-variant {
                            background: #8b5cf6 !important;
                            color: white !important;
                            border: none !important;
                        }
                        .save-as-variant:hover {
                            background: #7c3aed !important;
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
                        }
                        
                        @media (max-width: 1024px) {
                            .editor-container { grid-template-columns: 1fr; }
                            .chat-panel { position: absolute; right: 0; top: 0; height: 100%; width: 400px; transform: translateX(100%); transition: transform 0.3s; }
                            .chat-panel.active { transform: translateX(0); }
                        }
                    </style>
                `;
                $("head").append(styles);
            }
        },

        /**
         * Binds all necessary event listeners for the editor UI.
         */
        bindEvents: function() {
            console.log('🔗 Binding event listeners...');
            const self = this;
            const $body = $("body");

            // Unbind previous events to prevent duplicates
            $body.off(".aiEditor");

            // Close lightbox
            $body.on("click.aiEditor", "#ai-image-editor-lightbox .close-lightbox, #ai-image-editor-lightbox .lightbox-overlay", function(e) {
                if ($(e.target).hasClass("lightbox-overlay") || $(e.target).hasClass("close-lightbox")) {
                    console.log('🚪 Closing lightbox...');
                    self.close();
                }
            });

            // AI request buttons
            $body.on("click.aiEditor", ".ai-request-btn:not(:disabled)", function() {
                const service = $(this).data("service");
                console.log('🎯 AI request button clicked:', service);
                self.sendAIRequest(service);
            });

            // Quick action buttons
            $body.on("click.aiEditor", ".quick-action-btn", function() {
                const action = $(this).data("action");
                if (action === "open-mimic") {
                    console.log('🎯 Opening mimic panel...');
                    self.openMimicPanel();
                } else {
                    const prompt = $(this).data("prompt");
                    console.log('⚡ Quick action:', prompt);
                    $("#ai-chat-input").val(prompt);
                    self.updateCharCount();
                    self.sendAIRequest("generate");
                }
            });

            // Smart Rotate button
            $body.on("click.aiEditor", ".smart-rotate-btn", function(e) {
                e.preventDefault();
                console.log('🔄 Smart rotate clicked');
                self.smartRotate();
            });
            
            // Undo button
            $body.on("click.aiEditor", ".undo-btn:not(:disabled)", function(e) {
                e.preventDefault();
                console.log('↶ Undo clicked');
                self.undo();
            });
            
            // Redo button
            $body.on("click.aiEditor", ".redo-btn:not(:disabled)", function(e) {
                e.preventDefault();
                console.log('↷ Redo clicked');
                self.redo();
            });

            // Save as variant button
            $body.on("click.aiEditor", ".save-as-variant", function() {
                console.log('💾 Save as variant clicked');
                self.saveAsVariantImage();
            });

            // Mimic-related events
            $body.on("click.aiEditor", ".mimic-style-btn", function() {
                console.log('🎯 Mimic style button clicked');
                self.openMimicPanel();
            });
            
            $body.on("click.aiEditor", ".close-mimic-panel", function() {
                console.log('❌ Closing mimic panel');
                self.closeMimicPanel();
            });
            
            $body.on("change.aiEditor", "#mimic-category-filter", function() {
                const category = $(this).val();
                console.log('🏷️ Mimic category changed:', category);
                self.loadMimicImages(category);
            });
            
            $body.on("click.aiEditor", ".reference-image-item", function() {
                const mimicId = $(this).data("mimic-id");
                console.log('🖼️ Reference image selected:', mimicId);
                self.selectReferenceImage(mimicId);
            });
            
            $body.on("click.aiEditor", ".mimic-generate-btn:not(:disabled)", function() {
                console.log('🎯 Generate with mimic style clicked');
                self.generateWithMimicStyle();
            });

            // Message expansion toggle
            $body.on("click.aiEditor", ".toggle-message-btn", function() {
                const $btn = $(this);
                const messageId = $btn.closest('.chat-message').attr('id');
                const currentState = $btn.data('state');
                console.log('📝 Toggle message:', messageId, currentState);
                self.toggleMessageExpansion(messageId, currentState);
            });

            // Chat input interactions
            $body.on("click.aiEditor", "#ai-chat-input", function() { $(this).focus(); });
            $body.on("input.aiEditor", "#ai-chat-input", function() { self.updateCharCount(); });
            $body.on("keydown.aiEditor", "#ai-chat-input", function(e) {
                if (e.ctrlKey && e.key === "Enter") {
                    e.preventDefault();
                    console.log('⌨️ Ctrl+Enter detected, sending request...');
                    self.sendAIRequest("generate");
                }
            });
            $body.on("click.aiEditor", ".clear-input", function() {
                console.log('🧹 Clearing input');
                $("#ai-chat-input").val("").focus();
                self.updateCharCount();
            });

            // Template selector
            $body.on("change.aiEditor", "#template-selector", function() {
                const selectedValue = $(this).val();
                console.log('📋 Template selected:', selectedValue);
                if (selectedValue === 'smart-rotate-flat') {
                    self.smartRotate();
                    $(this).val(""); // Reset selector
                } else if (selectedValue === 'mimic-style') {
                    self.openMimicPanel();
                    $(this).val(""); // Reset selector
                } else if (selectedValue === 'tomer-template') {
                    self.applyTomerTemplate();
                    $(this).val(""); // Reset selector
                } else if (selectedValue) {
                    const prompts = {
                        "lifestyle-bg": "EXTRACT the existing product exactly as it is (do not modify or recreate the product) and place it on a modern lifestyle background with natural lighting",
                        "white-bg": "EXTRACT the existing product exactly as it is (preserve all details) and place it on a pure white background with professional studio lighting and subtle shadow",
                        "transparent-bg": "EXTRACT the existing product exactly as it is and remove the background completely, leaving only the product with clean edges",
                        "add-logo": "Keep the existing product image unchanged and add our company logo to the bottom right corner, make it subtle but visible",
                        "enhance-lighting": "PRESERVE the existing product exactly as shown but enhance the lighting to make it more appealing for e-commerce",
                        "color-correction": "PRESERVE the existing product shape and details but correct the colors to be more vibrant and true-to-life",
                        "amazon-ready": "EXTRACT the existing product without any modifications and optimize for Amazon listing: white background, centered product, proper margins",
                        "social-media": "EXTRACT the existing product exactly as it is and create a social media optimized version with eye-catching composition",
                        "hero-image": "EXTRACT the existing product without changes and create a hero image style with dramatic lighting and professional composition"
                    };
                    if (prompts[selectedValue]) {
                        $("#ai-chat-input").val(prompts[selectedValue]);
                        self.updateCharCount();
                    }
                    $(this).val(""); // Reset selector
                }
            });

            // AI Model selector
            $body.on("change.aiEditor", "#ai-model-selector", function() {
                const newModel = $(this).val();
                console.log('🤖 Model changed from', self.cM, 'to', newModel);
                self.cM = newModel;
                self.updateStatus(`Switched to ${$(this).find(":selected").text()}`);
            });

            // Image manipulation tools
            $body.on("click.aiEditor", ".tool-btn", function() {
                const action = $(this).data("action");
                console.log('🔧 Tool action:', action);
                self.handleImageTool(action);
            });

            // Footer actions
            $body.on("click.aiEditor", "#ai-image-editor-lightbox .save-to-media", function() { 
                console.log('💾 Save to media clicked');
                self.saveToMedia(); 
            });
            $body.on("click.aiEditor", "#ai-image-editor-lightbox .apply-as-main", function() { 
                console.log('📷 Apply as main clicked');
                self.applyAsMainImage(); 
            });
            $body.on("click.aiEditor", "#ai-image-editor-lightbox .apply-to-gallery", function() { 
                console.log('🖼️ Apply to gallery clicked');
                self.applyToGallery(); 
            });
            $body.on("click.aiEditor", "#ai-image-editor-lightbox .export-variations", function() { 
                console.log('📤 Export variations clicked');
                self.exportVariations(); 
            });
            $body.on("click.aiEditor", ".manage-templates", function() { 
                console.log('⚙️ Manage templates clicked');
                window.open(sspu_ajax.admin_url + "admin.php?page=sspu-image-templates", "_blank"); 
            });

            // Image interactions
            $body.on("click.aiEditor", ".generated-image", function() {
                const src = $(this).attr("src");
                console.log('🖼️ Generated image clicked, opening in new tab');
                window.open(src, "_blank");
            });
            $body.on("click.aiEditor", ".set-current-image-btn", function() {
                const url = $(this).data("url");
                console.log('🎯 Set current image clicked:', url);
                if (url) {
                    $("#ai-editor-image").attr("src", url);
                    self.cU = url;
                    // Add to history when setting as current
                    self.addImageToHistory(url);
                    self.updateStatus("Image loaded into editor.");
                }
            });
        },

        /**
         * Apply TOMER'S TEMPLATE - Amazon-ready product processing
         */
        applyTomerTemplate: function() {
            console.log('🛍️ Applying TOMER\'S TEMPLATE');
            const self = this;
            
            // Build the TOMER'S TEMPLATE prompt
            const prompt = `You will be provided with a product image.

Follow these steps to process the image:

1. **Background Removal:**
   * Automatically remove the background from the image, leaving only the product.
   * Ensure the background is completely transparent or replaced with a standard white (#FFFFFF) background, as required by Amazon.

2. **Image Standardization:**
   * Resize the image to meet Amazon's recommended dimensions (e.g., 1000 x 1000 pixels for square images).
   * Maintain the aspect ratio of the product to prevent distortion.

3. **Positioning Adjustments:**
   * Position the product in the center of the image frame.
   * Ensure the product occupies a significant portion of the image, typically 85-90% of the frame.

4. **Shadow and Angle Adjustments:**
   * Adjust the product's angle to be visually appealing and informative (e.g., a slight angle to showcase features).
   * Add a subtle, soft shadow beneath the product to give it depth and make it appear more three-dimensional.
   * Ensure the shadow is realistic and not too harsh or artificial.

5. **Amazon Style Guide Adherence:**
   * Adjust colors, lighting, and other elements to match Amazon's recommendations.

6. **Output:**
   * Create the image in high quality with proper lighting.
   * Ensure the final image meets Amazon's technical requirements for product listings.

**Constraints:**
* Adhere strictly to Amazon's style guidelines for product images.
* Maintain the quality and consistency of the processed images.
* Ensure the final image is visually appealing and accurately represents the product.`;

            $("#ai-chat-input").val(prompt);
            self.updateCharCount();
            self.sendAIRequest("generate");
            
            self.addChatMessage("system", "🛍️ Applying TOMER'S TEMPLATE for Amazon-ready product image processing...");
        },

        /**
         * Save the current image as a variant image
         */
        saveAsVariantImage: function() {
            console.group('💾 Save as Variant Image');
            const self = this;
            
            console.log('🔍 Checking conditions...');
            console.log('- Opened from variants:', this.openedFromVariants);
            console.log('- Variant row element:', this.variantRowElement);
            console.log('- Original variant image ID:', this.originalVariantImageId);
            
            if (!this.openedFromVariants || !this.variantRowElement) {
                console.error('❌ Not opened from variants or missing variant row element');
                this.addChatMessage("error", "This option is only available when opened from a variant.");
                console.groupEnd();
                return;
            }
            
            const currentImageSrc = $("#ai-editor-image").attr("src");
            console.log('🖼️ Current image source:', currentImageSrc ? currentImageSrc.substring(0, 50) + '...' : 'None');
            
            if (!currentImageSrc || !currentImageSrc.startsWith("data:")) {
                console.error('❌ No generated image to save');
                this.addChatMessage("error", "Please generate or edit an image first.");
                console.groupEnd();
                return;
            }
            
            this.updateStatus("Saving as variant image...");
            console.log('📤 Sending AJAX request to save image...');
            
            // Save to media library first
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: "POST",
                data: {
                    action: "sspu_save_edited_image",
                    nonce: sspu_ajax.nonce,
                    image_data: currentImageSrc,
                    filename: "variant-image-" + Date.now()
                },
                success: function(response) {
                    console.group('✅ AJAX Success Response');
                    console.log('Response:', response);
                    
                    if (response.success) {
                        const newImageId = response.data.attachment_id;
                        const newImageUrl = response.data.url;
                        
                        console.log('🆕 New Image ID:', newImageId);
                        console.log('🔗 New Image URL:', newImageUrl);
                        
                        // Update the variant row with the new image
                        const $row = $(self.variantRowElement);
                        console.log('📊 Variant row:', $row);
                        
                        const $preview = $row.find('.sspu-variant-image-preview');
                        const $idField = $row.find('.sspu-variant-image-id');
                        
                        console.log('🔍 Found preview element:', $preview.length > 0);
                        console.log('🔍 Found ID field element:', $idField.length > 0);
                        
                        // Log the old value
                        const oldImageId = $idField.val();
                        console.log('🔄 Replacing image ID:', oldImageId, '→', newImageId);
                        
                        // Clear existing preview and add new image
                        $preview.empty().append(
                            $('<img>', {
                                src: newImageUrl,
                                alt: 'Variant image',
                                'data-id': newImageId
                            })
                        );
                        console.log('✅ Preview updated with new image');
                        
                        // Update hidden field
                        $idField.val(newImageId);
                        console.log('✅ Hidden field updated with new ID:', newImageId);
                        
                        // Show AI Edit button
                        const $aiEditBtn = $row.find('.sspu-ai-edit-variant-image');
                        $aiEditBtn.show();
                        console.log('✅ AI Edit button shown');
                        
                        // Verify the update
                        console.group('🔍 Verification');
                        console.log('- New ID in field:', $idField.val());
                        console.log('- Image src in preview:', $preview.find('img').attr('src'));
                        console.log('- Data-id attribute:', $preview.find('img').data('id'));
                        console.groupEnd();
                        
                        self.addChatMessage("success", `✅ Image saved as variant image successfully!\n\nNew Image ID: ${newImageId}\nReplaced Image ID: ${oldImageId || 'None'}`);
                        self.updateStatus("Saved as variant image");
                        
                        // Close the editor after a short delay
                        setTimeout(() => {
                            console.log('🚪 Closing editor...');
                            self.close();
                        }, 2000);
                    } else {
                        console.error('❌ Save failed:', response.data.message);
                        self.addChatMessage("error", "Failed to save variant image: " + response.data.message);
                        self.updateStatus("Save failed");
                    }
                    console.groupEnd();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.group('❌ AJAX Error');
                    console.error('Status:', textStatus);
                    console.error('Error:', errorThrown);
                    console.error('Response:', jqXHR.responseText);
                    console.groupEnd();
                    
                    self.addChatMessage("error", "Failed to save variant image.");
                    self.updateStatus("Save error");
                }
            });
            
            console.groupEnd();
        },

        // --- HISTORY MANAGEMENT --- //
        
        /**
         * Add image to history
         */
        addImageToHistory: function(imageUrl) {
            console.group('📚 Add to History');
            console.log('🖼️ Adding image:', imageUrl ? imageUrl.substring(0, 50) + '...' : 'None');
            
            // If we're not at the end of history, remove future items
            if (this.historyIndex < this.imageHistory.length - 1) {
                const removedCount = this.imageHistory.length - this.historyIndex - 1;
                console.log(`🗑️ Removing ${removedCount} future history items`);
                this.imageHistory = this.imageHistory.slice(0, this.historyIndex + 1);
            }
            
            // Add new image to history
            this.imageHistory.push({
                url: imageUrl,
                timestamp: new Date(),
                isOriginal: this.imageHistory.length === 0
            });
            
            // Update index to point to new image
            this.historyIndex = this.imageHistory.length - 1;
            
            console.log('📊 History status:');
            console.log('- Total items:', this.imageHistory.length);
            console.log('- Current index:', this.historyIndex);
            console.log('- Is original:', this.imageHistory[this.historyIndex].isOriginal);
            
            // Update UI buttons
            this.updateUndoRedoButtons();
            console.groupEnd();
        },
        
        /**
         * Update undo/redo button states
         */
        updateUndoRedoButtons: function() {
            const $undoBtn = $(".undo-btn");
            const $redoBtn = $(".redo-btn");
            
            // Enable/disable undo button
            if (this.historyIndex > 0) {
                $undoBtn.prop("disabled", false).removeClass("disabled");
                console.log('✅ Undo enabled');
            } else {
                $undoBtn.prop("disabled", true).addClass("disabled");
                console.log('❌ Undo disabled');
            }
            
            // Enable/disable redo button
            if (this.historyIndex < this.imageHistory.length - 1) {
                $redoBtn.prop("disabled", false).removeClass("disabled");
                console.log('✅ Redo enabled');
            } else {
                $redoBtn.prop("disabled", true).addClass("disabled");
                console.log('❌ Redo disabled');
            }
            
            // Update status
            this.updateStatus(`History: ${this.historyIndex + 1} of ${this.imageHistory.length}`);
        },
        
        /**
         * Undo to previous image
         */
        undo: function() {
            console.group('↶ Undo');
            if (this.historyIndex > 0) {
                this.historyIndex--;
                const historyItem = this.imageHistory[this.historyIndex];
                console.log('🔄 Reverting to index:', this.historyIndex);
                console.log('📅 Image timestamp:', historyItem.timestamp);
                
                $("#ai-editor-image").attr("src", historyItem.url);
                this.cU = historyItem.url;
                this.updateUndoRedoButtons();
                this.addChatMessage("system", "↶ Reverted to previous image");
            } else {
                console.log('❌ Cannot undo - already at beginning');
            }
            console.groupEnd();
        },
        
        /**
         * Redo to next image
         */
        redo: function() {
            console.group('↷ Redo');
            if (this.historyIndex < this.imageHistory.length - 1) {
                this.historyIndex++;
                const historyItem = this.imageHistory[this.historyIndex];
                console.log('🔄 Advancing to index:', this.historyIndex);
                console.log('📅 Image timestamp:', historyItem.timestamp);
                
                $("#ai-editor-image").attr("src", historyItem.url);
                this.cU = historyItem.url;
                this.updateUndoRedoButtons();
                this.addChatMessage("system", "↷ Advanced to next image");
            } else {
                console.log('❌ Cannot redo - already at end');
            }
            console.groupEnd();
        },
        
        // --- SMART ROTATE --- //
        
        /**
         * Smart Rotate functionality
         */
        smartRotate: function() {
    console.group('🔄 Smart Rotate');
    const self = this;
    
    // Check if we have a model that supports generation
    const selectedModel = $("#ai-model-selector").val();
    const modelConfig = this.getModelConfig(selectedModel);
    console.log('🤖 Current model:', selectedModel);
    console.log('📋 Model config:', modelConfig);
    
    if (!modelConfig || !modelConfig.supports_generation) {
        console.log('⚠️ Current model doesn\'t support generation, switching to Gemini 2.0 Flash');
        // Need to switch to a generation model first
        $("#ai-model-selector").val('gemini-2.0-flash-exp');
        this.cM = 'gemini-2.0-flash-exp';
    }
    
    // Show loading state
    const $button = $(".smart-rotate-btn");
    const originalHTML = $button.html();
    $button.prop("disabled", true).html('<span class="spinner"></span>');
    
    this.updateStatus("Analyzing and reorienting product...");
    
    // Don't add the user message here - it will be added by the AJAX handler
    // this.addChatMessage("user", "🔄 Smart Rotate: Make product flat and front-facing");
    
    // Build the smart rotate prompt
    const smartRotatePrompt = this.buildSmartRotatePrompt();
    console.log('📝 Smart rotate prompt:', smartRotatePrompt);
    
    // Get current image as base64
    this.getImageAsBase64(function(base64Image) {
        console.log('🖼️ Got base64 image:', base64Image ? base64Image.substring(0, 50) + '...' : 'None');
        
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: "POST",
            data: {
                action: "sspu_ai_edit_image",
                nonce: sspu_ajax.nonce,
                image_data: base64Image,
                prompt: smartRotatePrompt,
                model: self.cM,
                session_id: self.sI
            },
            success: function(response) {
                console.group('✅ Smart Rotate Success');
                console.log('Response:', response);
                
                if (response.success && response.data.edited_image) {
                    // Update the displayed image
                    $("#ai-editor-image").attr("src", response.data.edited_image);
                    self.cU = response.data.edited_image;
                    
                    // Add to history
                    self.addImageToHistory(response.data.edited_image);
                    
                    // Add chat messages
                    self.addChatMessage("user", "🔄 Smart Rotate: Make product flat and front-facing");
                    self.addChatMessage("ai", 
                        "✅ Smart Rotate Complete!\n\n" + 
                        "The product has been reoriented to a flat, front-facing view with:\n" +
                        "• Perfect horizontal and vertical alignment\n" +
                        "• Professional studio lighting\n" +
                        "• Clean white background\n" +
                        "• Proper e-commerce presentation\n\n" +
                        (response.data.response || "")
                    );
                    
                    self.updateStatus("Smart Rotate successful!");
                } else {
                    console.error('❌ Smart rotate failed:', response.data?.message);
                    self.addChatMessage("user", "🔄 Smart Rotate: Make product flat and front-facing");
                    self.addChatMessage("error", "Smart Rotate failed: " + (response.data?.message || "Unknown error"));
                    self.updateStatus("Smart Rotate failed");
                }
                console.groupEnd();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('❌ AJAX error:', textStatus, errorThrown);
                self.addChatMessage("user", "🔄 Smart Rotate: Make product flat and front-facing");
                self.addChatMessage("error", "Network error: " + textStatus);
                self.updateStatus("Network error");
            },
            complete: function() {
                $button.prop("disabled", false).html(originalHTML);
                console.log('🔄 Smart rotate completed');
            }
        });
    });
    console.groupEnd();
},
        
        /**
         * Build the smart rotate prompt
         */
        buildSmartRotatePrompt: function() {
            return "Extract the main product from this photo. Give it a white background so it looks amazon ready with 10% margins on the sides. " +
                   "Add a bit of a shadow on the product. Make the product positioned flat and not at an angle. " +
                   "The product must be perfectly flat and facing directly forward (0° angle). " +
                   "Center the product with perfect horizontal and vertical alignment. " +
                   "Remove any angle or tilt - the product should appear as if photographed straight-on. " +
                   "Auto-correct any rotation so the product is perfectly upright. " +
                   "The result should look like a professional e-commerce product photo taken perpendicular to the product's front face.";
        },

        // --- MIMIC FUNCTIONALITY --- //

        /**
         * Load mimic reference images
         */
        loadMimicImages: function(category = 'all') {
            console.group('🎯 Load Mimic Images');
            console.log('📁 Category:', category);
            const self = this;
            
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: "POST",
                data: {
                    action: "sspu_get_mimic_images",
                    nonce: sspu_ajax.nonce,
                    category: category
                },
                success: function(response) {
                    console.log('✅ Mimic images loaded:', response);
                    if (response.success) {
                        self.mimicImages = response.data.mimic_images;
                        console.log('📊 Total mimic images:', self.mimicImages.length);
                        self.renderMimicImages();
                    } else {
                        console.error('❌ Failed to load mimic images');
                        $("#reference-images-grid").html('<p class="no-references">Failed to load reference images.</p>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('❌ AJAX error:', textStatus, errorThrown);
                    $("#reference-images-grid").html('<p class="no-references">Error loading reference images.</p>');
                }
            });
            console.groupEnd();
        },

        /**
         * Render mimic reference images
         */
        renderMimicImages: function() {
            console.log('🎨 Rendering mimic images...');
            const $grid = $("#reference-images-grid");
            
            if (this.mimicImages.length === 0) {
                console.log('❌ No mimic images to render');
                $grid.html('<p class="no-references">No reference images found. Upload some reference images to get started!</p>');
                return;
            }
            
            let html = '';
            this.mimicImages.forEach(function(image) {
                const isSelected = this.selectedReferenceImage && this.selectedReferenceImage.mimic_id === image.mimic_id;
                console.log(`🖼️ Rendering mimic image ${image.mimic_id}:`, image.name, isSelected ? '(selected)' : '');
                html += `
                    <div class="reference-image-item ${isSelected ? 'selected' : ''}" data-mimic-id="${image.mimic_id}">
                        <div class="reference-image-preview">
                            <img src="${image.thumbnail_url || image.image_url}" alt="${image.name}" />
                            <div class="reference-overlay">
                                <span class="reference-name">${image.name}</span>
                                <span class="reference-usage">Used ${image.usage_count} times</span>
                            </div>
                        </div>
                        <div class="reference-info">
                            <h4>${image.name}</h4>
                            <p>${image.description || 'No description'}</p>
                            <div class="reference-keywords">${image.style_keywords || ''}</div>
                        </div>
                    </div>`;
            }, this);
            
            $grid.html(html);
            console.log('✅ Rendered', this.mimicImages.length, 'mimic images');
        },

        /**
         * Select reference image
         */
        selectReferenceImage: function(mimicId) {
            console.group('🎯 Select Reference Image');
            console.log('🆔 Mimic ID:', mimicId);
            
            this.selectedReferenceImage = this.mimicImages.find(img => img.mimic_id == mimicId);
            console.log('📷 Selected image:', this.selectedReferenceImage);
            
            // Update UI
            $(".reference-image-item").removeClass("selected");
            $(`.reference-image-item[data-mimic-id="${mimicId}"]`).addClass("selected");
            
            // Enable generate button
            $(".mimic-generate-btn").prop("disabled", false);
            
            this.updateStatus(`Selected reference: ${this.selectedReferenceImage.name}`);
            console.groupEnd();
        },

        /**
         * Generate with mimic style
         */
        generateWithMimicStyle: function() {
            console.group('🎯 Generate with Mimic Style');
            const self = this;
            
            if (!this.selectedReferenceImage) {
                console.error('❌ No reference image selected');
                this.addChatMessage("error", "Please select a reference image first.");
                console.groupEnd();
                return;
            }
            
            const customPrompt = $("#mimic-custom-prompt").val().trim();
            console.log('📝 Custom prompt:', customPrompt);
            console.log('🖼️ Reference image:', this.selectedReferenceImage);
            
            // Disable button and show loading
            const $button = $(".mimic-generate-btn");
            $button.prop("disabled", true).html('<span class="spinner"></span> Mimicking Style...');
            
            this.updateStatus("Generating image with reference style...");
            this.addChatMessage("user", `Mimic style from: ${this.selectedReferenceImage.name}`);
            
            this.getImageAsBase64(function(base64Image) {
                console.log('🖼️ Got base64 source image');
                
                $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: "POST",
                    data: {
                        action: "sspu_mimic_image",
                        nonce: sspu_ajax.nonce,
                        source_image_data: base64Image,
                        reference_image_id: self.selectedReferenceImage.image_id,
                        custom_prompt: customPrompt,
                        session_id: self.sI
                    },
                    success: function(response) {
                        console.group('✅ Mimic Style Success');
                        console.log('Response:', response);
                        
                        if (response.success) {
                            // Update current image
                            if (response.data.edited_image) {
                                $("#ai-editor-image").attr("src", response.data.edited_image);
                                self.cU = response.data.edited_image;
                                self.addImageToHistory(response.data.edited_image);
                                console.log('✅ Updated current image');
                            }
                            
                            self.addChatMessage("ai", response.data.response, response.data.edited_image);
                            self.updateStatus("Style mimic complete!");
                            self.closeMimicPanel();
                        } else {
                            console.error('❌ Mimic failed:', response.data.message);
                            self.addChatMessage("error", "Failed to mimic style: " + response.data.message);
                            self.updateStatus("Mimic failed");
                        }
                        console.groupEnd();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('❌ AJAX error:', textStatus, errorThrown);
                        self.addChatMessage("error", "Network error: " + textStatus);
                        self.updateStatus("Network error");
                    },
                    complete: function() {
                        $button.prop("disabled", false).html('🎯 Generate with Selected Style');
                        console.log('🔄 Mimic style generation completed');
                    }
                });
            });
            console.groupEnd();
        },

        /**
         * Open mimic panel
         */
        openMimicPanel: function() {
            console.log('🎯 Opening mimic panel');
            $("#mimic-style-panel").slideDown(300);
            this.loadMimicImages();
        },

        /**
         * Close mimic panel
         */
        closeMimicPanel: function() {
            console.log('❌ Closing mimic panel');
            $("#mimic-style-panel").slideUp(300);
            this.selectedReferenceImage = null;
            $(".reference-image-item").removeClass("selected");
            $(".mimic-generate-btn").prop("disabled", true);
            $("#mimic-custom-prompt").val("");
        },

        // --- UI & HELPER METHODS --- //

        /**
         * Get model configuration
         */
        getModelConfig: function(modelId) {
            for (const provider in this.aM) {
                if (this.aM[provider][modelId]) {
                    return this.aM[provider][modelId];
                }
            }
            return null;
        },

        initializeModelSelector: function() {
            console.log('🤖 Initializing model selector with:', this.cM);
            $("#ai-model-selector").val(this.cM);
        },

        updateCharCount: function() {
            const input = $("#ai-chat-input").val();
            const len = input.length;
            $(".char-count").text(`${len}/1000`);
            $(".char-count").css("color", len > 1000 ? "#c00" : "#999");
        },

        updateStatus: function(statusText) {
            console.log('📊 Status:', statusText);
            $("#editor-status").text(statusText);
        },

        handleImageTool: function(action) {
            console.log('🔧 Handling image tool:', action);
            const $image = $("#ai-editor-image");
            let currentScale, newScale;
            switch (action) {
                case "smart-rotate":
                    this.smartRotate();
                    break;
                case "undo":
                    this.undo();
                    break;
                case "redo":
                    this.redo();
                    break;
                case "zoom-in":
                    currentScale = parseFloat($image.data("scale") || 1);
                    newScale = Math.min(currentScale + 0.25, 3);
                    $image.css("transform", `scale(${newScale})`).data("scale", newScale);
                    console.log('🔍 Zoom in:', newScale);
                    break;
                case "zoom-out":
                    currentScale = parseFloat($image.data("scale") || 1);
                    newScale = Math.max(currentScale - 0.25, 0.5);
                    $image.css("transform", `scale(${newScale})`).data("scale", newScale);
                    console.log('🔍 Zoom out:', newScale);
                    break;
                case "reset-zoom":
                    $image.css("transform", "scale(1)").data("scale", 1);
                    console.log('🔍 Reset zoom');
                    break;
                case "download":
                    console.log('⬇️ Downloading image');
                    const link = document.createElement("a");
                    link.href = $image.attr("src");
                    link.download = "ai-edited-image.png";
                    link.click();
                    break;
            }
        },

        loadImage: function() {
            console.group('🖼️ Load Image');
            const self = this;
            const $image = $("#ai-editor-image");
            const $loader = $("#ai-image-editor-lightbox .image-loading");

            console.log('🔗 Loading image URL:', this.cU);
            $loader.addClass("active");
            $image.attr("src", "").hide();

            $image.off("load").on("load", function() {
                console.log('✅ Image loaded successfully');
                $loader.removeClass("active");
                $image.fadeIn(300);
                const imgElement = this;
                const dimensions = `${imgElement.naturalWidth} × ${imgElement.naturalHeight}px`;
                $("#image-dimensions").text(dimensions);
                console.log('📐 Dimensions:', dimensions);
                
                fetch(imgElement.src)
                    .then(res => res.blob())
                    .then(blob => {
                        const sizeKB = (blob.size / 1024).toFixed(1);
                        $("#image-size").text(`${sizeKB} KB`);
                        console.log('📦 Size:', sizeKB, 'KB');
                    });
                
                // Add the original image to history
                self.addImageToHistory(self.cU);
                console.groupEnd();
            });

            $image.off("error").on("error", function() {
                console.error('❌ Failed to load image');
                $loader.removeClass("active");
                self.addChatMessage("error", "Could not load the source image. Please try again.");
                console.groupEnd();
            });

            $image.attr("src", this.cU);
        },

        addChatMessage: function(type, message, imageUrl) {
            console.group('💬 Add Chat Message');
            console.log('📝 Type:', type);
            console.log('📄 Message:', message);
            console.log('🖼️ Image URL:', imageUrl ? 'Yes' : 'No');
            
            const $history = $("#ai-chat-history");
            $(".welcome-message").fadeOut(300, function() { $(this).remove(); });

            // Generate unique ID for this message
            const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            console.log('🆔 Message ID:', messageId);

            const imageHTML = imageUrl ? `
                <div class="generated-image-container">
                    <img src="${imageUrl}" class="generated-image" alt="Generated Image" /><br>
                    <button class="button button-small set-current-image-btn" data-url="${imageUrl}">Set as Current Image</button>
                </div>` : "";

            const messageHTML = `
                <div class="chat-message ${type}" id="${messageId}">
                    <div class="message-content">
                        ${this.formatMessage(message)}
                        ${imageHTML}
                    </div>
                    <div class="message-time">${(new Date()).toLocaleTimeString()}</div>
                </div>`;

            $history.append(messageHTML);
            
            // Force scroll to bottom with animation
            $history.animate({ 
                scrollTop: $history[0].scrollHeight 
            }, 300);

            // Store in chat history
            this.cH.push({
                id: messageId,
                type: type,
                message: message,
                imageUrl: imageUrl,
                timestamp: new Date()
            });
            console.log('📊 Chat history length:', this.cH.length);

            // Add special handling for long messages
            const $newMessage = $(`#${messageId}`);
            const $messageContent = $newMessage.find('.message-content');
            
            // If message is very long, add a "show more" feature
            if (message.length > 500) {
                console.log('📏 Long message detected, adding show more feature');
                const shortMessage = message.substring(0, 500) + '...';
                const fullMessage = message;
                
                $messageContent.html(`
                    <div class="message-short">${this.formatMessage(shortMessage)}</div>
                    <div class="message-full" style="display: none;">${this.formatMessage(fullMessage)}</div>
                    <button class="toggle-message-btn" data-state="short">Show More</button>
                    ${imageHTML}
                `);
            }
            
            // Add fade-in animation for new messages
            $newMessage.hide().fadeIn(400);
            console.groupEnd();
        },

        formatMessage: function(message) {
            // First, escape any existing HTML to prevent XSS
            let escapedMessage = $('<div>').text(message).html();
            
            // Then apply our custom formatting
            return escapedMessage
                // Convert markdown bold **text** to HTML
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Convert markdown italic *text* to HTML
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                // Convert backticks `code` to HTML
                .replace(/`(.*?)`/g, '<code>$1</code>')
                // Convert newlines to <br> tags
                .replace(/\n/g, '<br>')
                // Convert bullet points • to HTML bullets
                .replace(/• /g, '&bull; ')
                // Convert numbered lists (1. 2. 3. etc.)
                .replace(/^(\d+)\.\s/gm, '<strong>$1.</strong> ')
                // Convert URLs to clickable links
                .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
                // Convert emoji shortcuts to actual emojis (optional)
                .replace(/:check:/g, '✅')
                .replace(/:cross:/g, '❌')
                .replace(/:warning:/g, '⚠️')
                .replace(/:info:/g, 'ℹ️');
        },

        /**
         * Toggle message expansion for long messages
         */
        toggleMessageExpansion: function(messageId, currentState) {
            console.log('📝 Toggling message:', messageId, 'from', currentState);
            const $message = $(`#${messageId}`);
            const $shortContent = $message.find('.message-short');
            const $fullContent = $message.find('.message-full');
            const $toggleBtn = $message.find('.toggle-message-btn');
            
            if (currentState === 'short') {
                $shortContent.slideUp(300);
                $fullContent.slideDown(300);
                $toggleBtn.text('Show Less').data('state', 'full');
            } else {
                $fullContent.slideUp(300);
                $shortContent.slideDown(300);
                $toggleBtn.text('Show More').data('state', 'short');
            }
        },

        getImageAsBase64: function(callback) {
            console.log('🔄 Converting image to base64...');
            const imageElement = document.getElementById("ai-editor-image");
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            const img = new Image();
            img.crossOrigin = "anonymous";
            img.onload = function() {
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                ctx.drawImage(img, 0, 0);
                try {
                    const base64 = canvas.toDataURL("image/png");
                    console.log('✅ Converted to PNG base64');
                    callback(base64);
                } catch (e) {
                    console.log('⚠️ PNG conversion failed, trying JPEG');
                    const base64 = canvas.toDataURL("image/jpeg", 0.95);
                    console.log('✅ Converted to JPEG base64');
                    callback(base64);
                }
            };
            img.onerror = function() {
                console.error('❌ Failed to convert image to base64');
                callback(null);
            };
            img.src = imageElement.src;
        },

        // --- AJAX METHODS --- //

        sendAIRequest: function(service) {
            console.group('🚀 Send AI Request');
            const self = this;
            const prompt = $("#ai-chat-input").val().trim();
            const selectedModel = $("#ai-model-selector").val();

            console.log('📝 Service:', service);
            console.log('💬 Prompt:', prompt);
            console.log('🤖 Model:', selectedModel);

            if (!prompt && service !== "batch_process") {
                console.log('❌ No prompt provided');
                $("#ai-chat-input").focus();
                console.groupEnd();
                return;
            }
            if (prompt.length > 1000) {
                console.log('❌ Prompt too long:', prompt.length);
                self.addChatMessage("error", "Please keep your prompt under 1000 characters.");
                console.groupEnd();
                return;
            }

            const $buttons = $(".ai-request-btn");
            $buttons.prop("disabled", true);
            const $activeButton = $(`.ai-request-btn[data-service="${service}"]`);
            const originalButtonHTML = $activeButton.html();
            $activeButton.html('<span class="spinner" style="display:inline-block;width:16px;height:16px;border-width:2px;vertical-align:middle;margin-right:8px;"></span> Processing...');

            self.updateStatus("Processing request...");
            if (prompt) {
                this.addChatMessage("user", prompt);
            }

            this.getImageAsBase64(function(base64Image) {
                console.log('🖼️ Got base64 image for request');
                
                const requestData = {
                    action: "sspu_ai_edit_image",
                    nonce: sspu_ajax.nonce,
                    image_data: base64Image,
                    prompt: prompt || "Extract the product and create multiple variations for e-commerce",
                    model: selectedModel,
                    session_id: self.sI
                };
                
                console.log('📤 Sending AJAX request...');
                
                $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: "POST",
                    data: requestData,
                    success: function(response) {
                        console.group('✅ AI Request Success');
                        console.log('Response:', response);
                        
                        if (response.success) {
                            self.addChatMessage("ai", response.data.response, response.data.edited_image);
                            
                            // Add to history if there's a new image
                            if (response.data.edited_image) {
                                console.log('🖼️ New image generated');
                                $("#ai-editor-image").attr("src", response.data.edited_image);
                                self.cU = response.data.edited_image;
                                self.addImageToHistory(response.data.edited_image);
                            }
                            
                            self.updateStatus(response.data.edited_image ? "New image generated with extracted product." : "Processing complete");
                        } else {
                            console.error('❌ Request failed:', response.data.message);
                            self.addChatMessage("error", "Failed: " + (response.data.message || "Unknown error"));
                            self.updateStatus("Error occurred");
                        }
                        console.groupEnd();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('❌ AJAX error:', textStatus, errorThrown);
                        self.addChatMessage("error", "Network error: " + textStatus + " - " + errorThrown);
                        self.updateStatus("Network error");
                    },
                    complete: function() {
                        $buttons.prop("disabled", false);
                        $activeButton.html(originalButtonHTML);
                        if (prompt) {
                            $("#ai-chat-input").val("").focus();
                            self.updateCharCount();
                        }
                        console.log('🔄 Request completed');
                    }
                });
            });
            console.groupEnd();
        },

        loadTemplates: function() {
            console.log('📋 Loading templates...');
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: "POST",
                data: {
                    action: "sspu_get_image_templates",
                    nonce: sspu_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.templates) {
                        console.log('✅ Templates loaded:', response.data.templates.length);
                        const $selector = $("#template-selector");
                        let optionsHTML = '<optgroup label="Custom Templates">';
                        response.data.templates.forEach(function(template) {
                            optionsHTML += `<option value="custom-${template.id}">${template.name}</option>`;
                        });
                        optionsHTML += "</optgroup>";
                        $selector.append(optionsHTML);
                    } else {
                        console.log('❌ No templates found');
                    }
                },
                error: function() {
                    console.error('❌ Failed to load templates');
                }
            });
        },

        saveToMedia: function() {
            console.group('💾 Save to Media');
            const self = this;
            const imageData = $("#ai-editor-image").attr("src");

            console.log('🖼️ Image data:', imageData ? imageData.substring(0, 50) + '...' : 'None');

            if (!imageData || !imageData.startsWith("data:")) {
                console.error('❌ No generated image to save');
                self.addChatMessage("error", "No new image to save. Generate or edit an image first.");
                console.groupEnd();
                return;
            }

            self.updateStatus("Saving to media library...");
            console.log('📤 Sending save request...');
            
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: "POST",
                data: {
                    action: "sspu_save_edited_image",
                    nonce: sspu_ajax.nonce,
                    image_data: imageData,
                    filename: "ai-edited-" + this.cI + "-" + Date.now()
                },
                success: function(response) {
                    console.group('✅ Save Success');
                    console.log('Response:', response);
                    
                    if (response.success) {
                        const oldImageId = self.cI;
                        const newImageId = response.data.attachment_id;
                        const newImageUrl = response.data.url;
                        
                        console.log('🔄 Image ID changed:', oldImageId, '→', newImageId);
                        console.log('🔗 New URL:', newImageUrl);
                        
                        self.addChatMessage("success", `✅ Image saved to media library!\n\nNew Image ID: ${newImageId}`);
                        self.cI = newImageId;
                        self.cU = newImageUrl;
                        $("#ai-editor-image").attr("src", newImageUrl);
                        self.updateStatus("Saved successfully");
                    } else {
                        console.error('❌ Save failed:', response.data.message);
                        self.addChatMessage("error", "Failed to save: " + response.data.message);
                        self.updateStatus("Save failed");
                    }
                    console.groupEnd();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('❌ AJAX error:', textStatus, errorThrown);
                    self.addChatMessage("error", "Failed to save image to media library.");
                    self.updateStatus("Save error");
                    console.groupEnd();
                }
            });
            console.groupEnd();
        },

        // --- WORKFLOW METHODS (APPLYING IMAGES) --- //

        applyAsMainImage: function() {
            console.group('📷 Apply as Main Image');
            const self = this;
            if ($("#ai-editor-image").attr("src").startsWith("data:")) {
                console.log('⏳ Need to save to media first...');
                this.saveToMedia();
                setTimeout(() => self.applyAsMainImageCallback(), 2000);
            } else {
                this.applyAsMainImageCallback();
            }
            console.groupEnd();
        },

        applyAsMainImageCallback: function() {
            console.group('📷 Apply as Main Image Callback');
            if (!this.cI) {
                console.error('❌ No image ID available');
                this.addChatMessage("error", "Please save the image to the media library first.");
                console.groupEnd();
                return;
            }
            
            console.log('🆔 Setting main image ID:', this.cI);
            console.log('🔗 Image URL:', this.cU);
            
            $("#sspu-main-image-id").val(this.cI).trigger("change");
            $("#sspu-main-image-preview").html(`<img src="${this.cU}" alt="" data-id="${this.cI}" style="max-width: 200px;" />`);
            this.addChatMessage("success", "✅ Applied as main product image!");
            this.updateStatus("Set as main image");
            
            setTimeout(() => {
                console.log('🚪 Closing editor...');
                this.close();
            }, 2000);
            console.groupEnd();
        },

        applyToGallery: function() {
            console.group('🖼️ Apply to Gallery');
            const self = this;
            if ($("#ai-editor-image").attr("src").startsWith("data:")) {
                console.log('⏳ Need to save to media first...');
                this.saveToMedia();
                setTimeout(() => self.applyToGalleryCallback(), 2000);
            } else {
                this.applyToGalleryCallback();
            }
            console.groupEnd();
        },

        applyToGalleryCallback: function() {
            console.group('🖼️ Apply to Gallery Callback');
            if (!this.cI) {
                console.error('❌ No image ID available');
                this.addChatMessage("error", "Please save the image to the media library first.");
                console.groupEnd();
                return;
            }

            const $galleryIdsInput = $("#sspu-additional-image-ids");
            const galleryIds = $galleryIdsInput.val() ? $galleryIdsInput.val().split(",") : [];
            
            console.log('📊 Current gallery IDs:', galleryIds);
            console.log('🆔 Adding image ID:', this.cI);

            if (galleryIds.includes(this.cI.toString())) {
                console.log('⚠️ Image already in gallery');
                this.addChatMessage("info", "Image is already in the gallery.");
            } else {
                galleryIds.push(this.cI);
                $galleryIdsInput.val(galleryIds.join(",")).trigger("change");
                console.log('✅ Updated gallery IDs:', galleryIds);
                
                const $previewContainer = $("#sspu-additional-images-preview");
                if ($previewContainer.length) {
                    $previewContainer.append(`
                        <div class="gallery-image" data-id="${this.cI}">
                            <img src="${this.cU}" alt="" style="max-width: 150px;" />
                            <button type="button" class="remove-gallery-image" data-id="${this.cI}">&times;</button>
                        </div>`);
                    console.log('✅ Added to preview container');
                }
                this.addChatMessage("success", "✅ Added to product gallery!");
                this.updateStatus("Added to gallery");
                
                setTimeout(() => {
                    console.log('🚪 Closing editor...');
                    this.close();
                }, 2000);
            }
            console.groupEnd();
        },

        exportVariations: function() {
            console.group('📤 Export Variations');
            if (this.cH.length === 0) {
                console.log('❌ No chat history');
                this.addChatMessage("info", "No variations to export yet.");
                console.groupEnd();
                return;
            }
            
            const imageUrls = this.cH.filter(item => item.imageUrl).map(item => item.imageUrl);
            console.log('🖼️ Found', imageUrls.length, 'images to export');
            
            if (imageUrls.length === 0) {
                console.log('❌ No generated images');
                this.addChatMessage("info", "No generated images to export.");
                console.groupEnd();
                return;
            }
            
            imageUrls.forEach((url, index) => {
                setTimeout(() => {
                    console.log(`⬇️ Downloading variation ${index + 1}`);
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = `variation-${index + 1}.png`;
                    link.click();
                }, 500 * index);
            });
            
            this.addChatMessage("success", `✅ Exporting ${imageUrls.length} variations...`);
            console.groupEnd();
        },

        /**
         * Closes the lightbox and resets the UI to its initial state.
         */
        close: function() {
            console.group('🚪 Closing AI Image Editor');
            console.log('📊 Final state:');
            console.log('- Current Image ID:', this.cI);
            console.log('- Current Image URL:', this.cU);
            console.log('- History length:', this.imageHistory.length);
            console.log('- Chat messages:', this.cH.length);
            
            $("#ai-image-editor-lightbox").fadeOut(300);
            $("body").removeClass("ai-editor-active");
            
            setTimeout(() => {
                console.log('🧹 Resetting UI...');
                $("#ai-chat-history").empty().append(`
                    <div class="welcome-message">
                        <h3>Welcome to AI Image Editor! 👋</h3>
                        <p><strong>Important:</strong> This tool EXTRACTS your existing product from images - it never recreates or modifies the product itself.</p>
                        <p>Ready for your next image editing session.</p>
                    </div>`);
                $("#ai-chat-input").val("");
                this.updateCharCount();
                this.updateStatus("Ready");
                this.closeMimicPanel();
                // Reset history
                this.imageHistory = [];
                this.historyIndex = -1;
                this.updateUndoRedoButtons();
                // Reset variant context
                this.openedFromVariants = false;
                this.variantRowElement = null;
                this.originalVariantImageId = null;
                console.log('✅ Reset complete');
            }, 300);
            
            console.groupEnd();
        }
    };

    // Helper function for opening AI editor from variant row
    window.openAIEditorForVariant = function(button) {
        console.group('🎨 Open AI Editor for Variant');
        const $row = $(button).closest('.sspu-variant-row');
        const imageId = $row.find('.sspu-variant-image-id').val();
        
        console.log('🆔 Variant image ID:', imageId);
        console.log('📍 Variant row:', $row);
        
        if (!imageId) {
            console.error('❌ No variant image ID found');
            alert('Please select a variant image first.');
            console.groupEnd();
            return;
        }
        
        console.log('📡 Fetching image from WordPress media...');
        // Get the image URL from WordPress
        wp.media.attachment(imageId).fetch().done(() => {
            const imageUrl = wp.media.attachment(imageId).get('url');
            console.log('🔗 Got image URL:', imageUrl);
            
            if (imageUrl && window.AIImageEditor) {
                console.log('✅ Opening AI Image Editor...');
                window.AIImageEditor.open(imageId, imageUrl, {
                    fromVariants: true,
                    variantRow: $row[0]
                });
            } else {
                console.error('❌ AI Image Editor not available or URL missing');
                alert('AI Image Editor not available.');
            }
            console.groupEnd();
        }).fail(() => {
            console.error('❌ Failed to fetch image from WordPress');
            alert('Failed to load variant image.');
            console.groupEnd();
        });
    };
    
    console.log('✅ AI Image Editor with Enhanced Debugging loaded successfully!');
})(jQuery);