<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Debug information
error_log("Script starting execution");

// Basic initialization
$scriptPath = __DIR__;
$gibbonRoot = realpath($scriptPath . '/../../');

// Include Gibbon's autoloader first
require_once $gibbonRoot . '/vendor/autoload.php';

// Include Gibbon core files
require_once $gibbonRoot . '/gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Note: Rely on autoloading instead of manual includes
// Let the autoloader handle these files
// require_once __DIR__ . '/src/Domain/ChatGateway.php';
// require_once __DIR__ . '/src/DeepSeekAPI.php';

use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\View\Page;
use Gibbon\Module\ChatBot\Domain\ChatGateway;
use Gibbon\Module\ChatBot\DeepSeekAPI;
use Gibbon\Forms\Form;

// Initialize the page
$page = $container->get('page');
$session = $container->get('session');

// Set page properties
$page->breadcrumbs
    ->add(__('Modules'))
    ->add(__('ChatBot'));

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/chatbot.php')) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Get database connection
try {
    $connection = $container->get(Connection::class);
    $connection2 = $connection->getConnection();
} catch (Exception $e) {
    $page->addError('Database connection error: ' . $e->getMessage());
    return;
}

// Initialize SettingGateway
try {
    $settingGateway = $container->get(SettingGateway::class);
    $apiKey = $settingGateway->getSettingByScope('ChatBot', 'deepseek_api_key');
    $modelName = $settingGateway->getSettingByScope('ChatBot', 'model_name');
    $maxTokens = $settingGateway->getSettingByScope('ChatBot', 'max_tokens');
    
    if (empty($apiKey)) {
        $page->addError(__('DeepSeek API key not configured. Please contact your system administrator.'));
        return;
    }
    
    // Initialize the API with container and properly configured connection
    $deepSeekAPI = new DeepSeekAPI($apiKey, $connection, $container);
    
} catch (Exception $e) {
    $page->addError(__('Failed to load settings: ') . $e->getMessage());
    return;
}

// Check if user is admin
$isAdmin = isActionAccessible($guid, $connection2, '/modules/ChatBot/training.php');

// Add CSS and JS files
$absoluteURL = $session->get('absoluteURL');

// Add meta tags for CSRF token and absoluteURL
echo "<meta name='gibbonCSRFToken' content='" . $session->get('gibbonCSRFToken') . "'>";
echo "<meta name='absoluteURL' content='" . $absoluteURL . "'>";

echo "<link rel='stylesheet' type='text/css' href='{$absoluteURL}/modules/ChatBot/css/chatbot.css'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";

// Add ChatBot configuration first
echo "<script>
window.chatBotConfig = {
    apiEndpoint: '{$absoluteURL}/modules/ChatBot/api',
    manageEndpoint: '{$absoluteURL}/modules/ChatBot/api/manage.php',
    trainingEndpoint: '{$absoluteURL}/modules/ChatBot/api/train.php',
    isTrainingMode: " . (isset($_GET['training']) ? 'true' : 'false') . ",
    isAdmin: " . ($isAdmin ? 'true' : 'false') . "
};
</script>";

// Add ChatBot script after config
echo "<script src='{$absoluteURL}/modules/ChatBot/js/chatbot.js'></script>";

// Initialize ChatBot after script loads
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing ChatBot');
    try {
        if (typeof ChatBot === 'undefined') {
            throw new Error('ChatBot class not loaded');
        }
        if (!window.chatBotConfig) {
            throw new Error('ChatBot configuration not found');
        }
        window.chatBot = new ChatBot(window.chatBotConfig);
        console.log('ChatBot initialized successfully');
    } catch (error) {
        console.error('Error initializing ChatBot:', error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = 'Failed to initialize chat: ' + error.message;
        document.querySelector('.chat-messages')?.appendChild(errorDiv);
    }
});
</script>";

// Set page title
echo "<h2>" . __('ChatBot') . "</h2>";

