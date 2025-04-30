<?php
include '../../gibbon.php';

if (! isActionAccessible($guid, $connection2, '/modules/ChatBot/index.php')) {
    // Access denied
    $page->addError($session->get('strAccessDenied'));
} else {
    // Proceed!
    $page->breadcrumbs->add('AI Teaching Assistant');
    
    // Add module CSS and JS
    $page->stylesheets->add('chatbot', 'modules/ChatBot/css/chatbot.css');
    $page->scripts->add('chatbot', 'modules/ChatBot/js/chatbot.js');
    
    // Get API key from settings
    $apiKey = getSettingByScope($connection2, 'ChatBot', 'deepseek_api_key');
    
    ?>
    <div class="content-wrapper">
        <div class="chatbot-page">
            <div class="chatbot-header">
                <h2>AI Teaching Assistant</h2>
                <div class="training-mode-toggle">
                    <input type="checkbox" id="trainingMode">
                    <label for="trainingMode">Training Mode</label>
                </div>
            </div>
            
            <div class="chatbot-container">
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
    
    <script>
    window.addEventListener('load', function() {
        window.chatBot = new ChatBot({
            apiEndpoint: '<?php echo $session->get('absoluteURL'); ?>/modules/ChatBot/src',
            isTrainingMode: false,
            isAdmin: <?php echo $session->get('isAdmin') ? 'true' : 'false'; ?>
        });
    });
    </script>
    <?php
}
?> 