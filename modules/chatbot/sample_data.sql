INSERT INTO gibbonChatBotFeedback (user_message, ai_response, feedback_type, feedback_text) VALUES 
('How do I solve quadratic equations?', 'To solve quadratic equations, you can use: 1) Factoring 2) Quadratic formula 3) Completing the square', 'like', 'Very helpful explanation'),
('What is photosynthesis?', 'Photosynthesis is the process by which plants convert sunlight into energy', 'like', 'Clear and concise'),
('Explain Newton\'s laws', 'Newton\'s three laws of motion are fundamental principles of physics...', 'like', 'Good detailed response'),
('What is DNA?', 'DNA is the molecule that carries genetic information...', 'dislike', 'Need more detail'),
('How to write an essay?', 'An essay typically consists of an introduction, body paragraphs, and conclusion...', 'like', 'Great structure explanation');

INSERT INTO gibbonChatBotTraining (question, answer, approved) VALUES 
('What is the scientific method?', 'The scientific method is a systematic approach to investigation that includes observation, hypothesis formation, experimentation, and conclusion drawing.', 1),
('How do you calculate area of a circle?', 'The area of a circle is calculated using the formula A = πr², where r is the radius of the circle.', 1),
('What are the main parts of a plant?', 'The main parts of a plant are roots, stem, leaves, flowers, fruits, and seeds. Each part has specific functions.', 1); 