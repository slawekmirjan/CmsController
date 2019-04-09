<?php
class CmsController extends CmsControllerCore
{
    /*
    * module: cmsproducts
    * date: 2017-12-09 08:45:00
    * version: 1.5.2
    */
    /*
    * module: cmsproducts
    * date: 2017-12-09 08:45:00
    * version: 1.5.2
    */
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function init()
    {
        if ($id_cms = (int)Tools::getValue('id_cms'))
        {
            $this->cms = new CMS($id_cms, $this->context->language->id, $this->context->shop->id);
        }
        elseif ($id_cms_category = (int)Tools::getValue('id_cms_category'))
        {
            $this->cms_category = new CMSCategory($id_cms_category, $this->context->language->id, $this->context->shop->id);
        }
        if (Configuration::get('PS_SSL_ENABLED') && Tools::getValue('content_only') && $id_cms && Validate::isLoadedObject($this->cms) && in_array($id_cms, array(
                (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
                (int)Configuration::get('LEGAL_CMS_ID_REVOCATION')
            ))
        )
        {
            $this->ssl = true;
        }
        parent::init();
        $this->canonicalRedirection();
        if (Validate::isLoadedObject($this->cms))
        {
            $adtoken = Tools::getAdminToken('AdminCmsContent' . (int)Tab::getIdFromClassName('AdminCmsContent') . (int)Tools::getValue('id_employee'));
            if (!$this->cms->isAssociatedToShop() || !$this->cms->active && Tools::getValue('adtoken') != $adtoken)
            {
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
            }
            else
            {
                $this->assignCase = 1;
            }
        }
        elseif (Validate::isLoadedObject($this->cms_category) && $this->cms_category->active)
        {
            $this->assignCase = 2;
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJS(_THEME_JS_DIR_ . 'cms.js');
        $this->addJS(_THEME_JS_DIR_.'pannellum.js');
		$this->addCSS(_THEME_CSS_DIR_ . 'product_list.css');
        $this->addCSS(_THEME_CSS_DIR_ . 'cms.css');
        $this->addCSS(_THEME_CSS_DIR_.'pannellum.css');
        $this->addCSS(_PS_MODULE_DIR_ . 'cmsproducts/cmsproducts.css');
    }

    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function initContent()
    {
        parent::initContent();
        $parent_cat = new CMSCategory(1, $this->context->language->id);
        $this->context->smarty->assign('id_current_lang', $this->context->language->id);
        $this->context->smarty->assign('home_title', $parent_cat->name);
        $this->context->smarty->assign('cgv_id', Configuration::get('PS_CONDITIONS_CMS_ID'));
        if ($this->assignCase == 1)
        {
            if (isset($this->cms->id_cms_category) && $this->cms->id_cms_category)
            {
                $path = Tools::getFullPath($this->cms->id_cms_category, $this->cms->meta_title, 'CMS');
            }
            elseif (isset($this->cms_category->meta_title))
            {
                $path = Tools::getFullPath(1, $this->cms_category->meta_title, 'CMS');
            }
            $this->cms->content = $this->returnContent($this->cms->content);
            $this->context->smarty->assign(array(
                'cms' => $this->cms,
                'content_only' => (int)Tools::getValue('content_only'),
                'path' => $path,
                'body_classes' => array(
                    $this->php_self . '-' . $this->cms->id,
                    $this->php_self . '-' . $this->cms->link_rewrite
                )
            ));
            if ($this->cms->indexation == 0)
            {
                $this->context->smarty->assign('nobots', true);
            }
        }
        elseif ($this->assignCase == 2)
        {
            $this->context->smarty->assign(array(
                'category' => $this->cms_category,
                'cms_category' => $this->cms_category,
                'sub_category' => $this->cms_category->getSubCategories($this->context->language->id),
                'cms_pages' => CMS::getCMSPages($this->context->language->id, (int)$this->cms_category->id, true, (int)$this->context->shop->id),
                'path' => ($this->cms_category->id !== 1) ? Tools::getPath($this->cms_category->id, $this->cms_category->name, false, 'CMS') : '',
                'body_classes' => array(
                    $this->php_self . '-' . $this->cms_category->id,
                    $this->php_self . '-' . $this->cms_category->link_rewrite
                )
            ));
        }
        $this->setTemplate(_PS_THEME_DIR_ . 'cms.tpl');
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public static function getImagesByID($id_product, $limit = 0)
    {
        $id_image = Db::getInstance()->ExecuteS('SELECT `id_image` FROM `' . _DB_PREFIX_ . 'image` WHERE cover=1 AND `id_product` = ' . (int)$id_product . ' ORDER BY position ASC LIMIT 0, ' . (int)$limit);
        $toReturn = array();
        if (!$id_image)
        {
            return null;
        }
        else
        {
            foreach ($id_image as $image)
            {
                $toReturn[] = $id_product . '-' . $image['id_image'];
            }
        }
        return $toReturn;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function returnProduct($id_product)
    {
        $explode[] = $id_product;
        foreach ($explode as $tproduct)
        {
            if ($tproduct != '')
            {
                $x = (array)new Product($tproduct, true, $this->context->language->id);
                if(!empty($x['id_product']) && $x['active'] == 1) {
                    $productss[$tproduct] = $x;
                    $productss[$tproduct]['id_product'] = $tproduct;
                    $image = self::getImagesByID($tproduct, 1);
                    $picture = explode('-', $image[0]);
                    $productss[$tproduct]['id_image'] = $picture[1];
                }
            }
        }
        $products = Product::getProductsProperties($this->context->language->id, $productss);
        $this->context->smarty->assign('products', $products);
        $contents = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cmsextracontent/views/templates/front/products.tpl');
        return $contents;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function returnProducts($id_product)
    {
        $explode_products = explode(",", $id_product);
        foreach ($explode_products AS $idp)
        {
            $explode[] = $idp;
            foreach ($explode as $tproduct)
            {
                if ($tproduct != '')
                {
                    $x = (array)new Product($tproduct, true, $this->context->language->id);
                    if(!empty($x['id']) && $x['active'] == 1) {
                        $productss[$tproduct] = $x;
                        $productss[$tproduct]['id_product'] = $tproduct;
                        $image = self::getImagesByID($tproduct, 1);
                        $picture = explode('-', $image[0]);
                        $productss[$tproduct]['id_image'] = $picture[1];
                    }
                }
            }
        }
        
        $products = Product::getProductsProperties($this->context->language->id, $productss);
        $this->context->smarty->assign('products', $products);
        $contents = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cmsextracontent/views/templates/front/products.tpl');
        return $contents;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function returnProductTextiles($name)
    {
        $nbProducts = Configuration::get('CMSEXTRACONTENT_ITEMS_COUNT'); 
        $id_categories = explode(',', Configuration::get('CMSEXTRACONTENT_ID_CATEGORY'));
        $id_attributes_groups = Configuration::get('CMSEXTRACONTENT_ATTRIBUTES_GROUPS');
        shuffle($id_categories);
        $explode = array();
        $name = str_replace('_', ' ', $name);
            foreach ($id_categories as $id_category) {
                $sql = 'SELECT DISTINCT p.id_product
                    FROM '._DB_PREFIX_.'product p
                    LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product = p.id_product
                    LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                    LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON al.id_attribute = pac.id_attribute
                    LEFT JOIN '._DB_PREFIX_.'attribute a ON al.id_attribute = a.id_attribute
                    '.($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').' 
                    WHERE LOWER(al.name) LIKE "%'.strtolower($name).'%" AND id_lang = '.$this->context->language->id.' AND p.active = 1'.($id_category ? ' AND c.id_category = '.(int)$id_category.' ' : '').' AND a.id_attribute_group IN ('.$id_attributes_groups.')';  
                     
                if($result = Db::getInstance()->executeS($sql)) {
                        
                        foreach ($result as $product) {
                            $explode[] = (int)$product['id_product'];
                        }
                }
            }
            if(count($explode) > 0) {
               shuffle($explode);
               if(!$this->fabricIsExists($name)) {
                   Db::getInstance()->insert('productscms_lang', array(
                        'fabric_name' => strtolower(pSQL($name)),
                        'active'   => 1,
                    ));
               } else {
                    if(!$this->fabricIsActive($name)) {
                        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'productscms_lang` SET active = 1 WHERE fabric_name ="'.strtolower(pSQL($name)).'"');
                    }
               }
               $explode = array_slice($explode, 0, $nbProducts);
                foreach ($explode as $tproduct) 
                {
                    $x = (array)new Product($tproduct, true, $this->context->language->id);
                    $productss[$tproduct] = $x;
                    $productss[$tproduct]['id_product'] = $tproduct;
                    $image = self::getImagesByID($tproduct, 1);
                    $picture = explode('-', $image[0]);
                    $productss[$tproduct]['id_image'] = $picture[1];
                }
                
                $products = Product::getProductsProperties($this->context->language->id, $productss);
            } else {
                if($this->fabricIsExists($name)) {
                    if($this->fabricIsActive($name)) {
                        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'productscms_lang` SET active = 0 WHERE fabric_name ="'.strtolower(pSQL($name)).'"');
                        $link = new Link;
                        $content = array(
                            '{fabric}' => $name,
                            '{cms_link}' => $link->getCMSLink($this->cms)
                        );
                            $email = Configuration::get('CMSEXTRACONTENT_EMAIL');
                            
                            if(Validate::isEmail($email))
                                Mail::Send(
                                    $this->context->language->id, 
                                    'fabric', 
                                    Mail::l('Fabric has been removed'), 
                                    $content, 
                                    $email, 
                                    null, 
                                    null, 
                                    null, 
                                    null, 
                                    null, 
                                    _PS_MODULE_DIR_.'cmsextracontent/mails/'
                                );
                    }
                }
                $products = false;
            }
            
            $this->context->smarty->assign('products', $products);
            $contents = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cmsextracontent/views/templates/front/products.tpl');
            return $contents;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function returnContent($contents)
    {
        
        if(Configuration::get('CMSEXTRACONTENT_LIVE_MODE')) {
            
            preg_match_all('/\{products\:[(0-9\,)]+\}/i', $contents, $matches);
            foreach ($matches[0] as $index => $match)
            {
                $explode = explode(":", $match);
                $contents = str_replace($match, $this->returnProducts(str_replace("}", "", $explode[1])), $contents);
            }
            
            preg_match_all('/\{product\:[(0-9\,)]+\}/i', $contents, $matches);
            foreach ($matches[0] as $index => $match)
            {
                $explode = explode(":", $match);
                $contents = str_replace($match, $this->returnProduct(str_replace("}", "", $explode[1])), $contents);
            }
            
            preg_match_all('/\{textile\:[(a-zA-z0-9_'.$this->specialCharsByISO($this->context->language->iso_code).')]+\}/i', $contents, $matches);
            
            foreach ($matches[0] as $index => $match)
            {
                $explode = explode(":", $match);
                $contents = str_replace($match, $this->returnProductTextiles(str_replace("}", "", $explode[1])), $contents);
            }
            
            preg_match_all('/{inspirations}/', $contents, $matches);
            foreach ($matches[0] as $index => $match)
            {
                $contents = str_replace($match, $this->getCmsCategories(), $contents);
            }
        }
        return $contents;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function getCmsCategories() {
        $cms_pages = CMS::getCMSPages($this->context->language->id, (int)$this->cms->id_cms_category, true, (int)$this->context->shop->id);
        $cms_pages = $cms_pages ? array_slice(array_reverse($cms_pages), 0, (int)Configuration::get('CMSEXTRACONTENT_CMS_COUNT')) : false;
        
        $this->context->smarty->assign('cms_pages', $cms_pages);
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cmsextracontent/views/templates/front/cms_pages.tpl');
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function specialCharsByISO($iso_code) {
        $iso_chars = array(
            'cs' => 'ÁáČčĎďÉéĚěÓóŘřŠšŤťÚúŮůÝýŽž',
            'sk' => 'ČčŠšŽž',
            'de' => 'ÄäÖöÜüẞß',
            'pl' => 'ĄąĘęŁłÓóŚśŹźŻż',
            'hu' => 'ÁáËëÉéÍíÓóÖöŐőÜüŰű'
        );
        return (count($iso_chars[$iso_code]) > 0 ? $iso_chars[$iso_code] : '');
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function fabricIsExists($name) {
        $result = Db::getInstance()->executeS('SELECT fabric_name FROM `'._DB_PREFIX_.'productscms_lang` WHERE fabric_name ="'.strtolower(pSQL($name)).'"');
        return count($result) > 0 ? true : false;
    }
    /*
    * module: cmsextracontent
    * date: 2019-04-09 14:38:22
    * version: 1.0.0
    */
    public function fabricIsActive($name) {
        $result = Db::getInstance()->getRow('SELECT active FROM `'._DB_PREFIX_.'productscms_lang` WHERE fabric_name ="'.strtolower(pSQL($name)).'"');
        return $result['active'];
    }
}