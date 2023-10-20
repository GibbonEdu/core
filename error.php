<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
?>

<!DOCTYPE html>
<html lang="">
    <head>
        <title></title>

        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <meta http-equiv="content-language" content="{{ locale }}"/>
        <meta name="author" content="Ross Parker, International College Hong Kong"/>
        <meta name="robots" content="noindex"/>
        <meta name="Referrer‐Policy" value="no‐referrer | same‐origin"/>
        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>

        <link rel="stylesheet" href="./themes/Default/css/main.css" type="text/css" media="all">
        <link rel="stylesheet" href="./resources/assets/css/core.min.css" type="text/css" media="all">
        <link rel="stylesheet" href="./resources/assets/css/theme.min.css" type="text/css" media="all">
    </head>

    <body class="h-screen flex flex-col font-sans body-gradient-purple m-0 p-0" style="background: radial-gradient(80% 1000px at 20% 500px, #ef99c7 0%, #794d95 100%) no-repeat fixed ;">

        <div class="px-4 sm:px-6 lg:px-12 pb-24">
            <div id="header" class="relative flex justify-between items-center">

                <a id="header-logo" class="block my-4 max-w-xs sm:max-w-full leading-none" href="<?php echo (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . dirname($_SERVER['PHP_SELF'] ?? ''); ?>">
                    <img class="block max-w-full h-20 mt-4 mb-4" alt="DEMO Logo" src="./themes/Default/img/logo.png" style="max-height:100px;">
                </a>

                <div class="flex-grow flex items-center justify-end text-right text-sm text-purple-200">               
                </div> 
            </div>
        </div>

        <div id="wrapOuter" class="flex-1 pt-24 bg-transparent-100">
            <div id="wrap" class="px-0 sm:px-6 lg:px-12 -mt-48">
                <div id="content-wrap" class="relative w-full min-h-1/2 flex content-start flex-coll lg:flex-row clearfix">

                    <div id="content" class="max-w-full w-full shadow bg-white sm:rounded px-8 pt-2 pb-6">
                        <h1>
                            Oh no!<br/>
                        </h1>
                        <p>
                            <?php echo !empty($error) ? $error : 'Something has gone wrong: the Gibbons have escaped!' ?><br/>
                            <br/>
                            <?php echo !empty($message) ? $message : 'An error has occurred. This could mean a number of different things, but generally indicates that you have a misspelt address, or are trying to access a page that you are not permitted to access. If you cannot solve this problem by retyping the address, or through other means, please contact your system administrator.' ?><br/>
                        </p>
                        </body>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
