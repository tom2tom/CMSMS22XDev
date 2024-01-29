<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage All Content') ) return;

if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab('pages');
}
$tree = $gCms->GetHierarchyManager();

if( !empty($params['orderlist']) ) {

/* unused
    function ordercontent_get_node_rec($str,$prefix = 'page_')
    {
        $gCms = cmsms();
        $tree = $gCms->GetHierarchyManager();

        if( !is_numeric($str) && startswith($str,$prefix) ) $str = substr($str,strlen($prefix));

        $id = (int)$str;
        $tmp = $tree->find_by_tag('id',$id);
        if( $tmp ) {
            $content = $tmp->getContent(false,true,true);
            if( $content ) {
                $rec = array();
                $rec['id'] = (int)$str;//WHAT FOR?
            }
        }
    }
*/
    function ordercontent_create_flatlist($list,$parent_id = -1)
    {
        $data = array();
        $cur_parent = 0;
        $order = 1;
        foreach( $list as &$item ) {
            if( is_string($item) ) {
                $pid = (int)substr($item,strlen('page_'));
                $cur_parent = $pid;
                $data[] = array('id'=>$pid,'parent_id'=>$parent_id,'order'=>$order++);
            }
            elseif( is_array($item) ) {
                $data = array_merge($data,ordercontent_create_flatlist($item,$cur_parent)); // recurse
            }
        }
        unset($item);
        return $data;
    }

    $orderlist = json_decode($params['orderlist'],TRUE);

    // step 1, create a flat list of the content items, and their new orders, and new parents.
    $orderlist = ordercontent_create_flatlist($orderlist);

    // step 2, merge in old orders and old parents
    $changelist = array();
    foreach( $orderlist as &$rec ) {
        $node = $tree->find_by_tag('id',$rec['id']);
        if( $node ) {
            $content = $node->getContent(FALSE,TRUE,TRUE);
            if( $content ) {
                $old_parent = $content->ParentId();
                if( $old_parent != $rec['parent_id'] ) {
                    $old_order = $content->ItemOrder();
                    if( $old_order != $rec['order'] ) {
                        $changelist[] = $rec;
                    }
                }
            }
        } //TODO handle unfound-node error
    }
    unset($rec);

    if( !$changelist ) {
        echo $this->ShowMessage($this->Lang('error_ordercontent_nothingtodo'));
    }
    else {
        $stmt = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'content SET item_order = ?, parent_id = ? WHERE content_id = ?');
        foreach( $changelist as $rec ) {
            $stmt->Execute(array($rec['order'],$rec['parent_id'],$rec['id']));
        }
        $contentops = $gCms->GetContentOperations();
        $contentops->SetAllHierarchyPositions();
        audit('',$this->GetName(),count($changelist).' content pages dynamically reordered');
    }
    $this->RedirectToAdminTab('pages');
}

$smarty->assign('tree',$tree);
echo $this->ProcessTemplate('admin_ordercontent.tpl');

#
# EOF
#
?>