// Add sidebar menu
$page->addSidebarExtra('
    <div class="column-no-break">
        <h2>' . __('ChatBot Menu') . '</h2>
        <ul class="moduleMenu">
            <li class="selected"><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/chatbot.php">' . __('AI Teaching Assistant') . '</a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/assessment_integration.php">' . __('Assessment Integration') . '</a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/learning_management.php">' . __('Learning Management') . '</a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/settings.php">' . __('Settings') . '</a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/feedback.php">' . __('Feedback Analytics') . '</a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/db_check_feedback.php" style="color:#4CAF50;">' . __('Check Feedback DB') . ' <i class="fas fa-database fa-xs"></i></a></li>
            <li><a href="' . $session->get('absoluteURL') . '/modules/ChatBot/debug_feedback_storage.php" target="_blank" style="color:#ff5252;">' . __('Debug Feedback Storage') . ' <i class="fas fa-bug fa-xs"></i></a></li>
            <li><a href="' . $session->get('absoluteURL') . '/modules/ChatBot/simple_api_test.php" target="_blank" style="color:#2a7fff;">' . __('Test API Key') . ' <i class="fas fa-external-link-alt fa-xs"></i></a></li>
            <li><a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/ai_learning.php">AI Learning System</a></li>
        </ul>
    </div>
');

?>

<div class="chatbot-layout">
    <?php if ($isAdmin && isset($_GET['training'])): ?>
    <!-- Training Management Section -->
    <div class="training-management" style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div class="training-sidebar">
            <div class="training-stats">
                <h3>Training Stats</h3>
                <div class="stat-item">
                    <span class="stat-label">Total Items:</span>
                    <span class="stat-value" id="totalItems">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Last Upload:</span>
                    <span class="stat-value" id="lastUpload">Never</span>
                </div>
            </div>

            <div class="training-actions">
                <button class="sidebar-btn" onclick="chatbot.uploadTrainingData()">
                    <i class="fas fa-upload"></i> Upload Training Data
                </button>
                <button class="sidebar-btn" onclick="window.location.href = chatbot.apiEndpoint + '/export.php'">
                    <i class="fas fa-download"></i> Export Training Data
                </button>
            </div>

            <div class="training-filters">
                <h3>Filters</h3>
                <div class="filter-group">
                    <label for="approved">Approval Status</label>
                    <select name="approved" id="approved" class="filter-select">
                        <option value="all">All</option>
                        <option value="1">Approved</option>
                        <option value="0">Not Approved</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="dateRange">Date Range</label>
                    <select name="dateRange" id="dateRange" class="filter-select">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="training-data-container" style="flex: 1;">
            <div class="training-data-header">
                <input type="text" id="searchTraining" placeholder="Search training data...">
                <div class="header-actions">
                    <button class="refresh-btn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="training-data-table-wrapper">
                <table class="training-data-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Approved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Training data rows will be dynamically added here -->
                    </tbody>
                </table>
            </div>

            <div class="training-data-footer">
                <div class="pagination">
                    <!-- Pagination buttons will be dynamically added here -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Chat Area -->
    <div class="chatbot-wrapper">
        <div class="chatbot-container">
            <!-- Hidden CSRF token for feedback functionality -->
            <input type="hidden" name="gibbonCSRFToken" value="<?php echo $_SESSION[$guid]['gibbonCSRFToken']; ?>">
            <div class="chatbot-header">
                <div class="chatbot-title">AI Teaching Assistant</div>
                <div class="chat-controls">
                    <?php if ($isAdmin): ?>
                    <div class="training-toggle">
                        <label class="switch">
                            <input type="checkbox" id="trainingMode" <?php echo isset($_GET['training']) ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                        </label>
                        <span>Training Mode</span>
                    </div>
                    <?php endif; ?>
                    <button class="control-btn manage-chats-btn" title="Manage Chats">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="control-btn save-chat-btn" title="Save Chat">
                        <i class="fas fa-save"></i>
                    </button>
                    <button class="control-btn new-chat-btn" title="New Chat">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="chat-messages"></div>
            <div class="chat-input">
                <textarea placeholder="Type your message here..." rows="1"></textarea>
                <button type="button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.chatbot-container {
    max-width: 900px;
    margin: 20px auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    background: white;
    display: flex;
    flex-direction: column;
    height: 600px;
}

