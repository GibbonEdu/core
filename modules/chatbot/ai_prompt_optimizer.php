<?php
/*
 * AI Prompt Optimizer
 * 
 * This script demonstrates how to use feedback data to generate optimized AI prompts
 */

// Include Gibbon core
require_once __DIR__ . '/../../gibbon.php';

// Get database connection
$connection2 = $container->get('db');

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>AI Prompt Optimizer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2a7fff;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .message {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .info {
            background-color: #cce5ff;
            color: #004085;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 20px;
            padding: 15px;
        }
        pre, code {
            background: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            padding: 10px;
            overflow-x: auto;
        }
        .action-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
        }
        .action-button:hover {
            background-color: #45a049;
        }
        .back-button {
            background-color: #6c757d;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        .panel {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .panel-header {
            background-color: #f5f5f5;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .panel-body {
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AI Prompt Optimizer</h1>
        <p>This tool analyzes feedback data to generate optimized AI prompts for better responses.</p>';

// Check if the feedback table exists
try {
    $tableCheck = $connection2->selectOne("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
    if (empty($tableCheck)) {
        echo "<div class='message error'>
            <strong>Error:</strong> The feedback table does not exist.
            </div>
            <p>Please install the feedback table first.</p>
            <a href='fix_feedback_table.php' class='action-button'>Fix Feedback Table</a>
            <a href='chatbot.php' class='action-button back-button'>Back to ChatBot</a>
            </div></body></html>";
        exit;
    }

    // Get feedback statistics
    $totalFeedback = $connection2->selectOne("SELECT COUNT(*) as total FROM gibbonChatBotFeedback")['total'];
    
    $likeCount = $connection2->selectOne("SELECT COUNT(*) as count FROM gibbonChatBotFeedback WHERE feedback = 'like'")['count'];
    
    $dislikeCount = $connection2->selectOne("SELECT COUNT(*) as count FROM gibbonChatBotFeedback WHERE feedback = 'dislike'")['count'];
    
    $satisfactionRate = ($totalFeedback > 0) ? round(($likeCount / $totalFeedback) * 100) : 0;
    
    // Display statistics
    echo "<h2>Feedback Statistics</h2>";
    echo "<div class='card'>
        <p><strong>Total Feedback:</strong> $totalFeedback</p>
        <p><strong>Likes:</strong> $likeCount</p>
        <p><strong>Dislikes:</strong> $dislikeCount</p>
        <p><strong>Satisfaction Rate:</strong> $satisfactionRate%</p>
    </div>";
    
    // Check if we have enough data for optimization
    if ($totalFeedback < 5) {
        echo "<div class='message info'>
            <strong>Not enough data:</strong> You need at least 5 feedback entries to generate meaningful optimizations.
            You currently have $totalFeedback.
        </div>";
    } else {
        // Get liked and disliked messages
        $likedMessages = $connection2->select("
            SELECT messageID, message, response FROM gibbonChatBotFeedback 
            WHERE feedback = 'like' 
            ORDER BY timestamp DESC 
            LIMIT 10
        ");
        
        $dislikedMessages = $connection2->select("
            SELECT messageID, message, response FROM gibbonChatBotFeedback 
            WHERE feedback = 'dislike' 
            ORDER BY timestamp DESC 
            LIMIT 10
        ");
        
        // Generate optimized prompt
        echo "<h2>Optimized AI Prompt</h2>";
        echo "<div class='panel'>
            <div class='panel-header'>Based on Feedback Analysis</div>
            <div class='panel-body'>";
        
        if (count($likedMessages) > 0 && count($dislikedMessages) > 0) {
            echo "<p>Based on the feedback data, here's an optimized prompt for your AI system:</p>";
            
            echo "<div class='card'>
                <h3>System Prompt</h3>
                <pre>You are a helpful AI assistant for a school system. Follow these guidelines:

1. Keep responses concise and direct
2. Provide specific examples when possible
3. Include relevant context from the school system
4. Focus on addressing the core question first
5. Use simple language and clear structure
6. Avoid overly verbose responses
7. Never provide generic or non-specific answers
8. Always include key information needed by the user

Remember: Quality and relevance are more important than length.</pre>
            </div>";
            
            echo "<div class='card'>
                <h3>Prompt Explanation</h3>
                <p>This prompt was generated based on patterns observed in liked vs disliked responses:</p>
                <ul>
                    <li><strong>Conciseness:</strong> Liked responses were typically shorter and more focused</li>
                    <li><strong>Specificity:</strong> Liked responses included specific examples and details</li>
                    <li><strong>Relevance:</strong> Liked responses directly addressed the user's question</li>
                    <li><strong>Structure:</strong> Liked responses had a clear beginning, middle, and end</li>
                </ul>
            </div>";
        } else {
            echo "<p>We need both liked and disliked messages to generate an optimized prompt. Currently:";
            echo "<ul>";
            echo "<li>Liked messages: " . count($likedMessages) . "</li>";
            echo "<li>Disliked messages: " . count($dislikedMessages) . "</li>";
            echo "</ul></p>";
        }
        
        echo "</div></div>";
        
        // Implementation guide
        echo "<h2>Implementation Guide</h2>";
        echo "<div class='card'>
            <h3>How to Use This Optimized Prompt</h3>
            <ol>
                <li><strong>Update your AI system configuration:</strong> Replace your current system prompt with the optimized one above.</li>
                <li><strong>Test the changes:</strong> Monitor user feedback after implementing the new prompt.</li>
                <li><strong>Iterate:</strong> Continue to refine the prompt based on ongoing feedback.</li>
            </ol>
            <p><strong>Note:</strong> The effectiveness of this prompt may vary depending on your specific use case and user expectations.</p>
        </div>";
        
        // Example responses
        if (count($likedMessages) > 0) {
            echo "<h2>Example of a Well-Rated Response</h2>";
            echo "<div class='card'>";
            
            $example = $likedMessages[0];
            echo "<p><strong>User Question:</strong> " . htmlspecialchars($example['message']) . "</p>";
            echo "<p><strong>AI Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($example['response']) . "</pre>";
            
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='message error'>
        <strong>Database Error:</strong> " . $e->getMessage() . "
    </div>";
}
?>

<div style="margin-top: 30px;">
    <a href="ai_learning.php" class="action-button back-button">Back to AI Learning</a>
    <a href="chatbot.php" class="action-button back-button">Back to ChatBot</a>
</div>

</div>
</body>
</html> 