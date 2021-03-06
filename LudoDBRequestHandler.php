<?php
/**
 * Request handler class for Front End Controller. This class will handle requests sent
 * by Views and pass them to the correct LudoDBObject's.
 * User: Alf Magne Kalleland
 * Date: 13.01.13
 */

class LudoDBRequestHandler
{
    /**
     * @var LudoDBObject|LudoDBService
     */
    protected $model;
    protected $serviceName;
    protected $cacheInstance;
    private $validServices = array();
    private $success = true;
    private $message = "";
    private $code = 200;
    private $arguments;
    private $responseKey = 'response';

    public function __construct()
    {

    }

    public function handle($request)
    {
        $request = $this->getParsed($request);
        try {

            $this->arguments = $this->getArguments($request);
            $this->model = $this->getModel($request, $this->arguments);
            $this->validServices = $this->getValidServices($request);
            $this->serviceName = $this->getServiceName($request);

            if (!in_array($this->serviceName, $this->validServices)) {
                throw new LudoDBException('Invalid service ' . $this->serviceName . ', resource: ' . $this->getClassName($request));
            }

            if (!$this->model->validateArguments($this->serviceName, $this->arguments)) {
                throw new LudoDBException('Invalid constructor arguments for resource:' . $this->getClassName($request) . ', service:' . $this->serviceName . ", arguments: " . implode(",", $this->arguments));
            }

            if (!$this->model->validateServiceData($this->serviceName, $request['data'])) {
                throw new LudoDBException('Invalid service arguments for resource:' . $this->getClassName($request) . ', service:' . $this->serviceName . ", arguments: " . implode(",", $this->arguments));
            }

            if ($this->serviceName === 'delete' || $this->serviceName === 'read') {
                if ($this->model instanceof LudoDBModel && !$this->model->getId()) {
                    throw new LudoDBException('Object not found');
                }
            }

            if (!method_exists($this->model, $this->serviceName)) {
                throw new LudoDBServiceNotImplementedException("Service " . $this->serviceName . " not implemented");
            }

            switch ($this->serviceName) {
                case 'read':
                    return $this->toJSON($this->read($request['data']));
                case 'save':
                    return $this->toJSON($this->model->save($request['data']));
                case 'delete':
                    return $this->toJSON($this->model->delete());
                default:
                    if (method_exists($this->model, $this->serviceName)) {
                        return $this->toJSON($this->model->{$this->serviceName}($request['data']));
                    }
            }
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            $this->code = $e->getCode();
            $this->success = false;
            return $this->toJSON(array());
        }
        throw new LudoDBInvalidServiceException("Invalid service " . $this->serviceName);
    }

    /**
     * Set new response key. Response from the service will be in an array of this key.
     * @param String $key
     * @default "response"
     */
    public function setResponseKey($key)
    {
        $this->responseKey = $key;
    }

    private function getParsed($request)
    {
        if (is_string($request)) $request = array('request' => $request);
        $lastChar = substr($request['request'], strlen($request['request']) - 1, 1);
        if ($lastChar === '/') {
            $request['request'] = substr($request['request'], 0, strlen($request['request']) - 1);
        }
        if (!isset($request['data'])) $request['data'] = array();
        $request['request'] = stripslashes($request['request']);
        return $request;
    }

    private function toJSON($data = array())
    {
        $ret = array(
            'success' => $this->success,
            'message' => $this->message,
            'code' => $this->code,
            $this->responseKey => $data
        );
        if (LudoDB::isLoggingEnabled()) {
            $ret['log'] = array(
                'time' => LudoDB::getElapsed(),
                'queries' => LudoDB::getQueryCount()
            );
        }
        return json_encode($ret);
    }

    protected function getArguments(array $request)
    {
        if (isset($request['arguments'])) {
            return is_array($request['arguments']) ? $request['arguments'] : array($request['arguments']);
        }
        $ret = array();
        $tokens = explode("/", $request['request']);
        for ($i = 1, $count = count($tokens); $i < $count - 1; $i++) {
            $ret[] = $tokens[$i];

        }
        return $ret;
    }

    /**
     * @param array $request
     * @param array $args
     * @return null|object
     * @throws LudoDBClassNotFoundException
     */
    protected function getModel(array $request, $args = array())
    {
        $className = $this->getClassName($request);
        if (isset($className)) {
            $cl = $this->getReflectionClass($className);
            if (empty($args)) {
                return $cl->newInstance();
            } else {
                return $cl->newInstanceArgs($args);
            }
        }
        throw new LudoDBClassNotFoundException('Invalid request for: ' . $request['request']);
    }

    private function getValidServices(array $request)
    {
        $className = $this->getClassName($request);
        if (isset($className)) {
            $servicesMethod = $this->getReflectionClass($className)->getMethod('getValidServices');
            return isset($servicesMethod) ? $servicesMethod->invoke($this->model) : array();
        }
        return array();
    }

    private function getReflectionClass($className)
    {
        $cl = new ReflectionClass($className);
        if (!$cl->implementsInterface('LudoDBService')) {
            throw new LudoDBClassNotFoundException('Invalid request for: ' . $className);
        }
        return $cl;
    }

    /**
     * @param $request
     * @return string|null
     */
    private function getClassName($request)
    {
        $tokens = explode("/", $request['request']);
        return class_exists($tokens[0]) ? $tokens[0] : null;
    }

    protected function getServiceName($request)
    {
        $tokens = explode("/", $request['request']);
        return $tokens[count($tokens) - 1];
    }

    private function read($requestData = array())
    {
        if (empty($requestData)) $requestData = null;
        $data = null;
        $caching = $this->model->cacheEnabled();
        if ($caching) {
            if ($this->ludoDBCache()->hasValue()) {
                $data = $this->ludoDBCache()->getCache();
            }
        }
        if (!isset($data)) {
            $data = $this->model->read($requestData);
            if ($caching && $this->model->getCacheKey()) {
                $this->ludoDBCache()->setCache($data)->commit();
            }
        }
        return $data;
    }

    /**
     * @return LudoDBCache
     */
    protected function ludoDBCache()
    {
        if (!isset($this->cacheInstance)) {
            $this->cacheInstance = new LudoDBCache($this->model);
        }
        return $this->cacheInstance;
    }

    public function clearCacheObject()
    {
        $this->cacheInstance = null;
    }
}
