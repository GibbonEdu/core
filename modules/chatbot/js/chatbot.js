'use strict';

// Make ChatBot available globally
window.ChatBot = class ChatBot {
    constructor(config) {
        if (!config) {
            throw new Error('ChatBot configuration is required');
        }
        if (!config.apiEndpoint) {
            throw new Error('API endpoint is required');
        }
        
        this.apiEndpoint = config.apiEndpoint;
        this.manageEndpoint = config.manageEndpoint;
        this.trainingEndpoint = config.trainingEndpoint;
        this.isTrainingMode = config.isTrainingMode || false;
        this.isAdmin = config.isAdmin || false;
        this.messages = [];
        this.trainingMode = false;
        
        // Initialize UI elements
        this.initializeUI();
        
        // Initialize event listeners
        this.initializeEventListeners();
        
        // Show welcome message
        this.showWelcomeMessage();

        // Initialize training management if elements exist
        if (document.querySelector('.training-sidebar')) {
            this.setupTrainingManagement();
        }

        // Initialize training manager if in training mode
        if (this.isTrainingMode && this.isAdmin) {
            this.trainingManager = new TrainingManager(this);
        }
    }

    initializeUI() {
        // Get UI elements
        this.chatMessages = document.querySelector('.chat-messages');
        this.messageInput = document.querySelector('.chat-input textarea');
        this.sendButton = document.querySelector('.chat-input button');
        this.trainingToggle = document.getElementById('trainingMode');
        this.manageChatBtn = document.querySelector('.manage-chats-btn');
        this.saveChatBtn = document.querySelector('.save-chat-btn');
        this.newChatBtn = document.querySelector('.new-chat-btn');
        this.trainingUpload = document.getElementById('trainingUpload');
        this.uploadBtn = document.querySelector('.upload-btn');
        this.csvUpload = document.getElementById('csvUpload');
        this.submitTrainingBtn = document.querySelector('.submit-training-btn');
        this.selectedFileName = document.getElementById('selectedFileName');
        
        // Training-related elements (optional)
        this.uploadTrainingBtn = document.querySelector('.upload-csv-btn');
        this.viewTrainingBtn = document.querySelector('.view-training-btn');
        this.exportTrainingBtn = document.querySelector('.export-training-btn');
        this.categoryFilter = document.getElementById('categoryFilter');
        this.dateFilter = document.getElementById('dateFilter');
        
        // Core elements check
        if (!this.chatMessages || !this.messageInput || !this.sendButton) {
            console.error('Required UI elements not found');
            throw new Error('Required UI elements not found');
        }
    }

    initializeEventListeners() {
        // Core chat functionality
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize textarea
        this.messageInput.addEventListener('input', () => {
            this.messageInput.style.height = 'auto';
            this.messageInput.style.height = this.messageInput.scrollHeight + 'px';
        });

        // Training mode toggle
        if (this.trainingToggle) {
            this.trainingToggle.addEventListener('change', (e) => {
                this.setTrainingMode(e.target.checked);
            });
        }

        // Chat management buttons
        if (this.manageChatBtn) {
            this.manageChatBtn.addEventListener('click', () => {
                console.log('Manage button clicked');
                this.showManageChatsDialog();
            });
        }

        // Save chat button
        if (this.saveChatBtn) {
            this.saveChatBtn.addEventListener('click', () => {
                console.log('Save button clicked');
                this.showSaveChatDialog();
            });
        } else {
            console.error('Save chat button not found in the DOM');
        }

        if (this.newChatBtn) {
            this.newChatBtn.addEventListener('click', () => this.startNewChat());
        }

        // CSV upload handling
        if (this.uploadBtn) {
            this.uploadBtn.addEventListener('click', () => {
                this.csvUpload.click();
            });
        }

        if (this.csvUpload) {
            this.csvUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.selectedFileName.textContent = `Selected: ${file.name}`;
                    this.selectedFileName.style.display = 'block';
                    this.submitTrainingBtn.style.display = 'inline-flex';
                }
            });
        }

        if (this.submitTrainingBtn) {
            this.submitTrainingBtn.addEventListener('click', () => {
                const file = this.csvUpload.files[0];
                if (file) {
                    this.handleCSVUpload(file);
                }
            });
        }

        // Training-related buttons (if they exist)
        if (this.uploadTrainingBtn) {
            this.uploadTrainingBtn.addEventListener('click', () => this.showUploadDialog());
        }
        if (this.viewTrainingBtn) {
            this.viewTrainingBtn.addEventListener('click', () => this.viewTrainingData());
        }
        if (this.exportTrainingBtn) {
            this.exportTrainingBtn.addEventListener('click', () => this.exportTrainingData());
        }
        if (this.categoryFilter) {
            this.categoryFilter.addEventListener('change', () => this.filterTrainingData());
        }
        if (this.dateFilter) {
            this.dateFilter.addEventListener('change', () => this.filterTrainingData());
        }
    }

    setTrainingMode(enabled) {
        this.trainingMode = enabled;
        console.log('Training mode:', enabled);
        
        // Update UI to reflect training mode state
        document.querySelectorAll('.training-mode-dependent').forEach(el => {
            el.style.display = enabled ? 'block' : 'none';
        });
        
        // Show training mode tooltip
        if (enabled) {
            this.showStatusMessage('Training Mode Enabled');
        }
    }

    showStatusMessage(message) {
        const statusDiv = document.createElement('div');
        statusDiv.className = 'status-message';
        statusDiv.textContent = message;
        this.chatMessages.appendChild(statusDiv);
        setTimeout(() => statusDiv.remove(), 3000);
    }

    showError(message) {
        const notification = document.createElement('div');
        notification.className = 'notification error';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    addMessageToChat(content, isUser = false) {
        if (!content) return;

        // Generate a unique message ID
        const messageID = 'msg_' + new Date().getTime() + '_' + Math.random().toString(36).substr(2, 9);
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
        messageDiv.dataset.messageId = messageID;
        
        // Set background color for user messages
        if (isUser) {
            messageDiv.style.backgroundColor = '#EBF5FB';
        }

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // Set content background color for user messages
        if (isUser) {
            contentDiv.style.backgroundColor = '#EBF5FB';
        }

        let formattedContent = '';
        
        try {
            // Handle different content types
            if (typeof content === 'string') {
                try {
                    // Try to parse as JSON first
                    const jsonContent = JSON.parse(content);
                    formattedContent = this.formatJSONContent(jsonContent);
                } catch (e) {
                    // If not JSON, use as is
                    formattedContent = content;
                }
            } else if (typeof content === 'object') {
                formattedContent = this.formatJSONContent(content);
            } else {
                formattedContent = String(content);
            }

            // Apply markdown formatting
            contentDiv.innerHTML = this.formatMarkdown(formattedContent);

            // Add timestamp
            const timestamp = document.createElement('div');
            timestamp.className = 'message-timestamp';
            timestamp.textContent = new Date().toLocaleTimeString();

            // Add feedback buttons for bot messages only
            if (!isUser) {
                const feedbackDiv = document.createElement('div');
                feedbackDiv.className = 'message-feedback';
                
                // Like button
                const likeBtn = document.createElement('button');
                likeBtn.className = 'feedback-btn like-btn';
                likeBtn.innerHTML = '<i class="far fa-thumbs-up"></i>';
                likeBtn.title = 'This was helpful';
                likeBtn.addEventListener('click', () => this.submitFeedback(messageID, 'like'));
                
                // Dislike button
                const dislikeBtn = document.createElement('button');
                dislikeBtn.className = 'feedback-btn dislike-btn';
                dislikeBtn.innerHTML = '<i class="far fa-thumbs-down"></i>';
                dislikeBtn.title = 'This was not helpful';
                dislikeBtn.addEventListener('click', () => this.submitFeedback(messageID, 'dislike'));
                
                feedbackDiv.appendChild(likeBtn);
                feedbackDiv.appendChild(dislikeBtn);
                
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(feedbackDiv);
                messageDiv.appendChild(timestamp);
            } else {
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(timestamp);
            }

            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } catch (error) {
            console.error('Error formatting message:', error);
            this.showError('Error displaying message');
        }
    }

    formatJSONContent(json) {
        if (!json) return '';

        // Handle lesson plan format
        if (json.lessonplan) {
            const plan = json.lessonplan;
            let output = '';
            
            // Title
            if (plan.title) {
                output += `# ${plan.title}\n\n`;
            }

            // Basic Info
            if (plan.gradelevel) {
                output += `Grade Level: ${plan.gradelevel}\n`;
            }
            if (plan.subject) {
                output += `Subject: ${plan.subject}\n`;
            }
            if (plan.duration) {
                output += `Duration: ${plan.duration}\n\n`;
            }

            // Objectives
            if (plan.objectives && plan.objectives.content) {
                output += `## Objectives\n${plan.objectives.content}\n\n`;
            }

            // Content sections
            if (plan.content) {
                output += this.formatContentSections(plan.content);
            }

            return output;
        }

        // Handle regular JSON objects
        let output = '';
        for (const [key, value] of Object.entries(json)) {
            if (typeof value === 'object' && value !== null) {
                output += `${key}:\n${this.formatJSONContent(value)}\n`;
            } else {
                output += `${key}: ${value}\n`;
            }
        }
        return output;
    }

    formatContentSections(content) {
        if (!content) return '';
        
        let output = '';
        if (Array.isArray(content)) {
            content.forEach((section, index) => {
                if (section.title) {
                    output += `## ${section.title}\n`;
                }
                if (section.content) {
                    output += `${section.content}\n\n`;
                }
            });
        } else if (typeof content === 'object') {
            for (const [key, value] of Object.entries(content)) {
                if (typeof value === 'object') {
                    output += `## ${key}\n${this.formatJSONContent(value)}\n`;
                } else {
                    output += `${value}\n\n`;
                }
            }
        } else {
            output += `${content}\n\n`;
        }
        return output;
    }

    formatLessonPlan(lessonPlan) {
        // Normalize the keys
        const plan = {
            title: lessonPlan.answer?.['Lesson Title'] || lessonPlan.lesson_title || lessonPlan['Lesson Title'],
            grade: lessonPlan.answer?.['Grade Level'] || lessonPlan.grade_level || lessonPlan['Grade Level'],
            duration: lessonPlan.answer?.['Lesson Duration'] || lessonPlan.lesson_duration || lessonPlan['Lesson Duration'],
            objective: lessonPlan.answer?.['Objective'] || lessonPlan.objective || lessonPlan['Objective'],
            materials: lessonPlan.answer?.['Materials Needed'] || lessonPlan.materials_needed || lessonPlan['Materials Needed'],
            outline: lessonPlan.answer?.['Lesson Outline'] || lessonPlan.lesson_outline || lessonPlan['Lesson Outline'],
            assessment: lessonPlan.answer?.['Assessment'] || lessonPlan.assessment || lessonPlan['Assessment'],
            extensions: lessonPlan.answer?.['Extensions'] || lessonPlan.extensions || lessonPlan['Extensions']
        };

        // Format materials list
        let materialsHtml = '';
        if (Array.isArray(plan.materials)) {
            materialsHtml = plan.materials.map(material => `<li>${this.escapeHtml(material)}</li>`).join('');
        } else if (typeof plan.materials === 'string') {
            materialsHtml = `<li>${this.escapeHtml(plan.materials)}</li>`;
        }

        // Format lesson outline
        let outlineHtml = '';
        if (Array.isArray(plan.outline)) {
            outlineHtml = plan.outline.map((step, index) => {
                let stepContent = '';
                if (typeof step === 'string') {
                    stepContent = this.escapeHtml(step);
                } else if (typeof step === 'object') {
                    const time = step.time || step.Time || '';
                    const activity = step.activity || step.Activity || step.details || step.Details || '';
                    stepContent = `${time ? `<span class="time">${this.escapeHtml(time)}</span>` : ''} ${this.escapeHtml(activity)}`;
                }
                return `
                    <div class="outline-section">
                        <h4>Step ${index + 1}</h4>
                        <div class="step-content">${stepContent}</div>
                    </div>`;
            }).join('');
        } else if (typeof plan.outline === 'string') {
            outlineHtml = `<p>${this.escapeHtml(plan.outline)}</p>`;
        }

        return `
            <div class="lesson-plan">
                <h2 class="lesson-title">${this.escapeHtml(plan.title || 'Untitled Lesson')}</h2>
                
                <div class="lesson-metadata">
                    <div class="metadata-item">
                        <span class="metadata-label">Grade Level:</span>
                        <span class="metadata-value">${this.escapeHtml(plan.grade || 'N/A')}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label">Duration:</span>
                        <span class="metadata-value">${this.escapeHtml(plan.duration || 'N/A')}</span>
                    </div>
                </div>

                <div class="lesson-section">
                    <h3>Objective</h3>
                    <p>${this.escapeHtml(plan.objective || 'No objective specified')}</p>
                </div>

                <div class="lesson-section">
                    <h3>Materials Needed</h3>
                    <ul class="materials-list">
                        ${materialsHtml || '<li>No materials specified</li>'}
                    </ul>
                </div>

                <div class="lesson-section">
                    <h3>Lesson Outline</h3>
                    <div class="lesson-outline">
                        ${outlineHtml || '<p>No outline specified</p>'}
                    </div>
                </div>

                ${plan.assessment ? `
                    <div class="lesson-section">
                        <h3>Assessment</h3>
                        <p>${this.escapeHtml(plan.assessment)}</p>
                    </div>
                ` : ''}

                ${plan.extensions ? `
                    <div class="lesson-section">
                        <h3>Extensions/Homework</h3>
                        <p>${this.escapeHtml(plan.extensions)}</p>
                    </div>
                ` : ''}
            </div>
        `;
    }

    formatMarkdown(text) {
        // Escape HTML to prevent XSS
        text = text.replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char]));

        // Define common programming terms to highlight
        const technicalTerms = [
            'data type', 'variable', 'function', 'class', 'object', 'method',
            'array', 'string', 'number', 'boolean', 'null', 'undefined',
            'API', 'HTTP', 'REST', 'JSON', 'XML', 'database', 'SQL',
            'frontend', 'backend', 'server', 'client', 'request', 'response',
            'algorithm', 'loop', 'condition', 'parameter', 'argument',
            'interface', 'module', 'component', 'library', 'framework'
        ];

        // Create regex pattern for technical terms
        const termPattern = new RegExp(`\\b(${technicalTerms.join('|')})\\b`, 'gi');

        // Apply markdown formatting
        return text
            // Headers
            .replace(/^# (.*$)/gm, '<h1>$1</h1>')
            .replace(/^## (.*$)/gm, '<h2>$1</h2>')
            .replace(/^### (.*$)/gm, '<h3>$1</h3>')
            
            // Code blocks
            .replace(/```([^`]+)```/g, '<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            
            // Emphasis
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            .replace(/_([^_]+)_/g, '<em>$1</em>')
            
            // Lists
            .replace(/^\s*[-*+]\s+(.*)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/^\d+\.\s+(.*)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ol>$1</ol>')
            
            // Links and Images
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>')
            .replace(/!\[([^\]]+)\]\(([^)]+)\)/g, '<img src="$2" alt="$1">')
            
            // Blockquotes
            .replace(/^>\s(.*)$/gm, '<blockquote>$1</blockquote>')
            
            // Tables
            .replace(/\|(.+)\|/g, '<tr><td>$1</td></tr>')
            .replace(/^[-|]+$/gm, '')
            .replace(/(<tr>.*<\/tr>)/s, '<table>$1</table>')
            
            // Horizontal Rule
            .replace(/^---$/gm, '<hr>')
            
            // Highlight technical terms
            .replace(termPattern, '<strong class="key-term">$1</strong>')
            
            // Paragraphs
            .replace(/\n\n/g, '</p><p>')
            .replace(/^(.+)$/gm, '<p>$1</p>')
            
            // Clean up empty paragraphs
            .replace(/<p>\s*<\/p>/g, '')
            .replace(/<p><\/p>/g, '');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    addLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message bot-message loading';
        loadingDiv.innerHTML = `
            <div class="message-content">
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        this.chatMessages.appendChild(loadingDiv);
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    removeLoadingIndicator() {
        const loadingDiv = this.chatMessages.querySelector('.loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }

    showWelcomeMessage() {
        const welcomeMessage = {
            content: "# AI Teaching Assistant\n\nHello! I'm your AI assistant.I can help you with:\n \n* Creating detailed lesson plans\n* Analyzing student grades\n* Providing teaching guidance\n* Answering educational questions\n\nHow can I help you today?"
        };
        this.addMessageToChat(welcomeMessage.content, false);
    }

    async sendMessage() {
        const messageText = this.messageInput.value.trim();
        if (!messageText) return;

        // Add user message to chat
        this.addMessageToChat(messageText, true);
        
        // Clear input and reset height
        this.messageInput.value = '';
        this.messageInput.style.height = 'auto';

        try {
            // Show loading indicator
            this.addLoadingIndicator();

            // Send request to backend
            const response = await fetch(`${this.apiEndpoint}/chat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: messageText,
                    isTrainingMode: this.isTrainingMode
                })
            });
            
            // Remove loading indicator
            this.removeLoadingIndicator();
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API Response:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to get response from AI');
            }

            // Add bot's response to chat
            this.addMessageToChat(data.answer, false);

        } catch (error) {
            console.error('Error in chat:', error);
            this.removeLoadingIndicator();
            this.showError(error.message);
        }
    }

    async showManageChatsDialog() {
        try {
            console.log('Fetching saved chats...');
            const response = await fetch(`${this.apiEndpoint}/manage.php?action=list`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            console.log('Received chats:', data);

            const dialog = document.createElement('div');
            dialog.className = 'chat-dialog modal';
            dialog.innerHTML = `
                <div class="chat-dialog-content">
                    <div class="dialog-header">
                        <h3>Manage Saved Chats</h3>
                        <button class="close-dialog-btn">&times;</button>
                    </div>
                    <div class="dialog-body">
                        ${data.chats && data.chats.length > 0 ? `
                            <div class="saved-chats-list">
                                ${data.chats.map(chat => `
                                    <div class="saved-chat-item" data-id="${chat.id}">
                                        <div class="chat-info">
                                            <span class="chat-title">${this.escapeHtml(chat.title)}</span>
                                            <span class="chat-date">${new Date(chat.created_at).toLocaleDateString()}</span>
                                        </div>
                                        <div class="chat-actions">
                                            <button class="action-btn load-chat-btn" title="Load Chat">
                                                <i class="fas fa-folder-open"></i> Load
                                            </button>
                                            <button class="action-btn rename-chat-btn" title="Rename Chat">
                                                <i class="fas fa-edit"></i> Rename
                                            </button>
                                            <button class="action-btn delete-chat-btn" title="Delete Chat">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : '<p class="no-chats-message">No saved chats found.</p>'}
                    </div>
                </div>
            `;

            // Add event listeners
            dialog.querySelector('.close-dialog-btn').addEventListener('click', () => dialog.remove());
            
            // Load chat event listeners
            dialog.querySelectorAll('.load-chat-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const chatId = btn.closest('.saved-chat-item').dataset.id;
                    await this.loadChat(chatId);
                    dialog.remove();
                });
            });

            // Rename chat event listeners
            dialog.querySelectorAll('.rename-chat-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const chatItem = btn.closest('.saved-chat-item');
                    const chatId = chatItem.dataset.id;
                    const currentTitle = chatItem.querySelector('.chat-title').textContent;
                    const newTitle = await this.promptForUniqueName('Enter new name for chat:', currentTitle);
                    
                    if (newTitle && newTitle !== currentTitle) {
                        await this.renameChat(chatId, newTitle);
                        chatItem.querySelector('.chat-title').textContent = newTitle;
                    }
                });
            });
            
            // Delete chat event listeners
            dialog.querySelectorAll('.delete-chat-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const chatItem = btn.closest('.saved-chat-item');
                    const chatId = chatItem.dataset.id;
                    
                    if (confirm('Are you sure you want to delete this chat?')) {
                        try {
                            await this.deleteChat(chatId);
                            chatItem.remove();
                            
                            // Show "no chats" message if no chats remain
                            const remainingChats = dialog.querySelectorAll('.saved-chat-item');
                            if (remainingChats.length === 0) {
                                const chatsList = dialog.querySelector('.saved-chats-list');
                                chatsList.innerHTML = '<p class="no-chats-message">No saved chats found.</p>';
                            }
                        } catch (error) {
                            this.showError('Failed to delete chat: ' + error.message);
                        }
                    }
                });
            });

            document.body.appendChild(dialog);
        } catch (error) {
            console.error('Error loading saved chats:', error);
            this.showError('Failed to load saved chats: ' + error.message);
        }
    }

    async showSaveChatDialog() {
        // Get all messages from the chat window
        const chatMessages = document.querySelectorAll('.chat-messages .message');
        if (chatMessages.length === 0) {
            this.showError('No messages to save');
            return;
        }

        try {
            const title = await this.promptForUniqueName('Enter a name for this chat:');
            if (!title) return;

            // Show loading state
            const loadingNotification = document.createElement('div');
            loadingNotification.className = 'notification info';
            loadingNotification.textContent = 'Saving chat...';
            document.body.appendChild(loadingNotification);

            // Collect messages
            const messages = [];
            chatMessages.forEach(msg => {
                const content = msg.querySelector('.message-content').innerHTML;
                const isUser = msg.classList.contains('user-message');
                messages.push({
                    role: isUser ? 'user' : 'bot',
                    content: content
                });
            });

            console.log('Saving chat with title:', title);
            console.log('Messages to save:', messages);

            const response = await fetch(`${this.apiEndpoint}/manage.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'save',
                    title: title,
                    messages: messages
                })
            });

            // Remove loading notification
            loadingNotification.remove();

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Save response:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to save chat');
            }

            this.showSuccess('Chat saved successfully!');
        } catch (error) {
            console.error('Error saving chat:', error);
            this.showError('Failed to save chat: ' + error.message);
            if (loadingNotification) {
                loadingNotification.remove();
            }
        }
    }

    async promptForUniqueName(message, defaultValue = '') {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'name-dialog modal';
            dialog.innerHTML = `
                <div class="name-dialog-content">
                    <div class="dialog-header">
                        <h3>Save Chat</h3>
                        <button class="close-dialog-btn">&times;</button>
                    </div>
                    <div class="dialog-body">
                        <div class="form-group">
                            <label for="chatName">Enter a unique name for this chat:</label>
                            <input type="text" id="chatName" class="chat-name-input" value="${this.escapeHtml(defaultValue)}" placeholder="Enter chat name">
                            <span class="error-message" style="display: none; color: red;"></span>
                        </div>
                        <div class="dialog-actions">
                            <button type="button" class="cancel-btn">Cancel</button>
                            <button type="button" class="save-btn">Save</button>
                        </div>
                    </div>
                </div>
            `;

            const input = dialog.querySelector('#chatName');
            const saveBtn = dialog.querySelector('.save-btn');
            const errorMsg = dialog.querySelector('.error-message');
            let nameTimeout;

            // Enable save button when input has text
            input.addEventListener('input', () => {
                const name = input.value.trim();
                saveBtn.disabled = name === '';
                errorMsg.style.display = 'none';
                
                clearTimeout(nameTimeout);
                if (name !== '') {
                    nameTimeout = setTimeout(async () => {
                        try {
                            const response = await fetch(`${this.apiEndpoint}/manage.php?check_name=${encodeURIComponent(name)}`);
                            const data = await response.json();
                            
                            if (data.exists && name !== defaultValue) {
                                errorMsg.textContent = 'This name is already taken';
                                errorMsg.style.display = 'block';
                                saveBtn.disabled = true;
                            }
                        } catch (error) {
                            console.error('Error checking name:', error);
                        }
                    }, 300);
                }
            });

            // Handle save button click
            saveBtn.addEventListener('click', () => {
                const name = input.value.trim();
                if (name) {
                    dialog.remove();
                    resolve(name);
                }
            });

            // Handle enter key in input
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !saveBtn.disabled) {
                    const name = input.value.trim();
                    if (name) {
                        dialog.remove();
                        resolve(name);
                    }
                }
            });

            // Handle cancel and close
            dialog.querySelector('.cancel-btn').addEventListener('click', () => {
                dialog.remove();
                resolve(null);
            });

            dialog.querySelector('.close-dialog-btn').addEventListener('click', () => {
                dialog.remove();
                resolve(null);
            });

            document.body.appendChild(dialog);
            input.focus();

            // Enable save button if there's a default value
            if (defaultValue) {
                saveBtn.disabled = false;
            }
        });
    }

    async renameChat(id, newTitle) {
        try {
            const response = await fetch(`${this.apiEndpoint}/manage.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id, title: newTitle })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to rename chat');
            }

            this.showSuccess('Chat renamed successfully');
        } catch (error) {
            this.showError('Failed to rename chat: ' + error.message);
        }
    }

    async deleteChat(id) {
        if (!confirm('Are you sure you want to delete this chat?')) return;

        try {
            const response = await fetch(this.manageEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to delete chat');
            }

            this.showStatusMessage('Chat deleted successfully');
            this.showManageChatsDialog();
        } catch (error) {
            this.showError('Failed to delete chat: ' + error.message);
        }
    }

    async loadChat(id) {
        try {
            // Show loading indicator
            this.addLoadingIndicator();

            const response = await fetch(`${this.apiEndpoint}/manage.php?action=load&id=${id}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Loaded chat data:', data);

            if (!data.success || !data.chat || !data.chat.messages) {
                throw new Error(data.error || 'Failed to load chat messages');
            }

            // Clear current chat messages
            this.chatMessages.innerHTML = '';
            this.messages = [];

            // Parse and display messages
            const messages = JSON.parse(data.chat.messages);
            messages.forEach(msg => {
                const isUser = msg.role === 'user';
                // Create a temporary div to decode HTML entities
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = msg.content;
                const decodedContent = tempDiv.textContent || tempDiv.innerText;
                
                // Add message to chat
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.innerHTML = this.formatMarkdown(decodedContent);
                
                messageDiv.appendChild(contentDiv);
                this.chatMessages.appendChild(messageDiv);

                // Store in messages array
                this.messages.push({
                    type: msg.role,
                    content: decodedContent
                });
            });

            // Remove loading indicator
            this.removeLoadingIndicator();

            // Show success message
            this.showSuccess('Chat loaded successfully');

        } catch (error) {
            console.error('Error loading chat:', error);
            this.showError('Failed to load chat: ' + error.message);
            this.removeLoadingIndicator();
        }
    }

    startNewChat() {
        // Clear messages array
        this.messages = [];

        // Clear chat display
        this.chatMessages.innerHTML = ''; // Clear existing messages

        // Define the consistent welcome message content (same as in showWelcomeMessage)
        const welcomeContent = "# Welcome to Clement Howell AI Teaching Assistant\n\n* I can help you with:\n\n* Creating detailed lesson plans\n* Analyzing student grades\n* Providing teaching guidance\n* Answering educational questions\n\nHow can I help you today?";

        // Add the message using the standard method for consistent formatting
        this.addMessageToChat(welcomeContent, false);

        // Store the formatted welcome message (optional, but good practice)
        // Note: addMessageToChat already pushes to this.messages if modified to do so,
        // otherwise, we need to handle it here if tracking is needed.
        // Let's assume addMessageToChat handles adding to the internal array if needed.

        this.showStatusMessage('Started new chat');
    }

    async handleCSVUpload(file) {
        try {
            const reader = new FileReader();
            
            reader.onload = async (e) => {
                const csv = e.target.result;
                const lines = csv.split('\n');
                
                // Clean and normalize headers
                const headers = lines[0].toLowerCase()
                    .split(',')
                    .map(h => h.trim().replace(/['"]/g, '')); // Remove quotes if present
                
                console.log('CSV Headers:', headers);
                
                // Find question and answer columns - try different variations
                const questionCol = headers.findIndex(h => 
                    h === 'question' || h === 'questions' || h === 'prompt' || h === 'input'
                );
                const answerCol = headers.findIndex(h => 
                    h === 'answer' || h === 'answers' || h === 'response' || h === 'output'
                );
                
                if (questionCol === -1 || answerCol === -1) {
                    throw new Error('CSV must contain columns for questions and answers. Please check the column headers.');
                }

                // Process all rows first
                const trainingData = [];
                lines.slice(1).forEach(row => {
                    if (row.trim()) {
                        const cells = row.split(',').map(cell => cell.trim().replace(/^["']|["']$/g, ''));
                        
                        if (cells.length >= Math.max(questionCol, answerCol) + 1) {
                            const question = cells[questionCol];
                            const answer = cells[answerCol];
                            
                            if (question && answer) {
                                trainingData.push({ question, answer });
                            }
                        }
                    }
                });

                if (trainingData.length === 0) {
                    throw new Error('No valid training data found in CSV');
                }

                // Create preview dialog
                const dialog = document.createElement('div');
                dialog.className = 'csv-preview-dialog';
                
                // Create table HTML
                let tableHtml = '<h3>Preview Training Data</h3>';
                tableHtml += '<table class="csv-preview-table"><thead><tr><th>#</th><th>Question</th><th>Answer</th></tr></thead><tbody>';

                // Add first 5 rows for preview
                trainingData.slice(0, 5).forEach((row, index) => {
                    tableHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${row.question}</td>
                            <td>${row.answer}</td>
                        </tr>
                    `;
                });
                tableHtml += '</tbody></table>';

                // Add total count and action buttons
                tableHtml += `
                    <p class="preview-total">Total items to upload: ${trainingData.length}</p>
                    <div class="csv-preview-actions">
                        <button class="cancel-btn">Cancel</button>
                        <button class="confirm-btn">Confirm Upload</button>
                    </div>
                `;

                dialog.innerHTML = tableHtml;

                // Create overlay
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                document.body.appendChild(overlay);
                document.body.appendChild(dialog);

                // Add event listeners
                dialog.querySelector('.cancel-btn').addEventListener('click', () => {
                    overlay.remove();
                    dialog.remove();
                });

                dialog.querySelector('.confirm-btn').addEventListener('click', async () => {
                    try {
                        // Show loading state
                        dialog.innerHTML = '<div class="loading">Uploading training data...</div>';

                        console.log('Sending training data:', { training_data: trainingData });
                        
                        // Send to backend
                        const response = await fetch(this.trainingEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ training_data: trainingData })
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        console.log('Training upload response:', data);

                        if (!data.success) {
                            throw new Error(data.error || 'Failed to process training data');
                        }

                        // Show success dialog with uploaded data
                        const successDialog = document.createElement('div');
                        successDialog.className = 'csv-preview-dialog success-dialog';
                        
                        let successHtml = `
                            <h3>Training Data Uploaded Successfully!</h3>
                            <div class="success-stats">
                                <p><strong>Total items:</strong> ${data.stats.total}</p>
                                <p><strong>Successfully uploaded:</strong> ${data.stats.success}</p>
                                ${data.stats.failed > 0 ? `<p><strong>Failed:</strong> ${data.stats.failed}</p>` : ''}
                            </div>
                            <h4>Uploaded Training Data:</h4>
                            <div class="uploaded-data-container">
                                <table class="csv-preview-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Question</th>
                                            <th>Answer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        // Add all uploaded items to the table
                        trainingData.forEach((item, index) => {
                            successHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.question}</td>
                                    <td>${item.answer}</td>
                                </tr>
                            `;
                        });

                        successHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="success-actions">
                                <button class="close-success-btn">Close</button>
                            </div>
                        `;

                        successDialog.innerHTML = successHtml;

                        // Create new overlay for success dialog
                        const successOverlay = document.createElement('div');
                        successOverlay.className = 'modal-overlay';
                        
                        // Add to DOM
                        document.body.appendChild(successOverlay);
                        document.body.appendChild(successDialog);

                        // Add close button handler
                        successDialog.querySelector('.close-success-btn').addEventListener('click', () => {
                            successDialog.remove();
                            successOverlay.remove();
                        });

                        // Add click outside to close
                        successOverlay.addEventListener('click', (e) => {
                            if (e.target === successOverlay) {
                                successDialog.remove();
                                successOverlay.remove();
                            }
                        });

                        // Reset file input
                        this.csvUpload.value = '';
                        this.selectedFileName.style.display = 'none';
                        this.submitTrainingBtn.style.display = 'none';

                        // Remove the original dialog and overlay
                        dialog.remove();
                        overlay.remove();

                        // Refresh training stats
                        this.loadTrainingStats();

                    } catch (error) {
                        console.error('Training data upload error:', error);
                        this.showError(error.message);
                        overlay.remove();
                        dialog.remove();
                    }
                });
            };

            reader.onerror = () => {
                this.showError('Error reading CSV file');
            };

            reader.readAsText(file);
        } catch (error) {
            console.error('CSV processing error:', error);
            this.showError('Failed to process CSV file: ' + error.message);
        }
    }

    setupTrainingManagement() {
        // Setup event listeners for training management buttons
        const viewDataBtn = document.querySelector('#viewTrainingData');
        if (viewDataBtn) {
            viewDataBtn.addEventListener('click', () => this.viewTrainingData());
        }

        const exportDataBtn = document.querySelector('#exportTrainingData');
        if (exportDataBtn) {
            exportDataBtn.addEventListener('click', () => this.exportTrainingData());
        }

        // Setup filters
        const categoryFilter = document.querySelector('#categoryFilter');
        const dateFilter = document.querySelector('#dateFilter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => this.filterTrainingData());
        }
        if (dateFilter) {
            dateFilter.addEventListener('change', () => this.filterTrainingData());
        }

        // Initial load of training stats
        this.loadTrainingStats();
    }

    async loadTrainingStats() {
        try {
            const response = await fetch(`${this.trainingEndpoint}/stats`);
            if (!response.ok) throw new Error('Failed to load training stats');
            
            const stats = await response.json();
            
            // Update stats in the sidebar
            const totalItems = document.querySelector('#totalTrainingItems');
            const lastUpload = document.querySelector('#lastUploadDate');
            
            if (totalItems) totalItems.textContent = stats.totalItems || 0;
            if (lastUpload) lastUpload.textContent = stats.lastUploadDate || 'Never';
            
        } catch (error) {
            console.error('Error loading training stats:', error);
            this.showError('Failed to load training stats');
        }
    }

    async viewTrainingData() {
        try {
            const response = await fetch(`${this.trainingEndpoint}/list`);
            if (!response.ok) throw new Error('Failed to load training data');
            
            const data = await response.json();
            
            // Create and show dialog
            const dialog = document.createElement('div');
            dialog.className = 'training-data-dialog';
            dialog.innerHTML = `
                <div class="training-data-header">
                    <h3 class="training-data-title">Training Data</h3>
                    <button class="close-btn" onclick="this.closest('.training-data-dialog').remove()">×</button>
                </div>
                <div class="training-data-content">
                    <table class="training-data-table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.items.map(item => `
                                <tr data-id="${item.id}">
                                    <td>${this.escapeHtml(item.question)}</td>
                                    <td>${this.escapeHtml(item.answer)}</td>
                                    <td>${new Date(item.created_at).toLocaleDateString()}</td>
                                    <td class="training-data-actions">
                                        <button class="training-data-btn edit" onclick="chatBot.editTrainingItem(${item.id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="training-data-btn delete" onclick="chatBot.deleteTrainingItem(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            // Add overlay
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.remove();
                    dialog.remove();
                }
            });
            
            document.body.appendChild(overlay);
            document.body.appendChild(dialog);
            
        } catch (error) {
            console.error('Error viewing training data:', error);
            this.showError('Failed to load training data');
        }
    }

    async exportTrainingData() {
        try {
            const response = await fetch(`${this.trainingEndpoint}/export`);
            if (!response.ok) throw new Error('Failed to export training data');
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `training_data_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            
        } catch (error) {
            console.error('Error exporting training data:', error);
            this.showError('Failed to export training data');
        }
    }

    async filterTrainingData() {
        const category = document.querySelector('#categoryFilter').value;
        const date = document.querySelector('#dateFilter').value;
        
        try {
            const response = await fetch(`${this.trainingEndpoint}/list?category=${category}&date=${date}`);
            if (!response.ok) throw new Error('Failed to filter training data');
            
            const data = await response.json();
            
            // Update the table with filtered data
            const tbody = document.querySelector('.training-data-table tbody');
            if (tbody) {
                tbody.innerHTML = data.items.map(item => `
                    <tr data-id="${item.id}">
                        <td>${this.escapeHtml(item.question)}</td>
                        <td>${this.escapeHtml(item.answer)}</td>
                        <td>${new Date(item.created_at).toLocaleDateString()}</td>
                        <td class="training-data-actions">
                            <button class="training-data-btn edit" onclick="chatBot.editTrainingItem(${item.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="training-data-btn delete" onclick="chatBot.deleteTrainingItem(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
            
        } catch (error) {
            console.error('Error filtering training data:', error);
            this.showError('Failed to filter training data');
        }
    }

    async editTrainingItem(id) {
        try {
            const response = await fetch(`${this.trainingEndpoint}/item/${id}`);
            if (!response.ok) throw new Error('Failed to load training item');
            
            const item = await response.json();
            
            // Create edit dialog
            const dialog = document.createElement('div');
            dialog.className = 'training-data-dialog';
            dialog.innerHTML = `
                <div class="training-data-header">
                    <h3 class="training-data-title">Edit Training Item</h3>
                    <button class="close-btn" onclick="this.closest('.training-data-dialog').remove()">×</button>
                </div>
                <div class="training-data-content">
                    <form id="editTrainingForm">
                        <div class="form-group">
                            <label for="question">Question</label>
                            <input type="text" id="question" value="${this.escapeHtml(item.question)}" required>
                        </div>
                        <div class="form-group">
                            <label for="answer">Answer</label>
                            <textarea id="answer" required>${this.escapeHtml(item.answer)}</textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Changes</button>
                            <button type="button" class="btn-secondary" onclick="this.closest('.training-data-dialog').remove()">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            
            // Add form submit handler
            const form = dialog.querySelector('#editTrainingForm');
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.saveTrainingItem(id, {
                    question: form.querySelector('#question').value,
                    answer: form.querySelector('#answer').value
                });
                dialog.remove();
                this.viewTrainingData(); // Refresh the view
            });
            
            document.body.appendChild(dialog);
            
        } catch (error) {
            console.error('Error editing training item:', error);
            this.showError('Failed to load training item');
        }
    }

    async deleteTrainingItem(id) {
        if (!confirm('Are you sure you want to delete this training item?')) return;
        
        try {
            const response = await fetch(`${this.trainingEndpoint}/item/${id}`, {
                method: 'DELETE'
            });
            
            if (!response.ok) throw new Error('Failed to delete training item');
            
            // Remove the item from the table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.remove();
            
            // Refresh stats
            this.loadTrainingStats();
            
        } catch (error) {
            console.error('Error deleting training item:', error);
            this.showError('Failed to delete training item');
        }
    }

    async saveTrainingItem(id, data) {
        try {
            const response = await fetch(`${this.trainingEndpoint}/item/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error('Failed to save training item');
            
            // Refresh stats
            this.loadTrainingStats();
            
        } catch (error) {
            console.error('Error saving training item:', error);
            this.showError('Failed to save training item');
        }
    }

    showUploadDialog() {
        // Create file input if it doesn't exist
        let fileInput = document.getElementById('csvFileInput');
        if (!fileInput) {
            fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.id = 'csvFileInput';
            fileInput.accept = '.csv';
            fileInput.style.display = 'none';
            document.body.appendChild(fileInput);
            
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.handleCSVUpload(file);
                }
            });
        }
        
        fileInput.click();
    }

    showSuccess(message) {
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    uploadTrainingData() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.csv';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) {
                document.body.removeChild(fileInput);
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(this.trainingEndpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    this.trainingManager.showSuccess(`Successfully uploaded ${result.uploaded} training items`);
                    this.trainingManager.loadTrainingData();
                    this.trainingManager.loadStats();
                } else {
                    this.trainingManager.showError(result.message || 'Failed to upload training data');
                }
            } catch (error) {
                console.error('Error uploading training data:', error);
                this.trainingManager.showError('Failed to upload training data. Please try again.');
            } finally {
                document.body.removeChild(fileInput);
            }
        });

        fileInput.click();
    }

    // Add usage tracking method
    trackUsage(usage) {
        const { prompt_tokens, completion_tokens, total_tokens } = usage;
        
        // You can implement token usage tracking here
        // For example, store in localStorage or send to server
        const currentUsage = JSON.parse(localStorage.getItem('tokenUsage') || '{}');
        const newUsage = {
            prompt_tokens: (currentUsage.prompt_tokens || 0) + prompt_tokens,
            completion_tokens: (currentUsage.completion_tokens || 0) + completion_tokens,
            total_tokens: (currentUsage.total_tokens || 0) + total_tokens,
            last_updated: new Date().toISOString()
        };
        localStorage.setItem('tokenUsage', JSON.stringify(newUsage));
    }

    showFeedbackResult(element, message, isSuccess = true) {
        // Create a feedback bubble
        const bubble = document.createElement('div');
        bubble.className = `feedback-bubble ${isSuccess ? 'success' : 'error'}`;
        bubble.textContent = message;
        
        // Position relative to the button
        const rect = element.getBoundingClientRect();
        
        // Add to page body to avoid layout issues
        document.body.appendChild(bubble);
        
        // Calculate position
        const bubbleRect = bubble.getBoundingClientRect();
        bubble.style.left = `${rect.left + (rect.width / 2) - (bubbleRect.width / 2)}px`;
        bubble.style.top = `${rect.top - bubbleRect.height - 8}px`;
        
        // Animate
        setTimeout(() => {
            bubble.classList.add('show');
            
            // Remove after animation
            setTimeout(() => {
                bubble.classList.remove('show');
                setTimeout(() => document.body.removeChild(bubble), 300);
            }, 1500);
        }, 10);
    }

    // Add a debug method for displaying database operation details with helpful information
    logDatabaseOperation(data) {
        if (data && data.db_operation) {
            const op = data.db_operation;
            console.group('Database Operation Details');
            console.log(`Operation Type: ${op.type}`);
            console.log(`Table: ${op.table}`);
            console.log(`Affected Rows: ${op.affected_rows}`);
            console.log(`Record ID: ${op.id}`);
            console.log(`Timestamp: ${data.timestamp}`);
            console.groupEnd();
            
            // Create a debug info div that shows up in the page
            const debugInfo = document.createElement('div');
            debugInfo.className = 'debug-info';
            debugInfo.innerHTML = `
                <div class="debug-header">Feedback Saved Successfully!</div>
                <div class="debug-row"><span>Record ID:</span> <span>${op.id}</span></div>
                <div class="debug-row"><span>Operation:</span> <span>${op.type}</span></div>
                <div class="debug-row"><span>Status:</span> <span class="success">✓ Success</span></div>
                <div class="debug-footer">Check your database for confirmation</div>
            `;
            
            // Add to document
            document.body.appendChild(debugInfo);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                debugInfo.classList.add('fade-out');
                setTimeout(() => {
                    document.body.removeChild(debugInfo);
                }, 500);
            }, 5000);
            
            return `${op.type.toUpperCase()} operation on record #${op.id} - ${op.affected_rows} row(s) affected`;
        }
        return null;
    }

    submitFeedback(messageID, feedbackType) {
        // Get CSRF token from meta tag
        let token = document.querySelector('meta[name="gibbonCSRFToken"]')?.content;
        
        if (!token) {
            // Fallback to input field if meta tag not available
            token = document.querySelector('input[name="gibbonCSRFToken"]')?.value;
        }
        
        if (!token) {
            console.error('No CSRF token found');
            this.showError('Security token not found. Please refresh the page and try again.');
            return;
        }
        
        // Get the feedback buttons for this message
        const messageDiv = document.querySelector(`.message[data-message-id="${messageID}"]`);
        if (!messageDiv) {
            console.error('Message not found:', messageID);
            return;
        }
        
        // Toggle active state for the clicked button
        const likeBtn = messageDiv.querySelector('.like-btn');
        const dislikeBtn = messageDiv.querySelector('.dislike-btn');
        
        if (feedbackType === 'like') {
            likeBtn.classList.toggle('active');
            if (likeBtn.classList.contains('active')) {
                dislikeBtn.classList.remove('active');
                likeBtn.classList.add('updating');
            }
        } else {
            dislikeBtn.classList.toggle('active');
            if (dislikeBtn.classList.contains('active')) {
                likeBtn.classList.remove('active');
                dislikeBtn.classList.add('updating');
            }
        }
        
        // Send feedback to the server
        const formData = new URLSearchParams();
        formData.append('gibbonCSRFToken', token);
        formData.append('messageID', messageID);
        formData.append('feedback', feedbackType);
        
        // Get the absolute URL from the page
        const baseURL = document.querySelector('meta[name="absoluteURL"]')?.content || '';
        
        // Log the request details
        console.log('Submitting feedback:', {
            messageID,
            feedbackType,
            token: token.substring(0, 5) + '...',  // Only log first 5 chars for security
            url: baseURL + '/modules/ChatBot/feedback.php'
        });
        
        fetch(baseURL + '/modules/ChatBot/feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
        .then(response => {
            console.log('Server response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Feedback response:', data);
            
            // Get the button that was clicked
            const activeBtn = feedbackType === 'like' ? likeBtn : dislikeBtn;
            
            // Remove updating class regardless of result
            activeBtn.classList.remove('updating');
            
            if (data.success) {
                // Visual feedback of success
                activeBtn.classList.add('success');
                
                // Format message based on the action
                let message = 'Feedback saved';
                if (data.action === 'removed') {
                    message = 'Feedback removed';
                    activeBtn.classList.remove('active');
                } else if (data.action === 'updated') {
                    message = 'Feedback updated';
                }
                
                // Show feedback bubble
                this.showFeedbackResult(activeBtn, message, true);
                
                // Remove success class after animation
                setTimeout(() => activeBtn.classList.remove('success'), 1500);
            } else {
                // If error, revert the UI changes
                likeBtn.classList.remove('active');
                dislikeBtn.classList.remove('active');
                throw new Error(data.message || 'Failed to save feedback');
            }
        })
        .catch(error => {
            console.error('Error submitting feedback:', error);
            
            // Get the button that was clicked
            const activeBtn = feedbackType === 'like' ? likeBtn : dislikeBtn;
            
            // Remove updating class on error
            activeBtn.classList.remove('updating');
            activeBtn.classList.remove('active');
            
            // Show error in bubble
            this.showFeedbackResult(activeBtn, 'Error saving feedback', false);
            
            // Also show main error
            this.showError(`Failed to save feedback: ${error.message}`);
        });
    }
}

