<?php /* Smarty version Smarty-3.1.19, created on 2015-07-05 13:44:15
         compiled from "C:\xampp\htdocs\zocart\modules\verticalmegamenus\views\templates\admin\list_menu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:232725598e757329579-57239668%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '87caabc37822f8b7025e1924e8dcb264f8c231c8' => 
    array (
      0 => 'C:\\xampp\\htdocs\\zocart\\modules\\verticalmegamenus\\views\\templates\\admin\\list_menu.tpl',
      1 => 1436080457,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '232725598e757329579-57239668',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'menuForm' => 0,
    'menuGroupForm' => 0,
    'menuItemForm' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5598e757399ad2_29397310',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5598e757399ad2_29397310')) {function content_5598e757399ad2_29397310($_smarty_tpl) {?><div class="panel">
    <div class="panel-heading">
    	<?php echo smartyTranslate(array('s'=>'List Menu','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
&nbsp;<span id="header-module-name"></span>
		<span class="panel-heading-action">
            <a href="javascript:void(0)" onclick="showModal('modalMenu', '')" class="list-toolbar-btn link-add"><span data-placement="left" data-html="true" data-original-title="Add New" class="label-tooltip" data-toggle="tooltip" title=""><i class="process-icon-new"></i></span></a>
		</span>
    </div>
    <div class="panel-body" style="padding:0">
        <div class="table-responsive">
            <table class="table" id="listMenu">
    			<thead>
    				<tr class="nodrag nodrop">
                        <th width="30" class="center"><?php echo smartyTranslate(array('s'=>'ID','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="40" class="center"><?php echo smartyTranslate(array('s'=>'Icon','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>                        
                        <th width="120"><?php echo smartyTranslate(array('s'=>'Title','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Width','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Type','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th><?php echo smartyTranslate(array('s'=>'Link','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Ordering','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="50" class="center"><?php echo smartyTranslate(array('s'=>'Status','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="50">#</th>
                    </tr>				
                </thead>
                <tbody></tbody>    
	       </table>            
        </div>        
    </div> 
</div>

<div class="panel" id="panel-list-group" style="display:none">
    <div class="panel-heading">
    	<?php echo smartyTranslate(array('s'=>'List Group in Menu','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
&nbsp;<span id="header-menu-name"></span>
		<span class="panel-heading-action">
            <a href="javascript:void(0)" onclick="showModal('modalGroup', '')" class="list-toolbar-btn link-add"><span data-placement="left" data-html="true" data-original-title="Add New" class="label-tooltip" data-toggle="tooltip" title=""><i class="process-icon-new"></i></span></a>
		</span>
    </div>
    <div class="panel-body" style="padding:0">
        <div class="table-responsive">
            <table class="table" id="listGroup">
    			<thead>
    				<tr class="nodrag nodrop">
                        <th width="50" class="center"><?php echo smartyTranslate(array('s'=>'ID','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th><?php echo smartyTranslate(array('s'=>'Title','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="100"><?php echo smartyTranslate(array('s'=>'Width','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="100"><?php echo smartyTranslate(array('s'=>'Type','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="200"><?php echo smartyTranslate(array('s'=>'Params','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Ordering','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="50" class="center"><?php echo smartyTranslate(array('s'=>'Status','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="50">#</th>
                    </tr>				
                </thead>
                <tbody></tbody>    
	       </table>            
        </div>        
    </div> 
</div>

<div class="panel" id="panel-list-menu-item" style="display:none">
    <div class="panel-heading">
    	<?php echo smartyTranslate(array('s'=>'Menu Item','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
&nbsp;<span id="header-group-name"></span>
		<span class="panel-heading-action">
            <a href="javascript:void(0)" onclick="showModal('modalMenuItem', '')" class="list-toolbar-btn link-add"><span data-placement="left" data-html="true" data-original-title="Add New" class="label-tooltip" data-toggle="tooltip" title=""><i class="process-icon-new"></i></span></a>
		</span>
    </div>
    <div class="panel-body" style="padding:0">
        <div class="table-responsive">
            <table class="table" id="listMenuItem">
    			<thead>
    				<tr class="nodrag nodrop">
                        <th width="30" class="center"><?php echo smartyTranslate(array('s'=>'ID','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>                      
                        <th width="120"><?php echo smartyTranslate(array('s'=>'Title','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Type','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th><?php echo smartyTranslate(array('s'=>'Link','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="50" class="center"><?php echo smartyTranslate(array('s'=>'Banner','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="100" class="center"><?php echo smartyTranslate(array('s'=>'Ordering','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th width="50" class="center"><?php echo smartyTranslate(array('s'=>'Status','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</th>
                        <th class="center" width="50">#</th>
                    </tr>				
                </thead>
                <tbody></tbody>    
	       </table>            
        </div>        
    </div> 
</div>

<!-- Modal -->
<div id="modalMenu" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <span class="modal-title"><i class="icon-cloud"></i><?php echo smartyTranslate(array('s'=>' Add or Edit Menu','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</span>
            </div>
            <div class="modal-body form-horizontal">
                
                <form id="frmMenu"><?php echo $_smarty_tpl->tpl_vars['menuForm']->value;?>
</form>
            </div>
            <div class="modal-footer">                
                <button type="button" class="btn btn-primary btnForgot" onclick="saveMenu()"><i class="icon-save"></i> <?php echo smartyTranslate(array('s'=>'Save','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</button>
            </div>
        </div>
    </div>
</div>





<div id="modalGroup" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <span class="modal-title"><i class="icon-cloud"></i><?php echo smartyTranslate(array('s'=>' Add or Edit Group','mod'=>'groupcategory'),$_smarty_tpl);?>
</span>
            </div>
            <div class="modal-body form-horizontal">                
                <form id="frmMenuGroup"><?php echo $_smarty_tpl->tpl_vars['menuGroupForm']->value;?>
</form>
                
                                               
            </div>
            <div class="modal-footer">                
                <button type="button" class="btn btn-primary btnForgot" onclick="saveGroup()"><i class="icon-save"></i> <?php echo smartyTranslate(array('s'=>'Save','mod'=>'groupcategory'),$_smarty_tpl);?>
</button>
            </div>
        </div>
    </div>
</div>


<div id="modalMenuItem" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <span class="modal-title"><i class="icon-cloud"></i><?php echo smartyTranslate(array('s'=>' Add or Edit Menu Item','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</span>
            </div>
            <div class="modal-body form-horizontal">                
                <form id="frmMenuItem"><?php echo $_smarty_tpl->tpl_vars['menuItemForm']->value;?>
</form>
            </div>
            <div class="modal-footer">                
                <button type="button" class="btn btn-primary btnForgot" onclick="saveMenuItem()"><i class="icon-save"></i> <?php echo smartyTranslate(array('s'=>'Save','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</button>
            </div>
        </div>
    </div>
</div>


<div id="modalProductId" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <span class="modal-title"><i class="icon-cloud"></i><?php echo smartyTranslate(array('s'=>' Add or Edit Item','mod'=>'groupcategory'),$_smarty_tpl);?>
</span>
            </div>
            <div class="modal-body form-horizontal">
                <label><?php echo smartyTranslate(array('s'=>'Enter Product ID','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</label>
                <input type="text" class="form-control" id="product-id" />                
            </div>
            <div class="modal-footer">                
                <button type="button" class="btn btn-primary btnForgot" onclick="addProductId()"></i> <?php echo smartyTranslate(array('s'=>'OK','mod'=>'verticalmegamenus'),$_smarty_tpl);?>
</button>
            </div>
        </div>
    </div>
</div><?php }} ?>
