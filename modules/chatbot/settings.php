<?php
/*
Gibbon, Flexible & Open School System
Copyright © 2010-2023 Craig Rayner, Sandra Kuipers & Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

// Use statements for Gibbon classes
use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

// Set page breadcrumb
$page->breadcrumbs->add(__('ChatBot Settings'));

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/settings.php')) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Get setting gateway from container
$settingGateway = $container->get(SettingGateway::class);

// Debug log for form submission
if (isset($_POST['submit'])) {
    error_log('ChatBot Settings - Form submitted');
    error_log('POST data: ' . print_r($_POST, true));
}

// Ensure settings exist
try {
    $sql = "INSERT IGNORE INTO gibbonSetting (scope, name, nameDisplay, description, value) VALUES 
            ('ChatBot', 'deepseek_api_key', 'DeepSeek API Key', 'API key for DeepSeek AI service', ''),
            ('ChatBot', 'model_name', 'Model Name', 'DeepSeek model name', 'deepseek-chat'),
            ('ChatBot', 'max_tokens', 'Maximum Tokens', 'Maximum number of tokens for AI responses', '2000')";
    $connection2->query($sql);
} catch (PDOException $e) {
    error_log('ChatBot Settings - Error ensuring settings exist: ' . $e->getMessage());
}

// Handle form submissions
if (isset($_POST['submit'])) {
    // Get form values
    $apiKey = $_POST['deepseek_api_key'] ?? '';
    $modelName = $_POST['model_name'] ?? 'deepseek-chat';
    $maxTokens = $_POST['max_tokens'] ?? '2000';
    
    // Debug log for API key
    error_log('ChatBot Settings - Received API key (length): ' . strlen($apiKey));
    
    // Validate input
    $maxTokens = intval($maxTokens);
    if ($maxTokens < 1 || $maxTokens > 8192) {
        $maxTokens = 2000;
    }
    
    try {
        // Begin transaction
        $connection2->beginTransaction();

        // Update API key if provided and not the placeholder value
        if (!empty($apiKey) && $apiKey !== '••••••••••••••••••••••••••') {
            error_log('ChatBot Settings - Attempting to save API key');
            
            // First try using SettingGateway
            try {
                $settingGateway->updateSettingByScope('ChatBot', 'deepseek_api_key', $apiKey);
                error_log('ChatBot Settings - API Key updated via SettingGateway');
            } catch (Exception $e) {
                error_log('ChatBot Settings - SettingGateway update failed: ' . $e->getMessage());
                
                // Fallback to direct SQL
                $sql = "UPDATE gibbonSetting SET value = :value 
                       WHERE scope = 'ChatBot' AND name = 'deepseek_api_key'";
                $stmt = $connection2->prepare($sql);
                $stmt->bindValue(':value', $apiKey);
                $stmt->execute();
                error_log('ChatBot Settings - API Key updated via direct SQL');
            }
            
            // Verify the update
            $verifySql = "SELECT value FROM gibbonSetting 
                        WHERE scope = 'ChatBot' AND name = 'deepseek_api_key'";
            $verifyResult = $connection2->query($verifySql);
            $verifyData = $verifyResult->fetch();
            error_log('ChatBot Settings - API Key verification: ' . print_r($verifyData, true));
        }

        // Update model name
        $sql = "UPDATE gibbonSetting SET value = :value 
               WHERE scope = 'ChatBot' AND name = 'model_name'";
        $stmt = $connection2->prepare($sql);
        $stmt->bindValue(':value', $modelName);
        $stmt->execute();

        // Update max tokens
        $sql = "UPDATE gibbonSetting SET value = :value 
               WHERE scope = 'ChatBot' AND name = 'max_tokens'";
        $stmt = $connection2->prepare($sql);
        $stmt->bindValue(':value', $maxTokens);
        $stmt->execute();

        // Commit transaction
        $connection2->commit();
        $page->addSuccess(__('Settings saved successfully.'));
        
        // Force reload settings from database
        try {
            $reloadSql = "SELECT * FROM gibbonSetting WHERE scope = 'ChatBot'";
            $reloadResult = $connection2->query($reloadSql);
            $currentSettings = $reloadResult->fetchAll();
            error_log('ChatBot Settings - Current settings after save: ' . print_r($currentSettings, true));
        } catch (Exception $e) {
            error_log('ChatBot Settings - Error reloading settings: ' . $e->getMessage());
        }
        
    } catch (PDOException $e) {
        $connection2->rollBack();
        error_log('ChatBot Settings - Database error: ' . $e->getMessage());
        error_log('SQL State: ' . $e->getCode());
        $page->addError(__('Database error') . ': ' . $e->getMessage());
    }
}

// Get current settings
try {
    // Get API key
    $apiKeyQuery = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='deepseek_api_key'");
    $apiKeyQuery->execute();
    $apiKeyResult = $apiKeyQuery->fetch(PDO::FETCH_ASSOC);
    $apiKey = $apiKeyResult ? $apiKeyResult['value'] : '';
    error_log('ChatBot Settings - Current API Key exists: ' . (!empty($apiKey) ? 'Yes' : 'No'));
    
    // Get model name
    $modelQuery = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='model_name'");
    $modelQuery->execute();
    $modelResult = $modelQuery->fetch(PDO::FETCH_ASSOC);
    $modelName = $modelResult ? $modelResult['value'] : 'deepseek-chat';
    
    // Get max tokens
    $tokensQuery = $connection2->prepare("SELECT value FROM gibbonSetting WHERE scope='ChatBot' AND name='max_tokens'");
    $tokensQuery->execute();
    $tokensResult = $tokensQuery->fetch(PDO::FETCH_ASSOC);
    $maxTokens = $tokensResult ? $tokensResult['value'] : '2000';
    
} catch (PDOException $e) {
    error_log('ChatBot Settings - Error retrieving settings: ' . $e->getMessage());
    $apiKey = '';
    $modelName = 'deepseek-chat';
    $maxTokens = '2000';
}

// Create form
$form = Form::create('chatbotSettings', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/ChatBot/settings.php');
$form->setTitle(__('ChatBot Settings'));
$form->addHiddenValue('address', $_SESSION[$guid]['address']);

// API Settings section
$form->addRow()->addHeading(__('API Settings'));

// DeepSeek API Key field
$row = $form->addRow();
$row->addLabel('deepseek_api_key', __('DeepSeek API Key'));
$password = $row->addPassword('deepseek_api_key')
    ->maxLength(255);

// Show placeholder bullets if API key exists (without setting the value)
if (!empty($apiKey)) {
    $password->setAttribute('placeholder', '••••••••••••••••••••••••••');
} else {
    $password->setAttribute('placeholder', __('Enter API key'));
}
if (!empty($_POST['deepseek_api_key'])) {
    // Update the API key
    $apiKey = $_POST['deepseek_api_key'];
    $settingGateway->updateSettingByScope('ChatBot', 'deepseek_api_key', $apiKey);
}

// Model Name field
$row = $form->addRow();
$row->addLabel('model_name', __('Model Name'));
$row->addTextField('model_name')
    ->setValue($modelName)
    ->required()
    ->maxLength(100)
    ->setTitle(__('The DeepSeek model to use for AI responses'));

// Max Tokens field
$row = $form->addRow();
$row->addLabel('max_tokens', __('Maximum Tokens'));
$row->addNumber('max_tokens')
    ->setValue($maxTokens)
    ->minimum(1)
    ->maximum(8192)
    ->required()
    ->setTitle(__('Maximum number of tokens for AI responses'));

// Submit button
$row = $form->addRow();
$row->addFooter();
$row->addSubmit();

echo $form->getOutput();

// Add help text
echo '<div class="message emphasis">';
echo '<p>' . __('Note: You need to obtain a DeepSeek API key to use the ChatBot. Get one from') . ' <a href="https://platform.deepseek.com" target="_blank">platform.deepseek.com</a></p>';
echo '</div>';

// Add API key testing link
echo '<div class="message" style="border-left: 4px solid #2a7fff;">';
echo '<p><strong>' . __('Having API key problems?') . '</strong> ' . __('Use our') . ' <a href="' . $_SESSION[$guid]['absoluteURL'] . '/modules/ChatBot/simple_api_test.php" target="_blank" style="color:#2a7fff; font-weight:bold;">' . __('API Key Testing Tool') . ' <i class="fas fa-external-link-alt fa-xs"></i></a> ' . __('to verify your key and update it directly.') . '</p>';
echo '</div>';