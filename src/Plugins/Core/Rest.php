<?php
namespace Plugins\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \FluxAPI\Query;

class Rest
{
    protected $_api = NULL;

    public $config = array(
        'base_route' => '',
        'default_data_format' => \FluxAPI\Api::DATA_FORMAT_JSON,
    );

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;

        if (!isset($this->_api->config['plugins.core.rest'])) {
            $this->_api->config['plugins.core.rest'] = $this->config;
        } else {
            $this->config = array_replace_recursive($this->_api->config['plugins.core.rest'],$this->config);
        }

        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        $this->registerModelRoutes();
    }

    public function getContentTypeFromFormat($format)
    {
        switch($format) {
            case \FluxAPI\Api::DATA_FORMAT_JSON:
                return 'application/json';
                break;

            case \FluxAPI\Api::DATA_FORMAT_XML:
                return 'text/xml';
                break;

            case \FluxAPI\Api::DATA_FORMAT_YAML:
                return 'text/yaml';
                break;

            // TODO: implement form-data

            default:
                return 'application/json';
        }
    }

    public function getFormatFromContentType($content_type)
    {
        switch($content_type) {
            case 'text/json':
                return \FluxAPI\Api::DATA_FORMAT_JSON;
                break;

            case 'application/json':
                return \FluxAPI\Api::DATA_FORMAT_JSON;
                break;

            case 'text/xml':
                return \FluxAPI\Api::DATA_FORMAT_XML;
                break;

            case 'application/xml':
                return \FluxAPI\Api::DATA_FORMAT_XML;
                break;

            case 'text/yaml':
                return \FluxAPI\Api::DATA_FORMAT_YAML;
                break;

            case 'application/yaml':
                return \FluxAPI\Api::DATA_FORMAT_YAML;
                break;

            default:
                return \FluxAPI\Api::DATA_FORMAT_ARRAY;
        }
    }

    public function getValidOutputFormat($format)
    {
        if (empty($format)) {
            $format = $this->config['default_data_format'];
        } else {
            if (!in_array($format,array(\FluxAPI\Api::DATA_FORMAT_JSON,\FluxAPI\Api::DATA_FORMAT_YAML,\FluxAPI\Api::DATA_FORMAT_XML))) {
                $format = $this->config['default_data_format'];
            }
        }
        return $format;
    }

    public function getOutputFormat(Request $request)
    {
        return $this->config['default_data_format'];
    }

    public function getInputFormat(Request $request)
    {
        return $this->getFormatFromContentType($request->headers->get('Content-Type'));
    }

    public function addFiltersToQueryFromRequest(Request $request, Query &$query)
    {
        $values = $request->query->all();

        foreach($values as $name => $value)
        {
            if ($name != 'fields') { // ignore fields parameter as this is for narrowing down the fields to catch in a query
                if (substr($name,0,1) == '@') {
                    $query->filter(substr($name,1),explode(',',$value));
                } else {
                    $query->filter('equals',array($name,$value));
                }
            }
        }
    }

    public function registerModelRoutes()
    {
        $self = $this;

        $models = $this->_api['plugins']->getPlugins('Model');

        foreach($models as $model_name => $model_class)
        {
            $model_route_name = strtolower($model_name);

            // view/load single model by id or using filters
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'/{id}.{format}',
                function(Request $request, $id = NULL, $format = NULL) use ($self, $model_name) {
                    return $self->loadModel($request, $model_name, $id, $format);
                }
            );
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id = NULL) use ($self, $model_name) {
                    return $self->loadModel($request, $model_name, $id);
                }
            );

            // view/load multiple models using filters
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'s.{format}',
                function(Request $request, $format = NULL) use ($self, $model_name) {
                    return $self->loadModels($request, $model_name, $format);
                }
            );
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->loadModels($request, $model_name);
                }
            );

            // update an existing a model
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name.'/{id}.{format}',
                function(Request $request, $id = NULL, $format = NULL) use ($self, $model_name) {
                    return $self->updateModel($request, $model_name, $id, $format);
                }
            );
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id = NULL) use ($self, $model_name) {
                    return $self->updateModel($request, $model_name, $id);
                }
            );

            // update multiple models
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name.'s.{format}',
                function(Request $request, $format = NULL) use ($self, $model_name) {
                    return $self->updateModels($request, $model_name, $format);
                }
            );
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->updateModels($request, $model_name);
                }
            );

            // create a new model
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'.{format}',
                function(Request $request, $id = NULL, $format = NULL) use ($self, $model_name) {
                    return $self->createModel($request, $model_name, $format);
                }
            );
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name,
                function(Request $request, $id = NULL, $format = NULL) use ($self, $model_name) {
                    return $self->createModel($request, $model_name);
                }
            );

            // delete a single model
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'/{id}.{format}',
                function(Request $request, $id = NULL, $format = NULL) use ($self, $model_name) {
                    return $self->deleteModel($request, $model_name, $id, $format);
                }
            );
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id = NULL) use ($self, $model_name) {
                    return $self->deleteModel($request, $model_name, $id);
                }
            );

            // delete multiple models
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'s.{format}',
                function(Request $request, $format = NULL) use ($self, $model_name) {
                    return $self->deleteModels($request, $model_name, $format);
                }
            );
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->deleteModels($request, $model_name);
                }
            );
        }
    }

    public function createModel(Request $request, $model_name, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            if ($input_format == \FluxAPI\Api::DATA_TYPE_ARRAY) {
                $data = $request->query->all();
            } else {
                $data = $request->getContent();
            }

            $create_method = 'create'.$model_name;
            $model = $this->_api->$create_method($data, $input_format);

            $save_method = 'save'.$model_name;
            return new Response(
                $this->_api->$save_method($model),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function updateModel(Request $request, $model_name, $id = NULL, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            if ($input_format == \FluxAPI\Api::DATA_TYPE_ARRAY) {
                $data = $request->query->all();
            } else {
                $data = $request->getContent();
            }

            $query = new Query();

            if (!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $update_method = 'update'.$model_name;
            return new Response(
                $this->_api->$update_method($query, $data, $input_format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function updateModels(Request $request, $model_name, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            if ($input_format == \FluxAPI\Api::DATA_TYPE_ARRAY) {
                $data = $request->query->all();
            } else {
                $data = $request->getContent();
            }

            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $update_method = 'update'.$model_name.'s';

            return new Response(
                $this->_api->$update_method($query, $data, $input_format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function loadModel(Request $request, $model_name, $id = NULL, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $query = new Query();

            if (!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $load_method = 'load'.$model_name;

            return new Response(
                $this->_api->$load_method($query, $format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function loadModels(Request $request, $model_name, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $load_method = 'load'.$model_name.'s';

            return new Response(
                $this->_api->$load_method($query, $format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function deleteModel(Request $request, $model_name, $id = NULL, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            if ($input_format == \FluxAPI\Api::DATA_TYPE_ARRAY) {
                $data = $request->query->all();
            } else {
                $data = $request->getContent();
            }

            $query = new Query();

            if (!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $delete_method = 'delete'.$model_name;

            return new Response(
                $this->_api->$delete_method($query, $data, $input_format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }

    public function deleteModels(Request $request, $model_name, $format = NULL)
    {
        $format = $this->getValidOutputFormat($format);

        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            if ($input_format == \FluxAPI\Api::DATA_TYPE_ARRAY) {
                $data = $request->query->all();
            } else {
                $data = $request->getContent();
            }

            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $delete_method = 'delete'.$model_name.'s';

            return new Response(
                $this->_api->$delete_method($query, $data, $input_format),
                200,
                array('Content-Type'=>$this->getContentTypeFromFormat($format))
            );
        } else {
            return NULL;
        }
    }
}
