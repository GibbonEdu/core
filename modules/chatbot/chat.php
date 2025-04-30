<?php
require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Initialize the page
$page = $container->get('page');
$session = $container->get('session');

// Set page title
$page->setTitle(__('ChatBot'));

// Add CSS and JS files with absolute URLs
$page->addStylesheet($session->get('absoluteURL') . '/modules/ChatBot/css/chatbot.css');
$page->addScript($session->get('absoluteURL') . '/modules/ChatBot/js/chatbot.js');

// Check access
if (!$session->has('gibbonPersonID')) {
    // Redirect to login page
    header('Location: ' . $session->get('absoluteURL') . '/index.php?q=login.php');
    exit;
}

// Get user role from database
$sql = "SELECT gibbonRole.category 
        FROM gibbonPerson 
        JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) 
        WHERE gibbonPersonID=:gibbonPersonID";
$result = $connection2->prepare($sql);
$result->execute(['gibbonPersonID' => $session->get('gibbonPersonID')]);

if ($result->rowCount() < 1) {
    // Handle error
    die('User not found');
}

$roleCategory = $result->fetchColumn();

// Only allow staff and admin access
if ($roleCategory == 'Student' || $roleCategory == 'Parent') {
    // Handle unauthorized access
    die('Access denied - insufficient privileges');
}

?>

<div class="chatbot-wrapper">
    <div class="chatbot-container">
        <div class="chatbot-header">
            <div class="chatbot-title">AI Teaching Assistant</div>
            <div class="chat-controls">
                <button class="manage-chats-btn" title="Manage saved chats">
                    <i class="fas fa-folder"></i> Manage Chats
                </button>
                <button class="save-chat-btn" title="Save current chat">
                    <i class="fas fa-save"></i> Save Chat
                </button>
                <button class="new-chat-btn" title="Start a new chat">
                    <i class="fas fa-plus"></i> New Chat
                </button>
                <button class="control-btn" title="Clear Chat">
                    <i class="fas fa-trash"></i> Clear Chat
                </button>
            </div>
            <div class="training-toggle">
                <label class="training-mode-btn" title="Toggle Training Mode">
                    <i class="fas fa-graduation-cap"></i>
                    Training Mode
                    <div class="toggle-switch">
                        <input type="checkbox" id="trainingMode">
                        <span class="toggle-slider"></span>
                    </div>
                </label>
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

<!-- Load Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- Initialize ChatBot -->
<script type="text/javascript">
// Wait for both DOM and ChatBot script to load
let chatBotScriptLoaded = false;
let domLoaded = false;

function initializeChatBot() {
    if (chatBotScriptLoaded && domLoaded) {
        console.log('Initializing ChatBot...');
        try {
            // Initialize with API endpoints
            window.chatBot = new ChatBot({
                apiEndpoint: '<?php echo $session->get('absoluteURL'); ?>/chhs/modules/ChatBot/api/chat.php',
                manageEndpoint: '<?php echo $session->get('absoluteURL'); ?>/chhs/modules/ChatBot/api/manage.php',
                trainingEndpoint: '<?php echo $session->get('absoluteURL'); ?>/chhs/modules/ChatBot/api/train.php'
            });
            console.log('ChatBot initialized successfully');
            
            // Add training mode toggle handler
            const trainingToggle = document.getElementById('trainingMode');
            if (trainingToggle) {
                trainingToggle.addEventListener('change', function() {
                    const statusMessage = document.createElement('div');
                    statusMessage.className = 'status-message';
                    statusMessage.textContent = `Training Mode is now ${this.checked ? 'active' : 'inactive'}`;
                    document.querySelector('.chat-messages').appendChild(statusMessage);
                    setTimeout(() => statusMessage.remove(), 3000);
                });
            }
        } catch (error) {
            console.error('Error initializing ChatBot:', error);
        }
    }
}

// Listen for ChatBot script load
document.querySelector('script[src*="chatbot.js"]').addEventListener('load', function() {
    console.log('ChatBot script loaded');
    chatBotScriptLoaded = true;
    initializeChatBot();
});

// Listen for DOM load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    domLoaded = true;
    initializeChatBot();
});
</script> 