.chatbot-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.header-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.chat-controls button {
    margin-right: 10px;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    background: #007bff;
    color: white;
    cursor: pointer;
    transition: background 0.2s;
}

.chat-controls button:hover {
    background: #0056b3;
}

.training-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.training-mode-toggle {
    display: flex;
    align-items: center;
}

.training-mode-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
    margin-left: 8px;
    border-radius: 20px;
    background-color: #ccc;
    transition: background-color 0.4s;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 20px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #2196F3;
}

input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.training-mode-tooltip {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    z-index: 1000;
    animation: fadeInOut 2s ease-in-out;
}

@keyframes fadeInOut {
    0% { opacity: 0; }
    15% { opacity: 1; }
    85% { opacity: 1; }
    100% { opacity: 0; }
}

.error-container {
    padding: 10px;
    margin: 10px;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    color: #856404;
}

.chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 20px;
}

.message {
    margin-bottom: 15px;
    max-width: 80%;
    clear: both;
}

.user-message {
    float: right;
}

.bot-message {
    float: left;
}

.message-content {
    padding: 10px 15px;
    border-radius: 15px;
    background: #f1f0f0;
    position: relative;
}

.user-message .message-content {
    background:rgb(97, 146, 199);
    color: white;
}

.bot-message .message-content {
    background: #f1f0f0;
}

.chat-input {
    padding: 15px;
    border-top: 1px solid #ddd;
    display: flex;
    align-items: flex-end;
    background: white;
    border-radius: 0 0 8px 8px;
}

.chat-input textarea {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: none;
    margin-right: 10px;
    min-height: 20px;
    max-height: 100px;
}

.chat-input button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    background: #007bff;
    color: white;
    cursor: pointer;
    transition: background 0.2s;
}

.chat-input button:hover {
    background: #0056b3;
}

.typing-indicator {
    display: flex;
    padding: 6px;
    justify-content: center;
    align-items: center;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    margin: 0 2px;
    background-color: #90949c;
    display: block;
    border-radius: 50%;
    opacity: 0.4;
    animation: typing 1s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 200ms; }
.typing-indicator span:nth-child(2) { animation-delay: 300ms; }
.typing-indicator span:nth-child(3) { animation-delay: 400ms; }

@keyframes typing {
    0% { transform: translateY(0px); }
    28% { transform: translateY(-6px); }
    44% { transform: translateY(0px); }
}

.chat-dialog {
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

.chat-dialog-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    min-width: 300px;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
}

.saved-chats-list {
    margin: 15px 0;
}

.saved-chat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 4px;
}

.chat-actions button {
    margin-left: 10px;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.chat-actions button:first-child {
    background: #28a745;
    color: white;
}

.chat-actions button:last-child {
    background: #dc3545;
    color: white;
}

.status-message {
    text-align: center;
    padding: 10px;
    margin: 10px 0;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    color: #155724;
}

.training-upload {
    margin-left: 15px;
    display: none;
}

.training-upload.visible {
    display: block;
}

.upload-controls {
    display: flex;
    gap: 10px;
}

.upload-btn, .submit-training-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    transition: background-color 0.2s;
}

.upload-btn {
    background: #28a745;
    color: white;
}

.upload-btn:hover {
    background: #218838;
}

.submit-training-btn {
    background: #007bff;
    color: white;
}

.submit-training-btn:hover {
    background: #0056b3;
}

.selected-file {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.csv-preview-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 80%;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 1000;
}

.csv-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.csv-preview-table th,
.csv-preview-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.csv-preview-table th {
    background: #f8f9fa;
}

.csv-preview-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

.csv-preview-actions button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.csv-preview-actions .confirm-btn {
    background: #28a745;
    color: white;
}

