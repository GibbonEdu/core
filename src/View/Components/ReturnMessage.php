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

namespace Gibbon\View\Components;

/**
 * Return Message.
 *
 * @version v22
 * @since   v22
 */
class ReturnMessage
{
    protected $editLink = '';
    protected $returns = []; 

    /**
     * Add default returns.
     */
    public function __construct()
    {
        $this->addReturns([ 
            //Successes
            'success0' => __('Your request was completed successfully.'),
            'success1' => __('Password reset was successful: you may now log in.'),
            'success5' => __('Your request has been successfully started as a background process. It will continue to run on the server until complete and you will be notified of any errors.'),
            'successa' => __('Your account has been successfully updated. You can now continue to use the system as per normal.'),
            
            //Errors
            'error0' => __('Your request failed because you do not have access to this action.'),
            'error1' => __('Your request failed because your inputs were invalid.'),
            'error2' => __('Your request failed due to a database error.'),
            'error3' => __('Your request failed because your inputs were invalid.'),
            'error4' => __('Your request failed because your passwords did not match.'),
            'error5' => __('Your request failed because there are no records to show.'),
            'error6' => __('Your request was completed successfully, but there was a problem saving some uploaded files.'),
            'error7' => __('Your request failed because some required values were not unique.'),
            'error8' => __('Your request failed because the link is invalid or has expired.'),

            //Warnings
            'warning0' => __('Your optional extra data failed to save.'),
            'warning1' => __('Your request was successful, but some data was not properly saved.'),
            'warning2' => __('Your request was successful, but some data was not properly deleted.'),
            'warning3' => __('Your request was successful but the emojis and symbols in your text have been removed due to compatibility constraints.'),
        ]);
    }

    /**
     * Sets the Edit Link for the return message.
     * @param string $editLink
     */
    public function setEditLink(string $editLink)
    {
        $this->editLink = $editLink;
    }

    /**
     * Registers a new return with the given key and message. 
     *
     * @param string $return
     * @param string $message
     */
    public function addReturn(string $return, string $message)
    {
        $this->returns[$return] = $message;
    }

    /**
     * Registers an array of new returns
     *
     * @param array $returns
     */
    public function addReturns(array $returns)
    {
        $this->returns = array_replace($this->returns, $returns);
    }

    /**
     * Process a return string and get back a context and text.
     *
     * @param string $return
     * @return array
     */
    public function process(string $return): array
    {
        if (isset($return)) {
            $returnMessage = $this->returns[$return] ?? __('Unknown Return');
            $returnClass = 'error';

            $classes = ['warning', 'success', 'message'];
            foreach ($classes as $class) {
                //TODO: Replace this with str_starts_with when PHP8 is required
                if (substr($return, 0, strlen($class)) == $class) {
                    $returnClass = $class;
                    break;
                }
            }

            if ($class == 'success' && !empty($this->editLink)) {
                $returnMessage .= ' '.sprintf(__('You can edit your newly created record %1$shere%2$s.'), "<a href='$this->editLink'>", '</a>');
            }

            return ['context' => $returnClass, 'text' => $returnMessage];
        }
        return null;
    }
}