// Initialize chat when DOM is loaded
/* document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing ChatBot');
    try {
        window.chatBot = new window.ChatBot();
        console.log('ChatBot initialized successfully');
    } catch (error) {
        console.error('Error initializing ChatBot:', error);
        console.error('Error stack:', error.stack);
    }
}); */

class TrainingManager {
    constructor(apiEndpoint) {
        this.apiEndpoint = apiEndpoint;
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.totalItems = 0;
        this.trainingData = [];
        this.initializeEventListeners();
        this.loadTrainingData();
    }

    initializeEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('searchTraining');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => {
                this.currentPage = 1;
                this.loadTrainingData();
            }, 300));
        }

        // Filter changes
        const approvedFilter = document.getElementById('approved');
        const dateRangeFilter = document.getElementById('dateRange');
        if (approvedFilter) {
            approvedFilter.addEventListener('change', () => {
                this.currentPage = 1;
                this.loadTrainingData();
            });
        }
        if (dateRangeFilter) {
            dateRangeFilter.addEventListener('change', () => {
                this.currentPage = 1;
                this.loadTrainingData();
            });
        }

        // Refresh button
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadTrainingData();
            });
        }
    }

    async loadTrainingData() {
        try {
            const searchQuery = document.getElementById('searchTraining')?.value || '';
            const approvedFilter = document.getElementById('approved')?.value || 'all';
            const dateRangeFilter = document.getElementById('dateRange')?.value || 'all';

            const response = await fetch(`${this.apiEndpoint}/train.php?action=list&page=${this.currentPage}&limit=${this.itemsPerPage}&search=${encodeURIComponent(searchQuery)}&approved=${approvedFilter}&dateRange=${dateRangeFilter}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.trainingData = data.data;
                this.totalItems = data.total;
                this.updateStats();
                this.renderTrainingData();
                this.updatePagination();
            } else {
                console.error('Failed to load training data:', data.message);
                this.showNotification('Failed to load training data', 'error');
            }
        } catch (error) {
            console.error('Error loading training data:', error);
            this.showNotification('Error loading training data', 'error');
        }
    }

    updateStats() {
        const totalItemsElement = document.getElementById('totalItems');
        const lastUploadElement = document.getElementById('lastUpload');
        
        if (totalItemsElement) {
            totalItemsElement.textContent = this.totalItems;
        }
        
        if (lastUploadElement && this.trainingData.length > 0) {
            const lastUpload = new Date(this.trainingData[0].created_at);
            lastUploadElement.textContent = lastUpload.toLocaleDateString();
        }
    }

    renderTrainingData() {
        const tbody = document.querySelector('.training-data-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        
        this.trainingData.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${this.escapeHtml(item.question)}</td>
                <td>${this.escapeHtml(item.answer)}</td>
                <td>${item.approved ? 'Yes' : 'No'}</td>
                <td>
                    <button class="action-btn edit-btn" onclick="chatbot.trainingManager.editItem(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete-btn" onclick="chatbot.trainingManager.deleteItem(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updatePagination() {
        const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
        const paginationContainer = document.querySelector('.pagination');
        if (!paginationContainer) return;

        paginationContainer.innerHTML = '';
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.textContent = 'Previous';
        prevButton.disabled = this.currentPage === 1;
        prevButton.onclick = () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadTrainingData();
            }
        };
        paginationContainer.appendChild(prevButton);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = i === this.currentPage ? 'active' : '';
            pageButton.onclick = () => {
                this.currentPage = i;
                this.loadTrainingData();
            };
            paginationContainer.appendChild(pageButton);
        }

        // Next button
        const nextButton = document.createElement('button');
        nextButton.textContent = 'Next';
        nextButton.disabled = this.currentPage === totalPages;
        nextButton.onclick = () => {
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.loadTrainingData();
            }
        };
        paginationContainer.appendChild(nextButton);
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // ... rest of the TrainingManager class methods ...
}

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize training manager when chatbot is ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait for chatbot to be initialized
    const initInterval = setInterval(() => {
        const chatbot = window.chatbot;
        if (chatbot && chatbot.isTrainingMode && chatbot.isAdmin) {
            chatbot.trainingManager = new TrainingManager(chatbot.apiEndpoint);
            clearInterval(initInterval);
        }
    }, 100);

    // Clear interval after 5 seconds if chatbot is not initialized
    setTimeout(() => clearInterval(initInterval), 5000);
});

// Add these styles to the document
document.head.appendChild(document.createElement('style')).textContent = `
    /* Message and code block styling */
    .message {
        margin: 10px 0;
        padding: 10px;
        border-radius: 8px;
    }

    .user-message {
        background-color: #e3f2fd;
        margin-left: 20%;
    }

    .bot-message {
        background-color: #f5f5f5;
        margin-right: 20%;
    }

    .message-content {
        word-wrap: break-word;
    }

/* Style for H1 inside bot messages (including welcome message) */
    .bot-message .message-content h1 {
        color: #007bff; /* Blue color */
        margin-top: 0; /* Adjust spacing if needed */
        margin-bottom: 10px;
        font-size: 1.5em; /* Adjust size if needed */
    }

    /* Code block styling */
    .message pre,
    .message code,
    .message-content pre,
    .message-content code {
        background-color: #ffffff !important;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 12px;
        margin: 8px 0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
        line-height: 1.5;
        color: #333333;
        overflow-x: auto;
        white-space: pre-wrap;
    }

    .message pre code,
    .message-content pre code {
        border: none;
        padding: 0;
        margin: 0;
        background-color: transparent !important;
    }

    /* Syntax highlighting */
    .key-term {
        color: #0066cc;
        font-weight: 500;
    }

    .code-keyword {
        color: #0066cc;
    }

    .code-string {
        color: #008000;
    }

    .code-comment {
        color: #808080;
        font-style: italic;
    }

    /* Rest of existing modal styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .chat-dialog-content,
    .name-dialog-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        min-width: 400px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .dialog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .dialog-header h3 {
        margin: 0;
        color: #333;
    }

    .close-dialog-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .saved-chats-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .saved-chat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 4px;
        background: #f8f9fa;
    }

    .chat-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .chat-title {
        font-weight: 500;
        color: #333;
    }

    .chat-date {
        font-size: 0.8em;
        color: #666;
    }

    .chat-actions {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        padding: 6px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        background: #fff;
        color: #333;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background: #e9ecef;
    }

    .load-chat-btn { color: #28a745; }
    .rename-chat-btn { color: #007bff; }
    .delete-chat-btn { color: #dc3545; }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
    }

    .chat-name-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .dialog-actions button {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .cancel-btn {
        background: #e9ecef;
        color: #333;
    }

    .save-btn {
        background: #007bff;
        color: white;
    }

    .save-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .error-message {
        font-size: 12px;
        margin-top: 4px;
    }

    .no-chats-message {
        text-align: center;
        color: #666;
        padding: 20px;
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        color: white;
        font-size: 14px;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }

    .notification.success {
        background-color: #28a745;
    }

    .notification.error {
        background-color: #dc3545;
    }

    .notification.info {
        background-color: #17a2b8;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }





`; 