.csv-preview-actions .cancel-btn {
    background: #dc3545;
    color: white;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.error-message {
    padding: 15px;
    margin: 10px;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    color: #856404;
}

.welcome-message {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.welcome-message h3 {
    color: #007bff;
    margin-top: 0;
    margin-bottom: 10px;
}

.welcome-message ul {
    margin: 10px 0;
    padding-left: 20px;
}

.welcome-message li {
    margin: 5px 0;
    color: #495057;
}

.welcome-message p {
    margin: 10px 0;
    color: #212529;
}

.message-content {
    padding: 15px;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.bot-message .message-content {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.user-message .message-content {
    background: #007bff;
    color: white;
}

.error-message {
    padding: 10px 15px;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    color: #856404;
    margin: 10px 0;
}

.chatInterface {
    max-width: 800px;
    margin: 20px auto;
}

.message-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    resize: vertical;
}

.chat-history {
    max-width: 800px;
    margin: 20px auto;
    height: 500px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    background: #f8f9fa;
}

.user-message,
.assistant-message,
.system-message {
    margin: 10px 0;
    padding: 10px 15px;
    border-radius: 10px;
    max-width: 80%;
    word-wrap: break-word;
}

.user-message {
    background: #007bff;
    color: white;
    margin-left: auto;
}

.assistant-message {
    background: white;
    border: 1px solid #ddd;
}

.system-message {
    background: #f8d7da;
    color: #721c24;
    text-align: center;
    max-width: 100%;
    margin: 10px auto;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const chatHistory = document.querySelector(".chat-messages");
    const messageInput = document.querySelector(".chat-input textarea");
    const sendButton = document.querySelector(".chat-input button");
    const clearButton = document.querySelector(".control-btn[title='Clear Chat']");
    const saveButton = document.querySelector(".control-btn[title='Save Chat']");
    const newButton = document.querySelector(".control-btn[title='New Chat']");
    const trainingMode = document.querySelector("#trainingMode");

    function addMessageToChat(role, message) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `message ${role}-message`;
        const contentDiv = document.createElement("div");
        contentDiv.className = "message-content";
        contentDiv.textContent = message;
        messageDiv.appendChild(contentDiv);
        chatHistory.appendChild(messageDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }

    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            addMessageToChat("user", message);
            messageInput.value = "";
            
            // Show loading indicator
            const loadingDiv = document.createElement("div");
            loadingDiv.className = "typing-indicator";
            for (let i = 0; i < 3; i++) {
                const span = document.createElement("span");
                loadingDiv.appendChild(span);
            }
            chatHistory.appendChild(loadingDiv);
            
            // Send to server
            fetch("' . $_SESSION[$guid]['absoluteURL'] . '/modules/ChatBot/ajax/chat.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    message: message,
                    training_mode: trainingMode.checked,
                    gibbonPersonID: "' . $_SESSION[$guid]['gibbonPersonID'] . '"
                })
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                const loadingIndicator = document.querySelector(".typing-indicator");
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                
                // Add AI response
                addMessageToChat("bot", data.response);
            })
            .catch(error => {
                console.error("Error:", error);
                // Remove loading indicator
                const loadingIndicator = document.querySelector(".typing-indicator");
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                const errorDiv = document.createElement("div");
                errorDiv.className = "error-message";
                errorDiv.textContent = "Error: Could not get response from the AI assistant.";
                chatHistory.appendChild(errorDiv);
            });
        }
    }

    // Event listeners
    sendButton.addEventListener("click", sendMessage);
    
    messageInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    clearButton.addEventListener("click", function() {
        chatHistory.innerHTML = '<div class="message system-message"><div class="message-content">Chat cleared. How can I help you?</div></div>';
    });

    saveButton.addEventListener("click", function() {
        // Implement chat saving functionality
        alert("Save chat functionality will be implemented soon.");
    });

    newButton.addEventListener("click", function() {
        chatHistory.innerHTML = '<div class="message system-message"><div class="message-content">Starting a new chat. How can I help you?</div></div>';
    });
});
</script>
