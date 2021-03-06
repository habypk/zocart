<?php
/*
*  @author SonNC Ovic <nguyencaoson.zpt@gmail.com>
*/
// check module da cai hay chua Module::isInstalled('productcomments') != 1
require (dirname(__FILE__).'/GroupCategoryLibraries.php');
//require (dirname(__FILE__).'/GroupCategoryFastCache.php');
//GroupCategoryFastCache::$storage = "auto";
//GroupCategoryFastCache::$path = dirname(__FILE__).'/cache/';
//GroupCategoryFastCache::$securityKey = "groupcategory.cache";
//GroupCategoryFastCache::cleanup();

class GroupCategory extends Module
{
    const INSTALL_SQL_FILE = 'install.sql';	
    public $arrType = array();
    public $arrLayout = array();
	public $imageHomeSize = array();
    public $pathTemp = '';
    public $pathBanner = '';
    public $pathIcon = '';
	public $livePath = '';	
    public $compareProductIds;
	public $codeCss = array();
	public $cache;
    public $cacheTime = 86400;
	protected static $productCache = array();
	public function __construct()
	{		
		$this->name = 'groupcategory';
		//$this->cache = new GroupCategoryFastCache();
		$this->arrType = array('saller'=>$this->l('Best Sellers'), 'view'=>$this->l('Most View'), 'special'=>$this->l('Specials'), 'arrival'=>$this->l('New Arrivals'));		
		$this->secure_key = Tools::encrypt('ovic-soft[group-category]'.$this->name);
		$this->imageHomeSize = Image::getSize(ImageType::getFormatedName('home'));
        $this->arrLayout = array('default'=>$this->l('Layout [default]'));
		
		$this->pathTemp = dirname(__FILE__).'/images/temps/';
        $this->pathBanner = dirname(__FILE__).'/images/banners/';
        $this->pathIcon = dirname(__FILE__).'/images/icons/';
		
		if(Configuration::get('PS_SSL_ENABLED'))
			$this->livePath = _PS_BASE_URL_SSL_.__PS_BASE_URI__.'modules/groupcategory/images/'; 
		else
			$this->livePath = _PS_BASE_URL_.__PS_BASE_URI__.'modules/groupcategory/images/';
		        		
		
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'OvicSoft';		
		$this->bootstrap = true;
		parent::__construct();
		$this->displayName = $this->l('Supershop - Group Category Module');
		$this->description = $this->l('Group Category Module');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}
	/*
    public function  __call($method, $args){        
        if(!method_exists($this, $method)) {            
          return $this->hooks($method, $args);
        }
    }
	*/
	public function install($keep = true)
	{
	   if ($keep)
		{			
			if (!file_exists(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
				return false;
			else if (!$sql = file_get_contents(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
				return false;
			$sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
			$sql = preg_split("/;\s*[\r\n]+/", trim($sql));
			foreach ($sql as $query)
				if (!DB::getInstance()->execute(trim($query))) return false;
			
		}
		if(!parent::install() 
			|| !$this->registerHook('displayHeader')
			|| !$this->registerHook('displayGroupFashions')
            || !$this->registerHook('actionProductAdd')
            || !$this->registerHook('actionProductAttributeDelete')
            || !$this->registerHook('actionProductAttributeUpdate')
            || !$this->registerHook('actionProductDelete')
            || !$this->registerHook('actionProductSave')
            || !$this->registerHook('actionProductUpdate')
            || !$this->registerHook('actionCartSave')
            || !$this->registerHook('actionCategoryAdd')
            || !$this->registerHook('actionCategoryDelete')
			|| !$this->registerHook('displayGroupFoods')
			|| !$this->registerHook('displayGroupSports')) return false;
		if (!Configuration::updateGlobalValue('MOD_GROUP_CATEGORY', '1')) return false;
        //$this->cache->cleanup();
        $this->clearCache();	
		$this->moduleUpdatePosition();
		return true;
	}
	public function moduleUpdatePosition(){
		$items = DB::getInstance()->executeS("Select DISTINCT position_name From "._DB_PREFIX_."groupcategory_groups  Where `position_name` <> ''");
		if($items){
			foreach ($items as $key => $item) {
				$position = Hook::getIdByName($item['position_name']);
				DB::getInstance()->execute("Update "._DB_PREFIX_."groupcategory_groups Set position = '".$position."' Where `position_name` = '".$item['position_name']."'");
			}
		}
	}
	public function uninstall($keep = true)
	{	   
		if (!parent::uninstall()) return false;
		$this->clearCache();
        if($keep){			
            if(!DB::getInstance()->execute('
			DROP TABLE IF EXISTS
			`'._DB_PREFIX_.'groupcategory_group_lang`,
            `'._DB_PREFIX_.'groupcategory_groups`,
            `'._DB_PREFIX_.'groupcategory_item_lang`,
            `'._DB_PREFIX_.'groupcategory_product_view`,
            `'._DB_PREFIX_.'groupcategory_styles`,
			`'._DB_PREFIX_.'groupcategory_items`')) return false;
			
        }		
        if (!Configuration::deleteByName('MOD_GROUP_CATEGORY')) return false;
        //$this->cache->cleanup();
        $this->clearCache();
		return true;
	}
	public function reset()
	{
		if (!$this->uninstall(false))
			return false;
		if (!$this->install(false))
			return false;
        
		return true;
	}
    function getCategoryIds($parentId = 0, $arr=null){
        if($arr == null) $arr = array();
        $items = DB::getInstance()->executeS("Select id_category From "._DB_PREFIX_."category Where id_parent = $parentId");
        if($items){
            foreach($items as $item){
                $arr[] = $item['id_category'];
                $arr = $this->getCategoryIds($item['id_category'], $arr);
            }
        }
        return $arr;
    }
    public function getAllStyle(){
        $shopId = Context::getContext()->shop->id;
        $items = DB::getInstance()->executeS("Select * From "._DB_PREFIX_."groupcategory_styles Where id_shop = ".$shopId);
        $content = '';
        if($items){
            foreach($items as $item){
				if($item['params']){
					$params = json_decode($item['params']);								
					$arrParams = get_object_vars($params);				
					$keys = array_keys($arrParams);
					$value = '';
					if($keys){
						foreach($keys as $key){
							$value .= '<div class="style-values"><span class="style-value" style="background: '.$arrParams[$key].'">&nbsp;</span><label>'.$key.'</label>: <span>'.$arrParams[$key].'</span></div>';
							
						}
					}
					$content .= '<tr><td>'.$item['id'].'</td><td>'.$item['name'].'</td><td>'.$value.'</td><td class="center"><a href="javascript:void(0)" item-id="'.$item['id'].'" class="lik-style-edit"><i class="icon-edit"></i></a>&nbsp;<a href="javascript:void(0)" item-id="'.$item['id'].'" class="lik-style-delete"><i class="icon-trash" ></i></a></td></tr>';
				}                
            }
        }		
        return $content;
    }
    public function getAllGroup(){
        $langId = Context::getContext()->language->id;
        $shopId = Context::getContext()->shop->id;
        $items = DB::getInstance()->executeS("Select DISTINCT g.*, s.name as styleName From "._DB_PREFIX_."groupcategory_groups as g Left Join "._DB_PREFIX_."groupcategory_styles as s On s.id = g.style_id Where g.id_shop = ".$shopId." Order By g.position, g.ordering");        
        $listGroup = '';        
        if($items){
            foreach($items as $item){
                
                $itemLang = GroupCategoryLibraries::getGroupLangById($item['id'], $langId, $shopId);
                if($item['status'] == "1"){
                    $status = '<a title="Enabled" class="list-action-enable action-enabled lik-group-status" item-id="'.$item['id'].'" value="'.$item['status'].'"><i class="icon-check"></i></a>';
                }else{
                    $status = '<a title="Disabled" class="list-action-enable action-disabled lik-group-status" item-id="'.$item['id'].'" value="'.$item['status'].'"><i class="icon-check"></i></a>';
                }
                $listGroup .= '<tr id="gr_'.$item['id'].'"><td class="center">'.$item['id'].'</td><td><a class="cat-group" href="javascript:void(0)" item-id="'.$item['id'].'">'.$itemLang['name'].'</a></td><td class="center">'.GroupCategoryLibraries::getCategoryLangNameById($item['categoryId'], $langId, $shopId).'</td><td class="center">'.Hook::getNameById($item['position']).'</td><td class="center">'.$item['styleName'].'</td><td class="pointer dragHandle center" ><div class="dragGroup"><div class="positions">'.$item['ordering'].'</div></div></td><td class="center">'.$status.'</td><td class="center"><a href="javascript:void(0)" item-id="'.$item['id'].'" class="lik-group-edit"><i class="icon-edit"></i></a>&nbsp;<a href="javascript:void(0)" item-id="'.$item['id'].'" class="lik-group-delete"><i class="icon-trash" ></i></a></td></tr>';
            }
        }
        
        return $listGroup;
    }
    public function getManufacturerOptions($selected = array()){
        $items = DB::getInstance()->executeS("Select id_manufacturer, name From "._DB_PREFIX_."manufacturer Where active = 1");
        $manufacturerOptions ='<option value="0">-- No selected --</option>';
        if($items){
            foreach($items as $item){
                if($selected){
                    if(in_array($item['id_manufacturer'], $selected)){
                        $manufacturerOptions .='<option selected="selected" value="'.$item['id_manufacturer'].'">'.$item['name'].'</option>';    
                    }else{
                        $manufacturerOptions .='<option value="'.$item['id_manufacturer'].'">'.$item['name'].'</option>';    
                    }                        
                }else{
                    $manufacturerOptions .='<option value="'.$item['id_manufacturer'].'">'.$item['name'].'</option>';
                }                
            }
        }
        return $manufacturerOptions;
    }
    public function getCategoryOptions($selected = 0, $parentId = 0){
        $langId = Context::getContext()->language->id;
        $shopId = Context::getContext()->shop->id;
        $categoryOptions = '';
        if($parentId <=0) $parentId = Configuration::get('PS_HOME_CATEGORY');		
        $items = GroupCategoryLibraries::getAllCategories($langId, $shopId, $parentId, '- ', null);        
        if($items){
            foreach($items as $item){
                if($item['id_category'] == $selected) $categoryOptions .='<option selected="selected" value="'.$item['id_category'].'">'.$item['sp'].$item['name'].'</option>';
                else $categoryOptions .='<option value="'.$item['id_category'].'">'.$item['sp'].$item['name'].'</option>';
            }
        }
        return  $categoryOptions;
    }
    public function getStyleOptions($selected = 0){
        $langId = Context::getContext()->language->id;
        $shopId = Context::getContext()->shop->id;
        $styleOptions = '';
        $items = DB::getInstance()->executeS("Select id, name From "._DB_PREFIX_."groupcategory_styles");        
        if($items){
            foreach($items as $item){
                if($item['id'] == $selected) $styleOptions .='<option selected="selected" value="'.$item['id'].'">'.$item['name'].'</option>';
                else $styleOptions .='<option value="'.$item['id'].'">'.$item['name'].'</option>';
            }
        }
        return  $styleOptions;
    }
    public function getLangOptions(){
        $langId = Context::getContext()->language->id;
        $items = DB::getInstance()->executeS("Select id_lang, name, iso_code From "._DB_PREFIX_."lang Where active = 1");
        $langOptions = '';
        if($items){
            foreach($items as $item){
                if($item['id_lang'] == $langId){
                    $langOptions .= '<option value="'.$item['id_lang'].'" selected="selected">'.$item['iso_code'].'</option>';
                }else{
                    $langOptions .= '<option value="'.$item['id_lang'].'">'.$item['iso_code'].'</option>';
                }
            }
        }
        return $langOptions;
    }
    public function getPositionOptions($selected = 0){
        $positionOptions = '';
        $items = DB::getInstance()->executeS("Select id_hook From "._DB_PREFIX_."hook_module Where id_module = ".$this->id);
        if($items){
            foreach($items as $item){
                if($selected == $item['id_hook']) $positionOptions .= '<option selected="selected" value="'.$item['id_hook'].'">'.Hook::getNameById($item['id_hook']).'</option>';
                else $positionOptions .= '<option value="'.$item['id_hook'].'">'.Hook::getNameById($item['id_hook']).'</option>';
            }
        }
        return $positionOptions; 
    }
    public function getLayoutOptions($selected = ''){
        $options = '';        
        foreach($this->arrLayout as $key=> $value){
            if($key == $selected) $options .= '<option selected="selected" value="'.$key.'">'.$value.'</option>';
            else $options .= '<option  value="'.$key.'">'.$value.'</option>';            
        }        
        return $options; 
    }
	public function getTypeOptions($selected = ''){
		$options = '';        
        foreach($this->arrType as $key=> $value){
            if($key == $selected) $options .= '<option selected="selected" value="'.$key.'">'.$value.'</option>';
            else $options .= '<option  value="'.$key.'">'.$value.'</option>';            
        }        
        return $options;
	}
	/**
	 *  getTypeCheckbox
	 * 	var $checked = array();
	 */
    public function getTypeCheckbox($checked = array()){
        $typeCheckbox = '';
        if($checked){            
            foreach($this->arrType as $key=>$value){
                if(in_array($key, $checked)){
                    $typeCheckbox .= '<div class="col-sm-6"><input type="checkbox" name="types[]" checked="checked" class="types" id="type-'.$key.'" value="'.$key.'" />&nbsp;<label for="type-'.$key.'" class="control-label">'.$value.'</label></div>';    
                }else{
                    $typeCheckbox .= '<div class="col-sm-6"><input type="checkbox" name="types[]" class="types" id="type-'.$key.'" value="'.$key.'" />&nbsp;<label for="type-'.$key.'" class="control-label">'.$value.'</label></div>';
                }                
            }
        }else{
            foreach($this->arrType as $key=>$value){                
                $typeCheckbox .= '<div class="col-sm-6"><input type="checkbox" name="types[]" class="types" id="type-'.$key.'" value="'.$key.'" />&nbsp;<label for="type-'.$key.'" class="control-label">'.$value.'</label></div>';
            }            
        }
        return $typeCheckbox;
    }

	
	public function getItemBannerSrc($image = '', $check = false){
        if($image && file_exists(_PS_MODULE_DIR_.'groupcategory/images/banners/'.$image))
            return $this->livePath.'banners/'.$image;
        else
            if($check == true) 
                return '';
            else
                return $this->livePath.'banners/default.jpg'; 
    }
    public function getGroupBannerSrc($image = '', $check = false){
        if($image && file_exists(_PS_MODULE_DIR_.'groupcategory/images/banners/'.$image))
            return $this->livePath.'banners/'.$image;
        else
            if($check == true) 
                return '';
            else
                return $this->livePath.'banners/default.jpg'; 
    }
    public function getGroupIconSrc($image = '', $check = false){
        if($image && file_exists(_PS_MODULE_DIR_.'groupcategory/images/icons/'.$image))
            return $this->livePath.'icons/'.$image;
        else
            if($check == true) 
                return '';
            else
                return $this->livePath.'icons/default.jpg'; 
    }
	public function getAllLanguage(){
        $langId = Context::getContext()->language->id;
        $items = DB::getInstance()->executeS("Select id_lang, name, iso_code From "._DB_PREFIX_."lang Where active = 1 Order By id_lang");
        $languages = array();
        if($items){
            foreach($items as $i=>$item){
            	$objItem = new stdClass();
				$objItem->id = $item['id_lang'];
				$objItem->iso_code = $item['iso_code'];
                if($item['id_lang'] == $langId){
                    $objItem->active = 1;
                }else{
                    $objItem->active = 0;
                }
				$languages[$i] = $objItem;
            }
        }
        return $languages;
    }
	function getGroupByLang($id, $langId=0, $shopId=0){
		if(!$langId) $langId = Context::getContext()->language->id;
        if(!$shopId) $shopId = Context::getContext()->shop->id;
		$itemLang = DB::getInstance()->getRow("Select name, banner, banner_link, banner_size From "._DB_PREFIX_."groupcategory_group_lang Where group_id = $id AND `id_lang` = '$langId' AND `id_shop` = '$shopId'" );
		if(!$itemLang) $itemLang = array('name'=>'', 'banner'=>'', 'banner_link'=>'', 'banner_size'=>'');
		return $itemLang;
	}
	function ovicRenderGroupForm($id = 0){
		$item = DB::getInstance()->getRow("Select * From "._DB_PREFIX_."groupcategory_groups Where id = $id");		
		
        if(!$item){
			$types = array();
			$manufactureIds = array();
			$manImageWidth = 126;
			$manImageHeight = 51;			
			$item = array('id'=>0, 'position'=>0, 'categoryId'=>0, 'type_default'=>'arrival', 'style_id'=>0, 'layout'=>'default', 'manufactureConfig'=>'', 'itemConfig'=>'', 'types'=>'', 'icon'=>'', 'ordering'=>1, 'status'=>1, 'note'=>'');
            $itemConfig	= array('countItem'=>12);		
		}else{			
			$manufactureConfig = json_decode($item['manufactureConfig']);			
			$manufactureIds = $manufactureConfig->ids;
			$manImageHeight = $manufactureConfig->imageHeight;
			$manImageWidth = $manufactureConfig->imageWidth;
			$types = json_decode($item['types']);
            $itemConfig = get_object_vars(json_decode($item['itemConfig'])) ;
		}
		$langActive = '<input type="hidden" id="groupLangActive" value="0" />';
		$languages = $this->getAllLanguage();
		$inputName = '';
		$inputBanner = '';
		$inputBannerLink = '';
		$inputHtml = '';
		if($languages){
			foreach ($languages as $key => $language) {				
				$itemLang = $this->getGroupByLang($id, $language->id);
				if($language->active == '1'){
					$langActive = '<input type="hidden" id="groupLangActive" value="'.$language->id.'" />';
					$inputName .= '<input type="text" value="'.$itemLang['name'].'" name="names[]"  class="form-control group-lang-'.$language->id.'" />';
					$inputBanner .= '<input type="text" value="'.$itemLang['banner'].'" name="banners[]" id="groupBanner-'.$language->id.'" class="form-control group-lang-'.$language->id.'"  />';
					$inputBannerLink .= '<input type="text" value="'.$itemLang['banner_link'].'" name="links[]" class="form-control group-lang-'.$language->id.'" />';
				}else{
					$inputName .= '<input type="text" value="'.$itemLang['name'].'" name="names[]"  class="form-control group-lang-'.$language->id.'" style="display:none" />';
					$inputBanner .= '<input type="text" value="'.$itemLang['banner'].'" name="banners[]" id="groupBanner-'.$language->id.'" class="form-control group-lang-'.$language->id.'" style="display:none" />';
					$inputBannerLink .= '<input type="text" value="'.$itemLang['banner_link'].'" name="links[]" class="form-control group-lang-'.$language->id.'" style="display:none" />';										
				}				
			}
		}
		$langOptions = $this->getLangOptions();
		$html = '';
		$html .= '<input type="hidden" name="groupId" value="'.$item['id'].'" />';		
		$html .= $langActive;
		$html .= '<input type="hidden" name="action" value="saveGroup" />';
		$html .= '<input type="hidden" name="secure_key" value="'.$this->secure_key.'" />';		
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2 required">'.$this->l('Group Name').'</label>
				    <div class="col-sm-10">
                        <div class="col-sm-10">
                            '.$inputName.'
                        </div>
                        <div class="col-sm-2">
                            <select class="group-lang" onchange="groupChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>                                                                        
                    </div>
                </div>';
		$html .= '<div class="form-group clearfix">
                    <label class="control-label col-sm-2 required">'.$this->l('Icon').'</label>
                    <div class="col-sm-10">
                        <div class="col-sm-4">                        
                            <div class="input-group">
                                <input type="text" class="form-control" name="groupIcon" value="'.$item['icon'].'" id="group-icon" readonly="readonly" />
                                <span class="input-group-btn">
                                    <button id="icon-uploader" type="button" class="btn btn-default"><i class="icon-folder-open"></i></button>
                                </span>
                            </div>                  
                        </div>
                        <label class="control-label col-sm-2 required">'.$this->l('Banner').'</label>
                        <div class="col-sm-4">                        
                            <div class="input-group">
                                '.$inputBanner.'
                                <span class="input-group-btn">
                                    <button id="image-uploader" type="button" class="btn btn-default"><i class="icon-folder-open"></i></button>
                                </span>
                            </div>                        
                        </div>
                        <div class="col-sm-2">
                            <select class="group-lang" onchange="groupChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>
                    </div>  
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Link banner').'</label>
				    <div class="col-sm-10">
				    	<div class="col-sm-10">
                            '.$inputBannerLink.'
                        </div>
                        <div class="col-sm-2">
                            <select class="group-lang" onchange="groupChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>
                    </div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Position').'</label>
				    <div class="col-sm-10">
                        <div class="col-sm-5">
                            <select class="form-control" name="position">'.$this->getPositionOptions($item['position']).'</select>
                        </div>
                        <label class="control-label col-sm-2">'.$this->l('Style').'</label> 
                        <div class="col-sm-5">
                            <select class="form-control" name="style_id" id="styleId">'.$this->getStyleOptions($item['style_id']).'</select>
                        </div>                       
                    </div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Category').'</label>
				    <div class="col-sm-10">
                        <div class="col-sm-5">
                            <select class="form-control" name="categoryId" >'.$this->getCategoryOptions($item['categoryId']).'</select>
                        </div> 
                        <label class="control-label col-sm-2">'.$this->l('Type default').'</label> 
                        <div class="col-sm-5">
                            <select class="form-control" name="type_default">'.$this->getTypeOptions($item['type_default']).'</select>
                        </div>                                                                         
                    </div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Show Types').'</label>
				    <div class="col-sm-10" id="type-checkbox">'.$this->getTypeCheckbox($types).'</div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Manufacturer').'</label>
				    <div class="col-sm-10">
                        <div class="col-sm-8">
                            <select class="form-control" id="manufacturerIds" name="manufacturerIds[]" multiple="" size="4" >'.$this->getManufacturerOptions($manufactureIds).'</select>
                        </div>
                        <div class="col-sm-4">                            
                            <input name="imageWidth" value="'.$manImageWidth.'" onkeypress="return groupCategory_HandleEnterNumberInt(event)" class="form-control" placeholder="Image Width (px)" />
                            <br />                            
                            <input name="imageHeight" value="'.$manImageHeight.'" onkeypress="return groupCategory_HandleEnterNumberInt(event)" class="form-control" placeholder="Image height (px)" />                            
                        </div>
                    </div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-2">'.$this->l('Max items').'</label>
				    <div class="col-sm-10">                        
                        <div class="col-sm-3">
                            <input name="countItem" value="'.$itemConfig['countItem'].'" onkeypress="return groupCategory_HandleEnterNumberInt(event)" class="form-control" placeholder="Max items" />
                        </div>                                                                 
                    </div>
                </div>';		
		return $html;
	}
	function getItemByLang($id, $langId=0, $shopId=0){
		if(!$langId) $langId = Context::getContext()->language->id;
        if(!$shopId) $shopId = Context::getContext()->shop->id;
		$itemLang = DB::getInstance()->getRow("Select name, banner, banner_link, banner_size From "._DB_PREFIX_."groupcategory_item_lang Where itemId = $id AND `id_lang` = '$langId' AND `id_shop` = '$shopId'" );
		if(!$itemLang) $itemLang = array('name'=>'', 'banner'=>'', 'banner_link'=>'', 'banner_size'=>'');
		return $itemLang;
	}
	function ovicRenderItemForm($id, $groupId){
		
		$item = DB::getInstance()->getRow("Select * From "._DB_PREFIX_."groupcategory_items Where id = $id");
		$parentCategory = DB::getInstance()->getValue("Select categoryId From "._DB_PREFIX_."groupcategory_groups Where id = ".$groupId);
		if(!$parentCategory) $parentCategory = 0;		
		if(!$item){
			$item = array('id'=>0, 'groupId'=>0, 'categoryId'=>0, 'maxItem'=>12, 'ordering'=>1, 'status'=>1);		
		}
		$langActive = '<input type="hidden" id="itemLangActive" value="0" />';
		$languages = $this->getAllLanguage();
		$inputName = '';
		$inputBanner = '';
		$inputBannerLink = '';
		$inputHtml = '';
		if($languages){
			foreach ($languages as $key => $language) {				
				$itemLang = $this->getItemByLang($id, $language->id);
				if($language->active == '1'){
					$langActive = '<input type="hidden" id="itemLangActive" value="'.$language->id.'" />';
					$inputName .= '<input type="text" value="'.$itemLang['name'].'" name="names[]"  class="form-control item-lang-'.$language->id.'" />';
					$inputBanner .= '<input type="text" value="'.$itemLang['banner'].'" name="banners[]" id="itemBanner-'.$language->id.'" class="form-control item-lang-'.$language->id.'"  />';
					$inputBannerLink .= '<input type="text" value="'.$itemLang['banner_link'].'" name="links[]" class="form-control item-lang-'.$language->id.'" />';
				}else{
					$inputName .= '<input type="text" value="'.$itemLang['name'].'" name="names[]"  class="form-control item-lang-'.$language->id.'" style="display:none" />';
					$inputBanner .= '<input type="text" value="'.$itemLang['banner'].'" name="banners[]" id="itemBanner-'.$language->id.'" class="form-control item-lang-'.$language->id.'" style="display:none" />';
					$inputBannerLink .= '<input type="text" value="'.$itemLang['banner_link'].'" name="links[]" class="form-control item-lang-'.$language->id.'" style="display:none" />';										
				}				
			}
		}		
		$langOptions = $this->getLangOptions();
		$html = '';
		$html .= '<input type="hidden" name="itemId" value="'.$item['id'].'" />';		
		$html .= $langActive;
		$html .= '<input type="hidden" name="action" value="saveItem" />';
		$html .= '<input type="hidden" name="secure_key" value="'.$this->secure_key.'" />';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-3 required">'.$this->l('Item Name').'</label>
				    <div class="col-sm-9">
                        <div class="col-sm-10">
                            '.$inputName.'
                        </div>
                        <div class="col-sm-2">
                            <select class="item-lang" onchange="itemChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>                                                                        
                    </div>
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-3">'.$this->l('Category').'</label>
				    <div class="col-sm-9">
                        <div class="col-sm-12">
                            <select class="form-control" name="categoryId">'.$this->getCategoryOptions($item['categoryId'], $parentCategory).'</select>
                        </div> 
                                                                                               
                    </div>
                </div>';
		$html .= '<div class="form-group clearfix">
                    <label class="control-label col-sm-3">'.$this->l('Banner').'</label>
                    <div class="col-sm-9">
                        <div class="col-sm-10">                        
                            <div class="input-group">
                                '.$inputBanner.'
                                <span class="input-group-btn">
                                    <button id="item-image-uploader" type="button" class="btn btn-default"><i class="icon-folder-open"></i></button>
                                </span>
                            </div>                        
                        </div>
                        <div class="col-sm-2">
                            <select class="item-lang" onchange="itemChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>
                    </div>  
                </div>';
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-3">'.$this->l('Link banner').'</label>
				    <div class="col-sm-9">
                        <div class="col-sm-10">
                            '.$inputBannerLink.'
                        </div>
                        <div class="col-sm-2">
                            <select class="item-lang" onchange="itemChangeLanguage(this.value)">'.$langOptions.'</select>
                        </div>                                                                                                
                    </div>
                </div>';		
		$html .= '<div class="form-group">
                    <label class="control-label col-sm-3">'.$this->l('Max item').'</label>
                    <div class="col-sm-9">                        
                        <div class="col-sm-3 " >                        
                            <input type="text" onkeypress="return groupCategory_HandleEnterNumberInt(event)" name="maxItem" value="'.$item['maxItem'].'" class="form-control" />     
                        </div>
                    </div>				    
                </div> ';
		return $html;
	}
	public function getContent()
	{	
	   $langId = Context::getContext()->language->id;		
        $shopId = Context::getContext()->shop->id;        
        $checkUpdate = DB::getInstance()->getRow("Select * From "._DB_PREFIX_."groupcategory_groups");
        if($checkUpdate){
            if(!isset($checkUpdate['id_shop'])){
                DB::getInstance()->execute("ALTER TABLE "._DB_PREFIX_."groupcategory_groups ADD `id_shop` INT(6) unsigned NOT NULL AFTER `id`");
                DB::getInstance()->execute("ALTER TABLE "._DB_PREFIX_."groupcategory_styles ADD `id_shop` INT(6) unsigned NOT NULL AFTER `id`");
                DB::getInstance()->execute("Update "._DB_PREFIX_."groupcategory_groups Set `id_shop` = ".$shopId);
                DB::getInstance()->execute("Update "._DB_PREFIX_."groupcategory_styles Set `id_shop` = ".$shopId);
            }
        }
		$this->context->controller->addJS(($this->_path).'js/back-end/common.js');                
        $this->context->controller->addJS(($this->_path).'js/back-end/ajaxupload.3.5.js');
		$this->context->controller->addJS(($this->_path).'js/back-end/jquery.serialize-object.min.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'js/jquery/plugins/jquery.tablednd.js');
        $this->context->controller->addJS(__PS_BASE_URI__.'js/jquery/plugins/jquery.colorpicker.js');
        $this->context->controller->addCSS(($this->_path).'css/back-end/style.css');
        $this->context->controller->addCSS(($this->_path).'css/back-end/style-upload.css');
                
        $this->context->smarty->assign(array(
            'baseModuleUrl'=> __PS_BASE_URI__.'modules/'.$this->name,
            'moduleId'=>$this->id,
            'langId'=>$langId,
			'secure_key'=> $this->secure_key,
            
            
            
            
            
            'style_tpl'=>dirname(__FILE__).'/views/templates/admin/style.tpl',
            'group_tpl'=>dirname(__FILE__).'/views/templates/admin/group.tpl',
            'listStyles'=>$this->getAllStyle(),
            'listGroup'=>$this->getAllGroup(),
            'groupForm'=>$this->ovicRenderGroupForm(),
            'itemForm' => $this->ovicRenderItemForm(0, 0)
        ));
		return $this->display(__FILE__, 'views/templates/admin/modules.tpl');
	}
    public function hookActionProductAdd($params)	{		
        //$this->cache->cleanup();
        $this->clearCache();
        return true;	
    }
    public function hookActionProductAttributeDelete($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionProductAttributeUpdate($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionProductDelete($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionProductSave($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionProductUpdate($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionCartSave($params)	{		
        //return $this->hookActionProductAdd();	
    }
    public function hookActionCategoryAdd($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookActionCategoryDelete($params)	{		
        return $this->hookActionProductAdd();	
    }
    public function hookdisplayHeader()
	{
		$this->arrType = array('saller'=>$this->l('Best Sellers'), 'view'=>$this->l('Most View'), 'special'=>$this->l('Specials'), 'arrival'=>$this->l('New Arrivals'));		
        $this->page_name = Dispatcher::getInstance()->getController();        
		if ($this->page_name == 'product')
		{		  
			$productId = (int)Tools::getValue('id_product');
            $check = DB::getInstance()->getValue("Select productId From "._DB_PREFIX_."groupcategory_product_view Where productId = ".$productId);
            if($check){
                DB::getInstance()->execute("Update "._DB_PREFIX_."groupcategory_product_view Set total = total + 1 Where productId =" .$productId);
            }else{
                DB::getInstance()->execute("Insert Into "._DB_PREFIX_."groupcategory_product_view (productId, total) Value ('$productId', 1)");
            }
		}        
			
		if(count($this->codeCss) == 0){
			$cssStyles = DB::getInstance()->executeS("Select id, name From "._DB_PREFIX_."groupcategory_styles");
			if($cssStyles){
				foreach($cssStyles as $cssStyle){
					$this->codeCss[] = file_get_contents(_PS_MODULE_DIR_.'groupcategory/css/front-end/style-'.$cssStyle['id'].".css");
					
				}        
			}
		}
        
		
        
        $themeOption = @Configuration::get('OVIC_CURRENT_OPTION');
        //$themeOption = 3;
        if(isset($themeOption) && $themeOption >0){
            $this->context->controller->addJS(($this->_path).'js/front-end/common'.$themeOption.'.js');
            $this->context->controller->addCSS(($this->_path).'css/front-end/style'.$themeOption.'.css');    
        }else{
            $themeOption = '';
            $this->context->controller->addJS(($this->_path).'js/front-end/common.js');
            $this->context->controller->addCSS(($this->_path).'css/front-end/style.css');            
        }
        
        $this->context->controller->addJS(($this->_path).'js/front-end/jquery.actual.min.js');
		$this->context->smarty->assign(array(
            'comparator_max_item' => (int)(Configuration::get('PS_COMPARATOR_MAX_ITEM')),            
            'groupCategoryUrl'=> __PS_BASE_URI__.'modules/'.$this->name,
            'imageSize'=>$this->imageHomeSize,
        	'h_per_w'=> round($this->imageHomeSize['height']/$this->imageHomeSize['width'], 2),
			'codeCss'=>$this->codeCss,
            'themeOption'=>$themeOption
        )); 
		include_once (_PS_CONTROLLER_DIR_.'front/CompareController.php');
		if(!$this->compareProductIds = CompareProduct::getCompareProducts($this->context->cookie->id_compare)) $this->compareProductIds = array();
	}
	public function hookdisplayGroupFashions($params)
	{
		return $this->hooks('hookdisplayGroupFashions', $params);
	}
	public function hookdisplayGroupFoods($params)
	{
		return $this->hooks('hookdisplayGroupFoods', $params);
	}
	public function hookdisplayGroupSports($params)
	{
		return $this->hooks('hookdisplayGroupSports', $params);
	}	
    public function hooks($hookName, $param){
        //$moduleLayout = 'groupcategory.tpl';
        $langId = Context::getContext()->language->id;		
        $shopId = Context::getContext()->shop->id;        
        $hookName = str_replace('hook','', $hookName);        
        $hookId = Hook::getIdByName($hookName);
        $items = DB::getInstance()->executeS("Select DISTINCT g.*, gl.`name`, gl.`banner`, gl.banner_link, gl.`banner_size` From "._DB_PREFIX_."groupcategory_groups as g INNER JOIN "._DB_PREFIX_."groupcategory_group_lang AS gl On g.id = gl.group_id Where g.status = 1 AND g.position = ".$hookId." AND g.id_shop=".$shopId." AND gl.id_lang = ".$langId." AND gl.id_shop = ".$shopId." Order By g.ordering");		
        $modules = array();
        if($items){            
            foreach($items as $i=>$item){                
                $modules[] = array(
					'id'=>$item['id'], 
					'name'=>$item['name'], 
					'layout'=>$item['layout'], 
					'content'=>$this->buildModule($item, $langId, $shopId)
					);                
            }
        }   
        $this->context->smarty->assign('groupCategoryModules', $modules);
        $hookname_div  = 'group-fashion';
       
        
        if ($hookName == 'displayGroupFoods'){
            $hookname_div  = 'group-foods';
        }elseif ($hookName == 'displayGroupSports'){
            $hookname_div  = 'group-sports';
        }elseif ($hookName == 'displayGroupFashions'){
            $hookname_div  = 'group-fashion';
        }
        
        $this->context->smarty->assign('hookname_div', $hookname_div);  
		return $this->display(__FILE__, 'groupcategory.tpl');
    }
    public function buildModule($moduleItem, $langId, $shopId){
        $themeOption = @Configuration::get('OVIC_CURRENT_OPTION');
        //$themeOption = 3;
        $limit = 12;
        $itemMinWidth = 200;
        if($moduleItem['itemConfig']){
            $itemConfig = @json_decode($moduleItem['itemConfig']);
            if(isset($itemConfig) && $itemConfig){
                if($itemConfig->countItem >0) $limit = $itemConfig->countItem;
                if($itemConfig->itemMinWidth >0) $itemMinWidth = $itemConfig->itemMinWidth;
            }
        }
        $manufactureConfig = @json_decode($moduleItem['manufactureConfig']);              
        $types = @json_decode($moduleItem['types']);
        $module = array();
        $module['id'] = $moduleItem['id'];
        $module['name'] = $moduleItem['name'];
        $module['type'] = $moduleItem['type_default'];
        $module['style_id'] = $moduleItem['style_id'];
        $module['layout'] = $moduleItem['layout'];
        $module['width'] = $this->imageHomeSize['width']; 
        $module['height'] = $this->imageHomeSize['height'];
        $module['icon'] = $this->getGroupIconSrc($moduleItem['icon']);
        
        $module['w_p_h'] = round($this->imageHomeSize['width']/$this->imageHomeSize['height'], 3);
      
        $module['itemMinWidth'] = $itemMinWidth;        
        $allProducts = array();
        $manufacturers = array();
        //if(!$this->cache->exists('menufacture-'.$langId.'-'.$shopId.'-'.$moduleItem['id'])){
            $manufacturers['width']= 0;
            $manufacturers['height']= 0;
            $manufacturers['w_p_h']= 0;
            if(isset($manufactureConfig) && $manufactureConfig){            
                $manufacturers['items'] = array();
                if(isset($manufactureConfig->ids) && count($manufactureConfig->ids) >0){
    				foreach($manufactureConfig->ids as $i => $manId){
    					$man =  new Manufacturer((int)$manId, $langId);
                        if($man){
                            $manufacturers['items'][] = array('moduleId'=>$moduleItem['id'], 'image'=>_THEME_MANU_DIR_.$man->id.'-brand-136x69.jpg', 'name'=>$man->name, 'link'=>$this->context->link->getManufacturerLink($man->id, $man->link_rewrite, $langId));    
                        }					
    				}				
    			}
            }
            //$this->cache->set('menufacture-'.$langId.'-'.$shopId.'-'.$moduleItem['id'], $manufacturers, $this->cacheTime);    
        //}else{
        //    $manufacturers = $this->cache->get('menufacture-'.$langId.'-'.$shopId.'-'.$moduleItem['id']);
        //}        
        
             
        $cacheBanner = false;
        $banners = array();
        //if($this->cache->exists('banner-'.$langId.'-'.$shopId.'-'.$moduleItem['id'])){
        //    $cacheBanner = true;
        //    $banners = $this->cache->get('banner-'.$langId.'-'.$shopId.'-'.$moduleItem['id']);
        //}
        
        //if($cacheBanner == false){
            $bannerSize = json_decode($moduleItem['banner_size']); 
            $parentBanner = $this->getGroupBannerSrc($moduleItem['banner']);                    
            $banners['width'] = $bannerSize->width;
            $banners['height'] = $bannerSize->height;
            $banners['w_per_h'] = $bannerSize->w_per_h;
            $banners['items'] = array();        
    		$banners['items'][] = array('image'=>$parentBanner, 'link'=>$moduleItem['banner_link'], 'title'=>$moduleItem['name'], 'groupId'=>$moduleItem['id'], 'itemId'=>0);    
        //}
        
        
        $arrSubCategory = $this->getCategoryIds($moduleItem['categoryId']);
       	$arrSubCategory[] = $moduleItem['categoryId'];
        
        $products = array();
        $products['type'] = 'all';
        $products['moduleId'] = $moduleItem['id'];        
        $products['groupId'] = 0;
        if($moduleItem['type_default'] == 'view'){
            $products['items'] = $this->getProductsOrderView($langId, $arrSubCategory, null, false, true, $limit, 0, true);
            if($products['items']) 
                $products['items'] = $this->getProductAttributesOther($products['items']);
            else 
                $products['items'] = array();
        }else{
            //$cacheKey = 'products-'.$langId.'-'.$shopId.'-all-'.$moduleItem['id'].'-0';
            //if(!$this->cache->exists($cacheKey)){
                if($moduleItem['type_default'] == 'saller'){        			
                	$products['items'] =  $this->getProductsOrderSales($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array();                    
                }elseif($moduleItem['type_default'] == 'price'){
                	$products['items'] =  $this->getProductsOrderPrice($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array();                    
        		}elseif($moduleItem['type_default'] == 'special'){
                	$products['items'] =  $this->getProductsOrderSpecial($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array();
                        			
        		}else{
                	$products['items'] =  $this->getProductsOrderAddDate($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array();				
        		}
            //    $this->cache->set($cacheKey, $products['items'], $this->cacheTime);
            //}else{
            //    $products['items'] = $this->cache->get($cacheKey);
            //}
        }
        $allProducts[] = $products;
        $allTypes = array();
        
        if(isset($types) && $types){
            foreach($types as $type){
                $allTypes[] = array('type'=>$type, 'name'=>$this->arrType[$type]);
                //$cacheKey = 'products-'.$langId.'-'.$shopId.'-'.$type.'-'.$moduleItem['id'].'-0';
                $products = array();
                $products['type'] = $type;
                $products['moduleId'] = $moduleItem['id'];
                $products['groupId'] = 0;
                if($type == 'view'){
                    $products['items'] = $this->getProductsOrderView($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array(); 
                }
                //if(!$this->cache->exists($cacheKey)){
                    if($type == 'saller'){        			
                    	$products['items'] =  $this->getProductsOrderSales($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                        if($products['items'])
                            $products['items'] = $this->getProductAttributesOther($products['items']);    
                        else
                            $products['items'] = array();
                        
                                            
                    }elseif($type == 'price'){
                    	$products['items'] =  $this->getProductsOrderPrice($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                        if($products['items'])
                            $products['items'] = $this->getProductAttributesOther($products['items']);
                        else 
                            $products['items'] = array();  
            		}elseif($type == 'special'){
                    	$products['items'] =  $this->getProductsOrderSpecial($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                        if($products['items'])
                            $products['items'] = $this->getProductAttributesOther($products['items']);
                        else 
                            $products['items'] = array();			
            		}else{
                    	$products['items'] =  $this->getProductsOrderAddDate($langId, $arrSubCategory, null, false, true, $limit, 0, true);
                        if($products['items'])
                            $products['items'] = $this->getProductAttributesOther($products['items']);
                        else 
                            $products['items'] = array();				
            		}
                //    $this->cache->set($cacheKey, $products['items'], $this->cacheTime);
                //}else{
                //    $products['items'] = $this->cache->get($cacheKey);
               // }
                $allProducts[] = $products;
            }
        }        
        
        
        $items = DB::getInstance()->executeS("Select DISTINCT i.*, il.name, il.`banner`, il.`banner_link` From "._DB_PREFIX_."groupcategory_items AS i Inner Join "._DB_PREFIX_."groupcategory_item_lang AS il On i.id = il.itemId Where i.status = 1 AND i.`groupId` = ".$moduleItem['id']." AND il.id_lang = ".$langId." AND id_shop = ".$shopId." Order By i.`ordering`");
        
        if($items){
            foreach($items as &$item){                
                $arrSubCategory = $this->getCategoryIds($item['categoryId']);
        		$arrSubCategory[] = $item['categoryId'];
                $products = array();
                $products['type'] = 'all';//$moduleItem['type_default'];
                $products['moduleId'] = $moduleItem['id'];        
                $products['groupId'] = $item['id'];                
                //$cacheKey = 'products-'.$langId.'-'.$shopId.'-'.$moduleItem['type_default'].'-'.$moduleItem['id'].'-'.$item['id'];
                if($moduleItem['type_default'] == 'view'){
                    $products['items'] = $this->getProductsOrderView($langId, $arrSubCategory, null, false, true, $item['maxItem'], 0, true);
                    if($products['items'])
                        $products['items'] = $this->getProductAttributesOther($products['items']);
                    else
                        $products['items'] = array();
                }else{                    
                    //if(!$this->cache->exists($cacheKey)){
                        if($moduleItem['type_default'] == 'saller'){        			
                        	$products['items'] =  $this->getProductsOrderSales($langId, $arrSubCategory, null, false, true, $item['maxItem'], 0, true);
                            if($products['items'])
                                $products['items'] = $this->getProductAttributesOther($products['items']);
                            else 
                                $products['items'] = array();                    
                        }elseif($moduleItem['type_default'] == 'price'){
                        	$products['items'] =  $this->getProductsOrderPrice($langId, $arrSubCategory, null, false, true, $item['maxItem'], 0, true);
                            if($products['items'])
                                $products['items'] = $this->getProductAttributesOther($products['items']);
                            else 
                                $products['items'] = array();                    
                		}elseif($moduleItem['type_default'] == 'special'){
                        	$products['items'] =  $this->getProductsOrderSpecial($langId, $arrSubCategory, null, false, true, $item['maxItem'], 0, true);
                            if($products['items'])
                                $products['items'] = $this->getProductAttributesOther($products['items']);
                            else
                                $products['items'] = array();			
                		}else{
                        	$products['items'] =  $this->getProductsOrderAddDate($langId, $arrSubCategory, null, false, true, $item['maxItem'], 0, true);
                            if($products['items'])
                                $products['items'] = $this->getProductAttributesOther($products['items']);
                            else 
                                $products['items'] = array();				
                		}
                        
                        //$this->cache->set($cacheKey, $products['items'], $this->cacheTime);
                    //}else{
                    //    $products['items'] = $this->cache->get($cacheKey);
                    //}
                }
                $allProducts[] = $products;                
                //if($cacheBanner == false){
                    $itemBanner = $this->getItemBannerSrc($item['banner'], true);
    				if(!$itemBanner){
    					$banners['items'][] = array('image'=>$parentBanner, 'link'=>$item['banner_link'], 'title'=>$item['name'], 'groupId'=>$moduleItem['id'], 'itemId'=>$item['id']);
    				}else{
    					$banners['items'][] = array('image'=>$itemBanner, 'link'=>$item['banner_link'], 'title'=>$item['name'], 'groupId'=>$moduleItem['id'], 'itemId'=>$item['id']);
    				}    
                //}        
            }
        }
        $currency = new Currency($this->context->currency->id);
        $this->context->smarty->assign(array(
			'module'=>$module,
            'types'=>$allTypes,
            'categories'=>$items,
            'manufacturers'=>$manufacturers,
            'banners'=>$banners,
            'products'=>$allProducts,
            'currencySign'=>$currency->sign,
            
		));
        
        if(isset($themeOption) && $themeOption >0){
            return $this->display(__FILE__, $moduleItem['layout'].'.option'.$themeOption.'.layout.tpl');    
        }else{
            
            return $this->display(__FILE__, $moduleItem['layout'].'.layout.tpl');
        }		
		
        
    }
    function getProductAttributesOther($products){
    	if($products){
        	$currency = new Currency($this->context->currency->id);
        	$timeNow = time();			
			$http = 'http';		
		 	if (isset($_SERVER["HTTPS"]) &&  $_SERVER["HTTPS"] == "on") {$http .= "s";}
		 	$http .= "://";
            foreach($products as $i=> &$product){
            	$product['image'] = Context::getContext()->link->getImageLink($product['link_rewrite'], $product['id_image'], 'home_default');
                if(strpos($product['image'], $http) === false) $product['image'] = $http.$product['image'];				
				$product['price_new'] = number_format(Tools::convertPriceFull($product['price'], $currency), 2, '.', ',');
				$product['price_old'] = '0';
				$product['reduction'] = '';
				if($product['specific_prices']){
				    
                	$from = strtotime($product['specific_prices']['from']);
                    $to = strtotime($product['specific_prices']['to']);                    
					if($product['specific_prices']['from_quantity'] == '1' && (($timeNow >= $from && $timeNow <= $to) || ($product['specific_prices']['to'] == '0000-00-00 00:00:00'))){
						$product['price_old'] = number_format(Tools::convertPriceFull($product['price_without_reduction'], $currency), 2, '.', ',');												
						if($product['specific_prices']['reduction_type'] == 'percentage'){
							$product['reduction'] = ($product['specific_prices']['reduction']*100).'%';
						}else{
							$product['reduction'] = number_format(Tools::convertPriceFull($product['specific_prices']['reduction'], $currency), 2, '.', ',');
						}						
                    }
                }
				$product['rates'] = '';
				$product['totalRates'] = '0';		
				if(Module::isInstalled('productcomments') == 1){
					$productRate = $this->getProductRatings($product['id_product']);				
					if(isset($productRate) && $productRate['avg'] >0){
						if($productRate['total'] >1)
							$product['totalRates'] = $productRate['total'].'s';
						else
							$product['totalRates'] = $productRate['total'];
						for($i=0; $i<5; $i++){
							if($productRate['avg'] >= $i){
								$product['rates'] .= '<div class="star"></div>';
							}else{
								$product['rates'] .= '<div class="star star_off"></div>';
							}
						}
					}else{
						$product['rates'] .= '<div class="star star_off"></div>';
						$product['rates'] .= '<div class="star star_off"></div>';
						$product['rates'] .= '<div class="star star_off"></div>';
						$product['rates'] .= '<div class="star star_off"></div>';
						$product['rates'] .= '<div class="star star_off"></div>';
					}
				}else{
					$product['rates'] .= '<div class="star star_off"></div>';
					$product['rates'] .= '<div class="star star_off"></div>';
					$product['rates'] .= '<div class="star star_off"></div>';
					$product['rates'] .= '<div class="star star_off"></div>';
					$product['rates'] .= '<div class="star star_off"></div>';
				}
				$product['isCompare'] = 0;									
				if($this->compareProductIds)
					if(in_array($product['id_product'], $this->compareProductIds)) $product['isCompare'] = 1;                
				
            }
        }
        return $products;
    }
    
	function getCacheId($name=null)
	{
		return parent::getCacheId('groupcategory|'.$name);
	}
    function clearCache()
	{
		if($this->cache) $this->cache->cleanup();
        Tools::clearCache();		
	}
	function headerClearCache(){
		parent::_clearCache('group.header.tpl');
		//$this->_clearCache('group.header.tpl');
	}
   	function typesClearCache(){
		parent::_clearCache('group.types.tpl');
   		//$this->_clearCache('group.types.tpl');
   	}
   	function menusClearCache(){
		parent::_clearCache('group.menus.tpl');
   		//$this->_clearCache('group.menus.tpl');
   	}
	function bannersClearCache(){
		parent::_clearCache('group.banners.tpl');
   		//$this->_clearCache('group.banners.tpl');
   	}
    
    
    
    public function getProductRatings($id_product)
	{
		$validate = Configuration::get('PRODUCT_COMMENTS_MODERATE');
		$sql = 'SELECT (SUM(pc.`grade`) / COUNT(pc.`grade`)) AS avg,
				MIN(pc.`grade`) AS min,
				MAX(pc.`grade`) AS max,
                COUNT(pc.`grade`) AS total
			FROM `'._DB_PREFIX_.'product_comment` pc
			WHERE pc.`id_product` = '.(int)$id_product.'
			AND pc.`deleted` = 0'.
			($validate == '1' ? ' AND pc.`validate` = 1' : '');
		return DB::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

	}
    public function getProductsOrderSales($id_lang, $arrCategory = array(), $params = null, $total=false, $short = true, $limit, $offset = 0, $getProperties = true){	
        $context = Context::getContext();
        $order_by = 'sales';
        $order_way = 'desc';
        $where = "";
        if($arrCategory) $catIds = implode(', ', $arrCategory);  
        if($params){
            $order_way = $params->orderType;
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }      
        
        
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM `'._DB_PREFIX_.'product_sale` ps
				LEFT JOIN `'._DB_PREFIX_.'product` p ON ps.`id_product` = p.`id_product`
				'.Shop::addSqlAssociation('product', 'p', false).'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1 AND p.`visibility` != \'none\'  '.$where;
				return (int) DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);				
		}        
        
        
        /*
        if($short == true){
        	$interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
            $sql = 'SELECT p.id_product, p.on_sale, p.price, p.id_category_default, p.reference, p.ean13, p.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`name`, pl.`link_rewrite`, ps.`quantity` AS sales, MAX(image_shop.`id_image`) id_image, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)) > 0 AS new';
        }else{
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, ps.`quantity` AS sales , MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }        
        */
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, ps.`quantity` AS sales, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, ps.`quantity` AS sales , MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }
        $sql .= ' FROM `'._DB_PREFIX_.'product_sale` ps
				LEFT JOIN `'._DB_PREFIX_.'product` p
			 	   ON p.`id_product` = ps.`id_product`
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product
                    ORDER BY `'.pSQL($order_by).'` '.pSQL($order_way).' Limit '.$offset.', '.$limit;
		$result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);               
		if (!$result) return false;
        if($getProperties == false) return $result;
		return Product::getProductsProperties($id_lang, $result);
	}
    
    function getProductsOrderPrice($id_lang, $arrCategory = array(), $params = null, $total = false, $short = true, $limit, $offset = 0, $getProperties = true){
        $context = Context::getContext();
        $order_by = 'price';
        $order_way = 'DESC';        
        $where = "";
        if($arrCategory) $catIds = implode(', ', $arrCategory);
        if($params){
            $order_way = $params->orderType;        
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }
        
        
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM  `'._DB_PREFIX_.'product` p 
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1
					AND p.`visibility` != \'none\' '.$where;				
				return (int) DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);				
		}
		
        
        /*
        if($short == true){
        	$interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
            $sql = 'SELECT p.id_product, p.on_sale, p.price, p.id_category_default, p.reference, p.ean13, p.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`name`, pl.`link_rewrite`, MAX(image_shop.`id_image`) id_image, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)) > 0 AS new';
        }else{
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }        
        */
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }
        $sql .= ' FROM  `'._DB_PREFIX_.'product` p 
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product
                    ORDER BY p.`'.pSQL($order_by).'` '.pSQL($order_way).' Limit '.$offset.', '.$limit;
        
                
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            
          if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);

    }
    function getProductsOrderRand($id_lang, $arrCategory = array(), $params = null, $total=false, $short = true, $limit, $offset=0, $getProperties = true){
        $context = Context::getContext();
        $order_by = 'RAND()';
        $order_way = '';        
        $where = "";
        if($arrCategory) $catIds = implode(', ', $arrCategory);
        if($params){
            $order_way = $params->orderType;   
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }
        
        
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		
        /*
        if($short == true){
        	$interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
            $sql = 'SELECT p.id_product, p.on_sale, p.price, p.id_category_default, p.reference, p.ean13, p.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`name`, pl.`link_rewrite`, MAX(image_shop.`id_image`) id_image, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)) > 0 AS new';
        }else{
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice ';
        }        
        */
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }
        $sql .= ' FROM  `'._DB_PREFIX_.'product` p 
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product
                    ORDER BY RAND() Limit '.$limit;
        
           
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    
          if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);

    }
    function getProductsOrderAddDate($id_lang, $arrCategory = array(), $params = null, $total=false, $short = true, $limit, $offset = 0, $getProperties = true){
        $context = Context::getContext();
        
        $order_by = 'date_add';
        $order_way = 'DESC';        
        $where = "";
        if($arrCategory) $catIds = implode(', ', $arrCategory);
        if($params){
            $order_way = $params->orderType;        
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }        
                 
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM  `'._DB_PREFIX_.'product` p 
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1 AND p.`visibility` != \'none\' '.$where;				
				return (int) DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);				
		}
        /*
        if($short == true){
        	$interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
            $sql = 'SELECT p.id_product, p.on_sale, p.price, p.id_category_default, p.reference, p.ean13, p.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`name`, pl.`link_rewrite`, MAX(image_shop.`id_image`) id_image, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)) > 0 AS new';
        }else{
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }        
        */
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }
        $sql .= ' FROM  `'._DB_PREFIX_.'product` p 
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product
                    ORDER BY p.`'.pSQL($order_by).'` '.pSQL($order_way).' Limit '.$offset.', '.$limit;
                    
        
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            
          if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);
    }
    function getProductById($id_lang, $productId, $short = true, $getProperties = true){        
        $context = Context::getContext();
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }
        $sql .= ' FROM `'._DB_PREFIX_.'category_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p
					ON p.`id_product` = cp.`id_product`
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE 
                    product_shop.`id_shop` = '.(int)$context->shop->id.'
					AND product_shop.`id_product` = '.$productId.' 
                    AND product_shop.`visibility` IN ("both", "catalog")
                    GROUP BY product_shop.id_product';

             
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (!$result) return false;
            if($getProperties == false) return $result;            
    		return Product::getProductProperties($id_lang, $result);
    }
       
    public function getProductsOrderSpecial($id_lang, $arrCategory = array(), $params = null, $total = false, $short = true, $limit, $offset = 0, $getProperties = true)
	{
        $currentDate = date('Y-m-d');
        $context = Context::getContext();
        $id_address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
		$ids = Address::getCountryAndState($id_address);
		$id_country = (int)($ids['id_country'] ? $ids['id_country'] : Configuration::get('PS_COUNTRY_DEFAULT'));
        
        $order_by = 'reduction';
        $order_way = 'DESC';
        $where = "";
        
        if($arrCategory) $catIds = implode(', ', $arrCategory);
        if($params){
            $order_way = $params->orderType;
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }        
        
                
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM  (`'._DB_PREFIX_.'product` p 
                INNER JOIN `'._DB_PREFIX_.'specific_price` sp On p.id_product = sp.id_product)  
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1 
                    AND sp.`id_shop` IN(0, '.(int)$context->shop->id.') 
					AND sp.`id_currency` IN(0, '.(int)$context->currency->id.') 
					AND sp.`id_country` IN(0, '.(int)$id_country.') 
					AND sp.`id_group` IN(0, '.(int)$context->customer->id_default_group.') 
					AND sp.`id_customer` IN(0) 
					AND sp.`from_quantity` = 1 					
					AND (sp.`from` = \'0000-00-00 00:00:00\' OR \''.pSQL($currentDate).'\' >= sp.`from`)
					AND (sp.`to` = \'0000-00-00 00:00:00\' OR \''.pSQL($currentDate).'\' <= sp.`to`)					
					AND sp.`reduction` > 0
					AND p.`visibility` != \'none\' '.$where;			
			return DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);					
		}
       
        /*
        if($short == true){
	     	$interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
            $sql = 'SELECT DISTINCT p.id_product, p.on_sale, p.price, p.id_category_default, p.reference, p.ean13, p.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`name`, pl.`link_rewrite`, MAX(image_shop.`id_image`) id_image, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.$interval.' DAY)) > 0 AS new';
            
        }else{
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }
        */
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }        
        $sql .= ' FROM (`'._DB_PREFIX_.'product` p 
                INNER JOIN `'._DB_PREFIX_.'specific_price` sp On p.id_product = sp.id_product) 
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE 
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND sp.`id_shop` IN(0, '.(int)$context->shop->id.') 
					AND sp.`id_currency` IN(0, '.(int)$context->currency->id.') 
					AND sp.`id_country` IN(0, '.(int)$id_country.') 
					AND sp.`id_group` IN(0, '.(int)$context->customer->id_default_group.')  
					AND sp.`id_customer` IN(0) 
					AND sp.`from_quantity` = 1 
					AND (sp.`from` = \'0000-00-00 00:00:00\' OR \''.pSQL($currentDate).'\' >= sp.`from`) 
					AND (sp.`to` = \'0000-00-00 00:00:00\' OR \''.pSQL($currentDate).'\' <= sp.`to`) 					
					AND sp.`reduction` > 0 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product 
                    ORDER BY sp.`'.pSQL($order_by).'` '.pSQL($order_way).' Limit '.$offset.', '.$limit;
        
       	                
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    
          if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);        
	}
    function getProductsByIds($id_lang, $productIds=array(), $total=false, $getProperties){
		$context = Context::getContext();
		if($productIds) $ids = trim(implode(', ', $productIds));		
		else return false;
		if(!$ids) return false;
        
        
        
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM  `'._DB_PREFIX_.'product` p 
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1 AND p.id_product IN ('.$ids.')
					AND p.`visibility` != \'none\' ';				
				return (int) DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);				
		}
        
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice ';
            
        }
        
        $sql .= ' FROM `'._DB_PREFIX_.'category_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p
					ON p.`id_product` = cp.`id_product`
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE 
                    product_shop.`id_shop` = '.(int)$context->shop->id.'  
                    AND product_shop.`visibility` IN ("both", "catalog") 
                    AND product_shop.id_product IN ('.$ids.') 
                    GROUP BY product_shop.id_product';

	
           	$result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);
    }
    function getProductList($id_lang, $arrCategory = array(), $notIn = '', $keyword = '', $getTotal = false, $offset=0, $limit=10){
        
        $where = "";
        if($arrCategory){
            $catIds = implode(', ', $arrCategory);
        }
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= ' AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.`id_product` Not In ('.$notIn.') AND cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= ' AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.`id_product` Not In ('.$notIn.') AND cp.id_category IN ('.$catIds.'))';
		}
        if($keyword != '') $where .= " AND (p.id_product) LIKE '%".$keyword."%' OR pl.name LIKE '%".$keyword."%'";
        $sqlTotal = 'SELECT COUNT(p.`id_product`) AS nb
					FROM `'._DB_PREFIX_.'product` p
					'.Shop::addSqlAssociation('product', 'p').' 
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					   ON p.`id_product` = pl.`id_product`
					   AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
					WHERE product_shop.`active` = 1 AND product_shop.`active` = 1 AND p.`visibility` != \'none\' '.$where;
        $total = (int)DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sqlTotal);
        if($getTotal == true) return $total;
        if($total <=0) return false;                    
        $sql = 'Select p.*, pl.`name`, pl.`link_rewrite`, IFNULL(stock.quantity, 0) as quantity_all, MAX(image_shop.`id_image`) id_image 
                FROM  `'._DB_PREFIX_.'product` p 
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
				LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`)
					AND tr.`id_country` = '.(int)Context::getContext()->country->id.'
					AND tr.`id_state` = 0
				LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
				'.Product::sqlStock('p').'
				WHERE product_shop.`active` = 1
					AND p.`visibility` != \'none\'  '.$where.'			
				GROUP BY product_shop.id_product Limit '.$offset.', '.$limit;
			
                $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
                return Product::getProductsProperties($id_lang, $result);

    }
    function getProductsOrderView($id_lang, $arrCategory = array(), $params = null, $total=false, $short = true, $limit, $offset = 0, $getProperties = true){
        $context = Context::getContext();
        
        $order_by = 'date_add';
        $order_way = 'DESC';        
        $where = "";
        if($arrCategory) $catIds = implode(', ', $arrCategory);
        if($params){
            $order_way = $params->orderType;        
            if($params->displayOnly == 'condition-new') $where .= " AND p.condition = 'new'";
            elseif($params->displayOnly == 'condition-used') $where .= " AND p.condition = 'used'";
            elseif($params->displayOnly == 'condition-refurbished') $where .= " AND p.condition = 'refurbished'";    
        }        
                 
        if (Group::isFeatureActive())
		{
			$groups = FrontController::getCurrentCustomerGroups();
			$where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cp.id_category IN ('.$catIds.') AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			)';
		}else{
            $where .= 'AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp 
				WHERE cp.id_category IN ('.$catIds.'))';
		}
		if($total == true){
			$sql = 'SELECT COUNT(p.id_product)
				FROM  `'._DB_PREFIX_.'product` p 
                '.Shop::addSqlAssociation('product', 'p', false).'				
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'				
				WHERE product_shop.`active` = 1 AND p.`visibility` != \'none\' '.$where;				
				return (int) DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);				
		}
        
        if($short == true){
        	$sql = 'SELECT p.id_product, p.ean13, p.reference, p.id_category_default, p.on_sale, p.quantity, p.minimal_quantity, p.price, p.wholesale_price, p.quantity_discount, p.show_price, p.condition, p.date_add, p.date_upd, 
                    product_shop.on_sale, product_shop.id_category_default, product_shop.minimal_quantity, product_shop.price, product_shop.wholesale_price, product_shop.show_price, product_shop.condition, product_shop.indexed, product_shop.date_add, product_shop.date_upd, 
                    stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,                     
					pl.`available_later`, pl.`link_rewrite`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
        }else{            
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
					il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
						DAY)) > 0 AS new, product_shop.price AS orderprice';
            
        }        
        
        $sql .= ' FROM  `'._DB_PREFIX_.'product` p 
                LEFT JOIN `'._DB_PREFIX_.'groupcategory_product_view` AS gv 
                    On gv.productId = p.id_product 
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
				'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image` i
					ON (i.`id_product` = p.`id_product`)'.
				Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int)$id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE
                    product_shop.`id_shop` = '.(int)$context->shop->id.' 
                    AND product_shop.`visibility` IN ("both", "catalog") '.$where.' 
                    GROUP BY product_shop.id_product 
                    ORDER BY gv.`total` DESC Limit '.$offset.', '.$limit;
                    
        
           $result = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            
          if (!$result) return false;
            if($getProperties == false) return $result;
    		return Product::getProductsProperties($id_lang, $result);
    }
    
}
