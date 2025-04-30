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

use Gibbon\Forms\Prefab\MenuItems;

$menu = new MenuItems($container->get('page'));
$menu->addItem('AI Teaching Assistant', ['uri' => '/modules/ChatBot/chatbot.php'])
     ->addItem('Assessment Integration', ['uri' => '/modules/ChatBot/assessment_integration.php'])
     ->addItem('Learning Management', ['uri' => '/modules/ChatBot/learning_management.php'])
     ->addItem('Settings', ['uri' => '/modules/ChatBot/settings.php'])
   //  ->addItem('Feedback Analytics', ['uri' => '/modules/ChatBot/feedback.php'])
    // ->addItem('Check Feedback DB', ['uri' => '/modules/ChatBot/db_check_feedback.php'])
    // ->addItem('Debug Feedback Storage', ['uri' => '/modules/ChatBot/debug_feedback_storage.php'])
     ->addItem('AI Learning System', ['uri' => '/modules/ChatBot/ai_learning.php']);

echo $menu->getOutput(); 