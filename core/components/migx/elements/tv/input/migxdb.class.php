<?php

/**
 * @var modX $this->modx
 * @var modTemplateVar $this
 * @var array $params
 *
 * @package modx
 * @subpackage processors.element.tv.renders.mgr.input
 */
class modTemplateVarInputRenderMigxdb extends modTemplateVarInputRender {
    public function process($value, array $params = array()) {

        $namespace = 'migx';
        $this->modx->lexicon->load('tv_widget', $namespace . ':default');
        //$properties = isset($params['columns']) ? $params : $this->getProperties();
        $properties = $params;
        
        require_once dirname(dirname(dirname(dirname(__file__)))) . '/model/migx/migx.class.php';
        $this->migx = new Migx($this->modx,$properties);
        /* get input-tvs */
       
        $this->migx->loadConfigs();
      
        $default_formtabs = '[{"caption":"Default", "fields": [{"field":"title","caption":"Title"}]}]';
        $default_columns = '[{"header": "Title", "width": "160", "sortable": "true", "dataIndex": "title"}]';
        
        $formtabs = $this->migx->getTabs();

        //$formtabs = $this->modx->fromJSON($this->modx->getOption('formtabs', $properties, $default_formtabs));
        //$formtabs = empty($properties['formtabs']) ? $this->modx->fromJSON($default_formtabs) : $formtabs;

        $resource = is_object($this->modx->resource) ? $this->modx->resource->toArray() : array();
        $this->migx->debug('resource',$resource);
        
        //multiple different Forms
        // Note: use same field-names and inputTVs in all forms

        $inputTvs = $this->migx->extractInputTvs($formtabs);

        /* get base path based on either TV param or filemanager_path */
        $this->modx->getService('fileHandler', 'modFileHandler', '', array('context' => $this->modx->context->get('key')));
        
        /* pasted from processors.element.tv.renders.mgr.input*/
        /* get working context */
        $wctx = isset($_GET['wctx']) && !empty($_GET['wctx']) ? $this->modx->sanitizeString($_GET['wctx']) : '';
        if (!empty($wctx)) {
            $workingContext = $this->modx->getContext($wctx);
            if (!$workingContext) {
                return $modx->error->failure($this->modx->lexicon('permission_denied'));
            }
            $wctx = $workingContext->get('key');
        } else {
            $wctx = $this->modx->context->get('key');
        }

        $this->migx->working_context = $wctx;
        $this->migx->source = $this->tv->getSource($this->migx->working_context, false);

        /* pasted end*/

        //$base_path = $modx->getOption('base_path', null, MODX_BASE_PATH);
        //$base_url = $modx->getOption('base_url', null, MODX_BASE_URL);

        //$columns = $this->modx->fromJSON($this->modx->getOption('columns', $properties, $default_columns));
        //$columns = empty($properties['columns']) ? $this->modx->fromJSON($default_columns) : $columns;
        
        $columns = $this->migx->getColumns();

        if (is_array($columns) && count($columns) > 0) {
            foreach ($columns as $key => $column) {
                $field['name'] = $column['dataIndex'];
                $field['mapping'] = $column['dataIndex'];
                $fields[] = $field;
                $col['dataIndex'] = $column['dataIndex'];
                $col['header'] = htmlentities($column['header'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                $col['sortable'] = $column['sortable'] == 'true' ? true : false;
                $col['width'] = $column['width'];
                $col['renderer'] = $column['renderer'];
                $cols[] = $col;
                $item[$field['name']] = isset($column['default']) ? $column['default'] : '';

                if (isset($inputTvs[$field['name']]) && $tv = $this->modx->getObject('modTemplateVar', array('name' => $inputTvs[$field['name']]['inputTV']))) {

                    $inputTV = $inputTvs[$field['name']];

                    $params = $tv->get('input_properties');
                    $params['wctx'] = $wctx;
                    /*
                    if (!empty($properties['basePath'])) {
                    if ($properties['autoResourceFolders'] == 'true' && isset($resource['id'])) {
                    $params['basePath'] = $base_path.$properties['basePath'] . $resource['id'] . '/';
                    } else {
                    $params['basePath'] = $base_path.$properties['basePath'];
                    }
                    }
                    */
                    $mediasource = $this->migx->getFieldSource($inputTV, $tv);
                    $pathconfigs[$key] = '&source=' . $mediasource->get('id');
                    //$pathconfigs[$key] = '&basePath='.$params['basePath'].'&basePathRelative='.$params['basePathRelative'].'&baseUrl='.$params['baseUrl'].'&baseUrlRelative='.$params['baseUrlRelative'];

                } else {
                    $pathconfigs[$key] = array();
                }
            }
        }
        
        $newitem[] = $item;
        $lang = $this->modx->lexicon->fetch();
        $lang['mig_add'] = !empty($properties['btntext']) ? $properties['btntext'] : $lang['mig_add'];
        $this->setPlaceholder('i18n', $lang);
        $this->setPlaceholder('properties', $properties);
        $this->setPlaceholder('resource', $resource);
        $this->setPlaceholder('object_id', $this->modx->getOption('object_id',$_REQUEST,''));
        $this->setPlaceholder('connected_object_id', $this->modx->getOption('object_id',$_REQUEST,''));
        $this->setPlaceholder('pathconfigs', $this->modx->toJSON($pathconfigs));
        $this->setPlaceholder('columns', $this->modx->toJSON($cols));
        $this->setPlaceholder('fields', $this->modx->toJSON($fields));
        $this->setPlaceholder('newitem', $this->modx->toJSON($newitem));
        $this->setPlaceholder('base_url', $this->modx->getOption('base_url'));
        $this->setPlaceholder('myctx', $wctx);
        $this->setPlaceholder('auth', $_SESSION["modx.{$this->modx->context->get('key')}.user.token"]);
        $this->setPlaceholder('customconfigs', $this->migx->customconfigs);
        $this->setPlaceholder('win_id', isset ($this->migx->customconfigs['win_id']) ? $this->migx->customconfigs['win_id'] : $this->tv->get('id'));
        
        $grid = isset ($this->migx->customconfigs['grid']) ? $this->migx->customconfigs['grid'] : 'default';
        $path = 'components/migx/';
        $corePath = $this->modx->getOption('migx.core_path', null, $this->modx->getOption('core_path') . $path);  
        $gridfile = $corePath.'elements/tv/grids/'.$grid.'.grid.tpl';
        $this->setPlaceholder('grid', $this->modx->controller->fetchTemplate($gridfile));
        
         
    }
    public function getTemplate() {
        
        $path = 'components/migx/';
        $corePath = $this->modx->getOption('migx.core_path', null, $this->modx->getOption('core_path') . $path);        
        return $corePath . 'elements/tv/migxdb.tpl';
    }
}
return 'modTemplateVarInputRenderMigxdb';
