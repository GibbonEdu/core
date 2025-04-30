-- Create saved chats table
CREATE TABLE IF NOT EXISTS `chatbot_saved_chats` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `title` varchar(255) NOT NULL,
    `messages` text NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_chatbot_saved_chats_user` FOREIGN KEY (`user_id`) REFERENCES `gibbonPerson` (`gibbonPersonID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) 
VALUES 
    ('ChatBot', 'deepseek_api_key', 'DeepSeek API Key', 'API key for DeepSeek AI service', ''),
    ('ChatBot', 'model_name', 'Model Name', 'DeepSeek model name (e.g., deepseek-chat)', 'deepseek-chat'),
    ('ChatBot', 'max_tokens', 'Max Tokens', 'Maximum number of tokens to generate in responses', '4000'); 
