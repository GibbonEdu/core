-- Remove saved chats table
DROP TABLE IF EXISTS `chatbot_saved_chats`;

-- Remove module settings
DELETE FROM `gibbonSetting` WHERE `scope`='ChatBot'; 