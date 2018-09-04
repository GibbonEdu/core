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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
//$student, $staff, $parent, $other, $applicationForm, $dataUpdater should all be TRUE/FALSE/NULL
//Returns query result
function getCustomFields($connection2, $guid, $student = null, $staff = null, $parent = null, $other = null, $applicationForm = null, $dataUpdater = null)
{
    $return = false;

    try {
        $data = array();
        $where = '';
        $whereInner = '';
        if ($student) {
            $data['student'] = $student;
            $whereInner .= 'activePersonStudent=:student OR ';
        }
        if ($staff) {
            $data['staff'] = $staff;
            $whereInner .= 'activePersonStaff=:staff OR ';
        }
        if ($parent) {
            $data['parent'] = $parent;
            $whereInner .= 'activePersonParent=:parent OR ';
        }
        if ($other) {
            $data['other'] = $other;
            $whereInner .= 'activePersonOther=:other OR ';
        }
        if ($applicationForm) {
            $data['applicationForm'] = $applicationForm;
            $where .= ' AND activeApplicationForm=:applicationForm';
        }
        if ($dataUpdater) {
            $data['dataUpdater'] = $dataUpdater;
            $where .= ' AND activeDataUpdater=:dataUpdater';
        }

        if ($whereInner != '') {
            $whereInner = ' AND ('.substr($whereInner, 0, -4).') ';
        }

        $sql = "SELECT * FROM gibbonPersonField WHERE active='Y' $whereInner $where";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result !== false) {
        $return = $result;
    }

    return $return;
}

//$row is the database row draw from gibbonPersonField, $value is the current value of that field
function renderCustomFieldRow($connection2, $guid, $row, $value = null, $fieldNameSuffix = '', $rowClass = '', $ignoreRequired = false)
{
    $return = '';

    $return .= "<tr class='$rowClass'>";
    $return .= '<td>';
    $return .= '<b>'.__($guid, $row['name']).'</b>';
    if ($row['required'] == 'Y' and $ignoreRequired == false) {
        $return .= ' *';
    }
    if ($row['description'] == 'Y') {
        $return .= '<br/>';
        $return .= "<span style='font-size: 90%'><i>".__($guid, $row['description']).'<br/>';
        $return .= '</span>';
    }
    $return .= '</td>';
    $return .= '<td class="right">';
    if ($row['type'] == 'varchar') {
        $return .= '<input name="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'" id="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."\" maxlength='".$row['options']."' value=\"$value\" type=\"text\" style=\"width: 300px\">";
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                    $return .= '<script type="text/javascript">';
            $return .= 'var '.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."=new LiveValidation('".$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."');";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'.add(Validate.Presence);';
            $return .= '</script>';
        }
    } elseif ($row['type'] == 'text') {
        $return .= '<textarea name="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'" id="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."\" rows='".$row['options']."' style=\"width: 300px\">$value</textarea>";
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                    $return .= '<script type="text/javascript">';
            $return .= 'var '.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."=new LiveValidation('".$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."');";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'.add(Validate.Presence);';
            $return .= '</script>';
        }
    } elseif ($row['type'] == 'date') {
        $return .= '<input name="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'" id="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."\" maxlength='10' value=\"".dateConvertBack($guid, $value).'" type="text" style="width: 300px">';
        $return .= '<script type="text/javascript">';
        $return .= 'var custom'.$row['gibbonPersonFieldID']."=new LiveValidation('custom".$row['gibbonPersonFieldID']."');";
        $return .= 'custom'.$row['gibbonPersonFieldID'].'.add( Validate.Format, {pattern: ';
        if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
            $return .= "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
        } else {
            $return .= $_SESSION[$guid]['i18n']['dateFormatRegEx'];
        }
        $return .= ', failureMessage: "Use ';
        if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
            $return .= 'dd/mm/yyyy';
        } else {
            $return .= $_SESSION[$guid]['i18n']['dateFormat'];
        }
        $return .= '." } );';
        $return .= '</script>';
        $return .= '<script type="text/javascript">';
        $return .= '$(function() {';
        $return .= '$( "#custom'.$row['gibbonPersonFieldID'].'" ).datepicker();';
        $return .= '});';
        $return .= '</script>';
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                    $return .= '<script type="text/javascript">';
            $return .= 'var '.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."=new LiveValidation('".$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."');";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'.add(Validate.Presence);';
            $return .= '</script>';
        }
    } elseif ($row['type'] == 'url') {
        $return .= '<input name="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'" id="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."\" maxlength='255' value=\"$value\" type=\"text\" style=\"width: 300px\">";
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                    $return .= '<script type="text/javascript">';
            $return .= 'var '.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."=new LiveValidation('".$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."');";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http:// or https://\" } );";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'.add(Validate.Presence);';
            $return .= '</script>';
        }
    } elseif ($row['type'] == 'select') {
        $return .= '<select style="width: 302px" name="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'" id="'.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].'">';
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                        $return .= '<option value="Please select...">'.__($guid, 'Please select...').'</option>';
        } else {
            $return .= '<option value=""></option>';
        }
        $options = explode(',', $row['options']);
        foreach ($options as $option) {
            $selected = '';
            if (trim($option) == $value) {
                $selected = 'selected';
            }
            $return .= "<option $selected value=\"".trim($option).'">'.trim($option).'</option>';
        }

        $return .= '</select>';
        if ($row['required'] == 'Y' and $ignoreRequired == false) { //is required
                    $return .= '<script type="text/javascript">';
            $return .= 'var '.$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."=new LiveValidation('".$fieldNameSuffix.'custom'.$row['gibbonPersonFieldID']."');";
            $return .= $fieldNameSuffix.'custom'.$row['gibbonPersonFieldID'].".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: \"".__($guid, 'Select something!').'"});';
            $return .= '</script>';
        }
    }
    $return .= '</td>';
    $return .= '</tr>';

    return $return;
}
