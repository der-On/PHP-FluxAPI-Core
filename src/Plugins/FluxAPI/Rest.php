<?php
namespace Plugins\FluxAPI;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \FluxAPI\Query;

class Rest
{
    protected $_api = NULL;

    public $config = array(
        'base_route' => '',
        'default_input_format' => \FluxAPI\Api::DATA_FORMAT_ARRAY,
        'default_output_format' => 'json',
        'default_mime_type' => 'application/json'
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

    public function getMimeTypeFromFormat($format, $default)
    {
        $format = ucfirst($format);

        $formats = $this->_api['plugins']->getPlugins('Format');

        if (in_array($format, array_keys($formats))) {
            $format_class = $formats[$format];
            return $format_class::getMimeType();
        } else {
            return $default;
        }
    }

    public function getFormatFromMimeType($mime_type, $default)
    {
        if (empty($mime_type)) {
            return $default;
        }

        $mime_type = strtolower(trim($mime_type));

        $formats = $this->_api['plugins']->getPlugins('Format');

        foreach($formats as $format => $format_class) {
            if ($format_class::getMimeType() == $mime_type) {
                return strtolower($format);
                continue;
            }
        }

        return $default;
    }

    public function getFormatFromExtension($ext, $default)
    {
        if (empty($ext)) {
            return $default;
        }

        $ext = strtolower(trim($ext));

        $formats = $this->_api['plugins']->getPlugins('Format');

        foreach($formats as $format => $format_class) {
            if ($format_class::getExtension() == $ext) {
                return strtolower($format);
                continue;
            }
        }

        return $default;
    }

    public function getOutputFormat(Request $request)
    {
        return $this->config['default_output_format'];
    }

    public function getInputFormat(Request $request)
    {
        return $this->getFormatFromMimeType($request->headers->get('Content-Type'), $this->config['default_input_format']);
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
                    $query->filter('equal',array($name,$value));
                }
            }
        }
    }

    public function getRequestData(Request $request, $format)
    {
        if ($format == \FluxAPI\Api::DATA_FORMAT_ARRAY) {
            $data = $request->request->all();
        } else {
            $data = $request->getContent();
        }

        return $data;
    }

    public function registerModelRoutes()
    {
        $self = $this;

        $models = $this->_api['plugins']->getPlugins('Model');

        foreach($models as $model_name => $model_class)
        {
            $model_route_name = strtolower($model_name);

            // view/load single model

            // with id and extension
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'/{id}.{ext}',
                function(Request $request, $id = NULL, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->loadModel($request, $model_name, $id, $format);
                }
            );
            // with id
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id = NULL) use ($self, $model_name) {
                    return $self->loadModel($request, $model_name, $id, $self->config['default_output_format']);
                }
            );
            // with no filters only and extension
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->loadModel($request, $model_name, NULL, $format);
                }
            );
            // with no filters only
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name,
                function(Request $request) use ($self, $model_name) {
                    return $self->loadModel($request, $model_name, NULL, $self->config['default_output_format']);
                }
            );

            // view/load multiple models
            // with extension
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'s.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->loadModels($request, $model_name, $format);
                }
            );
            // without extension
            $this->_api->app->get($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->loadModels($request, $model_name, $self->config['default_output_format']);
                }
            );

            // create a new model
            // with extension
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name.'.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->createModel($request, $model_name, $format);
                }
            );
            // without extension
            $this->_api->app->post($this->config['base_route'].'/'.$model_route_name,
                function(Request $request) use ($self, $model_name) {
                    $format = $self->config['default_output_format'];
                    return $self->createModel($request, $model_name, $format);
                }
            );

            // update an existing a model
            // with id and extension
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'/{id}.{ext}',
                function(Request $request, $id, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->updateModel($request, $model_name, $id, $format);
                }
            );
            // with id
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id) use ($self, $model_name) {
                    return $self->updateModel($request, $model_name, $id, $self->config['default_output_format']);
                }
            );
            // with filters only and extension
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->updateModel($request, $model_name, NULL, $format);
                }
            );
            // with filters only
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name,
                function(Request $request) use ($self, $model_name) {
                    return $self->updateModel($request, $model_name, NULL, $self->config['default_output_format']);
                }
            );

            // update multiple models
            // with extension
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'s.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->updateModels($request, $model_name, $format);
                }
            );
            // without extension
            $this->_api->app->put($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->updateModels($request, $model_name, $self->config['default_output_format']);
                }
            );

            // delete a single model
            // with id and extension
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'/{id}.{ext}',
                function(Request $request, $id = NULL, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->deleteModel($request, $model_name, $id, $format);
                }
            );
            // with id
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'/{id}',
                function(Request $request, $id = NULL) use ($self, $model_name) {
                    return $self->deleteModel($request, $model_name, $id, $self->config['default_output_format']);
                }
            );
            // with filters only and extension
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->deleteModel($request, $model_name, NULL, $format);
                }
            );
            // with filters only
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name,
                function(Request $request) use ($self, $model_name) {
                    return $self->deleteModel($request, $model_name, NULL, $self->config['default_output_format']);
                }
            );

            // delete multiple models
            // with extension
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'s.{ext}',
                function(Request $request, $ext = NULL) use ($self, $model_name) {
                    $format = $self->getFormatFromExtension($ext, $self->config['default_output_format']);
                    return $self->deleteModels($request, $model_name, $format);
                }
            );
            // without extension
            $this->_api->app->delete($this->config['base_route'].'/'.$model_route_name.'s',
                function(Request $request) use ($self, $model_name) {
                    return $self->deleteModels($request, $model_name, $self->config['default_output_format']);
                }
            );
        }
    }

    public function createModel(Request $request, $model_name, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            $data = $this->getRequestData($request, $input_format);

            $create_method = 'create'.$model_name;

            try {
                $model = $this->_api->$create_method($data, $input_format);
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }

            $save_method = 'save'.$model_name;

            try {
                if ($this->_api->$save_method($model)) {
                    return $this->_createModelSuccessResponse($model, $model_name, $format);
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during creation of resource.'), $format);
                }

            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    public function updateModel(Request $request, $model_name, $id = NULL, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            $data = $this->getRequestData($request, $input_format);

            $query = new Query();

            if(!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $update_method = 'update'.$model_name;

            try {
                if ($this->_api->$update_method($query, $data, $input_format)) {
                    return $this->_createSuccessResponse(
                        array('success' => true),
                        $format
                    );
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during update of resource.'), $format);
                }


            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }

        } else {
            return NULL;
        }
    }

    public function updateModels(Request $request, $model_name, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            $data = $this->getRequestData($request, $input_format);

            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $update_method = 'update'.$model_name.'s';

            try {
                if ($this->_api->$update_method($query, $data, $input_format)) {
                    return $this->_createSuccessResponse(
                        array('success' => true),
                        $format
                    );
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during update of resources.'), $format);
                }
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    public function loadModel(Request $request, $model_name, $id = NULL, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $query = new Query();

            if (!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $load_method = 'load'.$model_name;

            try {
                $result = $this->_api->$load_method($query, $format);

                if ($result) {
                    return $this->_createModelSuccessResponse($result, $model_name, $format, FALSE);
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during load of resource.'), $format);
                }
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    public function loadModels(Request $request, $model_name, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $load_method = 'load'.$model_name.'s';

            try {
                $result = $this->_api->$load_method($query, $format);

                if ($result) {
                    return $this->_createModelSuccessResponse($result, $model_name, $format, FALSE);
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during load of resources.'), $format);
                }
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    public function deleteModel(Request $request, $model_name, $id = NULL, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            $data = $this->getRequestData($request, $input_format);

            $query = new Query();

            if (!empty($id)) {
                $query->filter('equal',array('id',$id));
            }

            $this->addFiltersToQueryFromRequest($request, $query);

            $delete_method = 'delete'.$model_name;

            try {
                $result = $this->_api->$delete_method($query, $data, $input_format);

                if ($result) {
                    return $this->_createSuccessResponse(array('success' => true), $format);
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during delete of resource.'), $format);
                }
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    public function deleteModels(Request $request, $model_name, $format)
    {
        if ($this->_api['plugins']->hasPlugin('Model',$model_name)) {
            $input_format = $this->getInputFormat($request);

            $data = $this->getRequestData($request, $input_format);

            $query = new Query();

            $this->addFiltersToQueryFromRequest($request, $query);

            $delete_method = 'delete'.$model_name.'s';

            try {
                $result = $this->_api->$delete_method($query, $data, $input_format);

                if ($result) {
                    return $this->_createSuccessResponse(array('success' => true), $format);
                } else {
                    return $this->_createErrorResponse(new \ErrorException('Error during delete of resources.'), $format);
                }
            } catch (\Exception $error) {
                return $this->_createErrorResponse($error, $format);
            }
        } else {
            return NULL;
        }
    }

    protected function _createResponse($data, $status, $format, $encode_data = TRUE)
    {
        $formats = $this->_api['plugins']->getPlugins('Format');

        if ($encode_data && isset($formats[ucfirst($format)])) {
            $format_class = $formats[ucfirst($format)];
            $data = $format_class::encode($data);
        }

        return new Response(
            $data,
            $status,
            array('Content-Type'=>$this->getMimeTypeFromFormat($format, $this->config['default_mime_type']))
        );
    }

    protected function _createModelSuccessResponse($data, $model_name, $format, $encode_data = TRUE)
    {
        $formats = $this->_api['plugins']->getPlugins('Format');

        // model collection
        if (is_array($data)) {
            $model_name .= 's';
        }
        // single model
        elseif (is_object($data)) {
            $data = $data->toArray();
        }

        if ($encode_data && isset($formats[ucfirst($format)])) {
            $format_class = $formats[ucfirst($format)];
            $data = $format_class::encode($data, array('root' => $model_name));
        }

        return new Response(
            $data,
            200,
            array('Content-Type'=>$this->getMimeTypeFromFormat($format, $this->config['default_mime_type']))
        );
    }

    protected function _createSuccessResponse($data, $format, $encode_data = TRUE)
    {
        return $this->_createResponse($data, 200, $format, $encode_data);
    }

    protected function _createErrorResponse(\Exception $error, $format, $encode_data = TRUE)
    {
        $arr = array(
            'error' => array(
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
            )
        );

        if ($this->_api->config['debug']) {
            $arr['error']['file'] = $error->getFile();
            $arr['error']['line'] = $error->getLine();
            $arr['error']['trace'] = $error->getTraceAsString();
        }

        return $this->_createResponse(
            $arr,
            500,
            $format,
            $encode_data
        );
    }
}
