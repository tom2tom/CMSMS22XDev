<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideUtils;

if (!isset($gCms)) exit;
/* allowed $params
 'gid' int
 'list' string
 'tplid' int (for list)
 'template_name' string (for list)
 'sheetid' int (for list)
 'stylesheet_name' string (for list)
*/
if (isset($params['list']) || empty($params['gid'])) {
    //absent or empty or '*' for all, or comma-separated ids
    if (isset($params['list'])) {
        if ($params['list'] != '*') {
            $gids = array_map(function($val) {
                return (int)trim($val);
            }, explode(',', $params['list']));
            $gids = array_filter($gids);
            $all = false;
        } else {
            $all = true;
        }
    } else {
        $all = true;
    }
    $list = true;
} else {
    $list = false;
}

if ($list) {
    // get all potentially-usable properties, some of them are not used by default
    if ($all) {
        $sql = 'SELECT id,name,revision,author,create_date,modified_date,COALESCE(modified_date,create_date,\'\') AS latest FROM '.CMS_DB_PREFIX.'module_userguide WHERE active!=0 AND admin=0 ORDER BY position,name';
    } else {
        $sql = 'SELECT id,name,revision,author,create_date,modified_date,COALESCE(modified_date,create_date,\'\') AS latest FROM '.CMS_DB_PREFIX.'module_userguide WHERE id IN('.implode(',', $gids).') AND active!=0 AND admin=0 ORDER BY position,name';
    }
    $data = $db->GetArray($sql);
    if (!$data) {
        if ($db->ErrorNo() > 0) {
            audit('', $this->GetName(), $db->ErrorMsg());
            echo 'Database error';
            return;
        }
    }

    if (isset($params['stylesheet_name'])) {
        $sht = CmsLayoutStylesheet::load(trim($params['stylesheet_name']));
        if (!is_object($sht)) {
            audit('', $this->GetName().':'.$params['stylesheet_name'], 'No matching userguide stylesheet found');
            echo 'No userguide stylesheet '.$params['stylesheet_name'].' found';
            return;
        }
    } elseif (isset($params['sheetid'])) {
        $sht = CmsLayoutStylesheet::load((int)$params['sheetid']);
        if (!is_object($sht)) {
            audit($params['sheetid'], $this->GetName(), 'No matching userguide stylesheet found');
            echo 'No userguide stylesheet '.$params['sheetid'].' found';
            return;
        }
    } else {
        $name = $this->GetPreference('listStyles');
        if ($name) {
            $sht = CmsLayoutStylesheet::load($name);
            if (!is_object($sht)) {
                audit('', $this->GetName().':listStyles-preference', 'No matching userguide stylesheet found');
                echo 'No userguide stylesheet '.$name.' found';
                return;
            }
        } else {
           $sht = null; // no object
        }
    }
    if ($sht) {
        // inject its contents into page head TODO some better approach
        $css = $sht->get_content();
        $min = UserGuideUtils::minCSS($css);
        echo <<<EOS
<script>
var ds = document.createElement('style');
document.head.appendChild(ds);
ds.innerHTML = '$min';
</script>
EOS;
    }

    if (isset($params['template_name'])) {
        $template = trim($params['template_name']);
        $tplobj = CmsLayoutTemplate::load($template);
        if (!is_object($tplobj)) {
            audit('', $this->GetName().':'.$template, 'No matching userguide template found');
            echo 'No userguide template '.$template.' found';
            return;
        }
    } elseif (isset($params['tplid'])) {
        $tplobj = CmsLayoutTemplate::load((int)$params['tplid']);
        if (is_object($tplobj)) {
            $template = $tplobj->get_name();
        } else {
            audit($params['tplid'], $this->GetName(), 'No matching userguide template found');
            echo 'TODO error 6 advice';
            return;
        }
    } else {
        $tplobj = CmsLayoutTemplate::load_dflt_by_type($this->GetName().'::listguides'); // might throw
        if (is_object($tplobj)) {
            $template = $tplobj->get_name();
        } else {
            audit('', $this->GetName().':default', 'No default userguide template found');
            echo 'TODO error 7 advice';
            return;
        }
    }

    $tpl = $smarty->CreateTemplate('cms_template:'.$template, null, null, $smarty); //TODO suitable cache parameters $cache_id, $compile_id
    $tpl->assign('iconurl', $this->GetModuleURLPath().'/images/view.png');
    $tpl->assign('guides', $data);
    $tpl->display();
}
else { //single guide
    $sql = 'SELECT name,smarty,template_id,sheets,content FROM '.CMS_DB_PREFIX.'module_userguide WHERE id=? AND active!=0 AND admin=0';
    $row = $db->GetRow($sql, [$params['gid']]);
    if (!$row) {
        echo 'TODO error 8 advice';
        return;
    }

    if ($row['sheets']) {
        $allmin = '';
        $allids = explode(',', $row['sheets']);
        foreach ($allids as $one) {
            $sht = CmsLayoutStylesheet::load($one);
            if ($sht) {
                $css = $sht->get_content();
                $min = UserGuideUtils::minCSS($css);
                $allmin .= $min;
            }
        }
        if ($allmin) {
            echo <<<EOS
<script>
var ds = document.createElement('style');
document.head.appendChild(ds);
ds.innerHTML = '$allmin';
</script>
EOS;
        }
    } else {
        $allmin = false;
        audit('', $this->GetName().':stylesheets', 'No matching sheet(s) found');
    }
    if (!$allmin) {
        //apply default, if any
        $name = $this->GetPreference('guideStyles');
        if ($name) {
            $sht = CmsLayoutStylesheet::load($name);
            if (is_object($sht)) {
                // inject its contents into page head TODO some better approach
                $css = $sht->get_content();
                $min = UserGuideUtils::minCSS($css);
                echo <<<EOS
<script>
var ds = document.createElement('style');
document.head.appendChild(ds);
ds.innerHTML = '$min';
</script>
EOS;
            } else {
                audit('', $this->GetName().':guideStyles-preference', 'No matching stylesheet found');
                echo 'TODO error 9 advice';
                return;
            }
        }
    }

    // adjust values for display e.g. strip_tags, entitize, ...
    $clean = preg_replace(['/<[^>]*>/', '/<\s*\?\s*php.*$/i', '/<\s*\?\s*=?.*$/'], ['', '', ''], trim((string)$row['name']));
    $name = strtr($clean, ["\0"=>'', "'"=>'&#39;', '"'=>'&#34;']);

    $clean = UserGuideUtils::cleanContent($row['content']);
    //format for display TODO common code to Utils method?
    $content = preg_replace([
        '~<img src="(?!https?://)([^"]*)"~', //relative URLs to absolute
        '~ *<br ?/?>~',
        '~ *\r?\n~',
        '~ *\r~',
        '~\n{3,}~',
        '~([^>])\n\n~',
        '~([^>])\n~'
        ], [
        "<img src=\"{$config['root_url']}/\$1\"",
        "\n",
        "\n",
        "\n",
        "\n\n",
        "$1<br><br>\n",
        "$1<br>\n"
        ], $clean);

    if ($row['smarty']) {
        try {
            $content2 = $smarty->fetch('string:'.$content);
        } catch (Exception $e) {
            echo 'Smarty compilation failed';
            return;
        }
        if ($row['template_id']) {
            $tplobj = CmsLayoutTemplate::load((int)$row['template_id']);
            if (is_object($tplobj)) {
                $template = $tplobj->get_name();
            } else {
                audit($row['template_id'], $this->GetName(), 'No matching userguide template found');
                echo 'Template '.$row['template_id'].' not found';
                return;
            }
        } else {
            $tplobj = CmsLayoutTemplate::load_dflt_by_type($this->GetName().'::oneguide'); // might throw
            if (is_object($tplobj)) {
                $template = $tplobj->get_name();
            } else {
                audit('', $this->GetName().':default', 'No default userguide template found');
                echo 'No default userguide template found';
                return;
            }
        }

        $tpl = $smarty->CreateTemplate('cms_template:'.$template, null, null, $smarty); //TODO suitable cache parameters $cache_id, $compile_id
        $tpl->assign('name', $name);
        $tpl->assign('content', $content2);
        $tpl->display();
    } else {
        echo $name, '<br><br>', '<div class="guide">', $content, '</div>';
    }
}
